<?php

namespace App\Observers;

use App\Models\Document;
use App\Models\SubmissionDocument;
use App\Models\TrackingHistory;
use Illuminate\Support\Facades\Auth;

class SubmissionDocumentObserver
{
    /**
     * Handle the SubmissionDocument "created" event.
     */
    public function created(SubmissionDocument $submissionDocument): void
    {
        // Create a tracking history entry for document upload
        if ($submissionDocument->document) {
            $this->createTrackingEntry(
                $submissionDocument,
                'document_uploaded',
                'pending',
                'Document uploaded: ' . $submissionDocument->document->title
            );
        }
    }

    /**
     * Handle the SubmissionDocument "updated" event.
     */
    public function updated(SubmissionDocument $submissionDocument): void
    {
        // Only track status changes
        if ($submissionDocument->isDirty('status')) {
            $oldStatus = $submissionDocument->getOriginal('status');
            $newStatus = $submissionDocument->status;

            // Determine the event type based on the new status
            $eventType = match ($newStatus) {
                'approved' => 'document_approved',
                'rejected' => 'document_rejected',
                'revision_needed' => 'document_revision_needed',
                default => 'status_change'
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
                $eventType,
                $newStatus,
                $comment
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
                'document_deleted',
                'deleted',
                'Document removed: ' . $submissionDocument->document->title
            );
        }
    }

    /**
     * Create a tracking history entry for a document event.
     *
     * @param SubmissionDocument $submissionDocument
     * @param string $eventType
     * @param string $status
     * @param string $comment
     * @return TrackingHistory
     */
    private function createTrackingEntry(
        SubmissionDocument $submissionDocument,
        string $eventType,
        string $status,
        string $comment
    ): TrackingHistory {
        $submission = $submissionDocument->submission;

        return TrackingHistory::create([
            'submission_id' => $submission->id,
            /**
             * Defines the action type for document-related operations.
             * 
             * This constant determines how the system should handle document processing
             * within the submission context. Using a consistent action identifier
             * helps with event tracking and action dispatching.
             * 
             * @var string Action identifier for document operations
             */
            'action' => Document::ACTION_DOCUMENT_STATUS_CHANGED,
            'stage_id' => $submission->current_stage_id,
            'document_id' => $submissionDocument->document_id,
            'event_type' => $eventType,
            'status' => $status,
            'comment' => $comment,
            'processed_by' => Auth::id() ?? $submission->user_id,
            'created_at' => now(),
        ]);
    }
}
