<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubmissionTypeResource\Pages;
use App\Filament\Resources\SubmissionTypeResource\RelationManagers;
use App\Models\SubmissionType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Collection;

class SubmissionTypeResource extends Resource
{
    protected static ?string $model = SubmissionType::class;

    // Improved navigation and labels
    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?int $navigationSort = 10;
    protected static ?string $recordTitleAttribute = 'name';

    // Change navbar icon color
    protected static ?string $activeNavigationIconColor = 'primary';

    // Add resource description
    protected static ?string $navigationDescription = 'Manage submission types and their workflow configuration';

    public static function getNavigationGroup(): ?string
    {
        return 'System Configuration';
    }

    public static function getModelLabel(): string
    {
        return 'Submission Type';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Submission Types';
    }

    public static function getNavigationLabel(): string
    {
        return 'Submission Types';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug', 'description'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Submission Type Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Type Name')
                            ->required()
                            ->maxLength(255)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!$state) return;
                                $set('slug', Str::slug($state));
                            })
                            ->autocapitalize('words')
                            ->helperText('Enter a clear, descriptive name for this submission type')
                            ->autofocus(),

                        Forms\Components\TextInput::make('slug')
                            ->label('URL Slug')
                            ->required()
                            ->maxLength(255)
                            ->rules(['alpha_dash'])
                            ->unique(ignoreRecord: true)
                            ->helperText('URL-friendly version of the name (auto-generated)')
                            ->prefix(config('app.url') . '/submissions/'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->helperText('Provide a detailed description of what this submission type covers')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

            ]);
    }

    /**
     * Check if the user can create a new resource
     */
    public static function canCreate(): bool
    {
        // By default, creating new submission types is inactive/disabled
        // Only users with specific permissions can create new types
        return auth()->user()->can('create_submission::type') && 
               config('app.submission_types_enabled', false);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Type Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn($record) => Str::limit($record->description, 50))
                    ->wrap(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('URL Slug')
                    ->searchable()
                    ->copyable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('View'),
                    Tables\Actions\EditAction::make()
                        ->label('Edit'),
                    Tables\Actions\DeleteAction::make()
                        ->label('Delete')
                        ->requiresConfirmation()
                        ->modalDescription('Are you sure you want to delete this submission type? This action cannot be undone.')
                        ->modalIcon('heroicon-o-exclamation-triangle')
                        ->modalIconColor('danger'),
                ])->tooltip('Actions'),

                Tables\Actions\Action::make('requirements')
                    ->label('Requirements')
                    ->icon('heroicon-o-document')
                    ->color('info')
                    ->url(fn(SubmissionType $record): string =>
                    static::getUrl('requirements', ['record' => $record]))
                    ->badge(fn(SubmissionType $record) => $record->documentRequirements()->count() ?: ''),

                Tables\Actions\Action::make('stages')
                    ->label('Workflow')
                    ->icon('heroicon-o-squares-plus')
                    ->color('warning')
                    ->url(fn(SubmissionType $record): string =>
                    static::getUrl('stages', ['record' => $record]))
                    ->badge(fn(SubmissionType $record) => $record->workflowStages()->count() ?: ''),

                Tables\Actions\Action::make('view_submissions')
                    ->label('Submissions')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('success')
                    ->url(fn(SubmissionType $record): string =>
                    route('filament.admin.resources.submissions.index', [
                        'tableFilters[submission_type]' => $record->id
                    ]))
                    ->visible(fn() => auth()->user()->can('viewAny', \App\Models\Submission::class))
                    ->badge(fn(SubmissionType $record) => $record->submissions()->count() ?: ''),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Delete Selected')
                        ->requiresConfirmation()
                        ->modalDescription('Are you sure you want to delete the selected submission types? This action cannot be undone.'),

                    Tables\Actions\BulkAction::make('activate')
                        ->label('Mark as Active')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn(Collection $records) => $records->each->update(['is_active' => true]))
                        ->color('success')
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Mark as Inactive')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn(Collection $records) => $records->each->update(['is_active' => false]))
                        ->color('danger')
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('export')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (Collection $records) {
                            // Export logic would go here
                            return response()->streamDownload(function () use ($records) {
                                echo json_encode($records->toArray(), JSON_PRETTY_PRINT);
                            }, 'submission-types-export.json');
                        }),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-document')
            ->emptyStateHeading('No Submission Types')
            ->emptyStateDescription('Get started by creating your first submission type.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create Submission Type')
                    ->icon('heroicon-o-plus')
                    ->disabled(!static::canCreate()) // Make create button inactive
                    ->tooltip(static::canCreate() 
                        ? 'Create Submission Type' 
                        : 'You do not have permission to create submission types'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\DocumentRequirementsRelationManager::class,
            RelationManagers\WorkflowStagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubmissionTypes::route('/'),
            'create' => Pages\CreateSubmissionType::route('/create'),
            'edit' => Pages\EditSubmissionType::route('/{record}/edit'),
            'requirements' => Pages\ManageRequirements::route('/{record}/requirements'),
            'stages' => Pages\ManageStages::route('/{record}/stages'),
            'stage-requirements' => Pages\StageRequirements::route('/{record}/stages/{stageId}/requirements'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['documentRequirements', 'workflowStages', 'submissions']);
    }
}
