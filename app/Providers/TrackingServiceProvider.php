<?php

namespace App\Providers;

use App\Models\Submission;
use App\Models\SubmissionDocument;
use App\Observers\SubmissionObserver;
use App\Observers\SubmissionDocumentObserver;
use App\Services\WorkflowService;
use Illuminate\Support\ServiceProvider;

class TrackingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\TrackingService::class, function ($app) {
            return new \App\Services\TrackingService();
        });
        
        $this->app->singleton(WorkflowService::class, function ($app) {
            return new WorkflowService(
                $app->make(\App\Services\TrackingService::class),
                $app->make(\App\Services\SubmissionService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Submission::observe(SubmissionObserver::class);
        SubmissionDocument::observe(SubmissionDocumentObserver::class);
    }
}
