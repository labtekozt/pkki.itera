<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowStageRequirement extends Model
{
    use HasFactory, HasUuids;

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
}
