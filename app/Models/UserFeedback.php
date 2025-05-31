<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserFeedback extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'user_feedback';

    protected $fillable = [
        'user_id',
        'page_url',
        'page_title',
        'rating',
        'difficulty_areas',
        'age_range',
        'tech_comfort',
        'device_type',
        'browser_info',
        'comments',
        'contact_permission',
        'session_data',
        'is_critical',
        'processed_at',
        'processed_by',
        'admin_notes',
    ];

    protected $casts = [
        'difficulty_areas' => 'array',
        'session_data' => 'array',
        'browser_info' => 'array',
        'contact_permission' => 'boolean',
        'is_critical' => 'boolean',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'is_critical' => false,
        'contact_permission' => false,
    ];

    /**
     * Get the user who submitted the feedback.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who processed the feedback.
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Scope to get unprocessed feedback.
     */
    public function scopeUnprocessed($query)
    {
        return $query->whereNull('processed_at');
    }

    /**
     * Scope to get critical feedback.
     */
    public function scopeCritical($query)
    {
        return $query->where('is_critical', true);
    }

    /**
     * Scope to get feedback by rating.
     */
    public function scopeByRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope to get feedback within date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Check if feedback is critical (rating <= 2 or contains critical difficulty areas).
     */
    public function isCritical(): bool
    {
        if ($this->rating <= 2) {
            return true;
        }

        $criticalAreas = ['navigation', 'forms', 'error_messages'];
        $difficulties = $this->difficulty_areas ?? [];
        
        return !empty(array_intersect($criticalAreas, $difficulties));
    }

    /**
     * Mark feedback as processed.
     */
    public function markAsProcessed(User $admin, string $notes = null): void
    {
        $this->update([
            'processed_at' => now(),
            'processed_by' => $admin->id,
            'admin_notes' => $notes,
        ]);
    }

    /**
     * Get formatted difficulty areas.
     */
    public function getFormattedDifficultyAreasAttribute(): string
    {
        if (empty($this->difficulty_areas)) {
            return 'Tidak ada kesulitan yang dilaporkan';
        }

        $areaLabels = [
            'navigation' => 'Navigasi/Menu',
            'forms' => 'Mengisi Formulir',
            'file_upload' => 'Upload File',
            'text_size' => 'Ukuran Teks',
            'error_messages' => 'Pesan Error',
            'instructions' => 'Petunjuk/Bantuan',
            'buttons' => 'Tombol',
            'mobile' => 'Penggunaan di HP',
        ];

        $formatted = array_map(function ($area) use ($areaLabels) {
            return $areaLabels[$area] ?? $area;
        }, $this->difficulty_areas);

        return implode(', ', $formatted);
    }

    /**
     * Get age range label.
     */
    public function getAgeRangeLabelAttribute(): string
    {
        $labels = [
            'under_30' => 'Di bawah 30 tahun',
            '30_45' => '30-45 tahun',
            '46_60' => '46-60 tahun',
            '61_70' => '61-70 tahun',
            'over_70' => 'Di atas 70 tahun',
            'prefer_not_say' => 'Tidak ingin menyebutkan',
        ];

        return $labels[$this->age_range] ?? $this->age_range;
    }

    /**
     * Get tech comfort label.
     */
    public function getTechComfortLabelAttribute(): string
    {
        $labels = [
            'beginner' => 'Pemula (jarang menggunakan teknologi)',
            'basic' => 'Dasar (menggunakan WhatsApp, email)',
            'intermediate' => 'Menengah (menggunakan berbagai aplikasi)',
            'advanced' => 'Mahir (nyaman dengan teknologi)',
        ];

        return $labels[$this->tech_comfort] ?? $this->tech_comfort;
    }

    /**
     * Get device type label.
     */
    public function getDeviceTypeLabelAttribute(): string
    {
        $labels = [
            'mobile' => 'HP/Tablet',
            'desktop' => 'Komputer/Laptop',
            'both' => 'Keduanya',
        ];

        return $labels[$this->device_type] ?? $this->device_type;
    }
}
