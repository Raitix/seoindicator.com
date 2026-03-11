<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Content Decay Candidate model.
 * 
 * Stores individual pages detected as having content decay.
 *
 * @see SPEC-036: Content Decay Detection & Refresh Planner
 * @see TASK-36-2: Eloquent Models - Content Decay
 */
class ContentDecayCandidate extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'content_decay_candidates';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'project_id',
        'page_url',
        'page_url_hash',
        'page_title',
        'page_type',
        'severity',
        'decay_score',
        'detection_confidence',
        'lost_click_estimate',
        'recovery_upside_estimate',
        'primary_reason_code',
        'reason_codes',
        'signal_sources',
        'evidence_json',
        'status',
        'first_detected_at',
        'last_detected_at',
        'planned_at',
        'started_at',
        'refreshed_at',
        'recovered_at',
        'ignored_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'decay_score' => 'decimal:2',
        'detection_confidence' => 'decimal:2',
        'lost_click_estimate' => 'integer',
        'recovery_upside_estimate' => 'integer',
        'reason_codes' => 'array',
        'signal_sources' => 'array',
        'evidence_json' => 'array',
        'first_detected_at' => 'datetime',
        'last_detected_at' => 'datetime',
        'planned_at' => 'datetime',
        'started_at' => 'datetime',
        'refreshed_at' => 'datetime',
        'recovered_at' => 'datetime',
        'ignored_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'page_url_hash',
    ];

    // ==========================================
    // Boot
    // ==========================================

    protected static function booted(): void
    {
        static::creating(function (self $candidate): void {
            // Auto-generate page_url_hash if not set
            if (empty($candidate->page_url_hash) && !empty($candidate->page_url)) {
                $candidate->page_url_hash = hash('sha256', $candidate->page_url);
            }
        });
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * Get the project this candidate belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(ContentDecayProject::class, 'project_id');
    }

    /**
     * Get the signals for this candidate.
     */
    public function signals(): HasMany
    {
        return $this->hasMany(ContentDecaySignal::class, 'candidate_id');
    }

    /**
     * Get the refresh tasks for this candidate.
     */
    public function refreshTasks(): HasMany
    {
        return $this->hasMany(ContentRefreshTask::class, 'candidate_id');
    }

    // ==========================================
    // Scopes
    // ==========================================

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by severity.
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope to filter by page type.
     */
    public function scopeByPageType($query, string $type)
    {
        return $query->where('page_type', $type);
    }

    /**
     * Scope to filter by minimum decay score.
     */
    public function scopeMinimumScore($query, float $score)
    {
        return $query->where('decay_score', '>=', $score);
    }

    /**
     * Scope to get backlog candidates.
     */
    public function scopeBacklog($query)
    {
        return $query->where('status', 'backlog');
    }

    /**
     * Scope to get planned candidates.
     */
    public function scopePlanned($query)
    {
        return $query->where('status', 'planned');
    }

    /**
     * Scope to get in-progress candidates.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope to get refreshed candidates.
     */
    public function scopeRefreshed($query)
    {
        return $query->where('status', 'refreshed');
    }

    /**
     * Scope to get recovered candidates.
     */
    public function scopeRecovered($query)
    {
        return $query->where('status', 'recovered');
    }

    /**
     * Scope to get ignored candidates.
     */
    public function scopeIgnored($query)
    {
        return $query->where('status', 'ignored');
    }

    // ==========================================
    // Accessors
    // ==========================================

    /**
     * Get the is_recovered attribute.
     */
    public function getIsRecoveredAttribute(): bool
    {
        return $this->status === 'recovered';
    }

    /**
     * Get the severity label.
     */
    public function getSeverityLabelAttribute(): string
    {
        return match ($this->severity) {
            'critical' => 'Critical',
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low',
            default => 'Unknown',
        };
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'backlog' => 'Backlog',
            'planned' => 'Planned',
            'in_progress' => 'In Progress',
            'refreshed' => 'Refreshed',
            'recovered' => 'Recovered',
            'ignored' => 'Ignored',
            default => 'Unknown',
        };
    }

    /**
     * Get the decay score formatted.
     */
    public function getDecayScoreFormattedAttribute(): string
    {
        return number_format((float) $this->decay_score, 2);
    }

    /**
     * Get the primary reason label.
     */
    public function getPrimaryReasonLabelAttribute(): string
    {
        return match ($this->primary_reason_code) {
            'clicks_down' => 'Clicks Declined',
            'impressions_down' => 'Impressions Declined',
            'ctr_down' => 'CTR Declined',
            'avg_position_down' => 'Position Declined',
            'rank_visibility_down' => 'Rank Visibility Declined',
            'ai_visibility_down' => 'AI Visibility Declined',
            default => $this->primary_reason_code,
        };
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * Mark the candidate as planned.
     */
    public function markAsPlanned(): void
    {
        $this->update([
            'status' => 'planned',
            'planned_at' => now(),
        ]);
    }

    /**
     * Mark the candidate as in progress.
     */
    public function markAsInProgress(): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark the candidate as refreshed.
     */
    public function markAsRefreshed(): void
    {
        $this->update([
            'status' => 'refreshed',
            'refreshed_at' => now(),
        ]);
    }

    /**
     * Mark the candidate as recovered.
     */
    public function markAsRecovered(): void
    {
        $this->update([
            'status' => 'recovered',
            'recovered_at' => now(),
        ]);
    }

    /**
     * Mark the candidate as ignored.
     */
    public function markAsIgnored(): void
    {
        $this->update([
            'status' => 'ignored',
            'ignored_at' => now(),
        ]);
    }

    /**
     * Check if the candidate has a specific reason code.
     */
    public function hasReasonCode(string $code): bool
    {
        return in_array($code, $this->reason_codes ?? []);
    }

    /**
     * Get a value from the evidence JSON.
     */
    public function getEvidenceValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->evidence_json, $key, $default);
    }

    /**
     * Check if the candidate is actionable (not ignored or recovered).
     */
    public function isActionable(): bool
    {
        return !in_array($this->status, ['ignored', 'recovered']);
    }

    // ==========================================
    // Mutators
    // ==========================================

    /**
     * Set the page_url_hash attribute.
     * Auto-generates hash from page_url if not provided.
     */
    public function setPageUrlHashAttribute($value): void
    {
        $this->attributes['page_url_hash'] = $value ?? hash('sha256', $this->page_url ?? '');
    }
}
