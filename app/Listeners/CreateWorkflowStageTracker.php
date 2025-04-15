<?php

namespace App\Listeners;

use App\Events\WorkflowStageChanged;
use App\Services\TrackingHistoryService;
use Illuminate\Support\Facades\Auth;

class CreateWorkflowStageTracker
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
    public function handle(WorkflowStageChanged $event): void
    {
        $workflowStage = $event->workflowStage;
        $changeType = $event->changeType;
        $metadata = $event->metadata;
        
        // Get affected submissions (those currently at this stage)
        $affectedSubmissions = $workflowStage->currentSubmissions;
        
        // Create tracking entry for each affected submission
        foreach ($affectedSubmissions as $submission) {
            $this->trackingService->createTrackingRecord([
                'submission_id' => $submission->id,
                'stage_id' => $workflowStage->id,
                'event_type' => 'workflow_stage_' . $changeType,
                'status' => $submission->status,
                'comment' => $this->generateChangeComment($changeType, $workflowStage, $metadata),
                'processed_by' => Auth::id() ?? $metadata['updated_by'] ?? null,
                'metadata' => array_merge($metadata, [
                    'stage_id' => $workflowStage->id,
                    'stage_name' => $workflowStage->name,
                    'submission_type' => $workflowStage->submissionType->name ?? 'Unknown',
                    'submission_title' => $submission->title,
                ]),
            ]);
        }
    }
    
    /**
     * Generate a human-readable comment for the workflow stage change.
     */
    private function generateChangeComment(string $changeType, $workflowStage, array $metadata): string
    {
        switch ($changeType) {
            case 'created':
                return "New workflow stage '{$workflowStage->name}' was created.";
            
            case 'updated':
                if (isset($metadata['description']) && !empty($metadata['description'])) {
                    return "Workflow stage '{$workflowStage->name}' was updated: {$metadata['description']}";
                }
                return "Workflow stage '{$workflowStage->name}' was updated.";
            
            case 'deleted':
                return "Workflow stage '{$workflowStage->name}' was deleted.";
                
            default:
                return "Workflow stage '{$workflowStage->name}' {$changeType}.";
        }
    }
}