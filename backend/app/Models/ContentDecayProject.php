<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Content Decay Project Model for SPEC-036.
 *
 * Stores project-level settings for monitoring content decay on sites.
 */
class ContentDecayProject extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'content_decay_projects';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'site_id',
        'detection_enabled',
        'window_days',
        'min_clicks_threshold',
        'min_impressions_threshold',
        'min_decay_score',
        'digest_enabled',
        'digest_frequency',
        'settings_json',
        'last_scanned_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'detection_enabled' => 'boolean',
        'window_days' => 'integer',
        'min_clicks_threshold' => 'integer',
        'min_impressions_threshold' => 'integer',
        'min_decay_score' => 'decimal:2',
        'digest_enabled' => 'boolean',
        'settings_json' => 'array',
        'last_scanned_at' => 'datetime',
    ];

    /**
     * Valid window days options.
     */
    public const VALID_WINDOW_DAYS = [7, 14, 28, 56];

    /**
     * Valid digest frequencies.
     */
    public const VALID_DIGEST_FREQUENCIES = ['weekly', 'monthly'];

    /**
     * Get the user that owns this project.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the site for this project.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the candidates for this project.
     */
    public function candidates(): HasMany
    {
        return $this->hasMany(ContentDecayCandidate::class, 'project_id');
    }

    /**
     * Get candidates by status.
     */
    public function candidatesByStatus(string $status): HasMany
    {
        return $this->candidates()->where('status', $status);
    }

    /**
     * Get candidates by severity.
     */
    public function candidatesBySeverity(string $severity): HasMany
    {
        return $this->candidates()->where('severity', $severity);
    }

    /**
     * Scope for projects with detection enabled.
     */
    public function scopeDetectionEnabled($query)
    {
        return $query->where('detection_enabled', true);
    }

    /**
     * Scope for projects needing scan (based on last_scanned_at).
     */
    public function scopeNeedingScan($query, int $cooldownHours = 24)
    {
        return $query->where(function ($q) use ($cooldownHours) {
            $q->whereNull('last_scanned_at')
                ->orWhere('last_scanned_at', '<', now()->subHours($cooldownHours));
        });
    }

    /**
     * Update last scanned timestamp.
     */
    public function markAsScanned(): void
    {
        $this->update(['last_scanned_at' => now()]);
    }

    /**
     * Get settings with defaults.
     */
    public function getSettings(): array
    {
        return array_merge([
            'detection_enabled' => $this->detection_enabled,
            'window_days' => $this->window_days,
            'min_clicks_threshold' => $this->min_clicks_threshold,
            'min_impressions_threshold' => $this->min_impressions_threshold,
            'min_decay_score' => $this->min_decay_score,
            'digest_enabled' => $this->digest_enabled,
            'digest_frequency' => $this->digest_frequency,
        ], $this->settings_json ?? []);
    }
}
