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
        'design_title',
        'inventors_name',
        'design_type',
        'design_description',
        'novelty_statement',
        'designer_information',
        'locarno_class',
    ];

    /**
     * Get the submission that owns the industrial design detail.
     */
    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }
}
