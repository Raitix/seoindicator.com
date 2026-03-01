<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CustomDomain Model.
 *
 * Represents a custom domain for white-label reselling.
 *
 * @property int $id
 * @property int $account_id
 * @property string $domain
 * @property bool $dns_verified
 * @property bool $ssl_provisioned
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $verified_at
 *
 * @property-read Account $account
 *
 * @see TASK-15-2
 * @see SPEC-015
 * @see FEAT-015
 */
class CustomDomain extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'domain',
        'dns_verified',
        'ssl_provisioned',
        'verified_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'account_id' => 'integer',
        'dns_verified' => 'boolean',
        'ssl_provisioned' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the account that owns the custom domain.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope to get only verified domains.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVerified($query)
    {
        return $query->where('dns_verified', true);
    }

    /**
     * Scope to get domains with SSL provisioned.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSslReady($query)
    {
        return $query->where('ssl_provisioned', true);
    }

    /**
     * Scope to get pending domains (not yet verified).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('dns_verified', false);
    }

    /**
     * Scope to filter by account.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $accountId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope to filter by domain name.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $domain
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDomain($query, string $domain)
    {
        return $query->where('domain', $domain);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Check if the domain is DNS verified.
     *
     * @return bool
     */
    public function isVerified(): bool
    {
        return $this->dns_verified === true;
    }

    /**
     * Check if SSL is provisioned for the domain.
     *
     * @return bool
     */
    public function isSslReady(): bool
    {
        return $this->ssl_provisioned === true;
    }

    /**
     * Mark the domain as DNS verified.
     *
     * @return void
     */
    public function markAsVerified(): void
    {
        $this->dns_verified = true;
        $this->verified_at = now();
        $this->save();
    }

    /**
     * Mark SSL as provisioned.
     *
     * @return void
     */
    public function markSslProvisioned(): void
    {
        $this->ssl_provisioned = true;
        $this->save();
    }

    /**
     * Unmark DNS verification (reset to pending).
     *
     * @return void
     */
    public function unmarkVerified(): void
    {
        $this->dns_verified = false;
        $this->verified_at = null;
        $this->save();
    }
}
