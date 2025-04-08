<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowStage extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'submission_type_id',
        'code',
        'name',
        'order',
        'description',
        'required_documents',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'order' => 'integer',
        'required_documents' => 'array',
    ];

    /**
     * Get the submission type that owns this stage.
     */
    public function submissionType()
    {
        return $this->belongsTo(SubmissionType::class);
    }

    /**
     * Get the submissions currently at this stage.
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
     * Get the next stage in the workflow.
     */
    public function nextStage()
    {
        return $this->submissionType->workflowStages()
            ->where('order', '>', $this->order)
            ->orderBy('order')
            ->first();
    }

    /**
     * Get the previous stage in the workflow.
     */
    public function previousStage()
    {
        return $this->submissionType->workflowStages()
            ->where('order', '<', $this->order)
            ->orderBy('order', 'desc')
            ->first();
    }
}
