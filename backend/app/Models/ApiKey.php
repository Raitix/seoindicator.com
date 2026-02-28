<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * API Key model for TASK-6-2 and TASK-6-4.
 *
 * Stores API keys for programmatic access to the API.
 * Keys are stored as SHA-256 hashes, never in plaintext.
 */
class ApiKey extends Model
{
    use HasFactory;

    /**
     * Valid permission values.
     */
    public const VALID_PERMISSIONS = [
        'keywords:read',
        'keywords:write',
        'rankings:read',
        'sites:read',
        'reports:read',
        'reports:write',
        'webhooks:read',
        'webhooks:write',
    ];

    /**
     * Default rate limit (requests per minute).
     */
    public const DEFAULT_RATE_LIMIT = 60;

    /**
     * Minimum rate limit.
     */
    public const MIN_RATE_LIMIT = 1;

    /**
     * Maximum rate limit.
     */
    public const MAX_RATE_LIMIT = 10000;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'key_hash',
        'key_prefix',
        'permissions',
        'rate_limit',
        'last_used_at',
        'expires_at',
        'revoked_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'key_hash',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'rate_limit' => 'integer',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns this API key.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the key is revoked.
     */
    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * Check if the key is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Check if the key is active (not revoked and not expired).
     */
    public function isActive(): bool
    {
        return !$this->isRevoked() && !$this->isExpired();
    }

    /**
     * Scope to get only active keys.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to get only revoked keys.
     */
    public function scopeRevoked($query)
    {
        return $query->whereNotNull('revoked_at');
    }

    /**
     * Scope to get only expired keys.
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return match ($status) {
            'active' => $this->scopeActive($query),
            'revoked' => $this->scopeRevoked($query),
            'expired' => $this->scopeExpired($query),
            default => $query,
        };
    }

    /**
     * Mark the key as used (update last_used_at).
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Revoke the key.
     */
    public function revoke(): void
    {
        if ($this->revoked_at === null) {
            $this->update(['revoked_at' => now()]);
        }
    }
}
