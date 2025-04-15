<?php

namespace App\Listeners;

use App\Events\DocumentStatusChanged;
use App\Models\TrackingHistory;
use App\Services\TrackingHistoryService;
use Illuminate\Support\Facades\Auth;

class CreateDocumentStatusTracker
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
    public function handle(DocumentStatusChanged $event): void
    {
        // First, find all submission documents that use this document
        $submissionDocuments = $event->document->submissionDocuments;
        
        // Create tracking entries for each related submission
        foreach ($submissionDocuments as $submissionDocument) {
            $submission = $submissionDocument->submission;
            
            if (!$submission) {
                continue; // Skip if the submission doesn't exist
            }
            
            // Create a tracking record using the service
            $this->trackingService->createTrackingRecord([
                'submission_id' => $submission->id,
                'document_id' => $event->document->id,
                'stage_id' => $submission->current_stage_id,
                'event_type' => 'document_' . ($event->newStatus ?? 'changed'),
                'status' => $this->mapDocumentStatusToTrackingStatus($event->newStatus),
                'comment' => 'Document status change: ' . ($event->oldStatus ?? 'none') . ' -> ' . ($event->newStatus ?? 'new'),
                'processed_by' => Auth::id() ?? $submission->user_id,
                'source_status' => $event->oldStatus,
                'target_status' => $event->newStatus,
                'metadata' => [
                    'document_title' => $event->document->title,
                    'document_type' => $event->document->mimetype,
                    'old_status' => $event->oldStatus,
                    'new_status' => $event->newStatus,
                    'requirement_name' => $submissionDocument->requirement->name ?? 'Unknown',
                ],
            ]);
        }
    }
    
    /**
     * Map document status to valid tracking status.
     */
    private function mapDocumentStatusToTrackingStatus(?string $status): string
    {
        if (!$status) {
            return 'in_progress';
        }
        
        return match($status) {
            'approved' => 'approved',
            'rejected' => 'rejected',
            'revision_needed' => 'revision_needed',
            'deleted' => 'completed',
            'created' => 'started',
            default => 'in_progress',
        };
    }
}