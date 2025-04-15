<?php

namespace App\Examples;

use App\Events\DocumentStatusChanged;
use App\Events\SubmissionDocumentStatusChanged;
use App\Events\SubmissionStateChanged;
use App\Events\SubmissionStatusChanged;
use App\Events\WorkflowStageChanged;
use App\Models\Document;
use App\Models\Submission;
use App\Models\SubmissionDocument;
use App\Models\TrackingHistory;
use App\Models\WorkflowStage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;

/**
 * Examples of event-driven workflow in the application.
 * This class demonstrates how events work in the existing codebase.
 */
class EventDrivenExamples
{
    /**
     * Example 1: When a document status changes
     */
    public function documentStatusChangeFlow()
    {
        // Let's say an admin approves a document
        $document = Document::find('some-uuid');
        $oldStatus = $document->status;
        $document->status = 'approved';
        $document->save();
        
        // DocumentObserver in app/Observers/DocumentObserver.php detects the status change
        // and dispatches the DocumentStatusChanged event:
        
        // This happens automatically:
        // event(new DocumentStatusChanged($document, $oldStatus, 'approved'));
        
        // Then the CreateDocumentStatusTracker listener (app/Listeners/CreateDocumentStatusTracker.php) 
        // handles this event and creates tracking records for all related submissions
    }
    
    /**
     * Example 2: When a submission document's status changes through the UI
     */
    public function submissionDocumentApprovalFlow()
    {
        // In the DocumentsRelationManager when an admin reviews a document:
        $submissionDocument = SubmissionDocument::find('some-uuid');
        $oldStatus = $submissionDocument->status;
        
        // Admin approves the document
        $submissionDocument->status = 'approved';
        $submissionDocument->notes = 'Document approved after review';
        $submissionDocument->save();
        
        // The SubmissionDocumentObserver in app/Observers/SubmissionDocumentObserver.php 
        // detects the status change and:
        // 1. Creates a tracking entry directly
        // 2. Dispatches the SubmissionDocumentStatusChanged event:
        
        // This happens automatically:
        // event(new SubmissionDocumentStatusChanged($submissionDocument, $oldStatus, 'approved'));
        
        // Then the CreateSubmissionDocumentTracker listener handles this event and creates
        // detailed tracking records with metadata
    }
    
    /**
     * Example 3: When a submission advances to the next workflow stage
     */
    public function submissionWorkflowAdvanceFlow()
    {
        // In a controller or Filament action when advancing a submission to next stage:
        $submission = Submission::find('some-uuid');
        
        // This calls your workflow service
        $workflowService = app(\App\Services\WorkflowService::class);
        $workflowService->processAction($submission, 'advance_stage', [
            'comment' => 'Ready for next stage review',
        ]);
        
        // Inside WorkflowService, it calls TrackingService::advanceToNextStage
        // which:
        // 1. Updates the submission's stage and status
        // 2. Creates a tracking entry
        // 3. Fires the SubmissionStateChanged event:
        
        // $trackingEntry = TrackingHistory::create([/* ... */]);
        // event(new SubmissionStateChanged($submission, 'advance_stage', 'in_review', $trackingEntry));
        
        // Then the CreateSubmissionStateTracker listener receives this event and 
        // may create additional tracking entries
    }
    
    /**
     * Example 4: When a workflow stage itself changes
     */
    public function workflowStageUpdateFlow()
    {
        // When an admin updates a workflow stage in the admin panel:
        $workflowStage = WorkflowStage::find('some-uuid');
        $workflowStage->name = 'Updated Stage Name';
        $workflowStage->save();
        
        // The WorkflowStageObserver in app/Observers/WorkflowStageObserver.php
        // detects the change and:
        // 1. Records what changed
        // 2. Dispatches the WorkflowStageChanged event:
        
        // event(new WorkflowStageChanged($workflowStage, 'updated', [
        //     'changes' => ['name' => ['old' => 'Old Name', 'new' => 'Updated Stage Name']],
        //     'updated_by' => Auth::id(),
        // ]));
        
        // Then the CreateWorkflowStageTracker listener handles this event,
        // finds all submissions affected by this stage change, and creates
        // tracking entries for each one
    }
    
    /**
     * Example 5: Real use case - a complete document approval flow
     */
    public function completeDocumentApprovalFlow()
    {
        // Step 1: User uploads a document for a submission
        $submission = Submission::find('some-uuid');
        $documentRequirement = \App\Models\DocumentRequirement::find('requirement-uuid');
        
        // Create document record
        $document = Document::create([
            'uri' => 'submissions/123/document.pdf',
            'title' => 'Required Document',
            'mimetype' => 'application/pdf',
            'size' => 1024000, // 1MB in bytes
        ]);
        
        // Link document to submission
        $submissionDocument = SubmissionDocument::create([
            'submission_id' => $submission->id,
            'document_id' => $document->id,
            'requirement_id' => $documentRequirement->id,
            'status' => 'pending',
        ]);
        
        // The SubmissionDocumentObserver fires on creation and logs the upload
        // automatically creates tracking history entry with document_uploaded event type
        
        // Step 2: Admin reviews and approves the document
        $submissionDocument->status = 'approved';
        $submissionDocument->notes = 'Document meets requirements';
        $submissionDocument->save();
        
        // The SubmissionDocumentObserver automatically:
        // 1. Creates a tracking entry for document approval
        // 2. Fires SubmissionDocumentStatusChanged event
        // 3. The event listener creates detailed tracking record
        
        // Step 3: System checks if submission can advance to next stage
        $workflowService = app(\App\Services\WorkflowService::class);
        
        // This checks if all required documents are approved and stage can be exited
        if ($submission->canAdvanceToNextStage()) {
            // Advance the submission to next stage
            $workflowService->processAction($submission, 'advance_stage', [
                'comment' => 'All documents approved, advancing to next stage',
            ]);
            
            // The TrackingService inside will:
            // 1. Create a transition record
            // 2. Fire SubmissionStateChanged event
            // 3. The event listener may create additional tracking records
        }
    }
}