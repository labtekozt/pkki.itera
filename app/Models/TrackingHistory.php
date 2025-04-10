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
        'status',
        'processed_by',
        'comment',
        'metadata',
        'document_id',
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
     * Get the submission that owns this tracking history.
     */
    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }

    /**
     * Get the stage associated with this tracking history.
     */
    public function stage()
    {
        return $this->belongsTo(WorkflowStage::class, 'stage_id');
    }

    /**
     * Get the previous stage if this was a transition.
     */
    public function previousStage()
    {
        return $this->belongsTo(WorkflowStage::class, 'previous_stage_id');
    }

    /**
     * Get the user who processed this tracking history.
     */
    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
    
    /**
     * Get the document associated with this history entry.
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Check if this tracking entry requires action.
     */
    public function requiresAction(): bool
    {
        return in_array($this->status, ['revision_needed', 'objection']) && !$this->resolved_at;
    }

    /**
     * Mark this tracking entry as resolved.
     */
    public function markResolved(?string $comment = null): self
    {
        $this->resolved_at = now();
        if ($comment) {
            $this->comment = $comment;
        }
        $this->save();
        
        return $this;
    }

    /**
     * Quick create method for stage transitions.
     */
    public static function createTransition(
        Submission $submission, 
        WorkflowStage $fromStage, 
        WorkflowStage $toStage, 
        string $action, 
        string $status, 
        ?string $comment = null, 
        ?User $processor = null,
        array $metadata = []
    ): self {
        return self::create([
            'submission_id' => $submission->id,
            'stage_id' => $toStage->id,
            'previous_stage_id' => $fromStage->id,
            'source_status' => $submission->status,
            'target_status' => $status,
            'action' => $action,
            'status' => $status,
            'comment' => $comment,
            'processed_by' => $processor?->id,
            'metadata' => $metadata,
            'event_type' => 'stage_transition',
        ]);
    }
}
