<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Content Decay Signal Model for SPEC-036.
 *
 * Stores comparison windows (previous vs current) for each detected decay candidate.
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
        'previous_impressions',
        'previous_ctr',
        'previous_avg_position',
        'previous_rank_visibility',
        'previous_ai_visibility',
        'current_clicks',
        'current_impressions',
        'current_ctr',
        'current_avg_position',
        'current_rank_visibility',
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
        'previous_impressions' => 'integer',
        'previous_ctr' => 'decimal:4',
        'previous_avg_position' => 'decimal:2',
        'previous_rank_visibility' => 'decimal:2',
        'previous_ai_visibility' => 'decimal:2',
        'current_clicks' => 'integer',
        'current_impressions' => 'integer',
        'current_ctr' => 'decimal:4',
        'current_avg_position' => 'decimal:2',
        'current_rank_visibility' => 'decimal:2',
        'current_ai_visibility' => 'decimal:2',
        'unresolved_audit_issues' => 'integer',
        'content_age_days' => 'integer',
        'last_annotation_at' => 'datetime',
        'evidence_json' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the candidate this signal belongs to.
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(ContentDecayCandidate::class, 'candidate_id');
    }

    /**
     * Calculate click change percentage.
     */
    public function getClickChangePercent(): ?float
    {
        if ($this->previous_clicks == 0) {
            return null;
        }

        return (($this->current_clicks - $this->previous_clicks) / $this->previous_clicks) * 100;
    }

    /**
     * Calculate impression change percentage.
     */
    public function getImpressionChangePercent(): ?float
    {
        if ($this->previous_impressions == 0) {
            return null;
        }

        return (($this->current_impressions - $this->previous_impressions) / $this->previous_impressions) * 100;
    }

    /**
     * Calculate position change.
     */
    public function getPositionChange(): ?float
    {
        if ($this->previous_avg_position === null || $this->current_avg_position === null) {
            return null;
        }

        // Negative = improved, Positive = declined
        return $this->current_avg_position - $this->previous_avg_position;
    }

    /**
     * Calculate CTR change.
     */
    public function getCtrChange(): ?float
    {
        if ($this->previous_ctr === null || $this->current_ctr === null) {
            return null;
        }

        return $this->current_ctr - $this->previous_ctr;
    }

    /**
     * Get lost clicks (previous - current).
     */
    public function getLostClicks(): int
    {
        return max(0, $this->previous_clicks - $this->current_clicks);
    }

    /**
     * Check if signal indicates significant decay.
     */
    public function hasSignificantDecay(float $threshold = 20.0): bool
    {
        $clickChange = $this->getClickChangePercent();

        return $clickChange !== null && $clickChange < -$threshold;
    }

    /**
     * Scope for signals with click decay.
     */
    public function scopeWithClickDecay($query, float $minPercent = 20.0)
    {
        // This would need raw SQL for the percentage calculation
        return $query->whereColumn('current_clicks', '<', 'previous_clicks');
    }

    /**
     * Scope for signals within a date range.
     */
    public function scopeInDateRange($query, string $startDate, string $endDate)
    {
        return $query->where('current_start', '>=', $startDate)
            ->where('current_end', '<=', $endDate);
    }
}
