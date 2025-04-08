<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackingHistory extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tracking_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'submission_id',
        'stage_id',
        'status',
        'comment',
        'document_id',
        'processed_by',
    ];

    /**
     * Get the submission that owns this tracking entry.
     */
    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }

    /**
     * Get the workflow stage for this tracking entry.
     */
    public function stage()
    {
        return $this->belongsTo(WorkflowStage::class, 'stage_id');
    }

    /**
     * Get the document attached to this tracking entry.
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
     * Get a human-readable description of the status change.
     */
    public function getStatusDescriptionAttribute()
    {
        $descriptions = [
            'started' => 'Started',
            'in_progress' => 'In Progress',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'revision_needed' => 'Revision Needed',
            'objection' => 'Objection Raised',
            'completed' => 'Completed',
        ];

        return $descriptions[$this->status] ?? $this->status;
    }
}
