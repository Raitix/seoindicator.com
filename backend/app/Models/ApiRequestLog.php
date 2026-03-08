<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * API Request Log model for TASK-6-6.
 *
 * Stores API request logs for analytics and debugging.
 * Logs are retained for 30 days by default (configurable).
 */
class ApiRequestLog extends Model
{
    /**
     * The name of the "updated at" column.
     *
     * @var string|null
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'api_key_id',
        'endpoint',
        'method',
        'status_code',
        'response_time_ms',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'api_key_id' => 'integer',
            'status_code' => 'integer',
            'response_time_ms' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the API key that owns this log.
     */
    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    /**
     * Scope to filter by API key ID.
     */
    public function scopeForApiKey($query, int $apiKeyId)
    {
        return $query->where('api_key_id', $apiKeyId);
    }

    /**
     * Scope to filter by endpoint.
     */
    public function scopeForEndpoint($query, string $endpoint)
    {
        return $query->where('endpoint', $endpoint);
    }

    /**
     * Scope to filter by HTTP method.
     */
    public function scopeForMethod($query, string $method)
    {
        return $query->where('method', strtoupper($method));
    }

    /**
     * Scope to filter successful requests (2xx status codes).
     */
    public function scopeSuccessful($query)
    {
        return $query->whereBetween('status_code', [200, 299]);
    }

    /**
     * Scope to filter failed requests (4xx and 5xx status codes).
     */
    public function scopeFailed($query)
    {
        return $query->where(function ($q) {
            $q->whereBetween('status_code', [400, 499])
                ->orWhereBetween('status_code', [500, 599]);
        });
    }

    /**
     * Scope to filter logs within the retention period.
     */
    public function scopeWithinRetention($query, ?int $days = null)
    {
        $days = $days ?? config('api-logging.retention_days', 30);

        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to filter logs older than the retention period.
     */
    public function scopeOlderThanRetention($query, ?int $days = null)
    {
        $days = $days ?? config('api-logging.retention_days', 30);

        return $query->where('created_at', '<', now()->subDays($days));
    }
}
