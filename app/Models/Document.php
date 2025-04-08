<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'documents';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uri',
        'title',
        'mimetype',
        'size',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'size' => 'integer',
    ];

    /**
     * Get the submission documents that use this document.
     */
    public function submissionDocuments()
    {
        return $this->hasMany(SubmissionDocument::class);
    }

    /**
     * Get the tracking history entries that reference this document.
     */
    public function trackingHistory()
    {
        return $this->hasMany(TrackingHistory::class);
    }

    /**
     * Get the file extension.
     */
    public function getExtensionAttribute()
    {
        return pathinfo($this->uri, PATHINFO_EXTENSION);
    }

    /**
     * Get the file size formatted for human reading.
     */
    public function getHumanSizeAttribute()
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->size;
        $i = 0;

        while ($size >= 1024 && $i < 4) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2) . ' ' . $units[$i];
    }
}
