<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AnalysisJobResult model for storing individual indicator analysis results.
 *
 * @property int $id
 * @property int $job_id
 * @property string $indicator_slug
 * @property string $indicator_name
 * @property string $status
 * @property float|null $score
 * @property float|null $max_score
 * @property int|null $percentage
 * @property array|null $data
 * @property array|null $recommendations
 * @property string|null $error_message
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property int|null $processing_time_ms
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AnalysisJobResult extends Model
{
    use HasFactory;

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'job_id',
        'indicator_slug',
        'indicator_name',
        'status',
        'score',
        'max_score',
        'percentage',
        'data',
        'recommendations',
        'error_message',
        'started_at',
        'completed_at',
        'processing_time_ms',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'recommendations' => 'array',
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // ============================================
    // Relationships
    // ============================================

    /**
     * Get the job that owns this result.
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(AnalysisJob::class, 'job_id');
    }

    // ============================================
    // Status Scopes
    // ============================================

    /**
     * Scope a query to only include pending results.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope a query to only include running results.
     */
    public function scopeRunning($query)
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    /**
     * Scope a query to only include completed results.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope a query to only include failed results.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope a query to only include skipped results.
     */
    public function scopeSkipped($query)
    {
        return $query->where('status', self::STATUS_SKIPPED);
    }

    // ============================================
    // Custom Scopes
    // ============================================

    /**
     * Scope a query to filter by job.
     */
    public function scopeByJob($query, int $jobId)
    {
        return $query->where('job_id', $jobId);
    }

    /**
     * Scope a query to filter by indicator slug.
     */
    public function scopeByIndicator($query, string $slug)
    {
        return $query->where('indicator_slug', $slug);
    }

    // ============================================
    // Status Helpers
    // ============================================

    /**
     * Check if the result is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the result is running.
     */
    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * Check if the result is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the result is failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if the result is skipped.
     */
    public function isSkipped(): bool
    {
        return $this->status === self::STATUS_SKIPPED;
    }

    // ============================================
    // Status Transitions
    // ============================================

    /**
     * Mark the result as running.
     */
    public function markAsRunning(): bool
    {
        $this->status = self::STATUS_RUNNING;
        $this->started_at = now();
        return $this->save();
    }

    /**
     * Mark the result as completed.
     */
    public function markAsCompleted(float $score, ?float $maxScore = 100.00, ?array $data = null, ?array $recommendations = null): bool
    {
        $this->status = self::STATUS_COMPLETED;
        $this->score = $score;
        $this->max_score = $maxScore;
        $this->percentage = $maxScore > 0 ? (int) round(($score / $maxScore) * 100) : 0;
        $this->completed_at = now();

        if ($data !== null) {
            $this->data = $data;
        }

        if ($recommendations !== null) {
            $this->recommendations = $recommendations;
        }

        if ($this->started_at) {
            $this->processing_time_ms = $this->started_at->diffInMilliseconds(now());
        }

        return $this->save();
    }

    /**
     * Mark the result as failed.
     */
    public function markAsFailed(?string $errorMessage = null): bool
    {
        $this->status = self::STATUS_FAILED;
        if ($errorMessage) {
            $this->error_message = $errorMessage;
        }
        $this->completed_at = now();

        if ($this->started_at) {
            $this->processing_time_ms = $this->started_at->diffInMilliseconds(now());
        }

        return $this->save();
    }

    // ============================================
    // Accessors
    // ============================================

    /**
     * Get the percentage (calculated if not stored).
     */
    public function getPercentageAttribute($value): ?int
    {
        if ($value !== null) {
            return (int) $value;
        }

        if ($this->score === null || $this->max_score === null || $this->max_score == 0) {
            return null;
        }

        return (int) round(($this->score / $this->max_score) * 100);
    }

    /**
     * Get data as array (never null).
     */
    public function getDataAttribute($value): array
    {
        if ($value === null) {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }

    /**
     * Get recommendations as array (never null).
     */
    public function getRecommendationsAttribute($value): array
    {
        if ($value === null) {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }
}
