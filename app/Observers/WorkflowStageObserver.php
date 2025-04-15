<?php

namespace App\Observers;

use App\Events\WorkflowStageChanged;
use App\Models\TrackingHistory;
use App\Models\WorkflowStage;
use Illuminate\Support\Facades\Auth;

class WorkflowStageObserver
{
    /**
     * Handle the WorkflowStage "created" event.
     */
    public function created(WorkflowStage $workflowStage): void
    {
        // Dispatch event for workflow stage creation
        event(new WorkflowStageChanged(
            $workflowStage, 
            'created', 
            [
                'submission_type_id' => $workflowStage->submission_type_id,
                'created_by' => Auth::id(),
            ]
        ));
    }

    /**
     * Handle the WorkflowStage "updated" event.
     */
    public function updated(WorkflowStage $workflowStage): void
    {
        $changes = [];
        $changeDescription = '';
        
        // Track changes to important fields
        if ($workflowStage->isDirty('name')) {
            $changes['name'] = [
                'old' => $workflowStage->getOriginal('name'),
                'new' => $workflowStage->name,
            ];
            $changeDescription .= "Name changed from '{$workflowStage->getOriginal('name')}' to '{$workflowStage->name}'. ";
        }
        
        if ($workflowStage->isDirty('order')) {
            $changes['order'] = [
                'old' => $workflowStage->getOriginal('order'),
                'new' => $workflowStage->order,
            ];
            $changeDescription .= "Order changed from {$workflowStage->getOriginal('order')} to {$workflowStage->order}. ";
        }
        
        if ($workflowStage->isDirty('is_active')) {
            $changes['is_active'] = [
                'old' => $workflowStage->getOriginal('is_active'),
                'new' => $workflowStage->is_active,
            ];
            $newStatus = $workflowStage->is_active ? 'active' : 'inactive';
            $oldStatus = $workflowStage->getOriginal('is_active') ? 'active' : 'inactive';
            $changeDescription .= "Status changed from {$oldStatus} to {$newStatus}. ";
        }
        
        if (!empty($changes)) {
            // Dispatch event with the changes
            event(new WorkflowStageChanged(
                $workflowStage, 
                'updated', 
                [
                    'changes' => $changes,
                    'description' => $changeDescription,
                    'updated_by' => Auth::id(),
                ]
            ));
            
            // Update any submissions affected by this workflow stage change
            $this->updateRelatedSubmissions($workflowStage, $changeDescription);
        }
    }

    /**
     * Handle the WorkflowStage "deleted" event.
     */
    public function deleted(WorkflowStage $workflowStage): void
    {
        // Dispatch event for workflow stage deletion
        event(new WorkflowStageChanged(
            $workflowStage, 
            'deleted', 
            [
                'deleted_by' => Auth::id(),
            ]
        ));
    }
    
    /**
     * Update related submissions affected by workflow stage changes.
     */
    private function updateRelatedSubmissions(WorkflowStage $workflowStage, string $changeDescription): void
    {
        // Get all submissions that are currently at this stage
        $affectedSubmissions = $workflowStage->currentSubmissions;
        
        foreach ($affectedSubmissions as $submission) {
            // Create tracking history for each affected submission
            TrackingHistory::create([
                'submission_id' => $submission->id,
                'stage_id' => $workflowStage->id,
                'event_type' => 'workflow_stage_changed',
                'status' => $submission->status,
                'comment' => "Workflow stage updated: {$changeDescription}",
                'processed_by' => Auth::id(),
                'metadata' => [
                    'stage_id' => $workflowStage->id,
                    'stage_name' => $workflowStage->name,
                ],
            ]);
        }
    }
}