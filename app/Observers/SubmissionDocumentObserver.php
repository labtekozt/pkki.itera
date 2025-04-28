<?php

namespace App\Observers;

use App\Events\SubmissionDocumentStatusChanged;
use App\Models\Document;
use App\Models\SubmissionDocument;
use App\Models\TrackingHistory;
use App\Services\TrackingHistoryService;
use Illuminate\Support\Facades\Auth;

class SubmissionDocumentObserver
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
     * Handle the SubmissionDocument "updated" event.
     */

    public function created(SubmissionDocument $submissionDocument): void
    {
        // Fire an event when a new document is created with a non-pending status
        if ($submissionDocument->status !== 'pending') {
            event(new SubmissionDocumentStatusChanged($submissionDocument));
        }
    }
    public function updated(SubmissionDocument $submissionDocument): void
    {

        // Check if the status has changed
        if ($submissionDocument->isDirty('status')) {
            $oldStatus = $submissionDocument->getOriginal('status');
            $newStatus = $submissionDocument->status;

            // Only fire the event if the status has changed
            if ($oldStatus !== $newStatus) {
                event(new SubmissionDocumentStatusChanged($submissionDocument, $oldStatus));
            }
        }

        // Only track status changes
        if ($submissionDocument->isDirty('status')) {
            $oldStatus = $submissionDocument->getOriginal('status');
            $newStatus = $submissionDocument->status;

            // Determine the action based on the new status
            $action = match ($newStatus) {
                'approved' => 'approve',
                'rejected' => 'reject',
                'revision_needed' => 'revision',
                default => 'update'
            };

            // Determine the event type based on the new status
            $eventType = match ($newStatus) {
                'approved' => 'document_approved',
                'rejected' => 'document_rejected',
                'revision_needed' => 'document_revision_needed',
                default => 'status_change'
            };

            // Map document status to tracking status
            $trackingStatus = match ($newStatus) {
                'approved' => 'approved',
                'rejected' => 'rejected',
                'revision_needed' => 'revision_needed',
                default => 'in_progress'
            };

            $comment = 'Document status changed from ' .
                ucfirst(str_replace('_', ' ', $oldStatus)) .
                ' to ' .
                ucfirst(str_replace('_', ' ', $newStatus));

            if ($submissionDocument->notes) {
                $comment .= "\n\nNotes: " . $submissionDocument->notes;
            }

            $this->createTrackingEntry(
                $submissionDocument,
                $action,
                $eventType,
                $trackingStatus,
                $comment,
                $oldStatus,
                $newStatus
            );

            // Dispatch the status changed event for tracking
            event(new \App\Events\SubmissionDocumentStatusChanged(
                $submissionDocument,
                $oldStatus,
                $newStatus
            ));
        }
    }

    /**
     * Handle the SubmissionDocument "deleted" event.
     */
    public function deleted(SubmissionDocument $submissionDocument): void
    {
        // Create a tracking history entry for document deletion
        if ($submissionDocument->document) {
            $this->createTrackingEntry(
                $submissionDocument,
                'delete',
                'document_deleted',
                'completed',
                'Document removed: ' . $submissionDocument->document->title
            );
        }
    }

    /**
     * Create a tracking history entry for a document event.
     *
     * @param SubmissionDocument $submissionDocument
     * @param string $action
     * @param string $eventType
     * @param string $status
     * @param string $comment
     * @param string|null $sourceStatus
     * @param string|null $targetStatus
     * @return TrackingHistory
     */
    private function createTrackingEntry(
        SubmissionDocument $submissionDocument,
        string $action,
        string $eventType,
        string $status,
        string $comment,
        ?string $sourceStatus = null,
        ?string $targetStatus = null
    ): TrackingHistory {
        $submission = $submissionDocument->submission;

        return $this->trackingService->createTrackingRecord([
            'submission_id' => $submission->id,
            'stage_id' => $submission->current_stage_id,
            'action' => $action,
            'document_id' => $submissionDocument->document_id,
            'event_type' => $eventType,
            'status' => $status,
            'comment' => $comment,
            'processed_by' => Auth::id() ?? $submission->user_id,
            'source_status' => $sourceStatus,
            'target_status' => $targetStatus,
            'metadata' => [
                'document_type' => $submissionDocument->document_type,
                'original_filename' => $submissionDocument->document->original_filename ?? null,
            ],
        ]);
    }
}
