<?php

namespace App\Services\Admin;

use DateTime;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Request Tracking Service (TASK-27-6)
 * 
 * Handles request tracking data storage using Redis for lightweight tracking
 * with optional database aggregation.
 * 
 * Features:
 * - Redis-based lightweight storage
 * - Atomic counters for endpoint statistics
 * - Hourly aggregation
 * - Graceful error handling
 * - Database persistence support
 * 
 * @see SPEC-027: Admin Dashboard (Ops + Usage)
 */
class RequestTrackingService
{
    /**
     * Redis key prefix.
     */
    protected string $keyPrefix;

    /**
     * TTL for Redis keys (24 hours in seconds).
     */
    protected int $ttl;

    /**
     * Batch size for database flush operations.
     */
    protected int $batchSize;

    /**
     * Create a new service instance.
     */
    public function __construct(
        ?string $keyPrefix = null,
        ?int $ttl = null,
        int $batchSize = 100
    ) {
        $this->keyPrefix = $keyPrefix ?? config('request-tracking.key_prefix', 'request_tracking:');
        $this->ttl = $ttl ?? config('request-tracking.ttl', 86400);
        $this->batchSize = $batchSize;
    }

    /**
     * Record a request in the tracking queue.
     * 
     * @param string $endpoint Normalized endpoint path
     * @param string $method HTTP method
     * @param int|null $userId Authenticated user ID or null
     * @param DateTimeInterface $timestamp Request timestamp
     * @return bool True on success, false on failure
     */
    public function record(
        string $endpoint,
        string $method,
        ?int $userId,
        DateTimeInterface $timestamp
    ): bool {
        try {
            $data = [
                'endpoint' => $endpoint,
                'method' => $method,
                'user_id' => $userId,
                'timestamp' => $timestamp->format('c'), // ISO 8601
            ];

            $queueKey = $this->getQueueKey($timestamp);
            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

            // Push to Redis queue
            Redis::rpush($queueKey, $jsonData);

            // Set TTL on queue key (24 hours)
            Redis::expire($queueKey, $this->ttl);

            // Also increment endpoint counter
            $this->incrementEndpoint($endpoint, $method, $timestamp);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Request tracking failed', [
                'error' => $e->getMessage(),
                'endpoint' => $endpoint,
            ]);

