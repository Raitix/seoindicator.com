<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PublicAuditView model for tracking analytics on public audit pages.
 *
 * TASK-33-2: Create Public Audit Views Table
 *
 * @property int $id
 * @property int $audit_id
 * @property string $public_hash
 * @property \Illuminate\Support\Carbon $viewed_at
 * @property string|null $referrer
 * @property string|null $ip_hash
 * @property string|null $user_agent
 * @property string|null $country_code
 * @property bool $is_lead_capture
 * @property string|null $lead_email
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\Audit $audit
 */
class PublicAuditView extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'public_audit_views';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'audit_id',
        'public_hash',
        'viewed_at',
        'referrer',
        'ip_hash',
        'user_agent',
        'country_code',
        'is_lead_capture',
        'lead_email',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_lead_capture' => 'boolean',
            'viewed_at' => 'datetime',
        ];
    }

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_lead_capture' => false,
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the audit that owns this view.
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope for filtering by audit ID.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $auditId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForAudit($query, int $auditId)
    {
        return $query->where('audit_id', $auditId);
    }

    /**
     * Scope for filtering by public hash.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $hash
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForHash($query, string $hash)
    {
        return $query->where('public_hash', $hash);
    }

    /**
     * Scope for filtering lead captures only.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLeadCaptures($query)
    {
        return $query->where('is_lead_capture', true);
    }

    /**
     * Scope for filtering records with lead email.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithLeadEmail($query)
    {
        return $query->whereNotNull('lead_email');
    }

    /**
     * Scope for filtering by viewed_at date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \DateTimeInterface|string $start
     * @param \DateTimeInterface|string $end
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeViewedBetween($query, $start, $end)
    {
        return $query->whereBetween('viewed_at', [$start, $end]);
    }

    /**
     * Scope for filtering by country code.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $countryCode
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFromCountry($query, string $countryCode)
    {
        return $query->where('country_code', strtoupper($countryCode));
    }

    /**
     * Scope for filtering recent views.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days Number of days to look back
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('viewed_at', '>=', now()->subDays($days));
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    /**
     * Get masked email for privacy display.
     *
     * @return string|null
     */
    public function getMaskedEmailAttribute(): ?string
    {
        if (!$this->lead_email) {
            return null;
        }

        $parts = explode('@', $this->lead_email);
        if (count($parts) !== 2) {
            return null;
        }

        [$local, $domain] = $parts;
        $localLength = strlen($local);

        if ($localLength <= 2) {
            $masked = $local[0] . '*' . $local[$localLength - 1];
        } else {
            $masked = $local[0] . str_repeat('*', $localLength - 2) . $local[$localLength - 1];
        }

        return $masked . '@' . $domain;
    }

    /**
     * Check if this view has a lead email.
     *
     * @return bool
     */
    public function getHasLeadEmailAttribute(): bool
    {
        return !empty($this->lead_email);
    }
}
