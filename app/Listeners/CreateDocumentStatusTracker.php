<?php

namespace App\Listeners;

use App\Events\DocumentStatusChanged;
use App\Models\TrackingHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateDocumentStatusTracker
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
            
            // Format statuses for better readability
            $oldStatusFormatted = ucfirst(str_replace('_', ' ', $event->oldStatus ?? 'none'));
            $newStatusFormatted = ucfirst(str_replace('_', ' ', $event->newStatus ?? 'new'));
            
            // Get default tracking values
            $defaultValues = $this->getDefaultTrackingValues($event->document, $submissionDocument, $submission);
            
            // Create a database tracking record
            DB::table('tracking_histories')->insert(array_merge($defaultValues, [
                'id' => Str::uuid()->toString(),
                'submission_id' => $submission->id,
                'document_id' => $event->document->id,
                'stage_id' => $submission->current_stage_id,
                'event_type' => 'document_' . ($event->newStatus ?? 'changed'),
                'status' => $event->newStatus ?? 'changed',
                'comment' => "Document status change: {$oldStatusFormatted} -> {$newStatusFormatted}",
                'processed_by' => Auth::id() ?? $submission->user_id,
                'created_at' => now(),
                'updated_at' => now(),
                'metadata' => json_encode([
                    'document_title' => $event->document->title,
                    'document_type' => $event->document->mimetype,
                    'old_status' => $event->oldStatus,
                    'new_status' => $event->newStatus,
                    'requirement_name' => $submissionDocument->requirement->name ?? 'Unknown',
                ]),
            ]));
        }
    }
    
    /**
     * Get default tracking values based on the document and submission.
     * 
     * @param \App\Models\Document $document
     * @param \App\Models\SubmissionDocument $submissionDocument
     * @param \App\Models\Submission $submission
     * @return array
     */
    private function getDefaultTrackingValues($document, $submissionDocument, $submission): array
    {
        return [
            'action' => 'document_update',
        ];
    }
}