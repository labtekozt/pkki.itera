<?php

namespace App\Events;

use App\Models\Document;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The document instance.
     */
    public Document $document;

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
    public function __construct(Document $document, ?string $oldStatus, string $newStatus)
    {
        $this->document = $document;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }
}