<?php

namespace App\Services;

use App\Events\SubmissionStateChanged;
use App\Models\Submission;
use App\Models\TrackingHistory;
use App\Models\User;
use App\Models\WorkflowStage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkflowService
{
    protected TrackingService $trackingService;
    protected SubmissionService $submissionService;
    
    public function __construct(
        TrackingService $trackingService,
        SubmissionService $submissionService
    ) {
        $this->trackingService = $trackingService;
        $this->submissionService = $submissionService;
    }
    
    /**
     * Process a workflow action on a submission
     *
     * @param Submission $submission
     * @param string $action
     * @param array $options
     * @return Submission
     */
    public function processAction(Submission $submission, string $action, array $options = []): Submission
    {
        return match($action) {
            'approve' => $this->approveSubmission($submission, $options),
            'reject' => $this->rejectSubmission($submission, $options),
            'request_revision' => $this->requestRevision($submission, $options),
            'submit_revision' => $this->submitRevision($submission, $options),
            'advance_stage' => $this->advanceStage($submission, $options),
            'return_stage' => $this->returnToPreviousStage($submission, $options),
            'complete' => $this->completeSubmission($submission, $options),
            default => throw new \InvalidArgumentException("Unknown action: {$action}")
        };
    }
    
    /**
     * Approve current stage of a submission and advance if possible
     */
    public function approveSubmission(Submission $submission, array $options = []): Submission
    {
        $comment = $options['comment'] ?? 'Submission approved';
        $processor = $options['processor'] ?? Auth::user();
        
        return DB::transaction(function() use ($submission, $comment, $processor) {
            // First mark the current stage documents as approved if needed
            if (!empty($options['documents'])) {
                foreach ($options['documents'] as $documentId) {
                    $this->submissionService->updateDocument($documentId, [
                        'status' => 'approved',
                        'notes' => $comment,
                    ]);
                }
            }
            
            // Check if we can advance to the next stage
            if ($submission->canAdvanceToNextStage()) {
                return $this->trackingService->advanceToNextStage(
                    $submission,
                    $processor,
                    $comment
                );
            }
            
            // Otherwise just approve the current stage
            $this->trackingService->trackStateChange(
                $submission,
                'approve',
                'approved',
                [
                    'comment' => $comment,
                    'processor' => $processor,
                ]
            );
            
            // Update submission status
            $submission->status = 'in_review';
            $submission->save();
            
            return $submission->fresh();
        });
    }
    
    /**
     * Reject a submission
     */
    public function rejectSubmission(Submission $submission, array $options = []): Submission
    {
        $comment = $options['comment'] ?? 'Submission rejected';
        $processor = $options['processor'] ?? Auth::user();
        $reason = $options['reason'] ?? 'Does not meet requirements';
        
        return $this->trackingService->rejectSubmission(
            $submission,
            $processor,
            $comment,
            ['reason' => $reason]
        );
    }
    
    /**
     * Request revision for a submission
     */
    public function requestRevision(Submission $submission, array $options = []): Submission
    {
        $comment = $options['comment'] ?? 'Revision needed';
        $processor = $options['processor'] ?? Auth::user();
        
        return $this->trackingService->requestRevisions(
            $submission,
            $processor,
            $comment,
            $options
        );
    }
    
    /**
     * Submit revision for a submission
     */
    public function submitRevision(Submission $submission, array $options = []): Submission
    {
        $comment = $options['comment'] ?? 'Revision submitted';
        $processor = $options['processor'] ?? Auth::user();
        
        return $this->trackingService->submitRevisions(
            $submission,
            $processor,
            $comment,
            $options
        );
    }
    
    /**
     * Advance a submission to the next stage
     */
    public function advanceStage(Submission $submission, array $options = []): Submission
    {
        $comment = $options['comment'] ?? 'Advanced to next stage';
        $processor = $options['processor'] ?? Auth::user();
        
        return $this->trackingService->advanceToNextStage(
            $submission,
            $processor,
            $comment,
            $options
        );
    }
    
    /**
     * Return a submission to the previous stage
     */
    public function returnToPreviousStage(Submission $submission, array $options = []): Submission
    {
        $comment = $options['comment'] ?? 'Returned to previous stage';
        $processor = $options['processor'] ?? Auth::user();
        
        return $this->trackingService->returnToPreviousStage(
            $submission,
            $processor,
            $comment,
            $options
        );
    }
    
    /**
     * Complete a submission (final approval)
     */
    public function completeSubmission(Submission $submission, array $options = []): Submission
    {
        $comment = $options['comment'] ?? 'Submission completed';
        $processor = $options['processor'] ?? Auth::user();
        
        return $this->trackingService->completeSubmission(
            $submission,
            $processor,
            $comment,
            $options
        );
    }
    
    /**
     * Get workflow statistics for a submission
     */
    public function getWorkflowStatistics(Submission $submission): array
    {
        $tracking = $submission->trackingHistory()->get();
        
        $stats = [
            'total_days' => $submission->created_at,
            'stages_completed' => 0,
            'current_stage_days' => 0,
            'documents_approved' => $submission->submissionDocuments()->where('status', 'approved')->count(),
            'documents_total' => $submission->submissionDocuments()->count(),
            'revisions_requested' => $tracking->where('status', 'revision_needed')->count(),
            'total_actions' => $tracking->count(),
        ];
        
        if ($submission->currentStage) {
            $stageStart = $tracking
                ->where('stage_id', $submission->current_stage_id)
                ->sortBy('created_at')
                ->first()?->created_at ?? $submission->updated_at;
                
            $stats['current_stage_days'] = $stageStart->diffInDays(now());
            $stats['stages_completed'] = $submission->submissionType->workflowStages()
                ->where('order', '<', $submission->currentStage->order)
                ->count();
        }
        
        return $stats;
    }
    
    /**
     * Get the next available actions for this submission based on its current state
     */
    public function getAvailableActions(Submission $submission): array
    {
        $actions = [];
        
        // Basic actions based on status
        switch($submission->status) {
            case 'draft':
                $actions[] = [
                    'id' => 'submit',
                    'label' => 'Submit',
                    'description' => 'Submit for review',
                    'color' => 'primary',
                    'icon' => 'heroicon-o-paper-airplane',
                ];
                break;
                
            case 'submitted':
            case 'in_review':
                // Admin actions
                if (Auth::user() && Auth::user()->can('review_submissions')) {
                    $actions[] = [
                        'id' => 'approve',
                        'label' => 'Approve',
                        'description' => 'Approve current stage',
                        'color' => 'success',
                        'icon' => 'heroicon-o-check',
                    ];
                    
                    $actions[] = [
                        'id' => 'request_revision',
                        'label' => 'Request Revision',
                        'description' => 'Request changes',
                        'color' => 'warning',
                        'icon' => 'heroicon-o-pencil',
                    ];
                    
                    $actions[] = [
                        'id' => 'reject',
                        'label' => 'Reject',
                        'description' => 'Reject submission',
                        'color' => 'danger',
                        'icon' => 'heroicon-o-x-mark',
                    ];
                    
                    // Stage navigation
                    if ($submission->currentStage && !$submission->currentStage->isInitialStage()) {
                        $actions[] = [
                            'id' => 'return_stage',
                            'label' => 'Return to Previous Stage',
                            'description' => 'Send back to previous workflow stage',
                            'color' => 'gray',
                            'icon' => 'heroicon-o-arrow-left',
                        ];
                    }
                    
                    if ($submission->canAdvanceToNextStage()) {
                        $actions[] = [
                            'id' => 'advance_stage',
                            'label' => 'Advance Stage',
                            'description' => 'Move to next workflow stage',
                            'color' => 'primary',
                            'icon' => 'heroicon-o-arrow-right',
                        ];
                    }
                    
                    if ($submission->currentStage && $submission->currentStage->isFinalStage()) {
                        $actions[] = [
                            'id' => 'complete',
                            'label' => 'Complete Submission',
                            'description' => 'Finalize and generate certificate',
                            'color' => 'success',
                            'icon' => 'heroicon-o-check-badge',
                        ];
                    }
                }
                break;
                
            case 'revision_needed':
                // User actions
                $actions[] = [
                    'id' => 'submit_revision',
                    'label' => 'Submit Revision',
                    'description' => 'Submit requested changes',
                    'color' => 'primary',
                    'icon' => 'heroicon-o-paper-airplane',
                ];
                break;
        }
        
        return $actions;
    }
}