            return false;
        }
    }

    /**
     * Increment the counter for an endpoint+method combination.
     * 
     * @param string $endpoint Normalized endpoint path
     * @param string $method HTTP method
     * @param DateTimeInterface|null $timestamp Timestamp for key (defaults to now)
     * @return bool True on success, false on failure
     */
    public function incrementEndpoint(
        string $endpoint,
        string $method,
        ?DateTimeInterface $timestamp = null
    ): bool {
        try {
            $timestamp = $timestamp ?? new DateTime();
            $key = $this->getEndpointKey($endpoint, $method, $timestamp);

            // Atomic increment
            $count = Redis::incr($key);

            // Set TTL on new keys (24 hours)
            if ($count === 1) {
                Redis::expire($key, $this->ttl);
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get endpoint statistics from Redis.
     * 
     * @return array<string, array<string, int>> Endpoint stats grouped by method
     */
    public function getEndpointStats(): array
    {
        try {
            $pattern = $this->keyPrefix . 'endpoint:*';
            $keys = Redis::keys($pattern);

            $stats = [];

            foreach ($keys as $key) {
                // Parse endpoint and method from key
                // Format: request_tracking:endpoint:api/v1/users:GET:2026030410
                if (preg_match('/endpoint:([^:]+):([^:]+):\d+$/', $key, $matches)) {
                    $endpoint = $matches[1];
                    $method = $matches[2];
                    $count = (int) Redis::get($key);

                    if (!isset($stats[$endpoint])) {
                        $stats[$endpoint] = [];
                    }

                    if (!isset($stats[$endpoint][$method])) {
                        $stats[$endpoint][$method] = 0;
                    }

                    $stats[$endpoint][$method] += $count;
                }
            }

            return $stats;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Get hourly request breakdown for a specific date.
     * 
     * @param string $date Date in Y-m-d format
     * @return array<string, int> Hourly totals (e.g., ['10:00' => 150, '11:00' => 200])
     */
    public function getRequestsByPeriod(string $date): array
    {
        try {
            $dateKey = str_replace('-', '', $date); // 20260304
            $pattern = $this->keyPrefix . "endpoint:*";
            $keys = Redis::keys($pattern);

            $breakdown = [];

            foreach ($keys as $key) {
                // Key format: request_tracking:endpoint:api/v1/users:GET:2026030410
                // Extract hour from key (last 2 digits of the 10-digit suffix)
                if (preg_match('/:(\d{10})$/', $key, $matches)) {
                    $dateHourKey = $matches[1];
                    $keyDate = substr($dateHourKey, 0, 8); // YYYYMMDD
                    $hour = substr($dateHourKey, 8, 2); // HH
                    
                    // Only count if matches the requested date
                    if ($keyDate === $dateKey) {
                        $hourKey = "{$hour}:00";
                        $count = (int) Redis::get($key);

                        if (!isset($breakdown[$hourKey])) {
                            $breakdown[$hourKey] = 0;
                        }

                        $breakdown[$hourKey] += $count;
                    }
                }
            }

            // Sort by hour
            ksort($breakdown);

            return $breakdown;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Flush tracking data from Redis queue to database.
     * 
     * @return bool True on success, false on failure
     */
    public function flushToDatabase(): bool
    {
        try {
            $today = new DateTime();
            $queueKey = $this->getQueueKey($today);

            // Get all items from queue
            $items = Redis::lrange($queueKey, 0, -1);

            if (empty($items)) {
                return true;
            }

            // Process in batches
            $batch = [];
            $success = true;

            foreach ($items as $index => $jsonData) {
                $data = json_decode($jsonData, true);

                // Skip invalid JSON or missing required fields
                if ($data === null || !isset($data['endpoint'], $data['method'], $data['timestamp'])) {
                    continue;
                }

                $batch[] = [
                    'endpoint' => $data['endpoint'],
                    'method' => $data['method'],
                    'user_id' => $data['user_id'],
                    'timestamp' => $data['timestamp'],
                    'created_at' => now(),
                ];

                // Insert batch when full
                if (count($batch) >= $this->batchSize) {
                    try {
                        DB::table('request_logs')->insert($batch);
                    } catch (\Throwable $e) {
                        Log::warning('Failed to insert request tracking batch', [
                            'error' => $e->getMessage(),
                        ]);
                        $success = false;
                    }
                    $batch = [];
                }
            }

            // Insert remaining items
            if (!empty($batch)) {
                try {
                    DB::table('request_logs')->insert($batch);
                } catch (\Throwable $e) {
                    Log::warning('Failed to insert final request tracking batch', [
                        'error' => $e->getMessage(),
                    ]);
                    $success = false;
                }
            }

            // Only trim queue on success
            if ($success) {
                Redis::ltrim($queueKey, 1, 0); // Clear queue
            }

            return $success;
        } catch (\Throwable $e) {
            Log::warning('Failed to flush request tracking to database', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Clear tracking data for a specific hour.
     * 
     * @param string $date Date in Y-m-d format
     * @param int $hour Hour (0-23)
     * @return bool True on success
     */
    public function clearHour(string $date, int $hour): bool
    {
        try {
            $dateKey = str_replace('-', '', $date);
            $hourStr = str_pad($hour, 2, '0', STR_PAD_LEFT);
            $dateHourKey = $dateKey . $hourStr; // YYYYMMDDHH
            $pattern = $this->keyPrefix . "endpoint:*:{$dateHourKey}";
            $keys = Redis::keys($pattern);

            if (!empty($keys)) {
                Redis::del($keys);
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Clear all tracking data for a specific date.
     * 
     * @param string $date Date in Y-m-d format
     * @return bool True on success
     */
    public function clearDay(string $date): bool
    {
        try {
            $dateKey = str_replace('-', '', $date);
            $pattern = $this->keyPrefix . "endpoint:*:{$dateKey}*";
            $keys = Redis::keys($pattern);

            if (!empty($keys)) {
                Redis::del($keys);
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Clear all tracking data.
     * 
     * @return bool True on success
     */
    public function clear(): bool
    {
        try {
            $pattern = $this->keyPrefix . '*';
            $keys = Redis::keys($pattern);

            if (!empty($keys)) {
                Redis::del($keys);
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get the Redis key prefix.
     */
    public function getKeyPrefix(): string
    {
        return $this->keyPrefix;
    }

    /**
     * Get the TTL for Redis keys.
     */
    public function getTTL(): int
    {
        return $this->ttl;
    }

    /**
     * Get the queue key for a given timestamp.
     */
    protected function getQueueKey(DateTimeInterface $timestamp): string
    {
        return $this->keyPrefix . 'queue:' . $timestamp->format('Ymd');
    }

    /**
     * Get the endpoint counter key for a given endpoint, method, and timestamp.
     */
    protected function getEndpointKey(
        string $endpoint,
        string $method,
        DateTimeInterface $timestamp
    ): string {
        $hourKey = $timestamp->format('YmdH'); // YYYYMMDDHH

        return $this->keyPrefix . "endpoint:{$endpoint}:{$method}:{$hourKey}";
    }
}
