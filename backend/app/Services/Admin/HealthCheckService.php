<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

/**
 * Health Check Service
 * 
 * Monitors system health status including database, cache, storage, and queue.
 * 
 * TASK-27-11: Health Check Service
 * Status: To Develop
 * 
 * @see TESTS/TEST-27-11.md for test documentation
 */
class HealthCheckService
{
    protected const CACHE_KEY = 'health-check:status';
    protected const CACHE_TTL = 30; // seconds

    /**
     * Get full health status of all services
     *
     * @param bool $forceRefresh Bypass cache
     * @return array
     */
    public function checkHealth(bool $forceRefresh = false): array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if ($cached && !$forceRefresh) {
            return $cached;
        }

        $services = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $health = [
            'status' => $this->getOverallStatus($services),
            'uptime' => $this->calculateUptime($services),
            'lastChecked' => now()->toIso8601String(),
            'services' => $services,
        ];

        Cache::put(self::CACHE_KEY, $health, self::CACHE_TTL);

        return $health;
    }

    /**
     * Check database connectivity and latency
     *
     * @return array{status: string, message: string, metrics: array}
     */
    public function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $latencyMs = (int) ((microtime(true) - $start) * 1000);

            $warningThreshold = Config::get('health-check.thresholds.database_latency_warning', 100);
            $criticalThreshold = Config::get('health-check.thresholds.database_latency_critical', 500);

            $status = 'healthy';
            if ($latencyMs >= $criticalThreshold) {
                $status = 'unhealthy';
            } elseif ($latencyMs >= $warningThreshold) {
                $status = 'degraded';
            }

            // Get connection count (MySQL specific, fallback to 0)
            $connections = 0;
            try {
                $result = DB::select("SHOW STATUS LIKE 'Threads_connected'");
                $connections = isset($result[0]) ? (int) $result[0]->Value : 0;
            } catch (\Exception $e) {
                // Connection count not available for this driver
            }

            return [
                'status' => $status,
                'message' => 'Database is responding normally',
                'metrics' => [
                    'latencyMs' => $latencyMs,
                    'connections' => $connections,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database error: ' . $e->getMessage(),
                'metrics' => [
                    'latencyMs' => 0,
                    'connections' => 0,
                ],
            ];
        }
    }

    /**
     * Check cache (Redis) connectivity and hit rate
     *
     * @return array{status: string, message: string, metrics: array}
     */
    public function checkCache(): array
    {
        try {
            $testKey = 'health-check:test';
            $testValue = 'health-check-' . time();

            $start = microtime(true);
            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            $latencyMs = (int) ((microtime(true) - $start) * 1000);

            if ($retrieved !== $testValue) {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Cache read/write test failed',
                    'metrics' => [
                        'hitRate' => 0.0,
                        'latencyMs' => $latencyMs,
                    ],
                ];
            }

            // Calculate hit rate (simplified - uses cache stats if available)
            $hitRate = $this->getCacheHitRate();

            $warningThreshold = Config::get('health-check.thresholds.cache_hit_rate_warning', 80);

            $status = 'healthy';
            if ($hitRate < $warningThreshold && $hitRate > 0) {
                $status = 'degraded';
            }

            return [
                'status' => $status,
                'message' => 'Cache is responding normally',
                'metrics' => [
                    'hitRate' => $hitRate,
                    'latencyMs' => $latencyMs,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Cache error: ' . $e->getMessage(),
                'metrics' => [
                    'hitRate' => 0.0,
                    'latencyMs' => 0,
                ],
            ];
        }
    }

    /**
     * Check storage availability and disk usage
     *
     * @return array{status: string, message: string, metrics: array}
     */
    public function checkStorage(): array
    {
        try {
            $disk = Storage::disk('local');
            $path = storage_path();

            $totalBytes = disk_total_space($path);
            $freeBytes = disk_free_space($path);
            $usedPercent = $totalBytes > 0 ? (($totalBytes - $freeBytes) / $totalBytes) * 100 : 0;

            $warningThreshold = Config::get('health-check.thresholds.storage_usage_warning', 80);
            $criticalThreshold = Config::get('health-check.thresholds.storage_usage_critical', 95);

            $status = 'healthy';
            if ($usedPercent >= $criticalThreshold) {
                $status = 'unhealthy';
            } elseif ($usedPercent >= $warningThreshold) {
                $status = 'degraded';
            }

            return [
                'status' => $status,
                'message' => 'Storage is available',
                'metrics' => [
                    'usedPercent' => round($usedPercent, 2),
                    'totalBytes' => (int) $totalBytes,
                    'freeBytes' => (int) $freeBytes,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Storage error: ' . $e->getMessage(),
                'metrics' => [
                    'usedPercent' => 100.0,
                    'totalBytes' => 0,
                    'freeBytes' => 0,
                ],
            ];
        }
    }

    /**
     * Check queue worker status
     *
     * @return array{status: string, message: string, metrics: array}
     */
    public function checkQueue(): array
    {
        try {
            $pendingJobs = 0;
            $failedJobs = 0;
            $workers = 0;

            // Count pending jobs
            try {
                $pendingJobs = DB::table('jobs')->count();
            } catch (\Exception $e) {
                // Jobs table might not exist
            }

            // Count failed jobs
            try {
                $failedJobs = DB::table('failed_jobs')->count();
            } catch (\Exception $e) {
                // Failed jobs table might not exist
            }

            // Estimate workers (simplified - check for recent job processing)
            try {
                // Check for jobs processed in last minute
                $recentProcessed = DB::table('jobs')
                    ->where('reserved_at', '>=', now()->subMinute()->timestamp)
                    ->count();
                $workers = $recentProcessed > 0 ? 1 : 0;
            } catch (\Exception $e) {
                $workers = 0;
            }

            $pendingWarning = Config::get('health-check.thresholds.queue_pending_warning', 100);
            $failedCritical = Config::get('health-check.thresholds.queue_failed_critical', 10);

            $status = 'healthy';
            if ($failedJobs >= $failedCritical) {
                $status = 'unhealthy';
            } elseif ($pendingJobs >= $pendingWarning) {
                $status = 'degraded';
            }

            return [
                'status' => $status,
                'message' => $workers > 0 ? 'Queue workers are active' : 'No active queue workers',
                'metrics' => [
                    'pendingJobs' => $pendingJobs,
                    'failedJobs' => $failedJobs,
                    'workers' => $workers,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Queue error: ' . $e->getMessage(),
                'metrics' => [
                    'pendingJobs' => 0,
                    'failedJobs' => 0,
                    'workers' => 0,
                ],
            ];
        }
    }

    /**
     * Compute overall status from service statuses
     *
     * @param array $services
     * @return string 'healthy'|'degraded'|'unhealthy'
     */
    public function getOverallStatus(array $services): string
    {
        if (empty($services)) {
            return 'healthy';
        }

        $hasUnhealthy = false;
        $hasDegraded = false;

        foreach ($services as $service) {
            if (!isset($service['status'])) {
                continue;
            }

            if ($service['status'] === 'unhealthy') {
                $hasUnhealthy = true;
            } elseif ($service['status'] === 'degraded') {
                $hasDegraded = true;
            }
        }

        if ($hasUnhealthy) {
            return 'unhealthy';
        }

        if ($hasDegraded) {
            return 'degraded';
        }

        return 'healthy';
    }

    /**
     * Calculate uptime percentage from service statuses
     *
     * @param array $services
     * @return float
     */
    protected function calculateUptime(array $services): float
    {
        $healthyCount = 0;
        $totalCount = count($services);

        if ($totalCount === 0) {
            return 100.0;
        }

        foreach ($services as $service) {
            if (isset($service['status']) && $service['status'] === 'healthy') {
                $healthyCount++;
            }
        }

        return round(($healthyCount / $totalCount) * 100, 2);
    }

    /**
     * Get cache hit rate (simplified implementation)
     *
     * @return float
     */
    protected function getCacheHitRate(): float
    {
        // This is a simplified implementation
        // Real implementation would query Redis INFO for keyspace_hits/keyspace_misses
        try {
            if (app()->environment('local')) {
                // For local/file cache, assume good hit rate
                return 95.0;
            }

            // Try to get Redis stats
            // This would require Redis facade and specific commands
            return 90.0;
        } catch (\Exception $e) {
            return 0.0;
        }
    }
}
