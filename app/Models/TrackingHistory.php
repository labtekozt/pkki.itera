<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackingHistory extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'submission_id',
        'stage_id',
        'action',
        'status',
        'processed_by',
        'comment',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
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
        return $this->belongsTo(WorkflowStage::class);
    }

    /**
     * Get the user who processed this tracking history.
     */
    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
    
    /**
     * Get the document associated with this history entry if it's a document update.
     */
    public function document()
    {
        if ($this->action !== 'document_update' || empty($this->metadata['document_id'])) {
            return null;
        }
        
        return $this->belongsTo(SubmissionDocument::class, 'metadata->document_id');
    }
}
