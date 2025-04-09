<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
     * Get the document requirements associated with this workflow stage.
     */
    public function documentRequirements()
    {
        return $this->belongsToMany(
            DocumentRequirement::class,
            'workflow_stage_requirements',
            'workflow_stage_id',
            'document_requirement_id'
        )->withPivot('is_required', 'order')->orderBy('workflow_stage_requirements.order');
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
}
