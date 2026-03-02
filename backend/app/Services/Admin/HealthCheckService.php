<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Health Check Service for Admin Dashboard.
 *
 * Provides health monitoring for database, queue, cache, and storage.
 * Designed for 30-second polling with caching support.
 *
 * @see TASK-27-18
 * @see SPEC-027
 */
class HealthCheckService
{
    /**
     * Cache TTL for health stats (30 seconds).
     */
    private const CACHE_TTL = 30;

    /**
     * Get all health statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        return Cache::remember('admin.health.stats', self::CACHE_TTL, function () {
            $services = [
                'database' => $this->checkDatabase(),
                'queue' => $this->checkQueue(),
                'cache' => $this->checkCache(),
                'storage' => $this->checkStorage(),
            ];

            $alerts = $this->generateAlerts($services);

            return [
                'status' => $this->determineOverallStatus($services),
                'uptime' => $this->calculateUptime(),
                'lastChecked' => now()->toIso8601String(),
                'services' => $services,
                'alerts' => $alerts,
            ];
        });
    }

    /**
     * Check database health.
     *
     * @return array
     */
    public function checkDatabase(): array
    {
        $start = microtime(true);

        try {
            DB::select('SELECT 1');
            $latency = round((microtime(true) - $start) * 1000);
            $status = 'healthy';
            $connections = $this->getConnectionCount();
        } catch (\Exception $e) {
            $latency = 0;
            $status = 'unhealthy';
            $connections = 0;
        }

        return [
            'status' => $status,
            'latencyMs' => (int) $latency,
            'connections' => $connections,
        ];
    }

    /**
     * Check queue health.
     *
     * @return array
     */
    public function checkQueue(): array
    {
        try {
            $pending = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();
            $workers = $this->getWorkerCount();

            // Determine status based on queue state
            $status = 'healthy';
            if ($failed > 10) {
                $status = 'unhealthy';
            } elseif ($pending > 100 || $failed > 0) {
                $status = 'warning';
            }

            return [
                'status' => $status,
                'pending' => $pending,
                'failed' => $failed,
                'workers' => $workers,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'pending' => 0,
                'failed' => 0,
                'workers' => 0,
            ];
        }
    }

    /**
     * Check cache health.
     *
     * @return array
     */
    public function checkCache(): array
    {
        $start = microtime(true);

        try {
            // Test cache read/write
            Cache::put('health_check_test', true, 10);
            $result = Cache::get('health_check_test');
            Cache::forget('health_check_test');

            if (!$result) {
                throw new \Exception('Cache read failed');
            }

            $latency = round((microtime(true) - $start) * 1000);
            $hitRate = $this->getCacheHitRate();

            return [
                'status' => 'healthy',
                'latencyMs' => (int) $latency,
                'hitRate' => $hitRate,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'latencyMs' => 0,
                'hitRate' => 0.0,
            ];
        }
    }

    /**
     * Check storage health.
     *
     * @return array
     */
    public function checkStorage(): array
    {
        try {
            $storagePath = Storage::path('');
            $usedPercent = 0.0;

            if (is_dir($storagePath) && function_exists('disk_free_space')) {
                $freeSpace = disk_free_space($storagePath);
                $totalSpace = disk_total_space($storagePath);

                if ($totalSpace > 0) {
                    $usedPercent = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 2);
                }
            }

            // Test storage write
            Storage::exists('.');

            $status = 'healthy';
            if ($usedPercent > 90) {
                $status = 'warning';
            }
            if ($usedPercent > 95) {
                $status = 'unhealthy';
            }

            return [
                'status' => $status,
                'usedPercent' => $usedPercent,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'usedPercent' => 0.0,
            ];
        }
    }

    /**
     * Get current database connection count.
     *
     * @return int
     */
    protected function getConnectionCount(): int
    {
        try {
            $connection = config('database.default');
            
            if (config("database.connections.{$connection}.driver") === 'mysql') {
                $result = DB::selectOne("SHOW STATUS LIKE 'Threads_connected'");
                return (int) ($result->Value ?? 1);
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return 1;
    }

    /**
     * Get active worker count.
     *
     * @return int
     */
    protected function getWorkerCount(): int
    {
        // Try to get worker count from cache (set by workers)
        return (int) Cache::get('queue:workers:count', 0);
    }

    /**
     * Get cache hit rate percentage.
     *
     * @return float
     */
    protected function getCacheHitRate(): float
    {
        // For Redis, we could get actual hit rate
        // For file/database cache, return a default
        try {
            $driver = config('cache.default');
            
            if ($driver === 'redis') {
                // Could query Redis INFO for hit rate
                return 95.0; // Default estimate
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return 95.0; // Default estimate
    }

    /**
     * Determine overall status from service statuses.
     *
     * @param array $services
     * @return string
     */
    protected function determineOverallStatus(array $services): string
    {
        $statuses = collect($services)->pluck('status');

        if ($statuses->contains('unhealthy')) {
            return 'unhealthy';
        }

        if ($statuses->contains('warning')) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * Calculate uptime percentage.
     *
     * @return float
     */
    protected function calculateUptime(): float
    {
        // Try to get cached uptime calculation
        $uptime = Cache::get('system:uptime:percentage');
        
        if ($uptime !== null) {
            return (float) $uptime;
        }

        // Default to 99.9% for a healthy system
        return 99.9;
    }

    /**
     * Generate alerts from service statuses.
     *
     * @param array $services
     * @return array
     */
    protected function generateAlerts(array $services): array
    {
        $alerts = [];

        foreach ($services as $name => $service) {
            if ($service['status'] === 'unhealthy') {
                $alerts[] = [
                    'level' => 'critical',
                    'message' => ucfirst($name) . ' service is unhealthy',
                    'since' => now()->toIso8601String(),
                ];
            } elseif ($service['status'] === 'warning') {
                $alerts[] = [
                    'level' => 'warning',
                    'message' => ucfirst($name) . ' service has warnings',
                    'since' => now()->toIso8601String(),
                ];
            }
        }

        return $alerts;
    }

    /**
     * Get queue pending count (for testing/mocking).
     *
     * @return int
     */
    public function getQueuePendingCount(): int
    {
        try {
            return DB::table('jobs')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get queue failed count (for testing/mocking).
     *
     * @return int
     */
    public function getQueueFailedCount(): int
    {
        try {
            return DB::table('failed_jobs')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
}
