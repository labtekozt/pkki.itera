<?php

namespace App\Filament\Resources\SubmissionTypeResource\RelationManagers;

use App\Filament\Resources\SubmissionTypeResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

class WorkflowStagesRelationManager extends RelationManager
{
    protected static string $relationship = 'workflowStages';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Stage Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!$state || $this->mountedActionName === 'edit') return;
                                $set('code', Str::slug($state, '_'));
                            }),
                            
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Unique identifier for this stage'),
                            
                        Forms\Components\TextInput::make('order')
                            ->label('Stage Order')
                            ->numeric()
                            ->default(fn ($livewire) => $livewire->ownerRecord->workflowStages()->count() + 1)
                            ->helperText('Order in the workflow sequence'),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Stage')
                            ->helperText('Inactive stages will be skipped in the workflow')
                            ->default(true),
                            
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->helperText('Detailed explanation of this stage')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Document Requirements')
                    ->schema([
                        Forms\Components\Select::make('document_requirements')
                            ->label('Required Documents')
                            ->relationship('documentRequirements', 'name', function ($query, $get) {
                                $submissionTypeId = $this->getOwnerRecord()?->id;
                                
                                if ($submissionTypeId) {
                                    $query->where('submission_type_id', $submissionTypeId);
                                }
                            })
                            ->multiple()
                            ->preload()
                            ->helperText('Documents that must be provided at this stage')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->reorderable('order')
            ->defaultSort('order')
            ->columns([
                Tables\Columns\TextColumn::make('order')
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => Str::limit($record->description, 40)),
                    
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                    
                Tables\Columns\TextColumn::make('documentRequirements_count')
                    ->label('Requirements')
                    ->getStateUsing(function ($record) {
                        return $record->documentRequirements()->count();
                    })
                    ->color('success')
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('currentSubmissions_count')
                    ->label('Active Submissions')
                    ->getStateUsing(function ($record) {
                        return $record->currentSubmissions()->count();
                    })
                    ->color('warning')
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Active Status')
                    ->options([
                        '1' => 'Active Stages',
                        '0' => 'Inactive Stages',
                    ])
                    ->query(function ($query, array $data) {
                        if (isset($data['value'])) {
                            return $query->where('is_active', $data['value']);
                        }
                        
                        return $query;
                    }),
                    
                Tables\Filters\Filter::make('has_requirements')
                    ->label('Has Requirements')
                    ->query(function (Builder $query) {
                        return $query->whereHas('documentRequirements');
                    })
                    ->toggle(),
                    
                Tables\Filters\Filter::make('has_submissions')
                    ->label('Has Active Submissions')
                    ->query(function (Builder $query) {
                        return $query->whereHas('currentSubmissions');
                    })
                    ->toggle(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->modalWidth('lg'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth('lg'),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to delete this stage? This may affect ongoing submissions.'),
                Tables\Actions\Action::make('manage_requirements')
                    ->label('Stage Requirements')
                    ->color('success')
                    ->icon('heroicon-o-document-text')
                    ->url(fn ($record) => SubmissionTypeResource::getUrl(
                        'stage-requirements', 
                        ['record' => $record->submission_type_id, 'stageId' => $record->id]
                    )),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn ($record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['is_active' => !$record->is_active]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalDescription('Are you sure you want to delete these stages? This may affect ongoing submissions.'),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Stages')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => true]);
                            }
                        }),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Stages')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => false]);
                            }
                        }),
                ]),
            ]);
    }
}
