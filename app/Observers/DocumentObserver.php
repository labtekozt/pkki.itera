<?php

namespace App\Observers;

use App\Events\DocumentStatusChanged;
use App\Models\Document;
use App\Services\TrackingHistoryService;
use Illuminate\Support\Facades\Auth;

class DocumentObserver
{
    /**
     * @var TrackingHistoryService
     */
    protected $trackingService;
    
    /**
     * Create a new observer instance.
     */
    public function __construct(TrackingHistoryService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Handle the Document "created" event.
     */
    public function created(Document $document): void
    {
        // Dispatch event for document creation with NULL as oldStatus
        event(new DocumentStatusChanged($document, null, 'created'));
    }

    /**
     * Handle the Document "updated" event.
     */
    public function updated(Document $document): void
    {
        // If there's a change in status field, trigger event
        if ($document->isDirty('status')) {
            $oldStatus = $document->getOriginal('status');
            $newStatus = $document->status;
            
            event(new DocumentStatusChanged($document, $oldStatus, $newStatus));
        }
    }

    /**
     * Handle the Document "deleted" event.
     */
    public function deleted(Document $document): void
    {
        // Dispatch event for document deletion
        event(new DocumentStatusChanged($document, $document->status, 'deleted'));
    }
}