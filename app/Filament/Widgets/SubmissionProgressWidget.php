<?php

namespace App\Filament\Widgets;

use App\Models\Submission;
use App\Services\WorkflowService;
use Filament\Widgets\Widget;
use Illuminate\Support\Str;

class SubmissionProgressWidget extends Widget
{
    protected static string $view = 'filament.widgets.submission-progress-widget';
    
    public ?Submission $submission = null;
    public array $statistics = [];
    public array $progressSteps = [];
    
    public function mount(?Submission $submission = null)
    {
        $this->submission = $submission;
        
        if ($this->submission) {
            $this->loadSubmissionData();
        }
    }
    
    protected function loadSubmissionData(): void
    {
        $workflowService = app(WorkflowService::class);
        $this->statistics = $workflowService->getWorkflowStatistics($this->submission);
        
        // Build workflow steps for progress display
        $this->buildProgressSteps();
    }
    
    protected function buildProgressSteps(): void
    {
        if (!$this->submission || !$this->submission->submissionType) {
            $this->progressSteps = [];
            return;
        }
        
        $stages = $this->submission->submissionType->workflowStages()
            ->orderBy('order')
            ->get();
            
        $currentStageId = $this->submission->current_stage_id;
        $trackingHistory = $this->submission->trackingHistory()->get();
        
        $steps = [];
        foreach ($stages as $stage) {
            $stageHistory = $trackingHistory->where('stage_id', $stage->id);
            
            $step = [
                'id' => $stage->id,
                'name' => $stage->name,
                'status' => 'upcoming', // Default
                'date' => null,
                'description' => $stage->description,
                'actions' => [],
                'days_spent' => 0,
            ];
            
            if ($stageHistory->isNotEmpty()) {
                $firstAction = $stageHistory->sortBy('created_at')->first();
                $lastAction = $stageHistory->sortByDesc('created_at')->first();
                
                $step['date'] = $firstAction->created_at->format('M j, Y');
                
                // Calculate days spent in this stage
                if ($stage->id === $currentStageId) {
                    $step['days_spent'] = $firstAction->created_at->diffInDays(now());
                    $step['status'] = 'current';
                } else {
                    $nextStageEntry = $trackingHistory
                        ->where('previous_stage_id', $stage->id)
                        ->sortBy('created_at')
                        ->first();
                    
                    if ($nextStageEntry) {
                        $step['days_spent'] = $firstAction->created_at->diffInDays($nextStageEntry->created_at);
                    }
                    
                    $step['status'] = 'completed';
                }
                
                // Log recent actions
                foreach ($stageHistory->sortByDesc('created_at')->take(3) as $action) {
                    $step['actions'][] = [
                        'action' => Str::title(str_replace('_', ' ', $action->action)),
                        'status' => $action->status,
                        'date' => $action->created_at->format('M j, Y'),
                        'user' => $action->processor?->fullname ?? 'System',
                        'comment' => $action->comment,
                    ];
                }
            } else if ($stage->id === $currentStageId) {
                $step['status'] = 'current';
                $step['date'] = now()->format('M j, Y');
            }
            
            $steps[] = $step;
        }
        
        $this->progressSteps = $steps;
    }
    
    public function getStatusColor(string $status): string
    {
        return match($status) {
            'completed' => 'success',
            'current' => 'primary',
            'upcoming' => 'gray',
            default => 'gray',
        };
    }
}
