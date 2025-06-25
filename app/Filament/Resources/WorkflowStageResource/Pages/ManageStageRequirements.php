<?php

namespace App\Filament\Resources\WorkflowStageResource\Pages;

use App\Filament\Resources\WorkflowStageResource;
use App\Models\DocumentRequirement;
use App\Models\WorkflowStage;
use App\Models\WorkflowStageRequirement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ManageStageRequirements extends Page
{
    protected static string $resource = WorkflowStageResource::class;

    protected static string $view = 'filament.resources.workflow-stage-resource.pages.manage-stage-requirements';
    
    public ?WorkflowStage $record = null;
    
    public function mount(WorkflowStage $record): void
    {
        $this->record = $record;
        $this->form->fill();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Add Document Requirement')
                    ->schema([
                        Forms\Components\Select::make('document_requirement_id')
                            ->label('Document Requirement')
                            ->options(
                                DocumentRequirement::whereNotIn('id', function($query) {
                                    $query->select('document_requirement_id')
                                        ->from('workflow_stage_requirements')
                                        ->where('workflow_stage_id', $this->record->id);
                                })->pluck('name', 'id')
                            )
                            ->searchable()
                            ->required(),
                        
                        Forms\Components\Toggle::make('is_required')
                            ->label('Required')
                            ->default(true)
                            ->helperText('Is this document required for stage completion?'),
                        
                        Forms\Components\TextInput::make('order')
                            ->label('Display Order')
                            ->numeric()
                            ->default(1),
                    ])
                    ->columns(3),
            ]);
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                WorkflowStageRequirement::query()
                    ->where('workflow_stage_id', $this->record->id)
                    ->with('documentRequirement')
            )
            ->columns([
                Tables\Columns\TextColumn::make('documentRequirement.name')
                    ->label(__('resource.workflow_stage.requirements.document_name'))
                    ->searchable(),
                
                Tables\Columns\IconColumn::make('is_required')
                    ->label(__('resource.workflow_stage.requirements.required'))
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('order')
                    ->label(__('resource.workflow_stage.display_order'))
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
                        Forms\Components\Toggle::make('is_required')
                            ->label(__('resource.workflow_stage.requirements.required'))
                            ->default(true),
                        
                        Forms\Components\TextInput::make('order')
                            ->label(__('resource.workflow_stage.display_order'))
                            ->numeric(),
                    ])
                    ->modalHeading(__('resource.workflow_stage.requirements.edit_requirement')),
                
                Tables\Actions\DeleteAction::make()
                    ->modalHeading(__('resource.workflow_stage.requirements.remove_document_requirement'))
                    ->modalDescription(__('resource.workflow_stage.requirements.remove_confirmation')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order');
    }
    
    public function create(): void
    {
        $data = $this->form->getState();
        
        try {
            WorkflowStageRequirement::create([
                'id' => Str::uuid()->toString(),
                'workflow_stage_id' => $this->record->id,
                'document_requirement_id' => $data['document_requirement_id'],
                'is_required' => $data['is_required'],
                'order' => $data['order'],
            ]);
            
            Notification::make()
                ->title(__('resource.workflow_stage.requirements.added_successfully'))
                ->success()
                ->send();
                
            $this->form->fill();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('resource.workflow_stage.requirements.error_adding'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label(__('resource.workflow_stage.requirements.back_to_stages'))
                ->url($this->getResource()::getUrl('index'))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),
        ];
    }
}