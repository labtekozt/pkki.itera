<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrackingHistory extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'submission_id',
        'stage_id',
        'action',
        'metadata',
        'status',
        'comment',
        'document_id',
        'processed_by',
        'previous_stage_id',
        'source_status',
        'target_status',
        'event_type',
        'resolved_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'resolved_at' => 'datetime',
    ];

    /**
     * Get the submission this tracking entry belongs to.
     */
    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }

    /**
     * Get the workflow stage this tracking entry belongs to.
     */
    public function stage()
    {
        return $this->belongsTo(WorkflowStage::class, 'stage_id');
    }

    /**
     * Get the previous workflow stage if this was a transition.
     */
    public function previousStage()
    {
        return $this->belongsTo(WorkflowStage::class, 'previous_stage_id');
    }

    /**
     * Get the document associated with this tracking entry, if any.
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the user who processed this tracking entry.
     */
    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
    
    /**
     * Determine if this tracking entry represents a stage transition
     */
    public function isTransition(): bool
    {
        return $this->previous_stage_id !== null;
    }
    
    /**
     * Get a human-readable description of this tracking entry
     */
    public function getDescriptionAttribute(): string
    {
        $description = match($this->action) {
            'approve' => 'Approved current stage',
            'reject' => 'Rejected submission',
            'request_revision' => 'Requested revisions',
            'submit_revision' => 'Submitted revisions',
            'advance_stage' => 'Advanced to next stage',
            'return_stage' => 'Returned to previous stage',
            'complete' => 'Completed submission',
            default => 'Updated submission'
        };
        
        if ($this->isTransition() && $this->previousStage) {
            $description .= " (from {$this->previousStage->name} to {$this->stage->name})";
        }
        
        return $description;
    }

    /**
     * Get the status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'started' => 'blue',
            'in_progress' => 'orange',
            'approved' => 'green',
            'rejected' => 'red',
            'revision_needed' => 'yellow',
            'objection' => 'pink',
            'completed' => 'emerald',
            default => 'gray'
        };
    }

    /**
     * Get time elapsed since this event
     */
    public function getTimeElapsedAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Create a new tracking entry for a stage transition
     */
    public static function createTransition(
        Submission $submission,
        WorkflowStage $sourceStage,
        WorkflowStage $targetStage,
        string $action,
        string $status,
        ?string $comment = null,
        ?User $processor = null,
        array $metadata = []
    ): self {
        return self::create([
            'submission_id' => $submission->id,
            'stage_id' => $targetStage->id,
            'previous_stage_id' => $sourceStage->id,
            'action' => $action,
            'status' => $status,
            'comment' => $comment,
            'metadata' => $metadata,
            'processed_by' => $processor?->id,
            'source_status' => $submission->getOriginal('status'),
            'target_status' => $status,
            'event_type' => 'stage_transition',
        ]);
    }
}
