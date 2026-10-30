<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BadgeImpression model for tracking embedded badge analytics.
 *
 * Tracks views and clicks of embedded SEO audit badges on third-party sites.
 * This allows measuring the reach and engagement of public audit widgets.
 *
 * @property int $id
 * @property int $audit_id
 * @property string $public_hash
 * @property string|null $referrer_domain
 * @property \Illuminate\Support\Carbon $impression_at
 * @property \Illuminate\Support\Carbon|null $clicked_at
 * @property string|null $click_target
 * @property string|null $ip_hash
 * @property-read \App\Models\Audit $audit
 */
class BadgeImpression extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'badge_impressions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'audit_id',
        'public_hash',
        'referrer_domain',
        'impression_at',
        'clicked_at',
        'click_target',
        'ip_hash',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'impression_at' => 'datetime',
            'clicked_at' => 'datetime',
        ];
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'ip_hash',
    ];

    /**
     * The attributes that are guarded.
     *
     * @var array<int, string>
     */
    protected $guarded = [
        'id',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    // =========================================================================
    // Boot
    // =========================================================================

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-set impression_at if not provided
        static::creating(function (self $impression) {
            if (!$impression->impression_at) {
                $impression->impression_at = now();
            }
        });
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the audit that owns this impression.
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope for impressions belonging to a specific audit.
     */
    public function scopeForAudit($query, int $auditId)
    {
        return $query->where('audit_id', $auditId);
    }

    /**
     * Scope for impressions with a specific public hash.
     */
    public function scopeForHash($query, string $hash)
    {
        return $query->where('public_hash', $hash);
    }

    /**
     * Scope for impressions that have been clicked.
     */
    public function scopeClicked($query)
    {
        return $query->whereNotNull('clicked_at');
    }

    /**
     * Scope for impressions that have not been clicked.
     */
    public function scopeNotClicked($query)
    {
        return $query->whereNull('clicked_at');
    }

    /**
     * Scope for impressions with a specific click target.
     */
    public function scopeWithClickTarget($query, string $target)
    {
        return $query->where('click_target', $target);
    }

    /**
     * Scope for impressions within the last X days.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('impression_at', '>=', now()->subDays($days));
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    /**
     * Check if the impression has been clicked.
     */
    public function getIsClickedAttribute(): bool
    {
        return $this->clicked_at !== null;
    }

    // =========================================================================
    // Static Helper Methods
    // =========================================================================

    /**
     * Hash an IP address for privacy-compliant storage.
     *
     * Uses SHA-256 with the application key as salt for irreversible hashing.
     */
    public static function hashIp(string $ip): string
    {
        return hash('sha256', $ip . config('app.key'));
    }

    /**
     * Extract the domain from a referer header.
     *
     * @param string|null $referer The HTTP Referer header value
     * @return string|null The domain only (e.g., "example.com")
     */
    public static function extractDomain(?string $referer): ?string
    {
        if (!$referer) {
            return null;
        }

        $parsed = parse_url($referer, PHP_URL_HOST);

        return $parsed ?: null;
    }

    /**
     * Record a new badge impression.
     *
     * @param Audit $audit The audit being viewed
     * @param string|null $referer The HTTP Referer header
     * @param string|null $clientIp The client IP address
     * @return static
     */
    public static function recordImpression(Audit $audit, ?string $referer = null, ?string $clientIp = null): static
    {
        return static::create([
            'audit_id' => $audit->id,
            'public_hash' => $audit->public_hash,
            'referrer_domain' => static::extractDomain($referer),
            'impression_at' => now(),
            'ip_hash' => $clientIp ? static::hashIp($clientIp) : null,
        ]);
    }

    /**
     * Record a click event for an existing impression.
     *
     * @param string $target Where the click led ('audit' or 'home')
     * @return bool
     */
    public function recordClick(string $target = 'audit'): bool
    {
        if (!in_array($target, ['audit', 'home'])) {
            return false;
        }

        return $this->update([
            'clicked_at' => now(),
            'click_target' => $target,
        ]);
    }
}
