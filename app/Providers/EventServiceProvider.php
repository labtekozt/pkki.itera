<?php

namespace App\Providers;

use App\Events\DocumentStatusChanged;
use App\Events\SubmissionDocumentStatusChanged;
use App\Events\SubmissionStateChanged;
use App\Events\SubmissionStatusChanged;
use App\Events\WorkflowStageChanged;
use App\Listeners\CreateDocumentStatusTracker;
use App\Listeners\CreateSubmissionDocumentTracker;
use App\Listeners\CreateSubmissionStateTracker;
use App\Listeners\CreateSubmissionStatusTracker;
use App\Listeners\CreateWorkflowStageTracker;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        
        // Mail Events
        \Illuminate\Mail\Events\MessageSending::class => [
            \App\Listeners\LoadMailSettingsListener::class,
        ],
        
        // Document Status Events
        DocumentStatusChanged::class => [
            CreateDocumentStatusTracker::class,
        ],
        
        // Submission Document Status Events
        SubmissionDocumentStatusChanged::class => [
            CreateSubmissionDocumentTracker::class,
            \App\Listeners\UpdateSubmissionDocumentActiveStatus::class,
        ],
        
        // Submission Status Events
        SubmissionStatusChanged::class => [
            CreateSubmissionStatusTracker::class,
        ],
        
        // Submission State Changed Events
        SubmissionStateChanged::class => [
            CreateSubmissionStateTracker::class,
        ],
        
        // Workflow Stage Events
        WorkflowStageChanged::class => [
            CreateWorkflowStageTracker::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Register model observers
        \App\Models\Document::observe(\App\Observers\DocumentObserver::class);
        \App\Models\SubmissionDocument::observe(\App\Observers\SubmissionDocumentObserver::class);
        \App\Models\Submission::observe(\App\Observers\SubmissionObserver::class);
        \App\Models\WorkflowStage::observe(\App\Observers\WorkflowStageObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
