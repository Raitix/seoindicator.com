<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CrawlJob model for the Headless Crawler Service.
 *
 * Represents a crawl job that renders JavaScript-heavy pages
 * and extracts SEO-relevant content.
 *
 * @see TASK-22-2
 * @see SPEC-022
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $project_id
 * @property string $job_id
 * @property string $url
 * @property string $status
 * @property int $depth
 * @property array|null $options
 * @property int $pages_crawled
 * @property int $total_size
 * @property string|null $error
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class CrawlJob extends Model
{
    use HasFactory;

    /**
     * Status constants.
     */
    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'project_id',
        'job_id',
        'url',
        'status',
        'depth',
        'options',
        'pages_crawled',
        'total_size',
        'error',
        'started_at',
        'completed_at',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'options' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'depth' => 'integer',
        'pages_crawled' => 'integer',
        'total_size' => 'integer',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the user that owns the crawl job.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the project that the crawl job belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the pages for the crawl job.
     */
    public function pages(): HasMany
    {
        return $this->hasMany(CrawlPage::class, 'crawl_job_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope to get queued jobs.
     */
    public function scopeQueued($query)
    {
        return $query->where('status', self::STATUS_QUEUED);
    }

    /**
     * Scope to get running jobs.
     */
    public function scopeRunning($query)
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    /**
     * Scope to get completed jobs.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to get failed jobs.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to get cancelled jobs.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope to get jobs for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get jobs for a specific project.
     */
    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope to get expired jobs.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    /**
     * Check if the job is in a terminal state.
     */
    public function getIsCompleteAttribute(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Get the job duration in seconds.
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->completed_at);
    }

    /**
     * Get the human-readable size.
     */
    public function getSizeFormattedAttribute(): string
    {
        return $this->formatSize($this->total_size);
    }

    /**
     * Format size in human-readable format.
     */
    protected function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $bytes;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return number_format($size, 1) . ' ' . $units[$unitIndex];
    }

    /**
     * Check if the job can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_QUEUED, self::STATUS_RUNNING]);
    }

    /**
     * Get the content URL for this job.
     */
    public function getContentUrlAttribute(): string
    {
        return "/api/v1/crawler/jobs/{$this->job_id}/content";
    }
}
