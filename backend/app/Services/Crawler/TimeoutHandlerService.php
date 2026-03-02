<?php

namespace App\Services\Crawler;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Timeout Handler Service for Crawl Jobs.
 *
 * Tracks timing for crawl jobs, detects timeout conditions,
 * and initiates graceful shutdown when approaching timeout limits.
 *
 * @see TASK-22-17
 * @see SPEC-022
 */
class TimeoutHandlerService
{
    /**
     * The default job timeout in seconds.
     */
    protected int $defaultTimeout;

    /**
     * The default grace period in seconds.
     */
    protected int $defaultGracePeriod;

    /**
     * The default page timeout in seconds.
     */
    protected int $defaultPageTimeout;

    /**
     * Active tracking data by job ID.
     */
    protected array $tracking = [];

    /**
     * Create a new TimeoutHandlerService instance.
     *
     * @param int|null $timeout Job timeout in seconds
     * @param int|null $gracePeriod Grace period in seconds
     * @param int|null $pageTimeout Page timeout in seconds
     */
    public function __construct(
        ?int $timeout = null,
        ?int $gracePeriod = null,
        ?int $pageTimeout = null
    ) {
        $this->defaultTimeout = $timeout ?? config('crawler.timeouts.job', 300);
        $this->defaultGracePeriod = $gracePeriod ?? config('crawler.timeouts.grace_period', 30);
        $this->defaultPageTimeout = $pageTimeout ?? config('crawler.timeouts.page', 30);
    }

    /**
     * Start tracking a crawl job.
     *
     * @param string $jobId The job identifier
     * @param array $options Optional configuration (timeout, grace_period, page_timeout)
     * @return void
     */
    public function startTracking(string $jobId, array $options = []): void
    {
        $this->tracking[$jobId] = [
            'start_time' => Carbon::now(),
            'timeout' => $options['timeout'] ?? $this->defaultTimeout,
            'grace_period' => $options['grace_period'] ?? $this->defaultGracePeriod,
            'page_timeout' => $options['page_timeout'] ?? $this->defaultPageTimeout,
        ];
    }

    /**
     * Stop tracking a crawl job.
     *
     * @param string $jobId The job identifier
     * @return void
     */
    public function stopTracking(string $jobId): void
    {
        unset($this->tracking[$jobId]);
    }

    /**
     * Get the start time for a job.
     *
     * @param string $jobId The job identifier
     * @return Carbon|null The start time or null if not tracking
     */
    public function getStartTime(string $jobId): ?Carbon
    {
        return $this->tracking[$jobId]['start_time'] ?? null;
    }

    /**
     * Get the elapsed seconds for a job.
     *
     * @param string $jobId The job identifier
     * @return int Elapsed seconds (0 if not tracking)
     */
    public function getElapsedSeconds(string $jobId): int
    {
        $startTime = $this->getStartTime($jobId);

        if (!$startTime) {
            return 0;
        }

        return (int) Carbon::now()->diffInSeconds($startTime);
    }

    /**
     * Get the remaining seconds for a job.
     *
     * @param string $jobId The job identifier
     * @return int Remaining seconds (full timeout if not tracking)
     */
    public function getRemainingSeconds(string $jobId): int
    {
        if (!isset($this->tracking[$jobId])) {
            return $this->defaultTimeout;
        }

        $elapsed = $this->getElapsedSeconds($jobId);
        $timeout = $this->tracking[$jobId]['timeout'];

        return max(0, $timeout - $elapsed);
    }

    /**
     * Check if the job is approaching timeout.
     *
     * @param string $jobId The job identifier
     * @param int $thresholdSeconds Seconds remaining to consider "approaching"
     * @return bool True if approaching timeout
     */
    public function isApproachingTimeout(string $jobId, int $thresholdSeconds = 60): bool
    {
        return $this->getRemainingSeconds($jobId) < $thresholdSeconds;
    }

    /**
     * Check if the job has exceeded its timeout.
     *
     * @param string $jobId The job identifier
     * @return bool True if timeout exceeded
     */
    public function isTimeoutExceeded(string $jobId): bool
    {
        return $this->getRemainingSeconds($jobId) <= 0;
    }

