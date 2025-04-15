<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowAssignment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'submission_id',
        'stage_id',
        'reviewer_id',
        'assigned_by',
        'status',
        'notes',
        'assigned_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'assigned_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the submission associated with this assignment.
     */
    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }

    /**
     * Get the workflow stage associated with this assignment.
     */
    public function stage()
    {
        return $this->belongsTo(WorkflowStage::class, 'stage_id');
    }

    /**
     * Get the reviewer assigned to this workflow stage.
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Get the user who created this assignment.
     */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get the related tracking history entries for this assignment.
     */
    public function trackingHistory()
    {
        return $this->hasMany(TrackingHistory::class, 'metadata->assignment_id');
    }

    /**
     * Check if this assignment is active (not completed).
     */
    public function isActive()
    {
        return is_null($this->completed_at);
    }

    /**
     * Complete this assignment with a status.
     */
    public function complete(string $status, ?string $notes = null): self
    {
        $this->update([
            'status' => $status,
            'notes' => $notes,
            'completed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Scope a query to only include active assignments.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('completed_at');
    }

    /**
     * Scope a query to only include assignments for a specific submission.
     */
    public function scopeForSubmission($query, $submissionId)
    {
        return $query->where('submission_id', $submissionId);
    }

    /**
     * Scope a query to only include assignments for a specific reviewer.
     */
    public function scopeForReviewer($query, $reviewerId)
    {
        return $query->where('reviewer_id', $reviewerId);
    }

    /**
     * Get the status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'approved' => 'success',
            'rejected' => 'danger',
            'revision_needed' => 'warning',
            'pending' => 'info',
            'in_progress' => 'primary',
            default => 'secondary'
        };
    }
}