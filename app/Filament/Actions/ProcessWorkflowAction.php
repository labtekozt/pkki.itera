<?php

namespace App\Filament\Actions;

use App\Models\Submission;
use App\Models\WorkflowStage;
use App\Services\WorkflowService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ProcessWorkflowAction extends Action
{
    protected ?\Closure $getActionsCallback = null;
    protected string $defaultAction = 'approve';
    
    public static function getDefaultName(): ?string
    {
        return 'processWorkflow';
    }
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->label('Process');
        $this->icon('heroicon-o-cog');
        $this->color('primary');
        
        $this->form(function (Form $form, Model $record): Form {
            $workflowService = app(WorkflowService::class);
            $availableActions = $workflowService->getAvailableActions($record);
            
            $actions = [];
            foreach ($availableActions as $action) {
                $actions[$action['id']] = $action['label'];
            }
            
            // If custom actions are provided, use those instead
            if ($this->getActionsCallback) {
                $actions = call_user_func($this->getActionsCallback, $record, $actions);
            }
            
            return $form
                ->schema([
                    Forms\Components\Select::make('action')
                        ->label('Action')
                        ->options($actions)
                        ->default($this->defaultAction)
                        ->required()
                        ->reactive(),
                        
                    Forms\Components\Textarea::make('comment')
                        ->label('Comment')
                        ->required()
                        ->placeholder('Enter your comment...')
                        ->columnSpan('full'),
                        
                    Forms\Components\Select::make('next_stage_id')
                        ->label('Next Stage')
                        ->options(function (Forms\Get $get, Model $record) {
                            if ($get('action') !== 'advance_stage') {
                                return [];
                            }
                            
                            // Get available next stages
                            return $record->submissionType->workflowStages()
                                ->where('order', '>', $record->currentStage->order ?? 0)
                                ->where('is_active', true)
                                ->orderBy('order')
                                ->pluck('name', 'id')
                                ->toArray();
                        })
                        ->searchable()
                        ->visible(fn (Forms\Get $get) => $get('action') === 'advance_stage')
                        ->required(fn (Forms\Get $get) => $get('action') === 'advance_stage'),
                        
                    Forms\Components\Select::make('previous_stage_id')
                        ->label('Previous Stage')
                        ->options(function (Model $record) {
                            return $record->submissionType->workflowStages()
                                ->where('order', '<', $record->currentStage->order ?? PHP_INT_MAX)
                                ->where('is_active', true)
                                ->orderBy('order', 'desc')
                                ->pluck('name', 'id')
                                ->toArray();
                        })
                        ->searchable()
                        ->visible(fn (Forms\Get $get) => $get('action') === 'return_stage')
                        ->required(fn (Forms\Get $get) => $get('action') === 'return_stage'),
                        
                    Forms\Components\Select::make('reject_reason')
                        ->label('Rejection Reason')
                        ->options([
                            'incomplete' => 'Incomplete Documentation',
                            'ineligible' => 'Does Not Meet Eligibility Requirements',
                            'duplicate' => 'Duplicate Submission',
                            'inappropriate' => 'Inappropriate Content',
                            'technical' => 'Technical Issues',
                            'other' => 'Other (Please Specify)',
                        ])
                        ->visible(fn (Forms\Get $get) => $get('action') === 'reject')
                        ->required(fn (Forms\Get $get) => $get('action') === 'reject'),
                ]);
        });
        
        $this->action(function (array $data, Model $record) {
            try {
                $workflowService = app(WorkflowService::class);
                $options = [
                    'comment' => $data['comment'],
                    'processor' => Auth::user(),
                ];
                
                if (isset($data['next_stage_id'])) {
                    $options['target_stage_id'] = $data['next_stage_id'];
                }
                
                if (isset($data['previous_stage_id'])) {
                    $options['target_stage_id'] = $data['previous_stage_id'];
                }
                
                if (isset($data['reject_reason'])) {
                    $options['reason'] = $data['reject_reason'];
                }
                
                $submission = $workflowService->processAction($record, $data['action'], $options);
                
                Notification::make()
                    ->title('Workflow action processed successfully')
                    ->success()
                    ->send();
                    
            } catch (\Exception $e) {
                Notification::make()
                    ->title('Error processing workflow action')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
                    
                throw new Halt();
            }
        });
    }
    
    public function getActions(?\Closure $callback): static
    {
        $this->getActionsCallback = $callback;
        
        return $this;
    }
    
    public function defaultAction(string $actionName): static
    {
        $this->defaultAction = $actionName;
        
        return $this;
    }
}
