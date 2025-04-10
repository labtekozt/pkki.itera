<?php

namespace App\Observers;

use App\Models\Submission;
use App\Models\TrackingHistory;
use App\Services\TrackingService;

class SubmissionObserver
{
    /**
     * Handle the Submission "created" event.
     */
    public function created(Submission $submission): void
    {
        if (!$submission->current_stage_id && $submission->status === 'submitted') {
            // Set the first stage when submission is created
            $firstStage = $submission->submissionType->firstStage();
            
            if ($firstStage) {
                $submission->current_stage_id = $firstStage->id;
                $submission->save();
                
                // Create initial tracking history entry
                TrackingHistory::create([
                    'submission_id' => $submission->id,
                    'stage_id' => $firstStage->id,
                    'action' => 'submission_created',
                    'status' => 'started',
                    'processed_by' => $submission->user_id,
                    'event_type' => 'creation',
                    'source_status' => 'draft',
                    'target_status' => 'submitted',
                ]);
            }
        }
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
            
            // Only track if this wasn't handled elsewhere
            // The TrackingService already creates entries for most transitions
            if (!$submission->isDirty('current_stage_id')) {
                TrackingHistory::create([
                    'submission_id' => $submission->id,
                    'stage_id' => $submission->current_stage_id,
                    'action' => 'status_changed',
                    'status' => $newStatus,
                    'processed_by' => auth()->id(),
                    'event_type' => 'status_change',
                    'source_status' => $oldStatus,
                    'target_status' => $newStatus,
                ]);
            }
        }
        
        // Track stage changes
        if ($submission->isDirty('current_stage_id')) {
            $oldStageId = $submission->getOriginal('current_stage_id');
            
            // Only create an entry if not handled by TrackingService
            // and if this isn't the initial assignment
            if ($oldStageId) {
                TrackingHistory::create([
                    'submission_id' => $submission->id,
                    'stage_id' => $submission->current_stage_id,
                    'previous_stage_id' => $oldStageId,
                    'action' => 'stage_changed',
                    'status' => $submission->status,
                    'processed_by' => auth()->id(),
                    'event_type' => 'stage_change',
                    'source_status' => $submission->getOriginal('status'),
                    'target_status' => $submission->status,
                ]);
            }
        }
    }
}
