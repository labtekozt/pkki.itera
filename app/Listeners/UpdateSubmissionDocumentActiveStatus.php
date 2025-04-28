<?php

namespace App\Listeners;

use App\Events\SubmissionDocumentStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateSubmissionDocumentActiveStatus implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param  \App\Events\SubmissionDocumentStatusChanged  $event
     * @return void
     */
    public function handle(SubmissionDocumentStatusChanged $event): void
    {
        $document = $event->submissionDocument;
        $currentStatus = $document->status;

        // Determine if the document should be active based on its status
        $shouldBeActive = !in_array($currentStatus, ['rejected', 'replaced']);

        // Only update if needed
        if ($document->is_active !== $shouldBeActive) {
            $document->update([
                'is_active' => $shouldBeActive,
            ]);
        }
    }
}