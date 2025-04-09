<?php

namespace App\Filament\Resources\SubmissionTypeResource\Pages;

use App\Filament\Resources\SubmissionTypeResource;
use App\Models\DocumentRequirement;
use App\Models\WorkflowStage;
use App\Models\WorkflowStageRequirement;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class StageRequirements extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static string $resource = SubmissionTypeResource::class;

    protected static string $view = 'filament.resources.submission-type-resource.pages.stage-requirements';

    public ?WorkflowStage $stage = null;

    public function mount(string $record, string $stageId): void
    {
        $this->record = $record;
        $this->stage = WorkflowStage::findOrFail($stageId);
        
        // Make sure this stage belongs to the current submission type
        if ($this->stage->submission_type_id !== $this->record) {
            $this->redirect($this->getResource()::getUrl('stages', ['record' => $this->record]));
        }
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('attach_requirement')
                ->label('Attach Requirement')
                ->icon('heroicon-o-plus')
                ->form([
                    Forms\Components\Select::make('requirement_id')
                        ->label('Document Requirement')
                        ->options(function () {
                            // Get requirements belonging to this submission type
                            // that are not already attached to this stage
                            $attachedRequirementIds = $this->stage->stageRequirements()
                                ->pluck('document_requirement_id');
                                
                            return DocumentRequirement::where('submission_type_id', $this->record)
                                ->whereNotIn('id', $attachedRequirementIds)
                                ->pluck('name', 'id');
                        })
                        ->required()
                        ->searchable(),
                        
                    Forms\Components\Toggle::make('is_required')
                        ->label('Is Required')
                        ->default(true),
                        
                    Forms\Components\TextInput::make('order')
                        ->label('Display Order')
                        ->numeric()
                        ->default(fn () => $this->stage->stageRequirements()->count() + 1),
                ])
                ->action(function (array $data) {
                    WorkflowStageRequirement::create([
                        'workflow_stage_id' => $this->stage->id,
                        'document_requirement_id' => $data['requirement_id'],
                        'is_required' => $data['is_required'],
                        'order' => $data['order'],
                    ]);
                    
                    $this->notify('success', 'Requirement attached successfully');
                }),
                
            Actions\Action::make('back_to_stages')
                ->label('Back to Stages')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => $this->getResource()::getUrl('stages', ['record' => $this->record])),
        ];
    }
    
    public function getTitle(): string
    {
        return "Stage Requirements: {$this->stage->name}";
    }
    
    public function getSubHeading(): string
    {
        return "Manage document requirements for this workflow stage";
    }
    
    protected function getTableQuery(): Builder
    {
        return WorkflowStageRequirement::query()
            ->where('workflow_stage_id', $this->stage->id);
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('order')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('documentRequirement.name')
                    ->label('Requirement Name')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('documentRequirement.code')
                    ->label('Code')
                    ->searchable(),
                    
                Tables\Columns\IconColumn::make('is_required')
                    ->label('Required')
                    ->boolean(),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
                        Forms\Components\Toggle::make('is_required')
                            ->label('Is Required')
                            ->default(true),
                            
                        Forms\Components\TextInput::make('order')
                            ->label('Display Order')
                            ->numeric(),
                    ]),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
