<?php

namespace App\Listeners;

use App\Events\SubmissionStateChanged;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateSubmissionStateTracker
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
    public function handle(SubmissionStateChanged $event): void
    {
        $submission = $event->submission;
        $trackingEntry = $event->trackingEntry;
        
        // For submission state changes, we already have a tracking entry created
        // by the TrackingService. We'll create an additional record only if we need
        // more specific tracking or auditing.
        
        // Only create additional tracking record for certain significant events
        if (in_array($event->action, ['approve', 'reject', 'advance_stage', 'return_stage', 'complete'])) {
            DB::table('tracking_history')->insert([
                'id' => Str::uuid()->toString(),
                'submission_id' => $submission->id,
                'stage_id' => $submission->current_stage_id,
                'event_type' => 'state_change_' . $event->action,
                'status' => $event->status,
                'comment' => "State change action: {$event->action} - " . $trackingEntry->comment,
                'processed_by' => Auth::id() ?? $submission->user_id,
                'created_at' => now(),
                'updated_at' => now(),
                'source_status' => $trackingEntry->source_status ?? null,
                'target_status' => $trackingEntry->target_status ?? $event->status,
                'metadata' => json_encode([
                    'submission_title' => $submission->title,
                    'submission_type' => $submission->submissionType->name ?? 'Unknown',
                    'action' => $event->action,
                    'status' => $event->status,
                    'original_tracking_id' => $trackingEntry->id,
                    'stage_name' => $submission->currentStage->name ?? 'No stage',
                ]),
            ]);
        }
    }
}