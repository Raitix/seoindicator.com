<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GSC Connection Model for Google Search Console OAuth integration.
 *
 * Stores OAuth tokens for GSC API access with automatic encryption.
 *
 * @property int $id
 * @property int $user_id
 * @property string $site_url
 * @property string $property_url
 * @property string $access_token
 * @property string $refresh_token
 * @property \Carbon\Carbon $token_expires_at
 * @property string $status
 * @property \Carbon\Carbon|null $last_synced_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class GSCConnection extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'gsc_connections';

    /**
     * Connection status constants.
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REVOKED = 'revoked';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'site_url',
        'property_url',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'status',
        'last_synced_at',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
        ];
    }

    /**
     * Get the user that owns this GSC connection.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the access token has expired.
     */
    public function isTokenExpired(): bool
    {
        return $this->token_expires_at->isPast();
    }

    /**
     * Check if the token will expire within the given minutes.
     */
    public function tokenExpiresWithin(int $minutes): bool
    {
        return $this->token_expires_at->subMinutes($minutes)->isPast();
    }

    /**
     * Check if the connection is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Mark the connection as expired.
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
    }

    /**
     * Mark the connection as revoked.
     */
    public function markAsRevoked(): void
    {
        $this->update(['status' => self::STATUS_REVOKED]);
    }

    /**
     * Update the last synced timestamp.
     */
    public function markAsSynced(): void
    {
        $this->update(['last_synced_at' => now()]);
    }

    /**
     * Update OAuth tokens.
     */
    public function updateTokens(string $accessToken, string $refreshToken, \Carbon\Carbon $expiresAt): void
    {
        $this->update([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_expires_at' => $expiresAt,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Scope to get only active connections.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get connections that need token refresh.
     */
    public function scopeNeedsRefresh($query, int $withinMinutes = 5)
    {
        return $query->active()
            ->where('token_expires_at', '<=', now()->addMinutes($withinMinutes));
    }

    /**
     * Scope to get connections ready for sync.
     */
    public function scopeReadyForSync($query)
    {
        return $query->active();
    }

    /**
     * Get the project associated with this connection.
     */
    public function project()
    {
        return Project::where('user_id', $this->user_id)
            ->where('url', 'like', '%' . $this->site_url . '%')
            ->first();
    }
}
