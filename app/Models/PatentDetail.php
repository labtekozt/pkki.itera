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
        'patent_type',
        'invention_description',
        'technical_field',
        'background',
        'patent_status',
        'inventor_details',
        'filing_date',
        'application_number',
        'publication_date',
        'publication_number',
    ];

    protected $casts = [
        'filing_date' => 'date',
        'publication_date' => 'date',
    ];

    /**
     * Get the submission that owns the patent detail.
     */
    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }
}
