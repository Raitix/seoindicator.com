<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Report model for generated SEO reports.
 *
 * @property int $id
 * @property int $site_id
 * @property string $name
 * @property string $type
 * @property string $format
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $date_range_start
 * @property \Illuminate\Support\Carbon|null $date_range_end
 * @property \Illuminate\Support\Carbon|null $compare_range_start
 * @property \Illuminate\Support\Carbon|null $compare_range_end
 * @property array|null $sections
 * @property \Illuminate\Support\Carbon|null $generated_at
 * @property string|null $file_path
 * @property int|null $file_size
 * @property string|null $error
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property string $delivery_status
 * @property array|null $delivery_log
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Site $site
 * @property-read string $status_label
 */
class Report extends Model
{
    use HasFactory;

    public const STATUSES = [
        'pending' => 'Pending',
        'generating' => 'Generating',
        'completed' => 'Completed',
        'failed' => 'Failed',
    ];

    public const DELIVERY_STATUSES = [
        'pending' => 'Pending Delivery',
        'sent' => 'Sent',
        'partial' => 'Partially Sent',
        'failed' => 'Delivery Failed',
    ];

    /**
     * Maximum attachment size in bytes (10MB)
     */
    public const MAX_ATTACHMENT_SIZE = 10 * 1024 * 1024;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'site_id',
        'name',
        'type',
        'format',
        'status',
        'date_range_start',
        'date_range_end',
        'compare_range_start',
        'compare_range_end',
        'sections',
        'generated_at',
        'file_path',
        'file_size',
        'error',
        'delivered_at',
        'delivery_status',
        'delivery_log',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sections' => 'array',
        'date_range_start' => 'date',
        'date_range_end' => 'date',
        'compare_range_start' => 'date',
        'compare_range_end' => 'date',
        'generated_at' => 'datetime',
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'file_size' => 'integer',
        'delivery_log' => 'array',
    ];

    /**
     * Get the site that owns this report.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Human-readable status label accessor.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status ?? '');
    }
}
