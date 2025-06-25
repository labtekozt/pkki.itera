<?php

namespace App\Listeners;

use App\Settings\MailSettings;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;

class LoadMailSettingsListener
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
    public function handle(MessageSending $event): void
    {
        try {
            // Load mail settings from database before sending any email
            $mailSettings = app(MailSettings::class);
            
            if ($mailSettings->isMailSettingsConfigured()) {
                $mailSettings->loadMailSettingsToConfig();
                
                Log::info('Mail settings loaded from database for email sending', [
                    'to' => $event->message->getTo(),
                    'subject' => $event->message->getSubject(),
                ]);
            }
        } catch (\Exception $e) {
            // Log the error but don't prevent email sending
            Log::warning('Failed to load mail settings from database before sending email: ' . $e->getMessage());
        }
    }
}
