<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CopyrightDetail extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'submission_id',
        'work_type',
        'work_description',
        'creation_year',
        'is_published',
        'publication_date',
        'publication_place',
        'authors',
        'previous_registrations',
        'derivative_works',
        'registration_number',
        'registration_date',
    ];

    protected $casts = [
        'creation_year' => 'integer',
        'is_published' => 'boolean',
        'publication_date' => 'date',
        'registration_date' => 'date',
    ];

    /**
     * Get the submission that owns the copyright detail.
     */
    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }
}
