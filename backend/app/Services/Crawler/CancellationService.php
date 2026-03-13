<?php

namespace App\Services\Crawler;

use App\Models\CrawlJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Cancellation Service for TASK-22-17.
 *
 * Manages cancellation flags for crawl jobs using atomic cache operations.
 * Supports both immediate cancellation (queued jobs) and deferred cancellation
 * (running jobs detected by the worker).
 *
 * @see TASK-22-17
 * @see SPEC-022
 */
class CancellationService
{
    /**
     * Cache key prefix for cancellation flags.
     */
    protected const CACHE_PREFIX = 'crawler:cancel:';

    /**
     * Default TTL for cache entries (matches job timeout).
     */
    protected int $cacheTTL;

    /**
     * Create a new CancellationService instance.
     *
     * @param int|null $cacheTTL Cache TTL in seconds (from config if null)
     */
    public function __construct(?int $cacheTTL = null)
    {
        $this->cacheTTL = $cacheTTL ?? config('crawler.timeouts.job', 300);
    }

    /**
     * Request cancellation for a crawl job.
     *
     * For queued jobs, immediately updates status to cancelled.
     * For running jobs, sets a flag for the worker to detect.
     *
     * @param CrawlJob $crawlJob The crawl job to cancel
     * @param string|null $reason Optional cancellation reason
     * @return bool True if cancellation was requested, false if job cannot be cancelled
     */
    public function requestCancellation(CrawlJob $crawlJob, ?string $reason = null): bool
    {
        $jobId = $crawlJob->job_id;
        $status = $crawlJob->status;

        // Check if job can be cancelled
        if (!$this->canCancel($status)) {
            return false;
        }

        // Store cancellation metadata
        $this->storeCancellationMetadata($jobId, [
            'requested_at' => Carbon::now()->toDateTimeString(),
            'reason' => $reason ?? 'User requested cancellation',
        ]);

        // Set cancellation flag in cache
        Cache::put($this->getCacheKey($jobId), true, $this->cacheTTL);

        // For queued jobs, immediately update status
        if ($status === CrawlJob::STATUS_QUEUED) {
            $crawlJob->update([
                'status' => CrawlJob::STATUS_CANCELLED,
                'cancelled_at' => Carbon::now(),
            ]);
        }

        // Log the cancellation request
        Log::info('Crawler: Job cancellation requested', [
            'jobId' => $jobId,
            'status' => $status,
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * Check if a cancellation has been requested for a job.
     *
     * @param string $jobId The job identifier
     * @return bool True if cancellation was requested
     */
    public function isCancellationRequested(string $jobId): bool
    {
        return (bool) Cache::get($this->getCacheKey($jobId), false);
    }

    /**
     * Check if the job should cancel (alias for readability).
     *
     * @param string $jobId The job identifier
     * @return bool True if cancellation was requested
     */
    public function shouldCancel(string $jobId): bool
    {
        return $this->isCancellationRequested($jobId);
    }

    /**
     * Check if a job status allows cancellation.
     *
     * @param string $status The job status
     * @return bool True if the job can be cancelled
     */
    public function canCancel(string $status): bool
    {
        return in_array($status, [
            CrawlJob::STATUS_QUEUED,
            CrawlJob::STATUS_RUNNING,
        ]);
    }

    /**
     * Clear the cancellation flag for a job.
     *
     * @param string $jobId The job identifier
     * @return void
     */
    public function clearCancellationFlag(string $jobId): void
    {
        Cache::forget($this->getCacheKey($jobId));
    }

    /**
     * Clear all cancellation data for a job (flag + metadata).
     *
     * @param string $jobId The job identifier
     * @return void
     */
    public function clearAllCancellationData(string $jobId): void
    {
        Cache::forget($this->getCacheKey($jobId));
        Cache::forget($this->getCacheKey($jobId) . ':metadata');
    }

    /**
     * Store cancellation metadata.
     *
     * @param string $jobId The job identifier
     * @param array $metadata Metadata to store
     * @return void
     */
    public function storeCancellationMetadata(string $jobId, array $metadata): void
    {
        Cache::put(
            $this->getCacheKey($jobId) . ':metadata',
            $metadata,
            $this->cacheTTL
        );
    }

    /**
     * Get cancellation metadata.
     *
     * @param string $jobId The job identifier
     * @return array|null The metadata or null if not set
     */
    public function getCancellationMetadata(string $jobId): ?array
    {
        return Cache::get($this->getCacheKey($jobId) . ':metadata');
    }

    /**
     * Get cancellation info (combines flag and metadata).
     *
     * @param string $jobId The job identifier
     * @return array|null Cancellation info or null if not cancelled
     */
    public function getCancellationInfo(string $jobId): ?array
    {
        if (!$this->isCancellationRequested($jobId)) {
            return null;
        }

        return $this->getCancellationMetadata($jobId);
    }

    /**
     * Get the cache key for a job.
     *
     * @param string $jobId The job identifier
     * @return string The cache key
     */
    public function getCacheKey(string $jobId): string
    {
        return self::CACHE_PREFIX . $jobId;
    }

    /**
     * Get the cache TTL.
     *
     * @return int Cache TTL in seconds
     */
    public function getCacheTTL(): int
    {
        return $this->cacheTTL;
    }
}
