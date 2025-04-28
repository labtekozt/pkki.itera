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

    protected static ?string $navigationGroup = 'Review Management';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return 'Pending Reviews';
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
                Forms\Components\Section::make('Submission Details')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Submission Title')
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'in_review' => 'In Review',
                                'approved' => 'Approved',
                                'revision_needed' => 'Revision Needed',
                                'rejected' => 'Rejected',
                            ])
                            ->required(),

                        Forms\Components\Select::make('current_stage_id')
                            ->label('Current Stage')
                            ->relationship('currentStage', 'name')
                            ->disabled(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Review Decision')
                    ->schema([
                        Forms\Components\Textarea::make('review_comments')
                            ->label('Comments')
                            ->placeholder('Enter your review comments here...')
                            ->required(),
                            
                        Forms\Components\Textarea::make('reviewer_notes')
                            ->label('Reviewer Notes for Submitter')
                            ->helperText('These notes will be visible to the submitter when revision is needed or submission is rejected')
                            ->placeholder('Enter notes for the submitter regarding required revisions or reasons for rejection')
                            ->columnSpanFull()
                            ->visible(fn(Forms\Get $get) => in_array($get('status'), ['revision_needed', 'rejected'])),
                    ]),

                Forms\Components\Section::make('Assign Next Reviewer')
                    ->schema([
                        Forms\Components\Select::make('next_stage_id')
                            ->label('Next Stage')
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
                            ->label('Assign Reviewer')
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
                    ->label('Title')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('submissionType.name')
                    ->label('Type')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('currentStage.name')
                    ->label('Current Stage')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'in_review',
                        'success' => 'approved',
                        'danger' => fn($state) => in_array($state, ['rejected', 'cancelled']),
                        'primary' => fn($state) => in_array($state, ['submitted', 'draft']),
                        'info' => fn($state) => $state === 'completed',
                        'secondary' => fn($state) => $state === 'revision_needed',
                    ]),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Submitted By')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submission Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'submitted' => 'Submitted',
                        'in_review' => 'In Review',
                        'revision_needed' => 'Revision Needed',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('submission_type_id')
                    ->relationship('submissionType', 'name')
                    ->searchable()
                    ->label('Submission Type'),

                Tables\Filters\SelectFilter::make('current_stage_id')
                    ->relationship('currentStage', 'name')
                    ->searchable()
                    ->label('Current Stage'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('review')
                    ->label('Review')
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
