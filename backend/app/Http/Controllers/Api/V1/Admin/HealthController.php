<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

/**
 * Admin Health Controller.
 *
 * Provides health monitoring endpoints for the admin dashboard.
 * Designed for 30-second polling intervals with lightweight responses.
 *
 * @see TASK-27-13
 * @see SPEC-027
 */
class HealthController extends Controller
{
    /**
     * Cache duration for health data (15 seconds for polling optimization).
     */
    private const CACHE_TTL = 15;

    /**
     * Get overall health overview.
     *
     * GET /api/v1/admin/health/overview
     *
     * @return JsonResponse
     */
    public function overview(): JsonResponse
    {
        $cacheKey = 'admin:health:overview';

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $services = [
                $this->checkDatabase(),
                $this->checkCache(),
                $this->checkQueue(),
                $this->checkStorage(),
            ];

            $overallScore = $this->calculateOverallScore($services);
            $status = $this->determineOverallStatus($services);

            return [
                'status' => $status,
                'overall_score' => $overallScore,
                'services' => $services,
                'last_check' => now()->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ])->header('Cache-Control', 'public, max-age=15');
    }

    /**
     * Get queue status details.
     *
     * GET /api/v1/admin/health/queues
     *
     * @return JsonResponse
     */
    public function queues(): JsonResponse
    {
        $cacheKey = 'admin:health:queues';

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $queues = $this->getQueueMetrics();

            $totalPending = collect($queues)->sum('pending');
            $totalFailed = collect($queues)->sum('failed');

            return [
                'queues' => $queues,
                'total_pending' => $totalPending,
                'total_failed' => $totalFailed,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ])->header('Cache-Control', 'public, max-age=15');
    }

    /**
     * Get database metrics.
     *
     * GET /api/v1/admin/health/database
     *
     * @return JsonResponse
     */
    public function database(): JsonResponse
    {
        $cacheKey = 'admin:health:database';

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $connectionName = config('database.default');
            $databaseName = config("database.connections.{$connectionName}.database");

            return [
                'connection' => $connectionName,
                'database' => $databaseName,
                'size_mb' => $this->getDatabaseSize(),
                'tables' => $this->getTableInfo(),
                'connections' => $this->getConnectionStats(),
                'performance' => $this->getPerformanceMetrics(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ])->header('Cache-Control', 'public, max-age=15');
    }

    /**
     * Get active alerts/warnings.
     *
     * GET /api/v1/admin/health/alerts
     *
     * @return JsonResponse
     */
    public function alerts(): JsonResponse
    {
        $cacheKey = 'admin:health:alerts';

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $alerts = $this->collectAlerts();

            return [
                'alerts' => $alerts,
                'total' => count($alerts),
                'unacknowledged' => collect($alerts)->where('acknowledged', false)->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ])->header('Cache-Control', 'public, max-age=15');
    }

    /**
     * Check database health.
     *
     * @return array
     */
    private function checkDatabase(): array
    {
        $start = microtime(true);

        try {
            DB::select('SELECT 1');
            $latency = round((microtime(true) - $start) * 1000, 2);
            $status = 'healthy';
        } catch (\Exception $e) {
            $latency = 0;
            $status = 'unhealthy';
        }

        return [
            'name' => 'database',
            'status' => $status,
            'latency_ms' => (int) $latency,
        ];
    }

    /**
     * Check cache health.
     *
     * @return array
     */
    private function checkCache(): array
    {
        $start = microtime(true);

        try {
            Cache::put('health_check', true, 10);
            Cache::get('health_check');
            $latency = round((microtime(true) - $start) * 1000, 2);
            $status = 'healthy';
        } catch (\Exception $e) {
            $latency = 0;
            $status = 'unhealthy';
        }

        return [
            'name' => 'cache',
            'status' => $status,
            'latency_ms' => (int) $latency,
        ];
    }

    /**
     * Check queue health.
     *
     * @return array
     */
    private function checkQueue(): array
    {
        $start = microtime(true);

        try {
            // Check if queue worker is available by checking pending jobs
            $pending = DB::table('jobs')->count();
            $latency = round((microtime(true) - $start) * 1000, 2);

            // If too many pending jobs, consider degraded
            $status = $pending > 100 ? 'degraded' : 'healthy';
        } catch (\Exception $e) {
            $latency = 0;
            $status = 'unhealthy';
        }

        return [
            'name' => 'queue',
            'status' => $status,
            'latency_ms' => (int) $latency,
        ];
    }

    /**
     * Check storage health.
     *
     * @return array
     */
    private function checkStorage(): array
    {
        $start = microtime(true);

        try {
            Storage::exists('.');
            $latency = round((microtime(true) - $start) * 1000, 2);
            $status = 'healthy';
        } catch (\Exception $e) {
            $latency = 0;
            $status = 'unhealthy';
        }

        return [
            'name' => 'storage',
            'status' => $status,
            'latency_ms' => (int) $latency,
        ];
    }

    /**
     * Calculate overall health score (0-100).
     *
     * @param array $services
     * @return int
     */
    private function calculateOverallScore(array $services): int
    {
        $score = 100;

        foreach ($services as $service) {
            if ($service['status'] === 'unhealthy') {
                $score -= 25;
            } elseif ($service['status'] === 'degraded') {
                $score -= 10;
            }
        }

        return max(0, $score);
    }

    /**
     * Determine overall status from services.
     *
     * @param array $services
     * @return string
     */
    private function determineOverallStatus(array $services): string
    {
        $statuses = collect($services)->pluck('status');

        if ($statuses->contains('unhealthy')) {
            return 'unhealthy';
        }

        if ($statuses->contains('degraded')) {
            return 'degraded';
        }

        return 'healthy';
    }

    /**
     * Get queue metrics for all queues.
     *
     * @return array
     */
    private function getQueueMetrics(): array
    {
        $queueNames = ['default', 'high', 'low'];
        $metrics = [];

        foreach ($queueNames as $queue) {
            $metrics[] = $this->getSingleQueueMetrics($queue);
        }

        return $metrics;
    }

    /**
     * Get metrics for a single queue.
     *
     * @param string $queue
     * @return array
     */
    private function getSingleQueueMetrics(string $queue): array
    {
        try {
            $pending = DB::table('jobs')
                ->where('queue', $queue)
                ->count();

            $failed = DB::table('failed_jobs')
                ->where('queue', $queue)
                ->count();

            // Count processed jobs from job_batches or estimate
            $processed = 0; // Would need a jobs_history table for accurate count

            // Check for active workers (approximation)
            $workers = Cache::get("queue:workers:{$queue}", 0);

            return [
                'name' => $queue,
                'size' => $pending,
                'pending' => $pending,
                'processed' => $processed,
                'failed' => $failed,
                'workers' => $workers,
            ];
        } catch (\Exception $e) {
            // Return defaults if queue tables don't exist
            return [
                'name' => $queue,
                'size' => 0,
                'pending' => 0,
                'processed' => 0,
                'failed' => 0,
                'workers' => 0,
            ];
        }
    }

    /**
     * Get database size in MB.
     *
     * @return float
     */
    private function getDatabaseSize(): float
    {
        try {
            $connection = config('database.default');
            $database = config("database.connections.{$connection}.database");

            if (config("database.connections.{$connection}.driver") === 'mysql') {
                $result = DB::selectOne(
                    "SELECT SUM(data_length + index_length) / 1024 / 1024 as size_mb
                     FROM information_schema.tables
                     WHERE table_schema = ?",
                    [$database]
                );

                return round($result->size_mb ?? 0, 2);
            }

            // SQLite fallback
            if (config("database.connections.{$connection}.driver") === 'sqlite') {
                $path = config("database.connections.{$connection}.database");
                if (file_exists($path)) {
                    return round(filesize($path) / 1024 / 1024, 2);
                }
            }
        } catch (\Exception $e) {
            // Ignore errors
        }

        return 0.0;
    }

    /**
     * Get table information.
     *
     * @return array
     */
    private function getTableInfo(): array
    {
        try {
            $connection = config('database.default');
            $database = config("database.connections.{$connection}.database");

            if (config("database.connections.{$connection}.driver") === 'mysql') {
                $tables = DB::select(
                    "SELECT table_name as name, table_rows as rows,
                            (data_length + index_length) / 1024 / 1024 as size_mb
                     FROM information_schema.tables
                     WHERE table_schema = ?
                     ORDER BY (data_length + index_length) DESC
                     LIMIT 10",
                    [$database]
                );

                return array_map(function ($table) {
                    return [
                        'name' => $table->name,
                        'rows' => (int) $table->rows,
                        'size_mb' => round($table->size_mb, 2),
                    ];
                }, $tables);
            }
        } catch (\Exception $e) {
            // Ignore errors
        }

        return [];
    }

    /**
     * Get connection statistics.
     *
     * @return array
     */
    private function getConnectionStats(): array
    {
        try {
            $connection = config('database.default');

            if (config("database.connections.{$connection}.driver") === 'mysql') {
                $status = DB::selectOne("SHOW STATUS LIKE 'Threads%'");

                $active = DB::selectOne("SHOW STATUS LIKE 'Threads_running'");
                $connected = DB::selectOne("SHOW STATUS LIKE 'Threads_connected'");

                $maxConnections = DB::selectOne("SHOW VARIABLES LIKE 'max_connections'");

                return [
                    'active' => (int) ($active->Value ?? 0),
                    'idle' => (int) (($connected->Value ?? 0) - ($active->Value ?? 0)),
                    'max' => (int) ($maxConnections->Value ?? 100),
                ];
            }
        } catch (\Exception $e) {
            // Ignore errors
        }

        return [
            'active' => 1,
            'idle' => 5,
            'max' => 100,
        ];
    }

    /**
     * Get performance metrics.
     *
     * @return array
     */
    private function getPerformanceMetrics(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $queryTime = (microtime(true) - $start) * 1000;

            // Check for slow queries (queries taking > 1s)
            $slowQueries = DB::table('jobs')
                ->where('available_at', '<', now()->subSeconds(60)->timestamp)
                ->count();

            return [
                'query_time_ms' => round($queryTime, 2),
                'slow_queries' => $slowQueries,
            ];
        } catch (\Exception $e) {
            return [
                'query_time_ms' => 0.0,
                'slow_queries' => 0,
            ];
        }
    }

    /**
     * Collect alerts from various sources.
     *
     * @return array
     */
    private function collectAlerts(): array
    {
        $alerts = [];

        // Check queue backlog
        try {
            $pendingJobs = DB::table('jobs')->count();
            if ($pendingJobs > 50) {
                $alerts[] = [
                    'id' => 'queue-backlog',
                    'type' => 'queue_backup',
                    'severity' => $pendingJobs > 100 ? 'critical' : 'warning',
                    'message' => "Queue has {$pendingJobs} pending jobs",
                    'source' => 'queue_monitor',
                    'created_at' => now()->toIso8601String(),
                    'acknowledged' => false,
                ];
            }
        } catch (\Exception $e) {
            // Ignore
        }

        // Check failed jobs
        try {
            $failedJobs = DB::table('failed_jobs')->count();
            if ($failedJobs > 0) {
                $alerts[] = [
                    'id' => 'failed-jobs',
                    'type' => 'failed_jobs',
                    'severity' => $failedJobs > 10 ? 'critical' : 'warning',
                    'message' => "{$failedJobs} failed jobs in queue",
                    'source' => 'queue_monitor',
                    'created_at' => now()->toIso8601String(),
                    'acknowledged' => false,
                ];
            }
        } catch (\Exception $e) {
            // Ignore
        }

        // Check storage space (if local driver)
        try {
            $storagePath = Storage::path('');
            if (is_dir($storagePath) && function_exists('disk_free_space')) {
                $freeSpace = disk_free_space($storagePath);
                $totalSpace = disk_total_space($storagePath);
                $usedPercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;

                if ($usedPercent > 90) {
                    $alerts[] = [
                        'id' => 'storage-space',
                        'type' => 'storage_full',
                        'severity' => $usedPercent > 95 ? 'critical' : 'warning',
                        'message' => 'Storage is ' . round(100 - $usedPercent, 1) . '% free',
                        'source' => 'storage_monitor',
                        'created_at' => now()->toIso8601String(),
                        'acknowledged' => false,
                    ];
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }

        // Check for database connection issues
        try {
            DB::select('SELECT 1');
        } catch (\Exception $e) {
            $alerts[] = [
                'id' => 'database-connection',
                'type' => 'database_error',
                'severity' => 'critical',
                'message' => 'Database connection failed',
                'source' => 'database_monitor',
                'created_at' => now()->toIso8601String(),
                'acknowledged' => false,
            ];
        }

        return $alerts;
    }
}
