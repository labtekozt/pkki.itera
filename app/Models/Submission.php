<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Submission extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'submission_type_id',
        'current_stage_id',
        'title',
        'status',
        'certificate',
        'user_id',
    ];

    /**
     * Handle custom attributes and relationships during creation/updates
     */
    protected static function booted()
    {
        parent::booted();

        // When a submission is created
        static::created(function (Submission $submission) {
            // Create related type-specific detail records based on the submission type
            $typeSlug = $submission->submissionType->slug ?? null;
            
            if ($typeSlug === 'paten' && isset($submission->attributes['inventor_details'])) {
                // Create patent details
                PatentDetail::create([
                    'submission_id' => $submission->id,
                    'inventor_details' => $submission->attributes['inventor_details'] ?? null,
                    'patent_type' => 'utility', // Default type
                    'invention_description' => $submission->attributes['metadata']['invention_type'] ?? '',
                    'technical_field' => $submission->attributes['metadata']['technology_field'] ?? null,
                ]);
                
                // Remove these attributes as they're now stored in the related model
                unset($submission->attributes['inventor_details']);
                unset($submission->attributes['metadata']);
            }
        });
    }

    /**
     * Get the user that owns the submission.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the submission type of this submission.
     */
    public function submissionType()
    {
        return $this->belongsTo(SubmissionType::class);
    }

    /**
     * Get the current workflow stage of this submission.
     */
    public function currentStage()
    {
        return $this->belongsTo(WorkflowStage::class, 'current_stage_id');
    }

    /**
     * Get the submission documents for this submission.
     */
    public function submissionDocuments()
    {
        return $this->hasMany(SubmissionDocument::class);
    }

    /**
     * Get the tracking history entries for this submission.
     */
    public function trackingHistory()
    {
        return $this->hasMany(TrackingHistory::class);
    }

    /**
     * Get the latest tracking history entry for this submission.
     */
    public function latestTracking()
    {
        return $this->hasOne(TrackingHistory::class)->latest();
    }
    
    /**
     * Get the patent details for this submission if applicable.
     */
    public function patentDetail()
    {
        return $this->hasOne(PatentDetail::class);
    }
    
    /**
     * Get the industrial design details for this submission if applicable.
     */
    public function industrialDesignDetail()
    {
        return $this->hasOne(IndustrialDesignDetail::class);
    }

    /**
     * Get the brand detail associated with the submission.
     */
    public function brandDetail()
    {
        return $this->hasOne(BrandDetail::class);
    }

    /**
     * Get the haki detail associated with the submission.
     */
    public function hakiDetail()
    {
        return $this->hasOne(HakiDetail::class);
    }
    
    /**
     * Get the type-specific details for this submission.
     */
    public function getDetailsAttribute()
    {
        $typeSlug = $this->submissionType->slug ?? null;
        
        return match($typeSlug) {
            'paten' => $this->patentDetail,
            'brand' => $this->brandDetail,
            'haki' => $this->hakiDetail,
            'industrial_design' => $this->industrialDesignDetail,
            default => null,
        };
    }
}