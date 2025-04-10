<?php

namespace App\Services;

use App\Events\SubmissionStateChanged;
use App\Models\Submission;
use App\Models\TrackingHistory;
use App\Models\User;
use App\Models\WorkflowStage;
use Illuminate\Support\Facades\DB;

class TrackingService
{
    /**
     * Track a state change in a submission.
     *
     * @param Submission $submission
     * @param string $action
     * @param string $status
     * @param array $options
     * @return TrackingHistory
     */
    public function trackStateChange(
        Submission $submission,
        string $action,
        string $status,
        array $options = []
    ): TrackingHistory {
        $options = array_merge([
            'comment' => null,
            'metadata' => [],
            'processor' => auth()->user(),
            'document_id' => null,
        ], $options);

        $trackingEntry = TrackingHistory::create([
            'submission_id' => $submission->id,
            'stage_id' => $submission->current_stage_id,
            'action' => $action,
            'status' => $status,
            'comment' => $options['comment'],
            'metadata' => $options['metadata'],
            'processed_by' => $options['processor']?->id,
            'document_id' => $options['document_id'],
            'source_status' => $submission->status,
            'target_status' => $status,
            'event_type' => 'state_change',
        ]);

        // Fire a state changed event
        event(new SubmissionStateChanged($submission, $action, $status, $trackingEntry));

        return $trackingEntry;
    }

    /**
     * Advance a submission to the next stage.
     *
     * @param Submission $submission
     * @param User|null $processor
     * @param string|null $comment
     * @param array $metadata
     * @return Submission
     */
    public function advanceToNextStage(
        Submission $submission,
        ?User $processor = null,
        ?string $comment = null,
        array $metadata = []
    ): Submission {
        return DB::transaction(function () use ($submission, $processor, $comment, $metadata) {
            $currentStage = $submission->currentStage;
            $nextStage = $currentStage->nextStage();
            
            if (!$nextStage) {
                // This is the final stage, complete the submission
                return $this->completeSubmission($submission, $processor, $comment, $metadata);
            }
            
            $previousStage = $currentStage;
            
            // Update the submission's current stage
            $submission->current_stage_id = $nextStage->id;
            
            if ($nextStage->isFinalStage()) {
                $submission->status = 'in_review';
            } else {
                $submission->status = 'in_review';
            }
            
            $submission->save();
            
            // Create a tracking entry for this transition
            TrackingHistory::createTransition(
                $submission,
                $previousStage,
                $nextStage,
                'advance_stage',
                'approved',
                $comment,
                $processor,
                $metadata
            );
            
            return $submission->fresh();
        });
    }
    
    /**
     * Return a submission to a previous stage.
     *
     * @param Submission $submission
     * @param User|null $processor
     * @param string|null $comment
     * @param array $metadata
     * @return Submission
     */
    public function returnToPreviousStage(
        Submission $submission,
        ?User $processor = null,
        ?string $comment = null,
        array $metadata = []
    ): Submission {
        return DB::transaction(function () use ($submission, $processor, $comment, $metadata) {
            $currentStage = $submission->currentStage;
            $previousStage = $currentStage->previousStage();
            
            if (!$previousStage) {
                throw new \Exception("This submission is already at the initial stage.");
            }
            
            // Update the submission's current stage
            $submission->current_stage_id = $previousStage->id;
            $submission->status = 'revision_needed';
            $submission->save();
            
            // Create a tracking entry for this transition
            TrackingHistory::createTransition(
                $submission,
                $currentStage,
                $previousStage,
                'return_stage',
                'revision_needed',
                $comment,
                $processor,
                $metadata
            );
            
            return $submission->fresh();
        });
    }
    
    /**
     * Complete a submission (final approval).
     *
     * @param Submission $submission
     * @param User|null $processor
     * @param string|null $comment
     * @param array $metadata
     * @return Submission
     */
    public function completeSubmission(
        Submission $submission,
        ?User $processor = null,
        ?string $comment = null,
        array $metadata = []
    ): Submission {
        return DB::transaction(function () use ($submission, $processor, $comment, $metadata) {
            $currentStage = $submission->currentStage;
            
            // Generate a certificate number if not already present
            if (!$submission->certificate) {
                $submission->certificate = $this->generateCertificateNumber($submission);
            }
            
            $submission->status = 'completed';
            $submission->save();
            
            // Create a tracking entry for completion
            TrackingHistory::create([
                'submission_id' => $submission->id,
                'stage_id' => $currentStage->id,
                'action' => 'complete_submission',
                'status' => 'completed',
                'comment' => $comment,
                'metadata' => array_merge($metadata, ['certificate' => $submission->certificate]),
                'processed_by' => $processor?->id,
                'source_status' => 'in_review',
                'target_status' => 'completed',
                'event_type' => 'completion',
            ]);
            
            return $submission->fresh();
        });
    }
    
