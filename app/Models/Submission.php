<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Submission extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'submission_type_id',
        'current_stage_id',
        'title',
        'status',
        'inventor_details',
        'certificate',
        'metadata',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns this submission.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the submission type for this submission.
     */
    public function submissionType()
    {
        return $this->belongsTo(SubmissionType::class);
    }

    /**
     * Get the current workflow stage for this submission.
     */
    public function currentStage()
    {
        return $this->belongsTo(WorkflowStage::class, 'current_stage_id');
    }

    /**
     * Get the documents for this submission.
     */
    public function submissionDocuments()
    {
        return $this->hasMany(SubmissionDocument::class);
    }

    /**
     * Get the tracking history for this submission.
     */
    public function trackingHistory()
    {
        return $this->hasMany(TrackingHistory::class);
    }

    /**
     * Get the latest tracking history entry.
     */
    public function latestTracking()
    {
        return $this->hasOne(TrackingHistory::class)->latest();
    }

    /**
     * Advance to the next stage in the workflow.
     */
    public function advanceStage($status = 'started', $comment = null, $processedBy = null, $document = null)
    {
        $nextStage = $this->currentStage->nextStage();
        
        if (!$nextStage) {
            return false;
        }
        
        $this->update(['current_stage_id' => $nextStage->id]);
        
        // Create tracking history entry
        $this->trackingHistory()->create([
            'stage_id' => $nextStage->id,
            'status' => $status,
            'comment' => $comment,
            'document_id' => $document?->id,
            'processed_by' => $processedBy?->id,
        ]);
        
        return true;
    }

    /**
     * Initiate a submission with the first stage.
     */
    public function initiateSubmission($comment = null)
    {
        $firstStage = $this->submissionType->firstStage();
        
        if (!$firstStage) {
            return false;
        }
        
        $this->update([
            'current_stage_id' => $firstStage->id,
            'status' => 'submitted'
        ]);
        
        // Create tracking history entry
        $this->trackingHistory()->create([
            'stage_id' => $firstStage->id,
            'status' => 'started',
            'comment' => $comment ?? 'Submission initiated',
        ]);
        
        return true;
    }
}