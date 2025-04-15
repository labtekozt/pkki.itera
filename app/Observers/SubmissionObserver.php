<?php

namespace App\Observers;

use App\Events\SubmissionStatusChanged;
use App\Models\Submission;
use App\Models\TrackingHistory;
use Illuminate\Support\Facades\Auth;

class SubmissionObserver
{
    /**
     * Handle the Submission "created" event.
     */
    public function created(Submission $submission): void
    {
        // Create tracking entry for new submission
        TrackingHistory::create([
            'submission_id' => $submission->id,
            'stage_id' => $submission->current_stage_id,
            'event_type' => 'submission_created',
            'status' => $submission->status,
            'comment' => 'Submission created: ' . $submission->title,
            'processed_by' => Auth::id() ?? $submission->user_id,
        ]);
    }

    /**
     * Handle the Submission "updated" event.
     */
    public function updated(Submission $submission): void
    {
        // Track status changes
        if ($submission->isDirty('status')) {
            $oldStatus = $submission->getOriginal('status');
            $newStatus = $submission->status;
            
            $comment = 'Status changed from ' . 
                      ucfirst(str_replace('_', ' ', $oldStatus)) . 
                      ' to ' . 
                      ucfirst(str_replace('_', ' ', $newStatus));
            
            TrackingHistory::create([
                'submission_id' => $submission->id,
                'stage_id' => $submission->current_stage_id,
                'event_type' => 'status_change',
                'status' => $newStatus,
                'comment' => $comment,
                'processed_by' => Auth::id() ?? $submission->user_id,
                'source_status' => $oldStatus,
                'target_status' => $newStatus,
            ]);
            
            // Dispatch event
            event(new SubmissionStatusChanged($submission, $oldStatus, $newStatus));
        }
        
        // Track stage changes
        if ($submission->isDirty('current_stage_id')) {
            $oldStageId = $submission->getOriginal('current_stage_id');
            $newStageId = $submission->current_stage_id;
            
            $oldStageName = $oldStageId ? 
                \App\Models\WorkflowStage::find($oldStageId)?->name ?? 'Previous stage' : 
                'No stage';
                
            $newStageName = $newStageId ? 
                \App\Models\WorkflowStage::find($newStageId)?->name ?? 'New stage' : 
                'No stage';
            
            $comment = 'Stage changed from ' . $oldStageName . ' to ' . $newStageName;
            
            TrackingHistory::create([
                'submission_id' => $submission->id,
                'stage_id' => $newStageId,
                'previous_stage_id' => $oldStageId,
                'event_type' => 'stage_transition',
                'status' => $submission->status,
                'comment' => $comment,
                'processed_by' => Auth::id() ?? $submission->user_id,
            ]);
        }
    }

    /**
     * Handle the Submission "deleted" event.
     */
    public function deleted(Submission $submission): void
    {
        // Create tracking entry for deleted submission
        TrackingHistory::create([
            'submission_id' => $submission->id,
            'stage_id' => $submission->current_stage_id,
            'event_type' => 'submission_deleted',
            'status' => 'deleted',
            'comment' => 'Submission deleted: ' . $submission->title,
            'processed_by' => Auth::id() ?? $submission->user_id,
        ]);
    }
}
