<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class WorkflowStage extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'submission_type_id',
        'code',
        'name',
        'order',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the submission type that owns the workflow stage.
     */
    public function submissionType()
    {
        return $this->belongsTo(SubmissionType::class);
    }

    /**
     * Get the submissions that have this as their current stage.
     */
    public function currentSubmissions()
    {
        return $this->hasMany(Submission::class, 'current_stage_id');
    }

    /**
     * Get the tracking history entries for this stage.
     */
    public function trackingHistory()
    {
        return $this->hasMany(TrackingHistory::class, 'stage_id');
    }

    /**
     * Get all tracking entries where this stage was the previous stage.
     */
    public function previousTrackingHistory()
    {
        return $this->hasMany(TrackingHistory::class, 'previous_stage_id');
    }

    /**
     * The document requirements for this stage.
     */
    public function documentRequirements(): BelongsToMany
    {
        return $this->belongsToMany(DocumentRequirement::class, 'workflow_stage_requirements')
            ->using(WorkflowStageRequirement::class)
            ->withPivot(['id', 'is_required', 'order'])
            ->withTimestamps();
    }

    /**
     * Custom method to attach document requirements with UUID generation
     */
    public function attachRequirements(array $requirements)
    {
        $attachData = [];
        
        foreach ($requirements as $requirementId) {
            $attachData[$requirementId] = [
                'id' => Str::uuid()->toString(),
                'is_required' => true,
                'order' => 1, // Default order
            ];
        }
        
        return $this->documentRequirements()->attach($attachData);
    }

    /**
     * Get the stage requirement relationships.
     */
    public function stageRequirements()
    {
        return $this->hasMany(WorkflowStageRequirement::class);
    }

    /**
     * Get the next stage in the workflow.
     * 
     * @param bool $skipDisabled Whether to skip disabled stages
     * @return WorkflowStage|null
     */
    public function nextStage(bool $skipDisabled = true)
    {
        $query = $this->submissionType->workflowStages()
            ->where('order', '>', $this->order)
            ->orderBy('order');

        if ($skipDisabled) {
            $query->where('is_active', true);
        }

        return $query->first();
    }

    /**
     * Get the previous stage in the workflow.
     * 
     * @param bool $skipDisabled Whether to skip disabled stages
     * @return WorkflowStage|null
     */
    public function previousStage(bool $skipDisabled = true)
    {
        $query = $this->submissionType->workflowStages()
            ->where('order', '<', $this->order)
            ->orderBy('order', 'desc');

        if ($skipDisabled) {
            $query->where('is_active', true);
        }

        return $query->first();
    }

    /**
     * Check if this is the final stage in the workflow.
     * 
     * @return bool
     */
    public function isFinalStage(): bool
    {
        return !$this->nextStage();
    }

    /**
     * Check if this is the initial stage in the workflow.
     * 
     * @return bool
     */
    public function isInitialStage(): bool
    {
        return !$this->previousStage();
    }

    /**
     * Check if all required documents for this stage are fulfilled for a given submission.
     * 
     * @param Submission $submission
     * @return bool
     */
    public function areRequirementsFulfilled(Submission $submission): bool
    {
        // Get all required document requirements for this stage
        $requiredRequirementIds = $this->documentRequirements()
            ->wherePivot('is_required', true)
            ->pluck('document_requirements.id');

        // Count how many of the required requirements have approved documents
        $fulfilledCount = $submission->submissionDocuments()
            ->whereIn('requirement_id', $requiredRequirementIds)
            ->where('status', 'approved')
            ->distinct('requirement_id')
            ->count('requirement_id');

        // All requirements are fulfilled if the counts match
        return $fulfilledCount === $requiredRequirementIds->count();
    }

    /**
     * Check if a submission should transition to this stage based on
     * completion of requirements in the previous stage.
     * 
     * @param Submission $submission
     * @return bool
     */
    public function shouldTransitionTo(Submission $submission): bool
    {
        // Can't transition if this is the initial stage
        if ($this->isInitialStage()) {
            return false;
        }
        
        // Get the previous stage
        $previousStage = $this->previousStage();
        if (!$previousStage) {
            return false;
        }
        
        // Check if all requirements of the previous stage are fulfilled
        return $previousStage->areRequirementsFulfilled($submission);
    }

    /**
     * Check if the submission can exit this stage (all requirements are fulfilled)
     *
     * @param Submission $submission
     * @return bool
     */
    public function canExit(Submission $submission): bool
    {
        return $this->areRequirementsFulfilled($submission);
    }

    /**
     * Get available transitions from this stage.
     *
     * @return array
     */
    public function getAvailableTransitions(): array
    {
        $transitions = [];
        
        // Next stage transition
        $nextStage = $this->nextStage();
        if ($nextStage) {
            $transitions[] = [
                'action' => 'advance',
                'target_stage' => $nextStage,
                'description' => 'Advance to ' . $nextStage->name,
            ];
        }
        
        // Previous stage transition
        $previousStage = $this->previousStage();
        if ($previousStage) {
            $transitions[] = [
                'action' => 'return',
                'target_stage' => $previousStage,
                'description' => 'Return to ' . $previousStage->name,
            ];
        }
        
        // Final transitions
        if ($this->isFinalStage()) {
            $transitions[] = [
                'action' => 'complete',
                'target_stage' => null,
                'description' => 'Complete submission',
            ];
            
            $transitions[] = [
                'action' => 'reject',
                'target_stage' => null,
                'description' => 'Reject submission',
            ];
        }
        
        return $transitions;
    }
}
