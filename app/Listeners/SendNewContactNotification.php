<?php

namespace App\Listeners;

use App\Events\ContactUsCreated;
use App\Mail\NewContactNotificationMail;
use App\Settings\MailSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNewContactNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @var MailSettings
     */
    protected $mailSettings;

    /**
     * Create the event listener.
     */
    public function __construct(MailSettings $mailSettings)
    {
        $this->mailSettings = $mailSettings;
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\ContactUsCreated  $event
     * @return void
     */
    public function handle(ContactUsCreated $event)
    {
        $contact = $event->contact;
        
        if (!$this->mailSettings->isMailSettingsConfigured()) {
            Log::warning('Mail settings not configured. Cannot send contact notification.');
            return;
        }

        // Load mail settings from configuration
        $this->mailSettings->loadMailSettingsToConfig();

        // Get notification emails from settings with fallback
        $notificationEmails = $this->mailSettings->contact_notification_email 
            ? explode(',', $this->mailSettings->contact_notification_email)
            : ["info@pkki-itera.com"];

        try {
            Mail::to($notificationEmails)
                ->send(new NewContactNotificationMail($contact));
            
            // Log successful email sending
            Log::info('Contact notification email sent', [
                'contact_id' => $contact->id,
                'contact_email' => $contact->email,
                'notification_emails' => $notificationEmails,
            ]);
        } catch (\Exception $e) {
            // Log error if email sending fails
            Log::error('Failed to send contact notification email', [
                'contact_id' => $contact->id,
                'error' => $e->getMessage(),
            ]);
            
            $this->fail($e);
        }
    }
}
