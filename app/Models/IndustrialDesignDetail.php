<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndustrialDesignDetail extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'submission_id',
        'design_type',
        'design_description',
        'novelty_statement',
        'designer_information',
        'locarno_class',
        'filing_date',
        'application_number',
        'registration_date',
        'registration_number',
        'expiration_date',
    ];

    protected $casts = [
        'filing_date' => 'date',
        'registration_date' => 'date',
        'expiration_date' => 'date',
    ];

    /**
     * Get the submission that owns the industrial design detail.
     */
    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }
}
