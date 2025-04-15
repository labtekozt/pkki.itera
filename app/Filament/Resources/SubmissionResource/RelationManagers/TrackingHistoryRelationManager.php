<?php

namespace App\Filament\Resources\SubmissionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class TrackingHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'trackingHistory';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('stage_id')
                    ->relationship('stage', 'name', function (Builder $query) {
                        $submissionTypeId = $this->ownerRecord->submission_type_id;
                        if ($submissionTypeId) {
                            $query->where('submission_type_id', $submissionTypeId)
                                ->orderBy('order');
                        }
                    })
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('status')
                    ->options([
                        'started' => 'Started',
                        'in_progress' => 'In Progress',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'revision_needed' => 'Revision Needed',
                        'objection' => 'Objection',
                        'completed' => 'Completed',
                    ])
                    ->required(),

                Forms\Components\Textarea::make('comment')
                    ->maxLength(65535)
                    ->columnSpanFull(),

                Forms\Components\Select::make('document_id')
                    ->relationship('document', 'title')
                    ->searchable()
                    ->preload(),

                Forms\Components\Hidden::make('processed_by')
                    ->default(fn() => Auth::id()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('stage.name')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'started' => 'gray',
                        'in_progress' => 'info',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'revision_needed' => 'warning',
                        'objection' => 'danger',
                        'completed' => 'success',
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

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'started' => 'Started',
                        'in_progress' => 'In Progress',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'revision_needed' => 'Revision Needed',
                        'objection' => 'Objection',
                        'completed' => 'Completed',
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
            ])
            
            ->defaultSort('created_at', 'desc');
    }
}