    /**
     * Reject a submission.
     *
     * @param Submission $submission
     * @param User|null $processor
     * @param string|null $comment
     * @param array $metadata
     * @return Submission
     */
    public function rejectSubmission(
        Submission $submission,
        ?User $processor = null,
        ?string $comment = null,
        array $metadata = []
    ): Submission {
        return DB::transaction(function () use ($submission, $processor, $comment, $metadata) {
            $currentStage = $submission->currentStage;
            
            $submission->status = 'rejected';
            $submission->save();
            
            // Create a tracking entry for rejection
            TrackingHistory::create([
                'submission_id' => $submission->id,
                'stage_id' => $currentStage->id,
                'action' => 'reject_submission',
                'status' => 'rejected',
                'comment' => $comment,
                'metadata' => $metadata,
                'processed_by' => $processor?->id,
                'source_status' => $submission->getOriginal('status'),
                'target_status' => 'rejected',
                'event_type' => 'rejection',
            ]);
            
            return $submission->fresh();
        });
    }
    
    /**
     * Request revisions for a submission.
     *
     * @param Submission $submission
     * @param User|null $processor
     * @param string|null $comment
     * @param array $metadata
     * @return Submission
     */
    public function requestRevisions(
        Submission $submission,
        ?User $processor = null, 
        ?string $comment = null,
        array $metadata = []
    ): Submission {
        return DB::transaction(function () use ($submission, $processor, $comment, $metadata) {
            $currentStage = $submission->currentStage;
            
            $submission->status = 'revision_needed';
            $submission->save();
            
            // Create a tracking entry for revision request
            TrackingHistory::create([
                'submission_id' => $submission->id,
                'stage_id' => $currentStage->id,
                'action' => 'request_revision',
                'status' => 'revision_needed',
                'comment' => $comment,
                'metadata' => $metadata,
                'processed_by' => $processor?->id,
                'source_status' => $submission->getOriginal('status'),
                'target_status' => 'revision_needed',
                'event_type' => 'revision_request',
            ]);
            
            return $submission->fresh();
        });
    }
    
    /**
     * Submit revisions for a submission.
     *
     * @param Submission $submission
     * @param User|null $processor
     * @param string|null $comment
     * @param array $metadata
     * @return Submission
     */
    public function submitRevisions(
        Submission $submission,
        ?User $processor = null,
        ?string $comment = null,
        array $metadata = []
    ): Submission {
        return DB::transaction(function () use ($submission, $processor, $comment, $metadata) {
            $currentStage = $submission->currentStage;
            
            $submission->status = 'in_review';
            $submission->save();
            
            // Create a tracking entry for revision submission
            TrackingHistory::create([
                'submission_id' => $submission->id,
                'stage_id' => $currentStage->id,
                'action' => 'submit_revision',
                'status' => 'in_review',
                'comment' => $comment,
                'metadata' => $metadata,
                'processed_by' => $processor?->id,
                'source_status' => 'revision_needed',
                'target_status' => 'in_review',
                'event_type' => 'revision_submission',
            ]);
            
            // Resolve any pending revision requests
            $this->resolveRelatedRevisionRequests($submission);
            
            return $submission->fresh();
        });
    }
    
    /**
     * Generate a certificate number for a submission.
     *
     * @param Submission $submission
     * @return string
     */
    protected function generateCertificateNumber(Submission $submission): string
    {
        $type = substr(strtoupper($submission->submissionType->slug), 0, 3);
        $year = date('Y');
        $sequence = str_pad(Submission::whereYear('created_at', $year)->count() + 1, 6, '0', STR_PAD_LEFT);
        
        return "{$type}/{$year}/{$sequence}";
    }
    
    /**
     * Resolve any related revision requests.
     *
     * @param Submission $submission
     * @return void
     */
    protected function resolveRelatedRevisionRequests(Submission $submission): void
    {
        $submission->trackingHistory()
            ->where('status', 'revision_needed')
            ->whereNull('resolved_at')
            ->update(['resolved_at' => now()]);
    }
}
