<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Queue Monitoring Service for Admin Dashboard.
 *
 * Provides detailed queue health monitoring with per-queue breakdown,
 * 24-hour failure tracking, and configurable alert thresholds.
 *
 * @see TASK-27-12
 * @see SPEC-027
 */
class QueueMonitoringService
{
    /**
     * Cache TTL for queue stats (30 seconds).
     */
    private const CACHE_TTL = 30;

    /**
     * Cache key for queue stats.
     */
    private const CACHE_KEY = 'admin.queue.stats';

    /**
     * Get all queue statistics.
     *
     * @return array{
     *     status: string,
     *     pending: int,
     *     failed: int,
     *     failed_24h: int,
     *     workers: int,
     *     avgProcessingTimeMs: int,
     *     queues: array<string, array{pending: int, status: string}>
     * }
     */
    public function getQueueStats(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
                $pendingByQueue = $this->getPendingByQueue();
                $totalPending = array_sum($pendingByQueue);
                $failed = $this->getTotalFailedCount();
                $failed24h = $this->getFailedJobsLast24h();
                $workers = $this->getWorkerCount();
                $avgProcessingTime = $this->getAverageProcessingTime();
                $thresholds = $this->getAlertThresholds();

                $queues = [];
                foreach ($pendingByQueue as $queueName => $count) {
                    $queues[$queueName] = [
                        'pending' => $count,
                        'status' => $this->determineQueueStatus($count, 0, $thresholds),
                    ];
                }

                $status = $this->determineOverallStatus(
                    $totalPending,
                    $failed,
                    $failed24h,
                    $workers,
                    $avgProcessingTime,
                    $thresholds
                );

                return [
                    'status' => $status,
                    'pending' => $totalPending,
                    'failed' => $failed,
                    'failed_24h' => $failed24h,
                    'workers' => $workers,
                    'avgProcessingTimeMs' => $avgProcessingTime,
                    'queues' => $queues,
                ];
            });
        } catch (\Exception $e) {
            Log::error('QueueMonitoringService error: ' . $e->getMessage());

            return $this->getSafeDefaults();
        }
    }

    /**
     * Get pending job counts grouped by queue.
     *
     * @return array<string, int>
     */
    public function getPendingByQueue(): array
    {
        try {
            $results = DB::table('jobs')
                ->select('queue', DB::raw('COUNT(*) as count'))
                ->groupBy('queue')
                ->pluck('count', 'queue')
                ->map(fn ($count) => (int) $count)
                ->toArray();

            // Ensure default queue is always present
            if (!isset($results['default'])) {
                $results['default'] = 0;
            }

            return $results;
        } catch (\Exception $e) {
            Log::warning('Failed to get pending by queue: ' . $e->getMessage());

            return ['default' => 0];
        }
    }

    /**
     * Get failed jobs count from the last 24 hours.
     */
    public function getFailedJobsLast24h(): int
    {
        try {
            $count = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subDay())
                ->count();

            return (int) $count;
        } catch (\Exception $e) {
            Log::warning('Failed to get failed jobs 24h: ' . $e->getMessage());

            return 0;
        }
    }

    /**
     * Get active worker count from cache.
     *
     * Workers should update this cache key when they start/stop.
     */
    public function getWorkerCount(): int
    {
        try {
            return (int) Cache::get('queue:workers:count', 0);
        } catch (\Exception $e) {
            Log::warning('Failed to get worker count: ' . $e->getMessage());

            return 0;
        }
    }

    /**
     * Get average job processing time in milliseconds.
     *
     * This is calculated by workers and cached.
     * Workers should update 'queue:avg_processing_time' after completing jobs.
     */
    public function getAverageProcessingTime(): int
    {
        try {
            // Try to get cached average from workers
            $avgTime = Cache::get('queue:avg_processing_time');

            if ($avgTime !== null) {
                return (int) $avgTime;
            }

            // Fallback: try to calculate from completed jobs tracking
            // This requires a completed_jobs table or similar tracking
            $completedStats = Cache::get('queue:completed_stats');

            if ($completedStats && isset($completedStats['avg_time_ms'])) {
                return (int) $completedStats['avg_time_ms'];
            }

            // No data available
            return 0;
        } catch (\Exception $e) {
            Log::warning('Failed to get average processing time: ' . $e->getMessage());

            return 0;
        }
    }

    /**
     * Get alert thresholds from configuration.
     *
     * @return array{
     *     pending_warning: int,
     *     pending_critical: int,
     *     failed_warning: int,
     *     failed_critical: int,
     *     processing_time_warning: int,
     *     processing_time_critical: int
     * }
     */
    public function getAlertThresholds(): array
    {
        $defaults = [
            'pending_warning' => 100,
            'pending_critical' => 500,
            'failed_warning' => 10,
            'failed_critical' => 50,
            'processing_time_warning' => 1000,
            'processing_time_critical' => 5000,
        ];

        try {
            $configured = config('queue.monitoring.thresholds', []);

            return array_merge($defaults, $configured);
        } catch (\Exception $e) {
            return $defaults;
        }
    }

    /**
     * Determine overall queue health status.
     */
    protected function determineOverallStatus(
        int $pending,
        int $failed,
        int $failed24h,
        int $workers,
        int $avgProcessingTime,
        array $thresholds
    ): string {
        // Critical conditions
        if ($pending > $thresholds['pending_critical']) {
            return 'unhealthy';
        }

        if ($failed > $thresholds['failed_critical']) {
            return 'unhealthy';
        }

        if ($avgProcessingTime > $thresholds['processing_time_critical']) {
            return 'unhealthy';
        }

        // Warning conditions
        if ($pending > $thresholds['pending_warning']) {
            return 'warning';
        }

        if ($failed > $thresholds['failed_warning']) {
            return 'warning';
        }

        if ($avgProcessingTime > $thresholds['processing_time_warning']) {
            return 'warning';
        }

        // No workers with pending jobs is a warning
        if ($pending > 0 && $workers === 0) {
            return 'warning';
        }

        // Recent failures are concerning
        if ($failed24h > $thresholds['failed_warning']) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * Determine status for a specific queue.
     */
    protected function determineQueueStatus(int $pending, int $failed, array $thresholds): string
    {
        if ($pending > $thresholds['pending_critical']) {
            return 'unhealthy';
        }

        if ($pending > $thresholds['pending_warning']) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * Get total failed jobs count (all time).
     */
    protected function getTotalFailedCount(): int
    {
        try {
            return (int) DB::table('failed_jobs')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get safe default values when errors occur.
     *
     * @return array{
     *     status: string,
     *     pending: int,
     *     failed: int,
     *     failed_24h: int,
     *     workers: int,
     *     avgProcessingTimeMs: int,
     *     queues: array<string, array{pending: int, status: string}>
     * }
     */
    protected function getSafeDefaults(): array
    {
        return [
            'status' => 'unhealthy',
            'pending' => 0,
            'failed' => 0,
            'failed_24h' => 0,
            'workers' => 0,
            'avgProcessingTimeMs' => 0,
            'queues' => [
                'default' => [
                    'pending' => 0,
                    'status' => 'healthy',
                ],
            ],
        ];
    }
}
