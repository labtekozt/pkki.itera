<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubmissionType extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Get the document requirements for this submission type.
     */
    public function documentRequirements()
    {
        return $this->hasMany(DocumentRequirement::class);
    }

    /**
     * Get the workflow stages for this submission type.
     */
    public function workflowStages()
    {
        return $this->hasMany(WorkflowStage::class)->orderBy('order');
    }

    /**
     * Get the submissions of this type.
     */
    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Get the first stage of this submission type workflow.
     */
    public function firstStage()
    {
        return $this->workflowStages()->orderBy('order')->first();
    }
}