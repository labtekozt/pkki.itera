<?php

namespace App\Listeners;

use App\Events\SubmissionDocumentStatusChanged;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateSubmissionDocumentTracker
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SubmissionDocumentStatusChanged $event): void
    {
        $submissionDocument = $event->submissionDocument;
        $submission = $submissionDocument->submission;
        $document = $submissionDocument->document;

        if (!$submission || !$document) {
            return; // Can't proceed without valid relations
        }

        // Format status for better readability
        $oldStatusFormatted = ucfirst(str_replace('_', ' ', $event->oldStatus ?? 'none'));
        $newStatusFormatted = ucfirst(str_replace('_', ' ', $event->newStatus));

        // Get default tracking values
        $defaultValues = $this->getDefaultTrackingValues($submission, $document, $submissionDocument);

        // Create the tracking record with default values
        DB::table('tracking_histories')->insert(array_merge($defaultValues, [
            'event_type' => $this->determineEventType($event->newStatus),
            'status' => $event->newStatus,
            'comment' => "Document '{$document->title}' status changed from {$oldStatusFormatted} to {$newStatusFormatted}",
            'processed_by' => Auth::id() ?? $submission->user_id,
            'created_at' => now(),
            'updated_at' => now(),
            'metadata' => json_encode([
                'document_title' => $document->title,
                'document_type' => $document->mimetype,
                'document_size' => $document->size,
                'requirement_id' => $submissionDocument->requirement_id,
                'requirement_name' => $submissionDocument->requirement->name ?? 'Unknown',
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus,
                'notes' => $submissionDocument->notes,
            ]),
        ]));
    }

    /**
     * Get default tracking values based on the submission and document.
     * 
     * @param \App\Models\Submission $submission
     * @param \App\Models\Document $document
     * @param \App\Models\SubmissionDocument $submissionDocument
     * @return array
     */
    private function getDefaultTrackingValues($submission, $document, $submissionDocument): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'submission_id' => $submission->id,
            'stage_id' => $submission->current_stage_id,
            'action' => 'document_status_update',
        ];
    }

    /**
     * Determine the event type based on the new status.
     */
    private function determineEventType(string $status): string
    {
        return match ($status) {
            'approved' => 'document_approved',
            'rejected' => 'document_rejected',
            'revision_needed' => 'document_revision_needed',
            'replaced' => 'document_replaced',
            'pending' => 'document_pending',
            default => 'document_status_changed',
        };
    }
}
