<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrademarkDetail extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'submission_id',
        'trademark_type',
        'description',
        'goods_services_description',
        'nice_classes',
        'has_color_claim',
        'color_description',
        'first_use_date',
        'registration_number',
        'registration_date',
        'expiration_date',
    ];

    protected $casts = [
        'has_color_claim' => 'boolean',
        'first_use_date' => 'date',
        'registration_date' => 'date',
        'expiration_date' => 'date',
    ];

    /**
     * Get the submission that owns the trademark detail.
     */
    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }
}
