<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Keyword model for tracking keyword rankings.
 *
 * Represents a keyword that a user wants to track
 * in search engine results.
 *
 * @property int $id
 * @property int|null $site_id
 * @property int|null $user_id
 * @property string $keyword
 * @property string|null $target_url
 * @property string $search_engine
 * @property string $location
 * @property string $device
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $last_checked_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Site $site
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Ranking[] $rankingHistory
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\CompetitorRanking[] $competitorRankings
 */
class Keyword extends Model
{
    use HasFactory;

    public const STATUSES = ['active', 'paused', 'deleted'];
    public const SEARCH_ENGINES = ['google'];
    public const DEVICES = ['desktop'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'site_id',
        'user_id',
        'keyword',
        'gsc_query_match',
        'target_url',
        'search_engine',
        'location',
        'device',
        'status',
        'last_checked_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_checked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $keyword) {
            if (!$keyword->user_id && $keyword->site_id) {
                $site = Site::find($keyword->site_id);
                if ($site) {
                    $keyword->user_id = $site->user_id;
                }
            }
        });
    }

    /**
     * Override delete to soft-delete via status flag.
     */
    public function delete(): bool|null
    {
        $this->status = 'deleted';
        return $this->save();
    }

    /**
     * Get the site that owns this keyword.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the user that owns this keyword.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the ranking history for this keyword.
     */
    public function rankingHistory(): HasMany
    {
        return $this->hasMany(Ranking::class, 'keyword_id');
    }

    /**
     * Get competitor rankings for this keyword.
     */
    public function competitorRankings(): HasMany
    {
        return $this->hasMany(CompetitorRanking::class);
    }

    /**
     * Scope active keywords.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope by site.
     */
    public function scopeBySite(Builder $query, int $siteId): Builder
    {
        return $query->where('site_id', $siteId);
    }

    /**
     * Scope keywords that need a check (daily by default).
     */
    public function scopeNeedsCheck(Builder $query): Builder
    {
        return $query->where(function (Builder $inner) {
            $inner->whereNull('last_checked_at')
                ->orWhere('last_checked_at', '<=', now()->subDay());
        });
    }
}
