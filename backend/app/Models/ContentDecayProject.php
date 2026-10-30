<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Content Decay Project model.
 * 
 * Stores project-level settings for monitoring content decay on sites.
 * One project per site (unique constraint).
 *
 * @see SPEC-036: Content Decay Detection & Refresh Planner
 * @see TASK-36-2: Eloquent Models - Content Decay
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
        'last_digest_sent_at',
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
        'digest_frequency' => 'string',
        'settings_json' => 'array',
        'last_scanned_at' => 'datetime',
        'last_digest_sent_at' => 'datetime',
    ];

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * Get the user that owns this project.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the site this project belongs to.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the decay candidates for this project.
     */
    public function candidates(): HasMany
    {
        return $this->hasMany(ContentDecayCandidate::class, 'project_id');
    }

    // ==========================================
    // Scopes
    // ==========================================

    /**
     * Scope to get only projects with detection enabled.
     */
    public function scopeEnabled($query)
    {
        return $query->where('detection_enabled', true);
    }

    /**
     * Scope to get projects for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get projects with digest enabled.
     */
    public function scopeDigestEnabled($query)
    {
        return $query->where('digest_enabled', true);
    }

    /**
     * Scope to get projects that need scanning.
     */
    public function scopeNeedsScan($query)
    {
        return $query->whereNull('last_scanned_at')
            ->orWhere('last_scanned_at', '<=', now()->subDay());
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * Check if detection is enabled.
     */
    public function isDetectionEnabled(): bool
    {
        return (bool) $this->detection_enabled;
    }

    /**
     * Get the window days for decay comparison.
     */
    public function getWindowDays(): int
    {
        return (int) $this->window_days;
    }

    /**
     * Get detection thresholds as an array.
     * 
     * Merges column values with settings_json for flexibility.
     * Column values take precedence over settings_json values.
     */
    public function getThresholds(): array
    {
        $settings = $this->settings_json ?? [];
        
        return [
            'min_clicks' => $this->min_clicks_threshold 
                ?? $settings['min_clicks_threshold'] 
                ?? $settings['min_clicks'] 
                ?? 10,
            'min_impressions' => $this->min_impressions_threshold 
                ?? $settings['min_impressions_threshold'] 
                ?? $settings['min_impressions'] 
                ?? 100,
            'min_decay_score' => (float) ($this->min_decay_score 
                ?? $settings['min_decay_score'] 
                ?? 0),
        ];
    }

    /**
     * Determine if digest should be sent.
     */
    public function shouldSendDigest(): bool
    {
        return $this->digest_enabled && in_array($this->digest_frequency, ['weekly', 'monthly']);
    }

    /**
     * Get a value from the settings JSON.
     */
    public function getSettingsValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings_json, $key, $default);
    }
}
