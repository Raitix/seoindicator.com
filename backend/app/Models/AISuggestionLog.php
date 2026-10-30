<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AISuggestionLog model for tracking AI suggestion requests.
 *
 * Stores logs for debugging, analytics, and performance monitoring
 * of AI-powered SEO suggestions.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $session_id
 * @property string $indicator
 * @property string $provider
 * @property string|null $model
 * @property int|null $prompt_tokens
 * @property int|null $completion_tokens
 * @property int $latency_ms
 * @property bool $success
 * @property string|null $error_message
 * @property string|null $request_hash
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read User|null $user
 */
class AISuggestionLog extends Model
{
    /** @use HasFactory<\Database\Factories\AISuggestionLogFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'ai_suggestion_logs';

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
        'user_id',
        'session_id',
        'indicator',
        'provider',
        'model',
        'prompt_tokens',
        'completion_tokens',
        'latency_ms',
        'success',
        'error_message',
        'request_hash',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'latency_ms' => 'integer',
        'success' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Get the user that owns the log entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include logs for a specific indicator.
     */
    public function scopeForIndicator(\Illuminate\Database\Eloquent\Builder $query, string $indicator): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('indicator', $indicator);
    }

    /**
     * Scope a query to only include logs for a specific provider.
     */
    public function scopeForProvider(\Illuminate\Database\Eloquent\Builder $query, string $provider): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope a query to only include successful requests.
     */
    public function scopeSuccessful(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('success', true);
    }

    /**
     * Scope a query to only include failed requests.
     */
    public function scopeFailed(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('success', false);
    }

    /**
     * Scope a query to only include logs for a specific user.
     */
    public function scopeForUser(\Illuminate\Database\Eloquent\Builder $query, ?int $userId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include logs for a guest (no user).
     */
    public function scopeGuests(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNull('user_id');
    }
}
