<?php

namespace App\Services;

use App\Models\TrackingHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TrackingHistoryService
{
    /**
     * Create a new tracking history record.
     *
     * @param array $data
     * @return TrackingHistory
     */
    public function createTrackingRecord(array $data): TrackingHistory
    {
        // Set default values for required fields
        $data['id'] = $data['id'] ?? Str::uuid()->toString();
        $data['processed_by'] = $data['processed_by'] ?? Auth::id();
        $data['action'] = $data['action'] ?? $this->determineAction($data['event_type'] ?? '');
        
        // Validate status to ensure it matches the enum values in the database
        $data['status'] = $this->validateStatus($data['status'] ?? 'in_progress');
        
        // Create and return the tracking record
        return TrackingHistory::create($data);
    }
    
    /**
     * Determine the action based on the event type.
     *
     * @param string $eventType
     * @return string
     */
    protected function determineAction(string $eventType): string
    {
        // Map event types to appropriate actions
        return match (true) {
            str_contains($eventType, 'created') => 'create',
            str_contains($eventType, 'updated') => 'update',
            str_contains($eventType, 'deleted') => 'delete',
            str_contains($eventType, 'approved') => 'approve',
            str_contains($eventType, 'rejected') => 'reject',
            str_contains($eventType, 'revision') => 'revision',
            str_contains($eventType, 'submitted') => 'submit',
            str_contains($eventType, 'stage_transition') => 'transition',
            str_contains($eventType, 'status_change') => 'status_change',
            default => 'update',
        };
    }
    
    /**
     * Validate the status to ensure it matches allowed values.
     *
     * @param string $status
     * @return string
     */
    protected function validateStatus(string $status): string
    {
        // List of valid statuses from the database enum
        $validStatuses = [
            'started', 'in_progress', 'approved', 'rejected', 
            'revision_needed', 'objection', 'completed'
        ];
        
        return in_array($status, $validStatuses) ? $status : 'in_progress';
    }
}