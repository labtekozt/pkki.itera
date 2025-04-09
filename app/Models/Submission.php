<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Submission extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'submission_type_id',
        'current_stage_id',
        'title',
        'description',
        'status',
        'certificate',
        'user_id',
    ];

    /**
     * The allowed status values
     */
    public static $statusOptions = [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'in_review' => 'In Review',
        'revision_needed' => 'Revision Needed',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

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
     * Get the HAKI (copyright) detail associated with the submission.
     */
    public function hakiDetail()
    {
        return $this->hasOne(HakiDetail::class);
    }
    
    /**
     * Scope a query to only include submissions of a specific type.
     */
    public function scopeOfType(Builder $query, string $typeSlug): Builder
    {
        return $query->whereHas('submissionType', function ($query) use ($typeSlug) {
            $query->where('slug', $typeSlug);
        });
    }
    
    /**
     * Scope a query to only include submissions with a specific status.
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
    
    /**
     * Scope a query to only include submissions that are in active stages.
     */
    public function scopeInActiveStages(Builder $query): Builder
    {
        return $query->whereHas('currentStage', function ($query) {
            $query->where('is_active', true);
        });
    }
    
    /**
     * Check if the submission can be edited by a user
     */
    public function canBeEditedBy(User $user): bool
    {
        // Admin can always edit
        if ($user->can('review_submissions')) {
            return true;
        }
        
        // Owner can edit if it's a draft or needs revision
        if ($this->user_id === $user->id && in_array($this->status, ['draft', 'revision_needed'])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if the submission has all required documents for the current stage
     */
    public function hasAllRequiredDocuments(): bool
    {
        if (!$this->currentStage) {
            return false;
        }
        
        $requirements = $this->currentStage->documentRequirements()
            ->wherePivot('is_required', true)
            ->get();
            
        if ($requirements->isEmpty()) {
            return true;
        }
        
        $approvedCount = 0;
        
        foreach ($requirements as $requirement) {
            $document = $this->submissionDocuments()
                ->where('requirement_id', $requirement->id)
                ->where('status', 'approved')
                ->first();
                
            if (!$document) {
                return false;
            }
            
            $approvedCount++;
        }
        
        return $approvedCount === $requirements->count();
    }
}