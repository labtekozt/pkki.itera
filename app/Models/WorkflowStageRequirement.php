<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowStageRequirement extends Model
{   
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'workflow_stage_id',
        'document_requirement_id',
        'is_required',
        'order',
    ];

    /**
     * Get the workflow stage that owns the requirement.
     */
    public function workflowStage()
    {
        return $this->belongsTo(WorkflowStage::class);
    }

    /**
     * Get the document requirement that this relationship references.
     */
    public function documentRequirement()
    {
        return $this->belongsTo(DocumentRequirement::class);
    }
    
    /**
     * Check if this requirement is mandatory.
     *
     * @return bool
     */
    public function isRequired(): bool
    {
        return (bool) $this->is_required;
    }
    
    /**
     * Get all submission documents related to this requirement.
     */
    public function submissionDocuments()
    {
        return $this->hasManyThrough(
            SubmissionDocument::class,
            DocumentRequirement::class,
            'id', // Foreign key on document_requirements table
            'requirement_id', // Foreign key on submission_documents table
            'document_requirement_id', // Local key on workflow_stage_requirements table
            'id' // Local key on document_requirements table
        );
    }
}
