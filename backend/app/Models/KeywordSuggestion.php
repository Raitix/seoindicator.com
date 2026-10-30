<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * KeywordSuggestion model for keyword discovery feature.
 *
 * Represents a keyword suggestion discovered from various sources
 * (Google Suggest, competitor analysis, related searches).
 *
 * @property int $id
 * @property int $site_id
 * @property string $keyword
 * @property string $source
 * @property int|null $search_volume
 * @property int|null $difficulty_score
 * @property int|null $opportunity_score
 * @property bool $is_tracking
 * @property bool $is_dismissed
 * @property \Illuminate\Support\Carbon|null $discovered_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Site $site
 * @property-read string $tracking_status
 */
class KeywordSuggestion extends Model
{
    use HasFactory;

    public const SOURCES = ['suggest', 'competitor', 'related'];

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_tracking' => false,
        'is_dismissed' => false,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'site_id',
        'keyword',
        'source',
        'search_volume',
        'difficulty_score',
        'opportunity_score',
        'is_tracking',
        'is_dismissed',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_tracking' => 'boolean',
        'is_dismissed' => 'boolean',
        'search_volume' => 'integer',
        'difficulty_score' => 'integer',
        'opportunity_score' => 'integer',
        'discovered_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The name of the "created at" column.
     *
     * @var string|null
     */
    const CREATED_AT = null;

    /**
     * Set default discovered_at on model creation.
     */
    protected static function booted(): void
    {
        static::creating(function (self $suggestion) {
            if (!$suggestion->discovered_at) {
                $suggestion->discovered_at = now();
            }
        });
    }

    /**
     * Get the site that owns this suggestion.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the tracking status based on suggestion state.
     *
     * @return string 'tracking', 'dismissed', or 'available'
     */
    public function getTrackingStatusAttribute(): string
    {
        if ($this->is_tracking) {
            return 'tracking';
        }

        if ($this->is_dismissed) {
            return 'dismissed';
        }

        return 'available';
    }

    // =========================================================================
    // QUERY SCOPES
    // =========================================================================

    /**
     * Scope suggestions by site.
     */
    public function scopeBySite(Builder $query, int $siteId): Builder
    {
        return $query->where('site_id', $siteId);
    }

    /**
     * Scope suggestions by source.
     */
    public function scopeBySource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    /**
     * Scope suggestions that are not dismissed.
     */
    public function scopeNotDismissed(Builder $query): Builder
    {
        return $query->where('is_dismissed', false);
    }

    /**
     * Scope suggestions that are not being tracked.
     */
    public function scopeNotTracking(Builder $query): Builder
    {
        return $query->where('is_tracking', false);
    }

    /**
     * Scope suggestions with high opportunity score.
     *
     * @param int $minScore Minimum opportunity score (default: 70)
     */
    public function scopeHighOpportunity(Builder $query, int $minScore = 70): Builder
    {
        return $query->where('opportunity_score', '>=', $minScore);
    }
}
