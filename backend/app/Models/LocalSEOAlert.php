<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocalSEOAlert extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'local_seo_alerts';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that should be mutated to dates.
     */
    protected $dates = [
        'created_at',
        'read_at',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'location_id',
        'alert_type',
        'severity',
        'title',
        'message',
        'data',
        'is_read',
        'read_at',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    /**
     * Valid alert types.
     */
    public const ALERT_TYPES = [
        'ranking_improved',
        'ranking_dropped',
        'new_review',
        'negative_review',
        'rating_dropped',
        'competitor_overtake',
        'gbp_disconnected',
    ];

    /**
     * Valid severity levels.
     */
    public const SEVERITY_LEVELS = [
        'info',
        'warning',
        'critical',
    ];

    /**
     * Alias matching the original task acceptance wording.
     */
    public const SEVERITIES = self::SEVERITY_LEVELS;

    /**
     * Get the user that owns this alert.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the business location for this alert.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    /**
     * Alias for the task/spec naming used in acceptance criteria.
     */
    public function businessLocation(): BelongsTo
    {
        return $this->location();
    }

    /**
     * Scope to get only unread alerts.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope to get only read alerts.
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope to filter by alert type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('alert_type', $type);
    }

    /**
     * Alias matching the task acceptance wording.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('alert_type', $type);
    }

    /**
     * Scope to filter by severity.
     */
    public function scopeOfSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope to get only critical alerts.
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    /**
     * Scope to get only warning alerts.
     */
    public function scopeWarning($query)
    {
        return $query->where('severity', 'warning');
    }

    /**
     * Scope to get only info alerts.
     */
    public function scopeInfo($query)
    {
        return $query->where('severity', 'info');
    }

    /**
     * Scope to filter by location.
     */
    public function scopeForLocation($query, int $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    /**
     * Mark the alert as read.
     */
    public function markAsRead(): bool
    {
        if ($this->is_read) {
            return true;
        }

        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Check if the alert is read.
     */
    public function isRead(): bool
    {
        return $this->is_read;
    }

    /**
     * Check if the alert is unread.
     */
    public function isUnread(): bool
    {
        return !$this->is_read;
    }

    /**
     * Alias for location_id (for test compatibility).
     */
    public function getBusinessLocationIdAttribute(): int
    {
        return $this->location_id;
    }
}
