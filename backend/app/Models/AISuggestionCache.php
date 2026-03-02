<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * AISuggestionCache model for caching AI suggestion responses.
 *
 * Stores cached AI responses to reduce API calls and improve
 * response times for repeated identical requests.
 *
 * @property int $id
 * @property string $request_hash
 * @property string $indicator
 * @property string $context_hash
 * @property string $response_json
 * @property string $provider
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $expires_at
 */
class AISuggestionCache extends Model
{
    /** @use HasFactory<\Database\Factories\AISuggestionCacheFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'ai_suggestions_cache';

    /**
     * Indicates if the model should be timestamped.
     * Set to true to allow Eloquent to manage created_at automatically.
     */
    public $timestamps = true;

    /**
     * The name of the "created at" column.
     */
    const CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
     * Set to null since we don't have an updated_at column.
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'request_hash',
        'indicator',
        'context_hash',
        'response_json',
        'provider',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'response_json' => 'string',
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Scope a query to only include expired cache entries.
     */
    public function scopeExpired(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope a query to only include valid (non-expired) cache entries.
     */
    public function scopeValid(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('expires_at', '>=', now());
    }

    /**
     * Scope a query to only include cache for a specific indicator.
     */
    public function scopeForIndicator(\Illuminate\Database\Eloquent\Builder $query, string $indicator): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('indicator', $indicator);
    }

    /**
     * Scope a query to only include cache for a specific provider.
     */
    public function scopeForProvider(\Illuminate\Database\Eloquent\Builder $query, string $provider): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('provider', $provider);
    }

    /**
     * Check if the cache entry is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the cache entry is still valid.
     */
    public function isValid(): bool
    {
        return $this->expires_at->isFuture();
    }

    /**
     * Get the remaining time until expiration in seconds.
     */
    public function remainingTtl(): int
    {
        return max(0, now()->diffInSeconds($this->expires_at));
    }

    /**
     * Find a valid cache entry by request hash.
     */
    public static function findValidByHash(string $hash): ?self
    {
        return static::where('request_hash', $hash)
            ->valid()
            ->first();
    }

    /**
     * Clean up expired cache entries.
     *
     * @return int Number of deleted entries
     */
    public static function cleanupExpired(): int
    {
        return static::expired()->delete();
    }
}
