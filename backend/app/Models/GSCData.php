<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * GSC Data Model for storing Google Search Console metrics.
 *
 * Stores clicks, impressions, CTR, and position data per query and date.
 *
 * @property int $id
 * @property int $site_id
 * @property string $query
 * @property string|null $page
 * @property int $clicks
 * @property int $impressions
 * @property float $ctr
 * @property float $avg_position
 * @property Carbon $data_date
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class GSCData extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'gsc_data';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'site_id',
        'query',
        'page',
        'clicks',
        'impressions',
        'ctr',
        'avg_position',
        'data_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_date' => 'date',
            'clicks' => 'integer',
            'impressions' => 'integer',
            'ctr' => 'float',
            'avg_position' => 'float',
        ];
    }

    /**
     * Get the project that owns this GSC data.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Calculate CTR percentage for display.
     */
    public function getCTRPercentageAttribute(): string
    {
        return number_format($this->ctr * 100, 2) . '%';
    }

    /**
     * Get total engagement (clicks + impressions weighted).
     */
    public function getEngagementScoreAttribute(): float
    {
        // Weighted score: clicks are more valuable than impressions
        return ($this->clicks * 2) + ($this->impressions * 0.1);
    }

    /**
     * Check if data is recent (within last 7 days).
     */
    public function isRecent(): bool
    {
        return $this->data_date->diffInDays(now()) <= 7;
    }

    /**
     * Scope to get data for a specific project.
     */
    public function scopeForSite($query, int $siteId)
    {
        return $query->where('site_id', $siteId);
    }

    /**
     * Scope to get data within a date range.
     */
    public function scopeDateRange($query, Carbon $from, Carbon $to)
    {
        return $query->whereBetween('data_date', [$from, $to]);
    }

    /**
     * Scope to get data for specific queries.
     */
    public function scopeForQueries($query, array $queries)
    {
        return $query->whereIn('query', $queries);
    }

    /**
     * Scope to get top performing queries by clicks.
     */
    public function scopeTopByClicks($query, int $limit = 10)
    {
        return $query->orderByDesc('clicks')->limit($limit);
    }

    /**
     * Scope to get top performing queries by impressions.
     */
    public function scopeTopByImpressions($query, int $limit = 10)
    {
        return $query->orderByDesc('impressions')->limit($limit);
    }

    /**
     * Scope to get best performing queries by CTR.
     */
    public function scopeTopByCTR($query, int $limit = 10)
    {
        return $query->orderByDesc('ctr')->limit($limit);
    }

    /**
     * Scope to get best performing queries by position.
     */
    public function scopeTopByPosition($query, int $limit = 10)
    {
        return $query->orderBy('avg_position')->limit($limit);
    }

    /**
     * Scope to get data that needs refresh (older than 3 days).
     */
    public function scopeNeedsRefresh($query)
    {
        return $query->where('data_date', '<', now()->subDays(3));
    }

    /**
     * Scope to get opportunity keywords (high impressions, low CTR, good position).
     */
    public function scopeOpportunities($query, int $impressionThreshold = 1000)
    {
        return $query->where('impressions', '>=', $impressionThreshold)
            ->where('ctr', '<', 0.03) // Low CTR
            ->where('avg_position', '<=', 10); // Page 1 or 2
    }
}