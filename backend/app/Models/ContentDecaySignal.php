<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Content Decay Signal model.
 * 
 * Stores comparison windows (previous vs current) for each detected decay candidate.
 *
 * @see SPEC-036: Content Decay Detection & Refresh Planner
 * @see TASK-36-2: Eloquent Models - Content Decay
 */
class ContentDecaySignal extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'content_decay_signals';

    /**
     * Indicates if the model should be timestamped.
     * Only created_at is used, not updated_at.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'candidate_id',
        'previous_start',
        'previous_end',
        'current_start',
        'current_end',
        'previous_clicks',
        'current_clicks',
        'previous_impressions',
        'current_impressions',
        'previous_ctr',
        'current_ctr',
        'previous_avg_position',
        'current_avg_position',
        'previous_rank_visibility',
        'current_rank_visibility',
        'previous_ai_visibility',
        'current_ai_visibility',
        'unresolved_audit_issues',
        'content_age_days',
        'last_annotation_at',
        'evidence_json',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'previous_start' => 'date',
        'previous_end' => 'date',
        'current_start' => 'date',
        'current_end' => 'date',
        'previous_clicks' => 'integer',
        'current_clicks' => 'integer',
        'previous_impressions' => 'integer',
        'current_impressions' => 'integer',
        'previous_ctr' => 'decimal:4',
        'current_ctr' => 'decimal:4',
        'previous_avg_position' => 'decimal:2',
        'current_avg_position' => 'decimal:2',
        'previous_rank_visibility' => 'decimal:2',
        'current_rank_visibility' => 'decimal:2',
        'previous_ai_visibility' => 'decimal:2',
        'current_ai_visibility' => 'decimal:2',
        'unresolved_audit_issues' => 'integer',
        'content_age_days' => 'integer',
        'last_annotation_at' => 'datetime',
        'evidence_json' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * The default values for attributes.
     */
    protected $attributes = [
        'previous_clicks' => 0,
        'current_clicks' => 0,
        'previous_impressions' => 0,
        'current_impressions' => 0,
        'previous_ctr' => 0,
        'current_ctr' => 0,
        'unresolved_audit_issues' => 0,
    ];

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * Get the candidate this signal belongs to.
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(ContentDecayCandidate::class, 'candidate_id');
    }

    // ==========================================
    // Scopes
    // ==========================================

    /**
     * Scope to filter by candidate.
     */
    public function scopeForCandidate($query, int $candidateId)
    {
        return $query->where('candidate_id', $candidateId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('previous_start', [$startDate, $endDate])
            ->orWhereBetween('current_start', [$startDate, $endDate]);
    }

    // ==========================================
    // Helper Methods - Delta Calculations
    // ==========================================

    /**
     * Get the click delta (current - previous).
     */
    public function getClickDelta(): int
    {
        return $this->current_clicks - $this->previous_clicks;
    }

    /**
     * Get the impression delta (current - previous).
     */
    public function getImpressionDelta(): int
    {
        return $this->current_impressions - $this->previous_impressions;
    }

    /**
     * Get the CTR delta (current - previous).
     */
    public function getCtrDelta(): float
    {
        return (float) $this->current_ctr - (float) $this->previous_ctr;
    }

    /**
     * Get the average position delta (current - previous).
     * Positive value = worse position, negative = better.
     */
    public function getPositionDelta(): ?float
    {
        if ($this->current_avg_position === null || $this->previous_avg_position === null) {
            return null;
        }

        return (float) $this->current_avg_position - (float) $this->previous_avg_position;
    }

    /**
     * Get the rank visibility delta (current - previous).
     */
    public function getRankVisibilityDelta(): ?float
    {
        if ($this->current_rank_visibility === null || $this->previous_rank_visibility === null) {
            return null;
        }

        return (float) $this->current_rank_visibility - (float) $this->previous_rank_visibility;
    }

    /**
     * Get the AI visibility delta (current - previous).
     */
    public function getAiVisibilityDelta(): ?float
    {
        if ($this->current_ai_visibility === null || $this->previous_ai_visibility === null) {
            return null;
        }

        return (float) $this->current_ai_visibility - (float) $this->previous_ai_visibility;
    }

    /**
     * Get the click delta as a percentage.
     */
    public function getClickDeltaPercent(): ?float
    {
        if ($this->previous_clicks === 0) {
            return null;
        }

        return (($this->current_clicks - $this->previous_clicks) / $this->previous_clicks) * 100;
    }

    /**
     * Get the impression delta as a percentage.
     */
    public function getImpressionDeltaPercent(): ?float
    {
        if ($this->previous_impressions === 0) {
            return null;
        }

        return (($this->current_impressions - $this->previous_impressions) / $this->previous_impressions) * 100;
    }

    /**
     * Check if this signal has audit issues.
     */
    public function hasAuditIssues(): bool
    {
        return $this->unresolved_audit_issues > 0;
    }

    /**
     * Check if the content is stale (older than threshold days).
     */
    public function isStaleContent(int $thresholdDays = 180): bool
    {
        return $this->content_age_days !== null && $this->content_age_days >= $thresholdDays;
    }

    /**
     * Get a value from the evidence JSON.
     */
    public function getEvidenceValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->evidence_json, $key, $default);
    }
}