    /**
     * Check if graceful shutdown should start.
     *
     * Returns true when remaining time is less than or equal to grace period.
     *
     * @param string $jobId The job identifier
     * @return bool True if graceful shutdown should start
     */
    public function shouldStartGracefulShutdown(string $jobId): bool
    {
        if (!isset($this->tracking[$jobId])) {
            return false;
        }

        $remaining = $this->getRemainingSeconds($jobId);
        $gracePeriod = $this->tracking[$jobId]['grace_period'];

        return $remaining <= $gracePeriod;
    }

    /**
     * Check if crawling can continue.
     *
     * Returns false if timeout is exceeded.
     *
     * @param string $jobId The job identifier
     * @return bool True if crawling can continue
     */
    public function canContinueCrawling(string $jobId): bool
    {
        return !$this->isTimeoutExceeded($jobId);
    }

    /**
     * Check if there's enough time for the next page.
     *
     * @param string $jobId The job identifier
     * @return bool True if there's time for another page
     */
    public function hasTimeForNextPage(string $jobId): bool
    {
        if (!isset($this->tracking[$jobId])) {
            return true;
        }

        $remaining = $this->getRemainingSeconds($jobId);
        $gracePeriod = $this->tracking[$jobId]['grace_period'];
        $pageTimeout = $this->tracking[$jobId]['page_timeout'];

        // Need at least grace_period + page_timeout to safely crawl another page
        return $remaining > ($gracePeriod + $pageTimeout);
    }

    /**
     * Get seconds until grace period starts.
     *
     * @param string $jobId The job identifier
     * @return int Seconds until grace period
     */
    public function getSecondsUntilGrace(string $jobId): int
    {
        if (!isset($this->tracking[$jobId])) {
            return $this->defaultTimeout - $this->defaultGracePeriod;
        }

        $remaining = $this->getRemainingSeconds($jobId);
        $gracePeriod = $this->tracking[$jobId]['grace_period'];

        return max(0, $remaining - $gracePeriod);
    }

    /**
     * Check if a page operation timed out.
     *
     * @param int $actualSeconds Actual time the page took
     * @return bool True if page timed out
     */
    public function isPageTimeout(int $actualSeconds): bool
    {
        return $actualSeconds > $this->defaultPageTimeout;
    }

    /**
     * Get formatted duration string.
     *
     * @param string $jobId The job identifier
     * @return string Formatted duration (e.g., "2m 30s")
     */
    public function getFormattedDuration(string $jobId): string
    {
        $elapsed = $this->getElapsedSeconds($jobId);
        $minutes = (int) floor($elapsed / 60);
        $seconds = $elapsed % 60;

        return "{$minutes}m {$seconds}s";
    }

    /**
     * Get timeout context for logging.
     *
     * @param string $jobId The job identifier
     * @return array Timeout context data
     */
    public function getTimeoutContext(string $jobId): array
    {
        if (!isset($this->tracking[$jobId])) {
            return [
                'jobId' => $jobId,
                'elapsed' => 0,
                'remaining' => $this->defaultTimeout,
                'timeout' => $this->defaultTimeout,
                'grace_period' => $this->defaultGracePeriod,
            ];
        }

        return [
            'jobId' => $jobId,
            'elapsed' => $this->getElapsedSeconds($jobId),
            'remaining' => $this->getRemainingSeconds($jobId),
            'timeout' => $this->tracking[$jobId]['timeout'],
            'grace_period' => $this->tracking[$jobId]['grace_period'],
        ];
    }

    /**
     * Log a timeout event.
     *
     * @param string $jobId The job identifier
     * @return void
     */
    public function logTimeoutEvent(string $jobId): void
    {
        $context = $this->getTimeoutContext($jobId);

        Log::warning('Crawler: Job timeout exceeded', $context);
    }

    /**
     * Get the default timeout from config.
     *
     * @return int Default timeout in seconds
     */
    public function getDefaultTimeout(): int
    {
        return $this->defaultTimeout;
    }

    /**
     * Get the default grace period from config.
     *
     * @return int Default grace period in seconds
     */
    public function getDefaultGracePeriod(): int
    {
        return $this->defaultGracePeriod;
    }

    /**
     * Get the default page timeout from config.
     *
     * @return int Default page timeout in seconds
     */
    public function getDefaultPageTimeout(): int
    {
        return $this->defaultPageTimeout;
    }
}
