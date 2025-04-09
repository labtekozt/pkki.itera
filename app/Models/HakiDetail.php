<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HakiDetail extends Model
{
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'haki_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'submission_id',
        'work_type',
        'work_subtype',
        'haki_category',
        'haki_title',
        'work_description',
        'first_publication_date',
        'first_publication_place',
        'is_kkn_output',
        'from_grant_research',
        'self_funded',
        'registration_number',
        'registration_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'first_publication_date' => 'date',
        'registration_date' => 'date',
        'is_kkn_output' => 'boolean',
        'from_grant_research' => 'boolean',
        'self_funded' => 'boolean',
    ];

    /**
     * Get the submission that owns the haki detail.
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
