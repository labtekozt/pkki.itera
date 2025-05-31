<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubmissionReviewResource\Pages;
use App\Models\Submission;
use App\Models\User;
use App\Models\WorkflowStage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SubmissionReviewResource extends Resource
{
    protected static ?string $model = Submission::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('resource.submission_review.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('menu.nav_group.review_management');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', [
            'submitted',
            'in_review',
            'revision_needed',
            'approved',
            'rejected',
        ])->count();
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('resource.submission_review.sections.submission_details'))
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label(__('resource.submission_review.fields.title'))
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->label(__('resource.submission_review.fields.status'))
                            ->options([
                                'in_review' => __('resource.submission.status.in_review'),
                                'approved' => __('resource.submission.status.approved'),
                                'revision_needed' => __('resource.submission.status.revision_needed'),
                                'rejected' => __('resource.submission.status.rejected'),
                            ])
                            ->required(),

                        Forms\Components\Select::make('current_stage_id')
                            ->label(__('resource.submission_review.fields.current_stage'))
                            ->relationship('currentStage', 'name')
                            ->disabled(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make(__('resource.submission_review.sections.review_decision'))
                    ->schema([
                        Forms\Components\Textarea::make('review_comments')
                            ->label(__('resource.submission_review.fields.comments'))
                            ->placeholder(__('resource.submission_review.placeholders.comments'))
                            ->required(),
                            
                        Forms\Components\Textarea::make('reviewer_notes')
                            ->label(__('resource.submission_review.fields.reviewer_notes'))
                            ->helperText(__('resource.submission_review.help.reviewer_notes'))
                            ->placeholder(__('resource.submission_review.placeholders.reviewer_notes'))
                            ->columnSpanFull()
                            ->visible(fn(Forms\Get $get) => in_array($get('status'), ['revision_needed', 'rejected'])),
                    ]),

                Forms\Components\Section::make(__('resource.submission_review.sections.assign_next_reviewer'))
                    ->schema([
                        Forms\Components\Select::make('next_stage_id')
                            ->label(__('resource.submission_review.fields.next_stage'))
                            ->options(function (Submission $record) {
                                // Get current stage
                                $currentStage = $record->currentStage;
                                if (!$currentStage) {
                                    return [];
                                }

                                // Get next stage based on order
                                return WorkflowStage::where('submission_type_id', $record->submission_type_id)
                                    ->where('order', '>', $currentStage->order)
                                    ->where('is_active', true)
                                    ->orderBy('order')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->hidden(fn(Forms\Get $get) => $get('status') !== 'approved'),

                        Forms\Components\Select::make('next_reviewer_id')
                            ->label(__('resource.submission_review.fields.assign_reviewer'))
                            ->options(function () {
                                return User::role('reviewer')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->hidden(fn(Forms\Get $get) => $get('status') !== 'approved' || !$get('next_stage_id')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('resource.submission_review.columns.title'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('submissionType.name')
                    ->label(__('resource.submission_review.columns.type'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('currentStage.name')
                    ->label(__('resource.submission_review.columns.current_stage'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('resource.submission_review.columns.status'))
                    ->formatStateUsing(fn (string $state): string => __("resource.submission.status.{$state}"))
                    ->colors([
                        'warning' => 'in_review',
                        'success' => 'approved',
                        'danger' => fn($state) => in_array($state, ['rejected', 'cancelled']),
                        'primary' => fn($state) => in_array($state, ['submitted', 'draft']),
                        'info' => fn($state) => $state === 'completed',
                        'secondary' => fn($state) => $state === 'revision_needed',
                    ]),

                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('resource.submission_review.columns.submitted_by'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('resource.submission_review.columns.submission_date'))
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('resource.submission_review.columns.last_updated'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('resource.submission_review.filters.status'))
                    ->options([
                        'submitted' => __('resource.submission.status.submitted'),
                        'in_review' => __('resource.submission.status.in_review'),
                        'revision_needed' => __('resource.submission.status.revision_needed'),
                        'approved' => __('resource.submission.status.approved'),
                        'rejected' => __('resource.submission.status.rejected'),
                        'completed' => __('resource.submission.status.completed'),
                        'cancelled' => __('resource.submission.status.cancelled'),
                    ]),

                Tables\Filters\SelectFilter::make('submission_type_id')
                    ->relationship('submissionType', 'name')
                    ->searchable()
                    ->label(__('resource.submission_review.filters.submission_type')),

                Tables\Filters\SelectFilter::make('current_stage_id')
                    ->relationship('currentStage', 'name')
                    ->searchable()
                    ->label(__('resource.submission_review.filters.current_stage')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(__('actions.view')),
                Tables\Actions\Action::make('review')
                    ->label(__('resource.submission_review.actions.review'))
                    ->icon('heroicon-o-clipboard-document-check')
                    ->url(fn(Submission $record): string => route('filament.admin.resources.submission-reviews.review', $record)),
            ])
            ->bulkActions([
                // No bulk actions needed for review process
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubmissionReviews::route('/'),
            'review' => Pages\ReviewSubmission::route('/{record}/review'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // Default to showing only submissions that are in review status
        return parent::getEloquentQuery()
            ->whereIn('status', [
                'submitted',
                'in_review',
                'revision_needed',
                'approved',
                'rejected',
            ]);
    }
}
