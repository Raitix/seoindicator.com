<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * KeywordSuggestion model for keyword discovery feature.
 *
 * Stores suggested keywords from various sources (Google Suggest, Questions, etc.)
 * with opportunity scores to help users prioritize which keywords to track.
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
 * @property \Illuminate\Support\Carbon $discovered_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @see TASK-5-2
 * @see SPEC-005
 */
class KeywordSuggestion extends Model
{
    use HasFactory;

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
        'discovered_at' => 'datetime',
        'search_volume' => 'integer',
        'difficulty_score' => 'integer',
        'opportunity_score' => 'integer',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the site that owns this suggestion.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to filter by site.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $siteId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySite($query, int $siteId)
    {
        return $query->where('site_id', $siteId);
    }

    /**
     * Scope to filter by source.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $source
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope to exclude dismissed suggestions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotDismissed($query)
    {
        return $query->where('is_dismissed', false);
    }

    /**
     * Scope to exclude tracking suggestions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotTracking($query)
    {
        return $query->where('is_tracking', false);
    }

    /**
     * Scope to filter by minimum opportunity score.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $minScore
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHighOpportunity($query, int $minScore = 70)
    {
        return $query->where('opportunity_score', '>=', $minScore);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get the tracking status of the suggestion.
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
}
