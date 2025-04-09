<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandDetail extends Model
{
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'brand_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'submission_id',
        'application_type',
        'application_date',
        'application_origin',
        'application_category',
        'brand_type',
        'brand_label',
        'brand_label_reference',
        'brand_label_description',
        'brand_color_elements',
        'foreign_language_translation',
        'disclaimer',
        'priority_number',
        'nice_classes',
        'goods_services_search',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'application_date' => 'date',
    ];

    /**
     * Get the submission that owns the brand detail.
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
