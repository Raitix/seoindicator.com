<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Content Decay Candidate Model for SPEC-036.
 *
 * Stores individual pages detected as having content decay.
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
     * Valid status values.
     */
    public const STATUS_BACKLOG = 'backlog';
    public const STATUS_PLANNED = 'planned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_REFRESHED = 'refreshed';
    public const STATUS_RECOVERED = 'recovered';
    public const STATUS_IGNORED = 'ignored';

    public const VALID_STATUSES = [
        self::STATUS_BACKLOG,
        self::STATUS_PLANNED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_REFRESHED,
        self::STATUS_RECOVERED,
        self::STATUS_IGNORED,
    ];

    /**
     * Valid severity values.
     */
    public const SEVERITY_LOW = 'low';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_CRITICAL = 'critical';

    public const VALID_SEVERITIES = [
        self::SEVERITY_LOW,
        self::SEVERITY_MEDIUM,
        self::SEVERITY_HIGH,
        self::SEVERITY_CRITICAL,
    ];

    /**
     * Valid page types.
     */
    public const VALID_PAGE_TYPES = ['blog', 'product', 'landing', 'category', 'other'];

    /**
     * Status transitions map.
     */
    public const STATUS_TRANSITIONS = [
        self::STATUS_BACKLOG => [self::STATUS_PLANNED, self::STATUS_IGNORED],
        self::STATUS_PLANNED => [self::STATUS_IN_PROGRESS, self::STATUS_BACKLOG, self::STATUS_IGNORED],
        self::STATUS_IN_PROGRESS => [self::STATUS_REFRESHED, self::STATUS_PLANNED],
        self::STATUS_REFRESHED => [self::STATUS_RECOVERED, self::STATUS_IN_PROGRESS],
        self::STATUS_RECOVERED => [], // Terminal state
        self::STATUS_IGNORED => [self::STATUS_BACKLOG], // Can be restored
    ];

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
    public function tasks(): HasMany
    {
        return $this->hasMany(ContentRefreshTask::class, 'candidate_id');
    }

    /**
     * Scope for candidates by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for candidates by severity.
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope for candidates by page type.
     */
    public function scopeByPageType($query, string $pageType)
    {
        return $query->where('page_type', $pageType);
    }

    /**
     * Scope for candidates with minimum decay score.
     */
    public function scopeMinScore($query, float $minScore)
    {
        return $query->where('decay_score', '>=', $minScore);
    }

    /**
     * Scope for candidates with minimum lost clicks.
     */
    public function scopeMinLostClicks($query, int $minClicks)
    {
        return $query->where('lost_click_estimate', '>=', $minClicks);
    }

    /**
     * Scope for searching by URL or title.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('page_url', 'like', "%{$search}%")
                ->orWhere('page_title', 'like', "%{$search}%");
        });
    }

    /**
     * Check if a status transition is valid.
     */
    public function canTransitionTo(string $newStatus): bool
    {
        if (!in_array($newStatus, self::VALID_STATUSES)) {
            return false;
        }

        return in_array($newStatus, self::STATUS_TRANSITIONS[$this->status] ?? []);
    }

    /**
     * Transition to a new status with timestamp.
     */
    public function transitionTo(string $newStatus): bool
    {
        if (!$this->canTransitionTo($newStatus)) {
            return false;
        }

        $updates = ['status' => $newStatus];
        $now = now();

        switch ($newStatus) {
            case self::STATUS_PLANNED:
                $updates['planned_at'] = $now;
                break;
            case self::STATUS_IN_PROGRESS:
                $updates['started_at'] = $now;
                break;
            case self::STATUS_REFRESHED:
                $updates['refreshed_at'] = $now;
                break;
            case self::STATUS_RECOVERED:
                $updates['recovered_at'] = $now;
                break;
            case self::STATUS_IGNORED:
                $updates['ignored_at'] = $now;
                break;
            case self::STATUS_BACKLOG:
                // Clear all status timestamps when returning to backlog
                $updates['planned_at'] = null;
                $updates['started_at'] = null;
                $updates['refreshed_at'] = null;
                $updates['recovered_at'] = null;
                $updates['ignored_at'] = null;
                break;
        }

        $this->update($updates);
        return true;
    }

    /**
     * Check if candidate can be restored.
     */
    public function canBeRestored(): bool
    {
        return $this->status === self::STATUS_IGNORED;
    }

    /**
     * Restore an ignored candidate to backlog.
     */
    public function restore(): bool
    {
        if (!$this->canBeRestored()) {
            return false;
        }

        return $this->transitionTo(self::STATUS_BACKLOG);
    }

    /**
     * Generate URL hash for indexing.
     */
    public static function generateUrlHash(string $url): string
    {
        return hash('sha256', $url);
    }

    /**
     * Get the site through project relationship.
     */
    public function site(): BelongsTo
    {
        return $this->project->site();
    }
}
