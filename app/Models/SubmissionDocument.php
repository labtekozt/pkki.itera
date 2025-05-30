<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SubmissionDocument extends Model implements HasMedia
{
    use HasFactory, HasUuids, SoftDeletes, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'submission_id',
        'document_id',
        'requirement_id',
        'status',
        'is_active',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the submission that owns this document.
     */
    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }

    /**
     * Get the document for this submission document.
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the requirement this document fulfills.
     */
    public function requirement()
    {
        return $this->belongsTo(DocumentRequirement::class, 'requirement_id');
    }

    /**
     * Check if this document fulfills a required document.
     */
    public function isRequiredDocument()
    {
        return $this->requirement && $this->requirement->required;
    }

    /**
     * Replace this document with a new one.
     */
    public function replaceWith($newDocumentId, $notes = null)
    {
        // Mark current as replaced
        $this->update([
            'status' => 'replaced',
            'notes' => $notes ?? $this->notes,
        ]);

        // Create new submission document with same requirement
        return $this->submission->submissionDocuments()->create([
            'document_id' => $newDocumentId,
            'requirement_id' => $this->requirement_id,
            'status' => 'pending',
            'notes' => 'Replacement for document #' . $this->id,
        ]);
    }

    /**
     * Register media collections for this model
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('document')
            ->singleFile();
            
        $this->addMediaCollection('attachments');
    }
}
