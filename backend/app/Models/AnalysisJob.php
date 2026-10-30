<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * AnalysisJob model for tracking progressive SEO analysis jobs.
 *
 * @property int $id
 * @property int $user_id
 * @property int $site_id
 * @property string $status
 * @property int $total_indicators
 * @property int $completed_indicators
 * @property int $failed_indicators
 * @property int $progress_percent
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon|null $cancelled_at
 * @property string|null $error_message
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AnalysisJob extends Model
{
    use HasFactory;

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED = 'failed';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'site_id',
        'status',
        'total_indicators',
        'completed_indicators',
        'failed_indicators',
        'progress_percent',
        'started_at',
        'completed_at',
        'cancelled_at',
        'error_message',
    ];

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'total_indicators' => 0,
        'completed_indicators' => 0,
        'failed_indicators' => 0,
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    // ============================================
    // Relationships
    // ============================================

    /**
     * Get the user that owns this job.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the site being analyzed.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the results for this job.
     */
    public function results(): HasMany
    {
        return $this->hasMany(AnalysisJobResult::class, 'job_id');
    }

    // ============================================
    // Status Scopes
    // ============================================

    /**
     * Scope a query to only include pending jobs.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope a query to only include running jobs.
     */
    public function scopeRunning($query)
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    /**
     * Scope a query to only include completed jobs.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope a query to only include cancelled jobs.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope a query to only include failed jobs.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    // ============================================
    // Custom Scopes
    // ============================================

    /**
     * Scope a query to filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter by site.
     */
    public function scopeBySite($query, int $siteId)
    {
        return $query->where('site_id', $siteId);
    }

    /**
     * Scope a query to filter recent jobs.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ============================================
    // Status Helpers
    // ============================================

    /**
     * Check if the job is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the job is running.
     */
    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * Check if the job is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the job is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if the job is failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    // ============================================
    // Status Transitions
    // ============================================

    /**
     * Mark the job as running.
     */
    public function markAsRunning(): bool
    {
        $this->status = self::STATUS_RUNNING;
        $this->started_at = now();
        return $this->save();
    }

    /**
     * Mark the job as completed.
     */
    public function markAsCompleted(): bool
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();
        $this->progress_percent = 100;
        return $this->save();
    }

    /**
     * Mark the job as cancelled.
     */
    public function markAsCancelled(): bool
    {
        $this->status = self::STATUS_CANCELLED;
        $this->cancelled_at = now();
        return $this->save();
    }

    /**
     * Mark the job as failed.
     */
    public function markAsFailed(?string $errorMessage = null): bool
    {
        $this->status = self::STATUS_FAILED;
        if ($errorMessage) {
            $this->error_message = $errorMessage;
        }
        return $this->save();
    }

    // ============================================
    // Accessors
    // ============================================

    /**
     * Get progress percent.
     * Returns the stored value if explicitly set, otherwise calculates dynamically.
     */
    public function getProgressPercentAttribute($value): int
    {
        // Return stored value if it exists (not null)
        if ($value !== null) {
            return (int) $value;
        }

        // Calculate dynamically based on completed/total indicators
        return $this->calculateProgress();
    }

    /**
     * Calculate progress percent from completed/total indicators.
     */
    public function calculateProgress(): int
    {
        if ($this->total_indicators === 0) {
            return 0;
        }

        $completed = $this->completed_indicators + $this->failed_indicators;
        $percent = (int) round(($completed / $this->total_indicators) * 100);

        return min(100, max(0, $percent));
    }

    /**
     * Get the duration of the job in seconds.
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at) {
            return null;
        }

        $end = $this->completed_at ?? $this->cancelled_at ?? now();

        return $this->started_at->diffInSeconds($end);
    }

    /**
     * Get human-readable duration.
     */
    public function getDurationHumanAttribute(): ?string
    {
        $duration = $this->duration;
        if ($duration === null) {
            return null;
        }

        if ($duration < 60) {
            return $duration . 's';
        }

        $minutes = (int) floor($duration / 60);
        $seconds = $duration % 60;

        if ($minutes < 60) {
            return $minutes . 'm ' . $seconds . 's';
        }

        $hours = (int) floor($minutes / 60);
        $minutes = $minutes % 60;

        return $hours . 'h ' . $minutes . 'm';
    }
}
