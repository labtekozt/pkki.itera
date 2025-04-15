<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\TrackingService;

class Submission extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'submission_type_id',
        'current_stage_id',
        'title',
        'status',
        'certificate',
        'user_id',
    ];

    /**
     * Handle custom attributes and relationships during creation/updates
     */
    protected static function booted()
    {
        parent::booted();

        // When a submission is created
        static::created(function (Submission $submission) {
            // Create related type-specific detail records based on the submission type
            $typeSlug = $submission->submissionType->slug ?? null;

            if ($typeSlug === 'paten' && isset($submission->attributes['inventor_details'])) {
                // Create patent details
                PatentDetail::create([
                    'submission_id' => $submission->id,
                    'inventor_details' => $submission->attributes['inventor_details'] ?? null,
                    'patent_type' => 'utility', // Default type
                    'invention_description' => $submission->attributes['metadata']['invention_type'] ?? '',
                    'technical_field' => $submission->attributes['metadata']['technology_field'] ?? null,
                ]);

                // Remove these attributes as they're now stored in the related model
                unset($submission->attributes['inventor_details']);
                unset($submission->attributes['metadata']);
            }
        });
    }

    /**
     * Get the user that owns the submission.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the submission type of this submission.
     */
    public function submissionType()
    {
        return $this->belongsTo(SubmissionType::class);
    }

    /**
     * Get the current workflow stage of this submission.
     */
    public function currentStage()
    {
        return $this->belongsTo(WorkflowStage::class, 'current_stage_id');
    }

    /**
     * Get the submission documents for this submission.
     */
    public function submissionDocuments()
    {
        return $this->hasMany(SubmissionDocument::class);
    }

    /**
     * Get the tracking history entries for this submission.
     */
    public function trackingHistory()
    {
        return $this->hasMany(TrackingHistory::class);
    }

    /**
     * Get the workflow assignments for this submission.
     */
    public function reviewerAssignments()
    {
        return $this->hasMany(WorkflowAssignment::class);
    }

    /**
     * Get active reviewer assignments for this submission.
     */
    public function activeReviewerAssignments()
    {
        return $this->reviewerAssignments()
            ->whereNull('completed_at');
    }

    /**
     * Get reviewer assignments for the current stage.
     */
    public function currentStageAssignments()
    {
        return $this->reviewerAssignments()
            ->where('stage_id', $this->current_stage_id);
    }

    /**
     * Get active reviewer assignments for the current stage.
     */
    public function activeCurrentStageAssignments()
    {
        return $this->currentStageAssignments()
            ->whereNull('completed_at');
    }

    /**
     * Get the tracking history entries for this submission ordered chronologically.
     */
    public function orderedTrackingHistory()
    {
        return $this->hasMany(TrackingHistory::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the latest tracking history entry for this submission.
     */
    public function latestTracking()
    {
        return $this->hasOne(TrackingHistory::class)->latest();
    }

    /**
     * Get the state machine for the submission workflow.
     */
    public function getWorkflowStateMachine()
    {
        // This would return a state machine instance that defines
        // all possible transitions for this submission type
        // For now we'll use the tracking service directly

        return app(TrackingService::class);
    }

    /**
     * Get tracking entries that require action.
     */
    public function getPendingActionsAttribute()
    {
        return $this->trackingHistory()
            ->where('status', 'revision_needed')
            ->whereNull('resolved_at')
            ->get();
    }

    /**
     * Check if the submission can be advanced to the next stage.
     */
    public function canAdvanceToNextStage(): bool
    {
        if (!$this->currentStage) {
            return false;
        }

        // Check if current stage requirements are fulfilled
        if (!$this->currentStage->canExit($this)) {
            return false;
        }

        // Check if there's a next stage
        $nextStage = $this->currentStage->nextStage();
        if (!$nextStage) {
            return false;
        }

        // Can't advance if submission status is not appropriate
        return in_array($this->status, ['in_review', 'approved']);
    }

    /**
     * Advance to the next stage.
     */
    public function advanceToNextStage(?string $comment = null): self
    {
        if (!$this->canAdvanceToNextStage()) {
            throw new \Exception("This submission cannot be advanced to the next stage.");
        }

        return app(TrackingService::class)->advanceToNextStage(
            $this,
            auth()->user(),
            $comment
        );
    }

    /**
     * Request revisions for this submission.
     */
    public function requestRevisions(string $comment): self
    {
        return app(TrackingService::class)->requestRevisions(
            $this,
            auth()->user(),
            $comment
        );
    }

    /**
     * Get all transitions that are currently available for this submission.
     */
    public function getAvailableTransitions(): array
    {
        if (!$this->currentStage) {
            return [];
        }

        // Get possible transitions from the stage
        $transitions = $this->currentStage->getAvailableTransitions();

        // Filter transitions based on submission state
        return array_filter($transitions, function ($transition) {
            switch ($transition['action']) {
                case 'advance':
                    return $this->canAdvanceToNextStage();
                case 'return':
                    return in_array($this->status, ['in_review']);
                case 'complete':
                    return in_array($this->status, ['in_review']) && $this->currentStage->isFinalStage();
                case 'reject':
                    return in_array($this->status, ['in_review']);
                default:
                    return false;
            }
        });
    }

    /**
     * Get the workflow timeline for this submission.
     */
    public function getTimelineAttribute()
    {
        $history = $this->orderedTrackingHistory;
        $stages = $this->submissionType->workflowStages;

        $timeline = [];

        foreach ($stages as $stage) {
            $stageHistory = $history->where('stage_id', $stage->id);

            $timeline[] = [
                'stage' => $stage,
                'is_current' => $stage->id === $this->current_stage_id,
                'started' => $stageHistory->isNotEmpty(),
                'start_date' => $stageHistory->first()?->created_at,
                'end_date' => $stageHistory->where('event_type', 'stage_transition')->first()?->created_at,
                'actions' => $stageHistory->toArray(),
                'completed' => $stageHistory->where('status', 'completed')->isNotEmpty(),
            ];
        }

        return $timeline;
    }

    /**
     * Get the patent details for this submission if applicable.
     */
    public function patentDetail()
    {
        return $this->hasOne(PatentDetail::class);
    }

    /**
     * Get the industrial design details for this submission if applicable.
     */
    public function industrialDesignDetail()
    {
        return $this->hasOne(IndustrialDesignDetail::class);
    }

    public function brandDetail()
    {
        return $this->hasOne(BrandDetail::class);
    }

    public function hakiDetail()
    {
        return $this->hasOne(HakiDetail::class);
    }

    /**
     * Get the type-specific details for this submission.
     */
    public function getDetailsAttribute()
    {
        $typeSlug = $this->submissionType->slug ?? null;

        return match ($typeSlug) {
            'paten' => $this->patentDetail,
            'brand' => $this->brandDetail,
            'haki' => $this->hakiDetail,
            'industrial_design' => $this->industrialDesignDetail,
            default => null,
        };
    }
}
