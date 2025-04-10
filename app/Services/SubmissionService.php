<?php

namespace App\Services;

use App\Events\SubmissionStatusChanged;
use App\Models\Submission;
use App\Models\TrackingHistory;
use App\Models\WorkflowStage;
use App\Repositories\SubmissionRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Service class for handling submission-related business logic
 */
class SubmissionService
{
    protected $submissionRepository;

    public function __construct(SubmissionRepository $submissionRepository)
    {
        $this->submissionRepository = $submissionRepository;
    }

    /**
     * Create a new submission with optional documents
     */
    public function createSubmission(array $data, array $documents = []): Submission
    {
        return $this->submissionRepository->createSubmission($data, $documents);
    }

    /**
     * Update an existing submission
     */
    public function updateSubmission(Submission $submission, array $data, array $documents = []): Submission
    {
        return DB::transaction(function () use ($submission, $data, $documents) {
            // Track status changes
            $oldStatus = $submission->status;
            $newStatus = $data['status'] ?? $oldStatus;
            $statusChanged = ($oldStatus !== $newStatus);

            // Track stage changes
            $oldStageId = $submission->current_stage_id;
            $newStageId = $data['current_stage_id'] ?? $oldStageId;
            $stageChanged = ($oldStageId !== $newStageId);

            // Special handling for initial submission
            $isInitialSubmission = ($oldStatus === 'draft' && $newStatus === 'submitted');

            // Update basic submission data
            $submission->update(array_filter([
                'title' => $data['title'] ?? $submission->title,
                'status' => $newStatus,
                'current_stage_id' => $newStageId,
                'certificate' => $data['certificate'] ?? $submission->certificate,
            ]));

            // Create type-specific detail record if needed
            $this->updateTypeSpecificDetails($submission, $data);

            // Attach documents if provided
            if (!empty($documents)) {
                $this->submissionRepository->addDocumentsToSubmission($submission, $documents);
            }

            // Create tracking history record if status or stage changed
            if ($statusChanged || $stageChanged) {
                $actionType = $isInitialSubmission ? 'submit' : 'update';
                $comment = $data['comment'] ?? ($isInitialSubmission ? 'Initial submission' : 'Submission updated');

                $this->createStatusChangeHistory($submission, [
                    'action' => $actionType,
                    'comment' => $comment,
                    'processed_by' => $data['processed_by'] ?? Auth::id(),
                    'old_status' => $oldStatus,
                    'old_stage_id' => $oldStageId,
                ]);

                // Dispatch event for status change
                if ($statusChanged) {
                    event(new SubmissionStatusChanged($submission, $oldStatus, $newStatus));
                }

                // If this is an initial submission, automatically set document statuses to "pending review"
                if ($isInitialSubmission) {
                    $submission->submissionDocuments()->update([
                        'status' => 'pending'
                    ]);
                }
            }

            return $submission->fresh();
        });
    }

    /**
     * Advance a submission to the next workflow stage
     */
    public function advanceSubmission(Submission $submission, array $data = []): Submission
    {
        return $this->submissionRepository->advanceSubmission($submission, $data);
    }

    /**
     * Move a submission back to the previous stage
     */
    public function revertSubmission(Submission $submission, array $options = []): Submission
    {
        return $this->submissionRepository->revertSubmission($submission, $options);
    }

    /**
     * Update type-specific details for a submission
     */
    protected function updateTypeSpecificDetails(Submission $submission, array $data): void
    {
        $typeSlug = $submission->submissionType->slug ?? null;

        if (!$typeSlug) return;

        switch ($typeSlug) {
            case 'paten':
                if (isset($data['patentDetail']) && $submission->patentDetail) {
                    $submission->patentDetail->update(
                        $this->filterArrayData($data['patentDetail'])
                    );
                }
                break;

            case 'brand':
                if (isset($data['brandDetail']) && $submission->brandDetail) {
                    $submission->brandDetail->update(
                        $this->filterArrayData($data['brandDetail'])
                    );
                }
                break;

            case 'haki':
                if (isset($data['hakiDetail']) && $submission->hakiDetail) {
                    // Cast boolean fields properly
                    $detailData = $data['hakiDetail'];
                    foreach (['is_kkn_output', 'from_grant_research', 'self_funded'] as $boolField) {
                        if (isset($detailData[$boolField]) && is_string($detailData[$boolField])) {
                            $detailData[$boolField] = $detailData[$boolField] === 'true' || $detailData[$boolField] === '1';
                        }
                    }

                    $submission->hakiDetail->update(
                        $this->filterArrayData($detailData)
                    );
                }
                break;

            case 'industrial_design':
                if (isset($data['industrialDesignDetail']) && $submission->industrialDesignDetail) {
                    $submission->industrialDesignDetail->update(
                        $this->filterArrayData($data['industrialDesignDetail'])
                    );
                }
                break;
        }
    }

    /**
     * Create tracking history for status changes
     */
    protected function createStatusChangeHistory(Submission $submission, array $options = []): TrackingHistory
    {
        $action = $options['action'] ?? 'update';
        $processedBy = $options['processed_by'] ?? Auth::id();

        // Map submission status to tracking status
        $trackingStatus = match ($submission->status) {
            'submitted' => 'started',
            'in_review' => 'in_progress',
            'approved' => 'approved',
            'rejected' => 'rejected',
            'revision_needed' => 'revision_needed',
            'completed' => 'completed',
            default => 'in_progress',
        };

        $trackingData = [
            'submission_id' => $submission->id,
            'stage_id' => $submission->current_stage_id,
            'action' => $action,
            'status' => $trackingStatus, // Use mapped status
            'processed_by' => $processedBy,
            'comment' => $options['comment'] ?? null,
            'metadata' => [
                'old_status' => $options['old_status'] ?? null,
                'old_stage_id' => $options['old_stage_id'] ?? null,
            ]
        ];

        return TrackingHistory::create($trackingData);
    }

    /**
     * Filter data array without removing boolean false values
     */
    protected function filterArrayData(array $data): array
    {
        return array_filter($data, function ($value) {
            return !is_null($value);
        });
    }

    /**
     * Get the appropriate stage based on submission type and criteria
     */
    public function getAppropriateStage(string $submissionTypeId, array $criteria = []): ?WorkflowStage
    {
        return $this->submissionRepository->getAppropriateStage($submissionTypeId, $criteria);
    }
}
