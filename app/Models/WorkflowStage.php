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
        return $this->belongsToMany(DocumentRequirement::class, 'workflow_stage_requirements')
                    ->withPivot('is_required', 'order')
                    ->orderBy('workflow_stage_requirements.order');
    }
    
    /**
     * Get the stage requirements.
     */
    public function stageRequirements()
    {
        return $this->hasMany(WorkflowStageRequirement::class);
    }
}
