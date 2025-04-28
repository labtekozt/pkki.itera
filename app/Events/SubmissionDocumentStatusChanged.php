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
     *
     * @var \App\Models\SubmissionDocument
     */
    public $submissionDocument;

    /**
     * The old status before the update.
     *
     * @var string|null
     */
    public $oldStatus;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(SubmissionDocument $submissionDocument, ?string $oldStatus = null)
    {
        $this->submissionDocument = $submissionDocument;
        $this->oldStatus = $oldStatus;
    }
}