<?php

namespace App\Filament\Resources\WorkflowStageResource\Pages;

use App\Filament\Resources\WorkflowStageResource;
use App\Models\SubmissionType;
use App\Models\WorkflowStage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ManageStages extends Page
{
    protected static string $resource = WorkflowStageResource::class;

    protected static string $view = 'filament.resources.workflow-stage-resource.pages.manage-stages';
    
    public ?SubmissionType $record = null;
    
    public function mount(SubmissionType $record): void
    {
        $this->record = $record;
        $this->form->fill();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('resource.workflow.add_stage'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('resource.workflow.stage_name'))
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('code')
                            ->label(__('resource.workflow.stage_code'))
                            ->required()
                            ->unique(table: WorkflowStage::class, column: 'code')
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('order')
                            ->label(__('resource.workflow.display_order'))
                            ->numeric()
                            ->required()
                            ->default(function () {
                                // Get the max order and add 1
                                return WorkflowStage::where('submission_type_id', $this->record->id)
                                    ->max('order') + 1;
                            }),
                            
                        Forms\Components\Textarea::make('description')
                            ->label(__('resource.workflow.description'))
                            ->maxLength(65535)
                            ->columnSpanFull(),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('resource.workflow.active'))
                            ->default(true)
                            ->helperText(__('resource.workflow.active_helper')),
                    ])
                    ->columns(3),
            ]);
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                WorkflowStage::query()
                    ->where('submission_type_id', $this->record->id)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('resource.workflow.stage_name'))
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('code')
                    ->label(__('resource.workflow.code'))
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('order')
                    ->label(__('resource.workflow.order'))
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('resource.workflow.active'))
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label(__('resource.workflow_stage.status'))
                    ->options([
                        '1' => __('resource.workflow_stage.filter_active'),
                        '0' => __('resource.workflow_stage.filter_inactive'),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label(__('resource.workflow_stage.stage_name'))
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('code')
                            ->label(__('resource.workflow_stage.stage_code'))
                            ->required()
                            ->unique(table: WorkflowStage::class, column: 'code', ignoreRecord: true)
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('order')
                            ->label(__('resource.workflow_stage.display_order'))
                            ->numeric()
                            ->required(),
                            
                        Forms\Components\Textarea::make('description')
                            ->label(__('resource.workflow_stage.description'))
                            ->maxLength(65535)
                            ->columnSpanFull(),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('resource.workflow_stage.active'))
                            ->default(true),
                    ])
                    ->modalHeading(__('resource.workflow_stage.edit_workflow_stage')),
                
                Tables\Actions\Action::make('manage_requirements')
                    ->label(__('resource.workflow_stage.manage_requirements'))
                    ->icon('heroicon-o-document-check')
                    ->url(fn (WorkflowStage $record): string => route('filament.admin.resources.workflow-stages.manage-requirements', $record)),
                
                Tables\Actions\DeleteAction::make()
                    ->modalHeading(__('resource.workflow_stage.delete_workflow_stage'))
                    ->modalDescription(__('resource.workflow_stage.delete_confirmation')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order')
            ->reorderable('order');
    }
    
    public function create(): void
    {
        $data = $this->form->getState();
        
        try {
            WorkflowStage::create([
                'id' => Str::uuid()->toString(),
                'submission_type_id' => $this->record->id,
                'name' => $data['name'],
                'code' => $data['code'],
                'order' => $data['order'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'],
            ]);
            
            Notification::make()
                ->title(__('resource.workflow_stage.added_successfully'))
                ->success()
                ->send();
                
            $this->form->fill();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('resource.workflow_stage.error_adding'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label(__('resource.workflow_stage.back_to_submission_types'))
                ->url(route('filament.admin.resources.submission-types.index'))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),
        ];
    }
}