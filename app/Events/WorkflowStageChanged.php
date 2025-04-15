<?php

namespace App\Events;

use App\Models\WorkflowStage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowStageChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The workflow stage instance.
     */
    public WorkflowStage $workflowStage;

    /**
     * The change type (created, updated, etc.)
     */
    public string $changeType;

    /**
     * Any additional metadata about the change.
     */
    public array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(WorkflowStage $workflowStage, string $changeType, array $metadata = [])
    {
        $this->workflowStage = $workflowStage;
        $this->changeType = $changeType;
        $this->metadata = $metadata;
    }
}