<?php

namespace App\Filament\Resources\SubmissionReviewResource\Pages;

use App\Filament\Resources\SubmissionReviewResource;
use App\Models\Submission;
use App\Models\TrackingHistory;
use App\Models\User;
use App\Models\WorkflowAssignment;
use App\Models\WorkflowStage;
use App\Notifications\ReviewActionNotification;
use App\Notifications\RevisionRequestedNotification;
use App\Notifications\ReviewerAssignedNotification;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ReviewSubmission extends ViewRecord
{
    protected static string $resource = SubmissionReviewResource::class;
    
    protected static string $view = 'filament.resources.submission-review-resource.pages.review-submission';
    
    public $comments;
    public $reviewAction;
    public $reviewNotes;
    public $assignment;
    public $revisionCount;
    public $newReviewerId;
    
    public function mount(int|string $record): void
    {
        parent::mount($record);
        
        $user = auth()->user();
        $submission = $this->record;
        
        // Find the active assignment for this user and this submission stage
        $this->assignment = WorkflowAssignment::where('submission_id', $submission->id)
            ->where('stage_id', $submission->current_stage_id)
            ->where('reviewer_id', $user->id)
            ->whereNull('completed_at')
            ->first();
            
        // If no active assignment, redirect back with error unless the user is an admin
        if (!$this->assignment && !$user->hasRole(['admin', 'super_admin'])) {
            Notification::make()
                ->title('Not authorized to review this submission')
                ->danger()
                ->send();
                
            $this->redirect(SubmissionReviewResource::getUrl());
            return;
        }
        
        // Update assignment status if it's still pending
        if ($this->assignment && $this->assignment->status === 'pending') {
            $this->assignment->update(['status' => 'in_progress']);
        }
        
        // Count the number of revisions for this submission
        $this->revisionCount = TrackingHistory::where('submission_id', $submission->id)
            ->where('action', 'request_revision')
            ->count();
            
        // Initialize with any existing notes
        if ($this->assignment) {
            $this->reviewNotes = $this->assignment->notes;
        }
    }
    
    // Save reviewer notes
    public function saveNotes()
    {
        if ($this->assignment) {
            $this->assignment->update([
                'notes' => $this->reviewNotes
            ]);
            
            Notification::make()
                ->title('Notes saved successfully')
                ->success()
                ->send();
        }
    }
    
    // Assign a new reviewer to the current stage
    public function assignReviewer()
    {
        if (!$this->newReviewerId) {
            Notification::make()
                ->title('Please select a reviewer')
                ->warning()
                ->send();
            return;
        }
        
        $submission = $this->record;
        $currentStage = $submission->currentStage;
        
        if (!$currentStage) {
            Notification::make()
                ->title('Submission does not have a current stage')
                ->danger()
                ->send();
            return;
        }
        
        try {
            DB::beginTransaction();
            
            // Check if the reviewer is already assigned to this stage for this submission
            $existingAssignment = WorkflowAssignment::where('submission_id', $submission->id)
                ->where('stage_id', $currentStage->id)
                ->where('reviewer_id', $this->newReviewerId)
                ->whereNull('completed_at')
                ->first();
                
            if ($existingAssignment) {
                Notification::make()
                    ->title('Reviewer is already assigned to this stage')
                    ->warning()
                    ->send();
                    
                DB::rollBack();
                return;
            }
            
            // Create a new assignment
            $assignment = WorkflowAssignment::create([
                'submission_id' => $submission->id,
                'stage_id' => $currentStage->id,
                'reviewer_id' => $this->newReviewerId,
                'assigned_by' => auth()->id(),
                'status' => 'pending',
                'assigned_at' => now(),
            ]);
            
            // Create a tracking history entry
            TrackingHistory::create([
                'submission_id' => $submission->id,
                'stage_id' => $currentStage->id,
                'action' => 'assign_reviewer',
                'status' => $submission->status,
                'comment' => 'Reviewer assigned',
                'processed_by' => auth()->id(),
                'metadata' => [
                    'reviewer_id' => $this->newReviewerId,
                    'assignment_id' => $assignment->id,
                ],
            ]);
            
            // Send notification to the assigned reviewer
            $reviewer = User::find($this->newReviewerId);
            if ($reviewer) {
                $reviewer->notify(new ReviewerAssignedNotification($submission, $currentStage));
            }
            
            DB::commit();
            
            // Reset the form input
            $this->newReviewerId = null;
            
            Notification::make()
                ->title('Reviewer assigned successfully')
                ->success()
                ->send();
                
            // Refresh the page to show the new assignment
            $this->redirect(SubmissionReviewResource::getUrl('review', ['record' => $submission]));
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log the error
            logger()->error('Error assigning reviewer: ' . $e->getMessage(), [
                'submission_id' => $submission->id,
                'reviewer_id' => $this->newReviewerId,
                'exception' => $e,
            ]);
            
            Notification::make()
                ->title('Error assigning reviewer')
                ->body('An error occurred while assigning the reviewer. Please try again.')
                ->danger()
                ->send();
        }
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalHeading('Approve Submission')
                ->modalDescription('Are you sure you want to approve this submission? This will move it to the next stage in the workflow.')
                ->modalSubmitActionLabel('Yes, Approve')
                ->form([
                    Textarea::make('notes')
                        ->label('Approval Notes')
                        ->placeholder('Add any notes about your approval decision...')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $this->processReviewAction('approve', $data['notes'] ?? null);
                })
                ->visible(fn () => $this->canPerformAction()),
                
            Action::make('request_revision')
                ->label('Request Revision')
                ->color('warning')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalHeading('Request Revision')
                ->modalDescription('Please explain what revisions are needed for this submission.')
                ->modalSubmitActionLabel('Request Revision')
                ->form([
                    Textarea::make('notes')
                        ->label('Revision Notes')
                        ->placeholder('Explain what needs to be revised...')
                        ->required()
                        ->rows(5),
                ])
                ->action(function (array $data): void {
                    $this->processReviewAction('request_revision', $data['notes']);
                })
                ->visible(fn () => $this->canPerformAction() && $this->revisionCount < 3),
                
            Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->modalHeading('Reject Submission')
                ->modalDescription('Are you sure you want to reject this submission? This will end the review process.')
                ->modalSubmitActionLabel('Yes, Reject')
                ->form([
                    Textarea::make('notes')
                        ->label('Rejection Reason')
                        ->placeholder('Explain the reason for rejection...')
                        ->required()
                        ->rows(5),
                ])
                ->action(function (array $data): void {
                    $this->processReviewAction('reject', $data['notes']);
                })
                ->visible(fn () => $this->canPerformAction()),
                
            Action::make('admin_unlock')
                ->label('Override Revision Lock')
                ->color('danger')
                ->icon('heroicon-o-key')
                ->requiresConfirmation()
                ->modalHeading('Override Revision Lock')
                ->modalDescription('Allow more revisions for this submission.')
                ->action(function (): void {
                    // Reset the revision counter in metadata
                    $this->record->metadata = array_merge($this->record->metadata ?? [], ['revision_override' => true]);
                    $this->record->save();
                    
                    // Create a tracking entry for this override
                    TrackingHistory::create([
                        'submission_id' => $this->record->id,
                        'stage_id' => $this->record->current_stage_id,
                        'action' => 'admin_override',
                        'status' => $this->record->status,
                        'comment' => 'Admin override of revision limit',
                        'processed_by' => auth()->id(),
                        'metadata' => ['revision_limit_override' => true],
                    ]);
                    
                    $this->revisionCount = 0;
                    
                    Notification::make()
                        ->title('Revision lock overridden')
                        ->success()
                        ->send();
                })
                ->visible(fn () => 
                    auth()->user()->hasRole(['admin', 'super_admin']) && 
                    $this->revisionCount >= 3 && 
                    $this->record->status === 'revision_needed'
                ),
        ];
    }
    
    protected function canPerformAction(): bool
    {
        // Check if the user has an active assignment for this submission
        if (!$this->assignment && !auth()->user()->hasRole(['admin', 'super_admin'])) {
            return false;
        }
        
        // Check if the submission status allows actions
        return in_array($this->record->status, ['submitted', 'in_review', 'revision_needed']);
    }
    
    protected function processReviewAction(string $action, ?string $notes = null): void
    {
        $submission = $this->record;
        $user = auth()->user();
        
        DB::beginTransaction();
        
        try {
            // Update assignment status
            if ($this->assignment) {
                $this->assignment->complete($action, $notes);
            }
            
            // Create tracking history entry
            $trackingEntry = TrackingHistory::create([
                'submission_id' => $submission->id,
                'stage_id' => $submission->current_stage_id,
                'action' => $action,
                'status' => $this->getStatusFromAction($action),
                'comment' => $notes,
                'processed_by' => $user->id,
            ]);
            
            // Update submission status
            $newStatus = $this->getStatusFromAction($action);
            $submission->status = $newStatus;
            
            // If approved, try to advance to the next stage
            if ($action === 'approve') {
                $this->advanceToNextStage($submission);
            }
            
            $submission->save();
            
            // Send notifications to relevant users
            $this->sendNotifications($action, $notes);
            
            DB::commit();
            
            // Show success notification
            Notification::make()
                ->title('Review action completed')
                ->success()
                ->send();
                
            // Redirect back to the reviews list
            $this->redirect(SubmissionReviewResource::getUrl());
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log the error
            logger()->error('Error processing review action: ' . $e->getMessage());
            
            // Show error notification
            Notification::make()
                ->title('Error processing review')
                ->body('An error occurred while processing your review. Please try again.')
                ->danger()
                ->send();
        }
    }
    
    protected function getStatusFromAction(string $action): string
    {
        return match ($action) {
            'approve' => 'approved',
            'reject' => 'rejected',
            'request_revision' => 'revision_needed',
            default => 'in_review',
        };
    }
    
    protected function advanceToNextStage(Submission $submission): void
    {
        // Get current stage
        $currentStage = WorkflowStage::find($submission->current_stage_id);
        
        if (!$currentStage) {
            return;
        }
        
        // Check if there's a next stage
        $nextStage = WorkflowStage::where('submission_type_id', $submission->submission_type_id)
            ->where('order', '>', $currentStage->order)
            ->orderBy('order')
            ->first();
            
        if ($nextStage) {
            // Create transition tracking entry
            TrackingHistory::createTransition(
                $submission,
                $currentStage,
                $nextStage,
                'advance_stage',
                'in_review',
                'Advanced to next stage',
                auth()->user()
            );
            
            // Update submission with new stage
            $submission->current_stage_id = $nextStage->id;
            $submission->status = 'in_review';
        } else {
            // This was the last stage, mark submission as completed
            $submission->status = 'completed';
            
            // Create completed tracking entry
            TrackingHistory::create([
                'submission_id' => $submission->id,
                'stage_id' => $submission->current_stage_id,
                'action' => 'complete',
                'status' => 'completed',
                'comment' => 'Submission review completed',
                'processed_by' => auth()->id(),
            ]);
        }
    }
    
    protected function sendNotifications(string $action, ?string $notes): void
    {
        $submission = $this->record;
        
        // Notify the submitter
        $submitter = $submission->user;
        
        if ($submitter) {
            if ($action === 'request_revision') {
                $submitter->notify(new RevisionRequestedNotification($submission, $notes));
            } else {
                $submitter->notify(new ReviewActionNotification($submission, $action, $notes));
            }
        }
        
        // If approved, notify new stage reviewers
        if ($action === 'approve' && $submission->status === 'in_review') {
            // Get reviewers for the new stage
            $newStageReviewers = WorkflowAssignment::where('submission_id', $submission->id)
                ->where('stage_id', $submission->current_stage_id)
                ->whereNull('completed_at')
                ->with('reviewer')
                ->get();
                
            foreach ($newStageReviewers as $assignment) {
                $reviewer = $assignment->reviewer;
                if ($reviewer) {
                    $reviewer->notify(new ReviewActionNotification(
                        $submission, 
                        'assigned', 
                        'You have been assigned to review this submission'
                    ));
                }
            }
        }
    }
}