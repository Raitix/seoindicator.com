<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

/**
 * SocialAccount model for OAuth provider connections.
 *
 * Stores OAuth provider connections for users, supporting multiple
 * providers per user (Google, GitHub, etc.).
 *
 * @property int $id
 * @property int $user_id
 * @property string $provider
 * @property string $provider_id
 * @property string|null $provider_token
 * @property string|null $provider_refresh_token
 * @property \Illuminate\Support\Carbon|null $provider_token_expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @see TASK-25-2
 * @see SPEC-025
 * @see FEAT-025
 */
class SocialAccount extends Model
{
    /** @use HasFactory<\Database\Factories\SocialAccountFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'provider_token',
        'provider_refresh_token',
        'provider_token_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'provider_token',
        'provider_refresh_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider_token' => 'encrypted',
            'provider_refresh_token' => 'encrypted',
            'provider_token_expires_at' => 'datetime',
        ];
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * Get the user that owns this social account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // Scopes
    // ==========================================

    /**
     * Scope to filter by provider.
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter accounts with tokens.
     */
    public function scopeHasToken($query)
    {
        return $query->whereNotNull('provider_token');
    }

    /**
     * Scope to filter accounts with refresh tokens.
     */
    public function scopeHasRefreshToken($query)
    {
        return $query->whereNotNull('provider_refresh_token');
    }

    /**
     * Scope to filter accounts with non-expired tokens.
     */
    public function scopeTokenNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('provider_token_expires_at')
                ->orWhere('provider_token_expires_at', '>', now());
        });
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * Check if the token is expired.
     *
     * @return bool
     */
    public function isTokenExpired(): bool
    {
        if ($this->provider_token_expires_at === null) {
            return true; // No expiration = assume expired
        }

        return $this->provider_token_expires_at->isPast();
    }
}
