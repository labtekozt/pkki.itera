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
        return __('resource.system_configuration');
    }

    public static function getModelLabel(): string
    {
        return __('resource.submission_type');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.submission_types');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.submission_types');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug', 'description'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('resource.submission_type_details'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('resource.type_name'))
                            ->required()
                            ->maxLength(255)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!$state) return;
                                $set('slug', Str::slug($state));
                            })
                            ->autocapitalize('words')
                            ->helperText(__('resource.type_name_help'))
                            ->autofocus(),

                        Forms\Components\TextInput::make('slug')
                            ->label(__('resource.slug'))
                            ->required()
                            ->maxLength(255)
                            ->rules(['alpha_dash'])
                            ->unique(ignoreRecord: true)
                            ->helperText(__('resource.slug_help'))
                            ->prefix(config('app.url') . '/submissions/'),

                        Forms\Components\Textarea::make('description')
                            ->label(__('resource.description'))
                            ->rows(3)
                            ->helperText(__('resource.description_help'))
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
                    ->label(__('resource.type_name'))
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn($record) => Str::limit($record->description, 50))
                    ->wrap(),

                Tables\Columns\TextColumn::make('slug')
                    ->label(__('resource.slug'))
                    ->searchable()
                    ->copyable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('resource.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label(__('resource.view')),
                    Tables\Actions\EditAction::make()
                        ->label(__('resource.edit')),
                    Tables\Actions\DeleteAction::make()
                        ->label(__('resource.delete'))
                        ->requiresConfirmation()
                        ->modalDescription(__('resource.delete_confirmation'))
                        ->modalIcon('heroicon-o-exclamation-triangle')
                        ->modalIconColor('danger'),
                ])->tooltip(__('resource.actions')),

                Tables\Actions\Action::make('requirements')
                    ->label(__('resource.requirements'))
                    ->icon('heroicon-o-document')
                    ->color('info')
                    ->url(fn(SubmissionType $record): string =>
                    static::getUrl('requirements', ['record' => $record]))
                    ->badge(fn(SubmissionType $record) => $record->documentRequirements()->count() ?: ''),

                Tables\Actions\Action::make('stages')
                    ->label(__('resource.workflow'))
                    ->icon('heroicon-o-squares-plus')
                    ->color('warning')
                    ->url(fn(SubmissionType $record): string =>
                    static::getUrl('stages', ['record' => $record]))
                    ->badge(fn(SubmissionType $record) => $record->workflowStages()->count() ?: ''),

                Tables\Actions\Action::make('view_submissions')
                    ->label(__('resource.submissions'))
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
                        ->label(__('resource.delete_bulk'))
                        ->requiresConfirmation()
                        ->modalDescription(__('resource.bulk_delete_confirmation')),

                    Tables\Actions\BulkAction::make('activate')
                        ->label(__('resource.mark_as_active'))
                        ->icon('heroicon-o-check-circle')
                        ->action(fn(Collection $records) => $records->each->update(['is_active' => true]))
                        ->color('success')
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label(__('resource.mark_as_inactive'))
                        ->icon('heroicon-o-x-circle')
                        ->action(fn(Collection $records) => $records->each->update(['is_active' => false]))
                        ->color('danger')
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('export')
                        ->label(__('resource.export_selected'))
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
            ->emptyStateHeading(__('resource.no_submission_types'))
            ->emptyStateDescription(__('resource.create_submission_type_description'))
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('resource.create_submission_type'))
                    ->icon('heroicon-o-plus')
                    ->disabled(!static::canCreate()) // Make create button inactive
                    ->tooltip(static::canCreate() 
                        ? __('resource.create_submission_type') 
                        : __('resource.no_permission_create')),
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
