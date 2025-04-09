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
    protected static ?string $navigationGroup = 'System Configuration';
    protected static ?int $navigationSort = 10;
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $modelLabel = 'Submission Type';
    protected static ?string $pluralModelLabel = 'Submission Types';
    protected static ?string $navigationLabel = 'Submission Types';

    // Change navbar icon color
    protected static ?string $activeNavigationIconColor = 'primary';

    // Add resource description
    protected static ?string $navigationDescription = 'Manage submission types and their workflow configuration';

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
                            ->required()
                            ->maxLength(255)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!$state) return;
                                $set('slug', Str::slug($state));
                            })
                            ->autocapitalize('words')
                            ->label('Type Name')
                            ->helperText('A descriptive name for this submission type (e.g. Patent, Trademark)')
                            ->autofocus(),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->rules(['alpha_dash'])
                            ->unique(ignoreRecord: true)
                            ->helperText('Used in URLs and as a unique identifier')
                            ->prefix(config('app.url') . '/submissions/'),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->helperText('Explain what this submission type is used for')
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
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn($record) => Str::limit($record->description, 50))
                    ->wrap(),

                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->copyable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalDescription('Are you sure you want to delete this submission type? All associated submissions, requirements, and workflow stages will be affected.')
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
                        ->requiresConfirmation()
                        ->modalDescription('Are you sure you want to delete these submission types? All associated submissions, requirements, and workflow stages will be affected.'),

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
            ->emptyStateHeading('No Submission Types Yet')
            ->emptyStateDescription('Create a submission type to start configuring your IP management workflows.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create Submission Type')
                    ->icon('heroicon-o-plus')
                    ->disabled(!static::canCreate()) // Make create button inactive
                    ->tooltip(static::canCreate() 
                        ? 'Create a new submission type' 
                        : 'You don\'t have permission to create submission types'),
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
