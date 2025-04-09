<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatentDetail extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'submission_id',
        'application_type',
        'patent_title',
        'patent_description',
        'from_grant_research',
        'self_funded',
        'media_link',
        'inventors_name',
    ];

    protected $casts = [
        'from_grant_research' => 'boolean',
        'self_funded' => 'boolean',
    ];

    /**
     * Get the submission that owns the patent detail.
     */
    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }
}
