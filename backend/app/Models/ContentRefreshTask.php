<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Content Refresh Task Model for SPEC-036.
 *
 * Stores individual refresh tasks for decay candidates.
 */
class ContentRefreshTask extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'content_refresh_tasks';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'candidate_id',
        'title',
        'recommendation_type',
        'description',
        'notes',
        'owner_user_id',
        'due_date',
        'status',
        'source',
        'metadata_json',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'due_date' => 'date',
        'metadata_json' => 'array',
    ];

    /**
     * Valid status values.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const VALID_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    /**
     * Valid recommendation types.
     */
    public const RECOMMENDATION_UPDATE_CONTENT = 'update_content';
    public const RECOMMENDATION_ADD_KEYWORDS = 'add_keywords';
    public const RECOMMENDATION_FIX_TECHNICAL = 'fix_technical';
    public const RECOMMENDATION_UPDATE_META = 'update_meta';
    public const RECOMMENDATION_ADD_INTERNAL_LINKS = 'add_internal_links';
    public const RECOMMENDATION_CONSOLIDATE = 'consolidate';
    public const RECOMMENDATION_REDIRECT = 'redirect';
    public const RECOMMENDATION_OTHER = 'other';

    public const VALID_RECOMMENDATION_TYPES = [
        self::RECOMMENDATION_UPDATE_CONTENT,
        self::RECOMMENDATION_ADD_KEYWORDS,
        self::RECOMMENDATION_FIX_TECHNICAL,
        self::RECOMMENDATION_UPDATE_META,
        self::RECOMMENDATION_ADD_INTERNAL_LINKS,
        self::RECOMMENDATION_CONSOLIDATE,
        self::RECOMMENDATION_REDIRECT,
        self::RECOMMENDATION_OTHER,
    ];

    /**
     * Valid source values.
     */
    public const SOURCE_SYSTEM = 'system';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_AI_SUGGESTION = 'ai_suggestion';

    public const VALID_SOURCES = [
        self::SOURCE_SYSTEM,
        self::SOURCE_MANUAL,
        self::SOURCE_AI_SUGGESTION,
    ];

    /**
     * Status transitions map.
     */
    public const STATUS_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED],
        self::STATUS_IN_PROGRESS => [self::STATUS_COMPLETED, self::STATUS_PENDING, self::STATUS_CANCELLED],
        self::STATUS_COMPLETED => [], // Terminal state
        self::STATUS_CANCELLED => [self::STATUS_PENDING], // Can be reactivated
    ];

    /**
     * Get the candidate this task belongs to.
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(ContentDecayCandidate::class, 'candidate_id');
    }

    /**
     * Get the user assigned to this task.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * Scope for tasks by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for tasks by owner.
     */
    public function scopeByOwner($query, int $userId)
    {
        return $query->where('owner_user_id', $userId);
    }

    /**
     * Scope for overdue tasks.
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    /**
     * Scope for tasks due within a date range.
     */
    public function scopeDueBetween($query, string $from, string $to)
    {
        return $query->whereBetween('due_date', [$from, $to]);
    }

    /**
     * Scope for tasks ordered by due date.
     */
    public function scopeOrderByDueDate($query, string $direction = 'asc')
    {
        return $query->orderBy('due_date', $direction);
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
     * Transition to a new status.
     */
    public function transitionTo(string $newStatus): bool
    {
        if (!$this->canTransitionTo($newStatus)) {
            return false;
        }

        $this->update(['status' => $newStatus]);
        return true;
    }

    /**
     * Check if task is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->isPast()
            && !in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    /**
     * Check if task is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if task is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if task is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if task is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Mark task as in progress.
     */
    public function markInProgress(): bool
    {
        return $this->transitionTo(self::STATUS_IN_PROGRESS);
    }

    /**
     * Mark task as completed.
     */
    public function markCompleted(): bool
    {
        return $this->transitionTo(self::STATUS_COMPLETED);
    }

    /**
     * Mark task as cancelled.
     */
    public function markCancelled(): bool
    {
        return $this->transitionTo(self::STATUS_CANCELLED);
    }
}
