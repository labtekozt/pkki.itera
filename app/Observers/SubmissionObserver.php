<?php

namespace App\Observers;

use App\Events\SubmissionStatusChanged;
use App\Models\Submission;
use App\Models\TrackingHistory;
use App\Services\TrackingHistoryService;
use Illuminate\Support\Facades\Auth;

class SubmissionObserver
{
    /**
     * @var TrackingHistoryService
     */
    protected $trackingService;
    
    /**
     * Create a new observer instance.
     */
    public function __construct(TrackingHistoryService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Handle the Submission "created" event.
     */
    public function created(Submission $submission): void
    {
        // create new tracking entry for new submission with stage_id is documentation_type first stage
        $firstStage = $submission->submissionType->firstStage();
        
        $this->trackingService->createTrackingRecord([
            'submission_id' => $submission->id,
            'stage_id' => $firstStage->id,
            'action' => 'started',
            'event_type' => 'submission_created',
            'status' => 'started',
            'comment' => 'Submission created: ' . $submission->title,
            'processed_by' => Auth::id() ?? $submission->user_id,
            'metadata' => [
                'submission_title' => $submission->title,
                'submission_type' => $submission->submissionType->name ?? 'Unknown',
                'created_by' => $submission->user_id,
            ],
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

            $this->trackingService->createTrackingRecord([
                'submission_id' => $submission->id,
                'stage_id' => $submission->current_stage_id,
                'event_type' => 'status_change',
                'status' => $newStatus,
                'comment' => $comment,
                'processed_by' => Auth::id() ?? $submission->user_id,
                'source_status' => $oldStatus,
                'target_status' => $newStatus,
                'metadata' => [
                    'submission_title' => $submission->title,
                    'submission_type' => $submission->submissionType->name ?? 'Unknown',
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ],
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

            $this->trackingService->createTrackingRecord([
                'submission_id' => $submission->id,
                'stage_id' => $newStageId,
                'previous_stage_id' => $oldStageId,
                'event_type' => 'stage_transition',
                'status' => $submission->status,
                'comment' => $comment,
                'processed_by' => Auth::id() ?? $submission->user_id,
                'metadata' => [
                    'submission_title' => $submission->title,
                    'submission_type' => $submission->submissionType->name ?? 'Unknown',
                    'old_stage_id' => $oldStageId,
                    'new_stage_id' => $newStageId,
                    'old_stage_name' => $oldStageName,
                    'new_stage_name' => $newStageName,
                ],
            ]);
        }
    }

    /**
     * Handle the Submission "deleted" event.
     */
    public function deleted(Submission $submission): void
    {
        // Create tracking entry for deleted submission
        $this->trackingService->createTrackingRecord([
            'submission_id' => $submission->id,
            'stage_id' => $submission->current_stage_id,
            'event_type' => 'submission_deleted',
            'status' => 'completed',
            'comment' => 'Submission deleted: ' . $submission->title,
            'processed_by' => Auth::id() ?? $submission->user_id,
            'metadata' => [
                'submission_title' => $submission->title,
                'submission_type' => $submission->submissionType->name ?? 'Unknown',
                'deleted_by' => Auth::id() ?? $submission->user_id,
            ],
        ]);
    }
}
