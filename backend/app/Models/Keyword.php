<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Keyword model for tracking keyword rankings.
 *
 * Represents a keyword that a user wants to track
 * in search engine results.
 *
 * @property int $id
 * @property int $user_id
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
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\CompetitorRanking[] $competitorRankings
 */
class Keyword extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'keyword',
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

    /**
     * Get the user that owns this keyword.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the competitor rankings for this keyword.
     */
    public function competitorRankings(): HasMany
    {
        return $this->hasMany(CompetitorRanking::class);
    }
}
