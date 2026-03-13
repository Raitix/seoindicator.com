<?php

namespace App\Services\Crawler;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Timeout Handler Service for TASK-22-17.
 *
 * Tracks timing for crawl jobs, detects timeouts, and initiates graceful shutdown.
 * Supports multiple jobs tracked independently with per-job configuration.
 *
 * @see TASK-22-17
 * @see SPEC-022
 */
class TimeoutHandlerService
{
    /**
     * @var array<string, array{start: Carbon, timeout: int, grace_period: int}>
     */
    protected array $tracking = [];

    /**
     * Default job timeout in seconds.
     */
    protected int $defaultTimeout;

    /**
     * Default grace period in seconds.
     */
    protected int $defaultGracePeriod;

    /**
     * Default page timeout in seconds.
     */
    protected int $defaultPageTimeout;

    /**
     * Create a new TimeoutHandlerService instance.
     *
     * @param int|null $timeout Default job timeout (from config if null)
     * @param int|null $gracePeriod Default grace period (from config if null)
     * @param int|null $pageTimeout Default page timeout (from config if null)
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
     * Start tracking a job's timeout.
     *
     * @param string $jobId The job identifier
     * @param array $options Optional per-job configuration (timeout, grace_period)
     * @return void
     */
    public function startTracking(string $jobId, array $options = []): void
    {
        $timeout = $options['timeout'] ?? $this->defaultTimeout;
        $gracePeriod = $options['grace_period'] ?? $this->defaultGracePeriod;

        $this->tracking[$jobId] = [
            'start' => Carbon::now(),
            'timeout' => $timeout,
            'grace_period' => $gracePeriod,
        ];
    }

    /**
     * Stop tracking a job and clear its data.
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
        return $this->tracking[$jobId]['start'] ?? null;
    }

    /**
     * Get elapsed seconds since tracking started.
     *
     * @param string $jobId The job identifier
     * @return int Elapsed seconds, or 0 if not tracking
     */
    public function getElapsedSeconds(string $jobId): int
    {
        $start = $this->getStartTime($jobId);
        
        if ($start === null) {
            return 0;
        }

        return (int) $start->diffInSeconds(Carbon::now());
    }

    /**
     * Get remaining seconds until timeout.
     *
     * @param string $jobId The job identifier
     * @return int Remaining seconds, or default timeout if not tracking
     */
    public function getRemainingSeconds(string $jobId): int
    {
        $tracking = $this->tracking[$jobId] ?? null;
        
        if ($tracking === null) {
            return $this->defaultTimeout;
        }

        $elapsed = $this->getElapsedSeconds($jobId);
        $remaining = $tracking['timeout'] - $elapsed;

        return max(0, $remaining);
    }

    /**
     * Check if approaching timeout (remaining < threshold).
     *
     * @param string $jobId The job identifier
     * @param int $threshold The threshold in seconds (default: 60)
     * @return bool True if approaching timeout
     */
    public function isApproachingTimeout(string $jobId, int $threshold = 60): bool
    {
        return $this->getRemainingSeconds($jobId) < $threshold;
    }

    /**
     * Check if timeout has been exceeded.
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
     * Graceful shutdown should start when remaining time < grace period.
     *
     * @param string $jobId The job identifier
     * @return bool True if graceful shutdown should start
     */
    public function shouldStartGracefulShutdown(string $jobId): bool
    {
        $tracking = $this->tracking[$jobId] ?? null;
        
        if ($tracking === null) {
            return false;
        }

        $remaining = $this->getRemainingSeconds($jobId);
        return $remaining <= $tracking['grace_period'];
    }

    /**
     * Check if crawling can continue.
     *
     * @param string $jobId The job identifier
     * @return bool True if there is time remaining
     */
    public function canContinueCrawling(string $jobId): bool
    {
        return !$this->isTimeoutExceeded($jobId);
    }

    /**
     * Check if page timeout occurred.
     *
     * @param int $pageDurationSeconds How long the page took
     * @return bool True if page timeout occurred
     */
    public function isPageTimeout(int $pageDurationSeconds): bool
    {
        return $pageDurationSeconds > $this->defaultPageTimeout;
    }

    /**
     * Get seconds until grace period starts.
     *
     * @param string $jobId The job identifier
     * @return int Seconds until grace period, or 0 if already in grace
     */
    public function getSecondsUntilGrace(string $jobId): int
    {
        $tracking = $this->tracking[$jobId] ?? null;
        
        if ($tracking === null) {
            return $this->defaultTimeout - $this->defaultGracePeriod;
        }

        $remaining = $this->getRemainingSeconds($jobId);
        $graceStart = $tracking['grace_period'];

        return max(0, $remaining - $graceStart);
    }

    /**
     * Check if there is enough time for the next page.
     *
     * @param string $jobId The job identifier
     * @return bool True if there is time for another page
     */
    public function hasTimeForNextPage(string $jobId): bool
    {
        $tracking = $this->tracking[$jobId] ?? null;
        
        if ($tracking === null) {
            return true;
        }

        // Need at least: grace period + page timeout for safe continuation
        $remaining = $this->getRemainingSeconds($jobId);
        $buffer = $tracking['grace_period'] + $this->defaultPageTimeout;

        return $remaining > $buffer;
    }

    /**
     * Get formatted duration string (e.g., "2m 30s").
     *
     * @param string $jobId The job identifier
     * @return string Formatted duration
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
     * @return array Context array with timing data
     */
    public function getTimeoutContext(string $jobId): array
    {
        $tracking = $this->tracking[$jobId] ?? null;

        return [
            'jobId' => $jobId,
            'elapsed' => $this->getElapsedSeconds($jobId),
            'remaining' => $this->getRemainingSeconds($jobId),
            'timeout' => $tracking['timeout'] ?? $this->defaultTimeout,
            'grace_period' => $tracking['grace_period'] ?? $this->defaultGracePeriod,
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
        Log::warning('Crawler: Job timeout exceeded', [
            'jobId' => $jobId,
            'elapsed' => $this->getElapsedSeconds($jobId),
            'timeout' => $this->tracking[$jobId]['timeout'] ?? $this->defaultTimeout,
        ]);
    }

    /**
     * Get the default timeout from config.
     *
     * @return int
     */
    public function getDefaultTimeout(): int
    {
        return config('crawler.timeouts.job', 300);
    }

    /**
     * Get the default grace period from config.
     *
     * @return int
     */
    public function getDefaultGracePeriod(): int
    {
        return config('crawler.timeouts.grace_period', 30);
    }

    /**
     * Get the default page timeout from config.
     *
     * @return int
     */
    public function getDefaultPageTimeout(): int
    {
        return config('crawler.timeouts.page', 30);
    }
}
