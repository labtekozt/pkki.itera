<?php

namespace App\Events;

use App\Models\SubmissionDocument;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubmissionDocumentStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The submission document instance.
     */
    public SubmissionDocument $submissionDocument;

    /**
     * The old status.
     */
    public ?string $oldStatus;

    /**
     * The new status.
     */
    public string $newStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(SubmissionDocument $submissionDocument, ?string $oldStatus, string $newStatus)
    {
        $this->submissionDocument = $submissionDocument;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }
}