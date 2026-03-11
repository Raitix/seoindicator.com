<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Content Refresh Task model.
 * 
 * Stores individual refresh tasks for decay candidates.
 *
 * @see SPEC-036: Content Decay Detection & Refresh Planner
 * @see TASK-36-2: Eloquent Models - Content Decay
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
     * The default values for attributes.
     */
    protected $attributes = [
        'status' => 'pending',
        'source' => 'system',
    ];

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * Get the candidate this task belongs to.
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(ContentDecayCandidate::class, 'candidate_id');
    }

    /**
     * Get the user who owns this task.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
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
     * Scope to get overdue tasks.
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Scope to filter by owner.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('owner_user_id', $userId);
    }

    /**
     * Scope to get pending tasks.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Alias for scopePending - backward compatibility.
     */
    public function scopeTodo($query)
    {
        return $this->scopePending($query);
    }

    /**
     * Scope to get in-progress tasks.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope to get completed tasks.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Alias for scopeCompleted - backward compatibility.
     */
    public function scopeDone($query)
    {
        return $this->scopeCompleted($query);
    }

    /**
     * Scope to get cancelled tasks.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Alias for scopeCancelled - backward compatibility.
     */
    public function scopeSkipped($query)
    {
        return $this->scopeCancelled($query);
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * Mark the task as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }

    /**
     * Alias for markAsCompleted - backward compatibility.
     */
    public function markAsDone(): void
    {
        $this->markAsCompleted();
    }

    /**
     * Mark the task as cancelled.
     */
    public function markAsCancelled(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Alias for markAsCancelled - backward compatibility.
     */
    public function markAsSkipped(): void
    {
        $this->markAsCancelled();
    }

    /**
     * Check if the task is overdue.
     */
    public function isOverdue(): bool
    {
        if ($this->due_date === null) {
            return false;
        }

        return $this->due_date->isPast() && !in_array($this->status, ['completed', 'cancelled']);
    }

    /**
     * Get a value from the metadata JSON.
     */
    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->metadata_json, $key, $default);
    }
}
