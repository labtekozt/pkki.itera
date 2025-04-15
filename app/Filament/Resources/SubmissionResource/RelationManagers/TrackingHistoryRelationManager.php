<?php

namespace App\Filament\Resources\SubmissionResource\RelationManagers;

use App\Models\Document;
use App\Models\TrackingHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Enums\IconPosition;

class TrackingHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'trackingHistory';

    // Define tracking event types as constants for consistency
    private const EVENT_DOC_UPLOADED = 'document_uploaded';
    private const EVENT_DOC_APPROVED = 'document_approved';
    private const EVENT_DOC_REJECTED = 'document_rejected';
    private const EVENT_DOC_REVISION = 'document_revision_needed';
    private const EVENT_STAGE_CHANGE = 'stage_transition';
    private const EVENT_STATUS_CHANGE = 'status_change';

    // Define tracking status types
    private const STATUS_STARTED = 'started';
    private const STATUS_IN_PROGRESS = 'in_progress';
    private const STATUS_APPROVED = 'approved';
    private const STATUS_REJECTED = 'rejected';
    private const STATUS_REVISION_NEEDED = 'revision_needed';
    private const STATUS_OBJECTION = 'objection';
    private const STATUS_COMPLETED = 'completed';

    // Make the relation manager read-only
    // Keep the form definition for system use, but users won't access it
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('stage_id')
                    ->relationship('stage', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('event_type')
                    ->options([
                        self::EVENT_DOC_UPLOADED => 'Document Uploaded',
                        self::EVENT_DOC_APPROVED => 'Document Approved',
                        self::EVENT_DOC_REJECTED => 'Document Rejected',
                        self::EVENT_DOC_REVISION => 'Document Revision Needed',
                        self::EVENT_STAGE_CHANGE => 'Stage Changed',
                        self::EVENT_STATUS_CHANGE => 'Status Changed',
                    ])
                    ->required(),

                Forms\Components\Select::make('status')
                    ->options([
                        self::STATUS_STARTED => 'Started',
                        self::STATUS_IN_PROGRESS => 'In Progress',
                        self::STATUS_APPROVED => 'Approved',
                        self::STATUS_REJECTED => 'Rejected',
                        self::STATUS_REVISION_NEEDED => 'Revision Needed',
                        self::STATUS_OBJECTION => 'Objection',
                        self::STATUS_COMPLETED => 'Completed',
                    ])
                    ->required(),

                Forms\Components\Textarea::make('comment')
                    ->maxLength(65535)
                    ->columnSpanFull(),

                Forms\Components\Hidden::make('processed_by')
                    ->default(fn() => Auth::id()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('event_type')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        self::EVENT_DOC_UPLOADED => 'Document Uploaded',
                        self::EVENT_DOC_APPROVED => 'Document Approved',
                        self::EVENT_DOC_REJECTED => 'Document Rejected',
                        self::EVENT_DOC_REVISION => 'Document Revision Needed',
                        self::EVENT_STAGE_CHANGE => 'Stage Changed',
                        self::EVENT_STATUS_CHANGE => 'Status Changed',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        self::EVENT_DOC_UPLOADED => 'info',
                        self::EVENT_DOC_APPROVED => 'success',
                        self::EVENT_DOC_REJECTED => 'danger',
                        self::EVENT_DOC_REVISION => 'warning',
                        self::EVENT_STAGE_CHANGE => 'primary',
                        self::EVENT_STATUS_CHANGE => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('stage.name')
                    ->label('Stage')
                    ->sortable(),



                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        self::STATUS_STARTED => 'gray',
                        self::STATUS_IN_PROGRESS => 'info',
                        self::STATUS_APPROVED => 'success',
                        self::STATUS_REJECTED => 'danger',
                        self::STATUS_REVISION_NEEDED => 'warning',
                        self::STATUS_OBJECTION => 'danger',
                        self::STATUS_COMPLETED => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('comment')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (!$state || strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('processor.fullname')
                    ->label('Processed By')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event_type')
                    ->options([
                        self::EVENT_DOC_UPLOADED => 'Document Uploaded',
                        self::EVENT_DOC_APPROVED => 'Document Approved',
                        self::EVENT_DOC_REJECTED => 'Document Rejected',
                        self::EVENT_DOC_REVISION => 'Document Revision Needed',
                        self::EVENT_STAGE_CHANGE => 'Stage Changed',
                        self::EVENT_STATUS_CHANGE => 'Status Changed',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        self::STATUS_STARTED => 'Started',
                        self::STATUS_IN_PROGRESS => 'In Progress',
                        self::STATUS_APPROVED => 'Approved',
                        self::STATUS_REJECTED => 'Rejected',
                        self::STATUS_REVISION_NEEDED => 'Revision Needed',
                        self::STATUS_OBJECTION => 'Objection',
                        self::STATUS_COMPLETED => 'Completed',
                    ]),

                Tables\Filters\SelectFilter::make('stage_id')
                    ->relationship('stage', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Stage'),

                Tables\Filters\SelectFilter::make('processed_by')
                    ->relationship('processor', 'fullname')
                    ->searchable()
                    ->preload()
                    ->label('Processor'),

                Tables\Filters\Filter::make('document_related')
                    ->label('Document-Related Events Only')
                    ->query(
                        fn(Builder $query): Builder => $query
                            ->whereIn('event_type', [
                                self::EVENT_DOC_UPLOADED,
                                self::EVENT_DOC_APPROVED,
                                self::EVENT_DOC_REJECTED,
                                self::EVENT_DOC_REVISION,
                            ])
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn($record) => view('filament.tracking-history-detail', [
                        'record' => $record,
                        'document' => $record->document,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
