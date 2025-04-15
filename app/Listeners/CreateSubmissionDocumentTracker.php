<?php

namespace App\Listeners;

use App\Events\SubmissionDocumentStatusChanged;
use App\Services\TrackingHistoryService;
use Illuminate\Support\Facades\Auth;

class CreateSubmissionDocumentTracker
{
    /**
     * @var TrackingHistoryService
     */
    protected $trackingService;

    /**
     * Create the event listener.
     */
    public function __construct(TrackingHistoryService $trackingService)
    {
        $this->trackingService = $trackingService;
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
        
        // Create the tracking record
        $this->trackingService->createTrackingRecord([
            'submission_id' => $submission->id,
            'document_id' => $document->id,
            'stage_id' => $submission->current_stage_id,
            'event_type' => $this->determineEventType($event->newStatus),
            'status' => $this->mapStatusToTrackingStatus($event->newStatus),
            'comment' => "Document '{$document->title}' status changed from {$oldStatusFormatted} to {$newStatusFormatted}",
            'processed_by' => Auth::id() ?? $submission->user_id,
            'source_status' => $event->oldStatus,
            'target_status' => $event->newStatus,
            'metadata' => [
                'document_title' => $document->title,
                'document_type' => $document->mimetype,
                'document_size' => $document->size,
                'requirement_id' => $submissionDocument->requirement_id,
                'requirement_name' => $submissionDocument->requirement->name ?? 'Unknown',
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus,
                'notes' => $submissionDocument->notes,
            ],
        ]);
    }
    
    /**
     * Determine the event type based on the new status.
     */
    private function determineEventType(string $status): string
    {
        return match($status) {
            'approved' => 'document_approved',
            'rejected' => 'document_rejected',
            'revision_needed' => 'document_revision_needed',
            'replaced' => 'document_replaced',
            'pending' => 'document_pending',
            default => 'document_status_changed',
        };
    }
    
    /**
     * Map document status to tracking status.
     */
    private function mapStatusToTrackingStatus(string $status): string
    {
        return match($status) {
            'approved' => 'approved',
            'rejected' => 'rejected',
            'revision_needed' => 'revision_needed',
            'submitted' => 'in_progress',
            'pending' => 'in_progress',
            'replaced' => 'in_progress',
            default => 'in_progress',
        };
    }
}