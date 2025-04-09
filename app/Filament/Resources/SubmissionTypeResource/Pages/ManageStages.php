<?php

namespace App\Filament\Resources\SubmissionTypeResource\Pages;

use App\Filament\Resources\SubmissionTypeResource;
use App\Models\WorkflowStage;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ManageStages extends ManageRelatedRecords
{
    protected static string $resource = SubmissionTypeResource::class;

    protected static string $relationship = 'workflowStages';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $title = 'Manage Workflow Stages';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->form([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (!$state) return;
                            $set('code', Str::slug($state, '_'));
                        }),
                        
                    Forms\Components\TextInput::make('code')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                        
                    Forms\Components\Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull(),
                        
                    Forms\Components\TextInput::make('order')
                        ->label('Stage Order')
                        ->numeric()
                        ->default(fn ($livewire) => WorkflowStage::where('submission_type_id', $livewire->ownerRecord->id)->count() + 1),
                        
                    Forms\Components\Select::make('document_requirements')
                        ->label('Document Requirements')
                        ->relationship('documentRequirements', 'name', fn ($query, $record) => 
                            $query->where('submission_type_id', $record->ownerRecord->id))
                        ->multiple()
                        ->preload(),
                ])
                ->mutateFormDataUsing(function (array $data) {
                    $data['submission_type_id'] = $this->getOwnerRecord()->id;
                    return $data;
                })
                ->successRedirectUrl(fn () => $this->getResource()::getUrl('stages', ['record' => $this->getOwnerRecord()])),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('order')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('description')
                    ->limit(50),
                
                Tables\Columns\TextColumn::make('documentRequirements_count')
                    ->label('Requirements')
                    ->counts('documentRequirements'),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('manage_requirements')
                    ->label('Stage Requirements')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (WorkflowStage $record): string => 
                        SubmissionTypeResource::getUrl('stage-requirements', ['record' => $this->getOwnerRecord(), 'stageId' => $record->id])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
