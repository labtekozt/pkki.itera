<?php

namespace App\Listeners;

use App\Events\SubmissionStatusChanged;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateSubmissionStatusTracker
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
    public function handle(SubmissionStatusChanged $event): void
    {
        $submission = $event->submission;
        
        // Format statuses for better readability
        $oldStatusFormatted = ucfirst(str_replace('_', ' ', $event->oldStatus ?? 'none'));
        $newStatusFormatted = ucfirst(str_replace('_', ' ', $event->newStatus));
        
        // Determine the event type based on status transition
        $eventType = $this->determineEventType($event->oldStatus, $event->newStatus);
        
        // Create the tracking record
        DB::table('tracking_history')->insert([
            'id' => Str::uuid()->toString(),
            'submission_id' => $submission->id,
            'stage_id' => $submission->current_stage_id,
            'event_type' => $eventType,
            'status' => $event->newStatus,
            'comment' => "Submission status changed from {$oldStatusFormatted} to {$newStatusFormatted}",
            'processed_by' => Auth::id() ?? $submission->user_id,
            'created_at' => now(),
            'updated_at' => now(),
            'source_status' => $event->oldStatus,
            'target_status' => $event->newStatus,
            'metadata' => json_encode([
                'submission_title' => $submission->title,
                'submission_type' => $submission->submissionType->name ?? 'Unknown',
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus,
                'stage_name' => $submission->currentStage->name ?? 'No stage',
            ]),
        ]);
    }
    
    /**
     * Determine the event type based on the status transition.
     */
    private function determineEventType(?string $oldStatus, string $newStatus): string
    {
        // Special cases for specific transitions
        if ($oldStatus === 'draft' && $newStatus === 'submitted') {
            return 'submission_submitted';
        }
        
        if ($newStatus === 'completed') {
            return 'submission_completed';
        }
        
        if ($newStatus === 'rejected') {
            return 'submission_rejected';
        }
        
        if ($newStatus === 'revision_needed') {
            return 'submission_revision_requested';
        }
        
        if ($oldStatus === 'revision_needed' && $newStatus === 'in_review') {
            return 'submission_revision_submitted';
        }
        
        // Default case
        return 'status_change';
    }
}