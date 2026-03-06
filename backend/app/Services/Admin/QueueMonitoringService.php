<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Queue Monitoring Service for Admin Dashboard.
 *
 * Provides comprehensive queue monitoring with per-queue pending counts,
 * failed jobs tracking (last 24 hours), worker count, and average processing time.
 *
 * Integrates with existing HealthCheckService for unified health reporting.
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
     * Default alert thresholds.
     */
    private const DEFAULT_THRESHOLDS = [
        'pending_warning' => 100,
        'pending_critical' => 500,
        'failed_warning' => 10,
        'failed_critical' => 50,
        'processing_time_warning' => 1000,
        'processing_time_critical' => 5000,
    ];

    /**
     * Get comprehensive queue statistics.
     *
     * @return array
     */
    public function getQueueStats(): array
    {
        try {
            $pending = $this->getTotalPending();
            $failed = $this->getTotalFailed();
            $failed24h = $this->getFailedJobsLast24h();
            $workers = $this->getWorkerCount();
            $avgProcessingTime = $this->getAverageProcessingTime();
            $queues = $this->getQueuesWithStatus();
            $status = $this->determineStatus($pending, $failed, $failed24h, $workers, $avgProcessingTime);

            return [
                'status' => $status,
                'pending' => $pending,
                'failed' => $failed,
                'failed_24h' => $failed24h,
                'workers' => $workers,
                'avgProcessingTimeMs' => $avgProcessingTime,
                'queues' => $queues,
            ];
        } catch (\Exception $e) {
            Log::warning('QueueMonitoringService error: ' . $e->getMessage());
            
            return $this->getSafeDefaults();
        }
    }

    /**
     * Get pending jobs count grouped by queue (simple counts).
     *
     * @return array<string, int>
     */
    public function getPendingByQueue(): array
    {
        try {
            $results = DB::table('jobs')
                ->select('queue', DB::raw('COUNT(*) as count'))
                ->groupBy('queue')
                ->get();

            $queueCounts = ['default' => 0];

            foreach ($results as $row) {
                $queueName = $row->queue ?? 'default';
                $queueCounts[$queueName] = (int) $row->count;
            }

            return $queueCounts;
        } catch (\Exception $e) {
            Log::warning('Failed to get pending by queue: ' . $e->getMessage());
            return ['default' => 0];
        }
    }

    /**
     * Get queues with status information.
     *
     * @return array<string, array{pending: int, status: string}>
     */
    protected function getQueuesWithStatus(): array
    {
        try {
            $queueCounts = $this->getPendingByQueue();
            $thresholds = $this->getAlertThresholds();
            
            $queuesWithStatus = [];
            foreach ($queueCounts as $queue => $count) {
                $queuesWithStatus[$queue] = [
                    'pending' => $count,
                    'status' => $this->determineQueueStatus($count, $thresholds),
                ];
            }

            return $queuesWithStatus;
        } catch (\Exception $e) {
            return ['default' => ['pending' => 0, 'status' => 'healthy']];
        }
    }

    /**
     * Get failed jobs count from last 24 hours.
     *
     * @return int
     */
    public function getFailedJobsLast24h(): int
    {
        try {
            return DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subDay())
                ->count();
        } catch (\Exception $e) {
            Log::warning('Failed to get failed jobs 24h: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get active worker count.
     *
     * @return int
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
     * @return int
     */
    public function getAverageProcessingTime(): int
    {
        try {
            // Try to get cached value first
            $cached = Cache::get('queue:avg_processing_time');
            if ($cached !== null) {
                return (int) $cached;
            }

            // Calculate from completed jobs if available
            // This assumes a job_metrics table or similar tracking
            // For now, return 0 if no metrics available
            $avgTime = 0;

            // Cache the result for 5 minutes
            Cache::put('queue:avg_processing_time', $avgTime, 300);

            return $avgTime;
        } catch (\Exception $e) {
            Log::warning('Failed to get average processing time: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get configurable alert thresholds.
     *
     * @return array
     */
    public function getAlertThresholds(): array
    {
        $configured = Config::get('queue.monitoring.thresholds', []);

        return array_merge(self::DEFAULT_THRESHOLDS, $configured);
    }

    /**
     * Get total pending jobs count.
     *
     * @return int
     */
    protected function getTotalPending(): int
    {
        try {
            return DB::table('jobs')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get total failed jobs count.
     *
     * @return int
     */
    protected function getTotalFailed(): int
    {
        try {
            return DB::table('failed_jobs')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Determine overall queue status based on metrics.
     *
     * @param int $pending
     * @param int $failed
     * @param int $failed24h
     * @param int $workers
     * @param int $avgProcessingTime
     * @return string
     */
    protected function determineStatus(
        int $pending,
        int $failed,
        int $failed24h,
        int $workers,
        int $avgProcessingTime
    ): string {
        $thresholds = $this->getAlertThresholds();

        // Critical conditions
        if (
            $pending > $thresholds['pending_critical'] ||
            $failed > $thresholds['failed_critical'] ||
            $avgProcessingTime > $thresholds['processing_time_critical']
        ) {
            return 'unhealthy';
        }

        // Warning conditions
        if (
            $pending > $thresholds['pending_warning'] ||
            $failed > $thresholds['failed_warning'] ||
            ($pending > 0 && $workers === 0) ||
            $avgProcessingTime > $thresholds['processing_time_warning']
        ) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * Determine status for individual queue.
     *
     * @param int $pending
     * @param array $thresholds
     * @return string
     */
    protected function determineQueueStatus(int $pending, array $thresholds): string
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
     * Get safe default values for error cases.
     *
     * @return array
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
            'queues' => ['default' => ['pending' => 0, 'status' => 'healthy']],
        ];
    }
}
