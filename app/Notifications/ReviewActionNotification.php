<?php

namespace App\Notifications;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewActionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Submission $submission;
    protected string $action;
    protected ?string $notes;

    /**
     * Create a new notification instance.
     */
    public function __construct(Submission $submission, string $action, ?string $notes = null)
    {
        $this->submission = $submission;
        $this->action = $action;
        $this->notes = $notes;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->subject("Submission Update: {$this->submission->title}")
            ->greeting("Hello {$notifiable->fullname}");
            
        switch ($this->action) {
            case 'approve':
                $mailMessage->line('Your submission has been approved!')
                    ->line('It has been moved to the next stage in the review process.');
                break;
            case 'reject':
                $mailMessage->line('Your submission has been rejected.')
                    ->line('Please see the reviewer comments for more information.');
                break;
            case 'assigned':
                $mailMessage->line('You have been assigned to review a submission.')
                    ->line('Please log in to the system to review it.');
                break;
            default:
                $mailMessage->line("Your submission '{$this->submission->title}' has been updated.");
                break;
        }
        
        if ($this->notes) {
            $mailMessage->line('Reviewer Notes:')
                ->line($this->notes);
        }
        
        return $mailMessage
            ->action('View Submission', url("/submissions/{$this->submission->id}"))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'submission_id' => $this->submission->id,
            'title' => $this->submission->title,
            'action' => $this->action,
            'notes' => $this->notes,
        ];
    }
}