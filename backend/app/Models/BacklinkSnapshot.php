<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BacklinkSnapshot model for historical backlink metrics.
 * 
 * Stores daily snapshots of backlink metrics for trend analysis.
 * One snapshot per project per day (unique constraint).
 * 
 * @see TASK-23-1
 * @see SPEC-023
 * 
 * @property int $id
 * @property int $project_id
 * @property int $total_backlinks
 * @property int $referring_domains_count
 * @property int $dofollow_count
 * @property int $nofollow_count
 * @property float $avg_domain_trust
 * @property float $anchor_diversity_score
 * @property \Carbon\Carbon $snapshot_date
 * @property \Carbon\Carbon $created_at
 */
class BacklinkSnapshot extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'snapshot_date' => 'date',
        'created_at' => 'datetime',
        'avg_domain_trust' => 'decimal:2',
        'anchor_diversity_score' => 'decimal:2',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'project_id',
        'total_backlinks',
        'referring_domains_count',
        'dofollow_count',
        'nofollow_count',
        'avg_domain_trust',
        'anchor_diversity_score',
        'snapshot_date',
        'created_at',
    ];

    /**
     * Get the project that owns this snapshot.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Scope a query to only include snapshots for a specific project.
     */
    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, string $from, string $to)
    {
        // Use whereDate for proper date comparison across different database drivers
        return $query->whereDate('snapshot_date', '>=', $from)
                     ->whereDate('snapshot_date', '<=', $to);
    }

    /**
     * Scope a query to order by date ascending (for charting).
     */
    public function scopeOrderByDateAsc($query)
    {
        return $query->orderBy('snapshot_date', 'asc');
    }

    /**
     * Transform the snapshot for API response.
     */
    public function toApiResponse(): array
    {
        return [
            'date' => $this->snapshot_date->toDateString(),
            'total_backlinks' => $this->total_backlinks,
            'referring_domains_count' => $this->referring_domains_count,
            'dofollow_count' => $this->dofollow_count,
            'nofollow_count' => $this->nofollow_count,
            'avg_domain_trust' => (float) $this->avg_domain_trust,
            'anchor_diversity_score' => (float) $this->anchor_diversity_score,
        ];
    }
}
