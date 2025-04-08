<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentRequirement extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'submission_type_id',
        'code',
        'name',
        'description',
        'required',
        'order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'required' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the submission type that owns this requirement.
     */
    public function submissionType()
    {
        return $this->belongsTo(SubmissionType::class);
    }

    /**
     * Get the submission documents that fulfill this requirement.
     */
    public function submissionDocuments()
    {
        return $this->hasMany(SubmissionDocument::class, 'requirement_id');
    }
}
