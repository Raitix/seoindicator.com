<?php

namespace App\Services\Crawler;

use App\Models\CrawlJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Cancellation Service for Crawl Jobs.
 *
 * Manages cancellation flags in cache for crawl jobs,
 * enabling graceful cancellation of running jobs.
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
     * The cache TTL in seconds (matches job timeout).
     */
    protected int $cacheTTL;

    /**
     * Create a new CancellationService instance.
     *
     * @param int|null $cacheTTL Cache TTL in seconds
     */
    public function __construct(?int $cacheTTL = null)
    {
        $this->cacheTTL = $cacheTTL ?? config('crawler.timeouts.job', 300);
    }

    /**
     * Request cancellation for a crawl job.
     *
     * @param CrawlJob $crawlJob The crawl job to cancel
     * @param string|null $reason Optional cancellation reason
     * @return bool True if cancellation requested, false if cannot be cancelled
     */
    public function requestCancellation(CrawlJob $crawlJob, ?string $reason = null): bool
    {
        // Check if job can be cancelled
        if (!$this->canCancel($crawlJob->status)) {
            return false;
        }

        $jobId = $crawlJob->job_id;
        $reason = $reason ?? 'User requested cancellation';

        // Store cancellation flag in cache
        Cache::put($this->getCacheKey($jobId), true, $this->cacheTTL);

        // Store cancellation metadata
        $this->storeCancellationMetadata($jobId, [
            'requested_at' => Carbon::now()->toIso8601String(),
            'reason' => $reason,
        ]);

        // For queued jobs, immediately set status to cancelled
        if ($crawlJob->status === CrawlJob::STATUS_QUEUED) {
            $crawlJob->update([
                'status' => CrawlJob::STATUS_CANCELLED,
                'cancelled_at' => Carbon::now(),
                'cancel_reason' => $reason,
            ]);
        } else {
            // For running jobs, just record the cancellation request
            // Worker will detect and handle
            $crawlJob->update([
                'cancelled_at' => Carbon::now(),
                'cancel_reason' => $reason,
            ]);
        }

        Log::info('Crawler: Job cancellation requested', [
            'jobId' => $jobId,
            'status' => $crawlJob->status,
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * Check if a cancellation has been requested.
     *
     * @param string $jobId The job identifier
     * @return bool True if cancellation requested
     */
    public function isCancellationRequested(string $jobId): bool
    {
        return (bool) Cache::get($this->getCacheKey($jobId), false);
    }

    /**
     * Check if a job should cancel (alias for isCancellationRequested).
     *
     * @param string $jobId The job identifier
     * @return bool True if should cancel
     */
    public function shouldCancel(string $jobId): bool
    {
        return $this->isCancellationRequested($jobId);
    }

    /**
     * Check if a job status can be cancelled.
     *
     * @param string $status The job status
     * @return bool True if can be cancelled
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
     * Clear all cancellation data for a job.
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
     * @return array|null Metadata or null if not found
     */
    public function getCancellationMetadata(string $jobId): ?array
    {
        return Cache::get($this->getCacheKey($jobId) . ':metadata');
    }

    /**
     * Get cancellation info for a job.
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
}
