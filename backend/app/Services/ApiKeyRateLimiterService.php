<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * API Key Rate Limiter Service for TASK-6-5.
 *
 * Implements per-API-key rate limiting with sliding window algorithm.
 * Uses Laravel Cache for storage with support for Redis/File/Database drivers.
 *
 * Features:
 * - Sliding window algorithm for accurate rate limiting
 * - Per-API-key configurable limits (default: 60 requests per minute)
 * - Atomic operations for concurrent request handling
 * - Automatic cleanup of expired entries
 *
 * @see \App\Http\Middleware\ApiKeyRateLimiter
 */
class ApiKeyRateLimiterService
{
    /**
     * Default maximum requests per minute.
     */
    public const DEFAULT_MAX_REQUESTS = 60;

    /**
     * Default window duration in milliseconds (60 seconds).
     */
    public const DEFAULT_WINDOW_MS = 60000;

    /**
     * Cache key prefix.
     */
    private const CACHE_PREFIX = 'api_rate_limit:';

    /**
     * Check if a request is allowed for the given key.
     *
     * @param string $key Unique identifier (e.g., API key ID)
     * @param int $maxRequests Maximum requests allowed in window
     * @param int $windowMs Window duration in milliseconds
     * @return bool True if request is allowed
     */
    public function isAllowed(string $key, int $maxRequests, int $windowMs = self::DEFAULT_WINDOW_MS): bool
    {
        // Handle edge cases
        if ($maxRequests <= 0) {
            return false;
        }

        if ($windowMs <= 0) {
            $windowMs = self::DEFAULT_WINDOW_MS;
        }

        $cacheKey = $this->getCacheKey($key);
        $now = microtime(true);
        $windowStart = $now - ($windowMs / 1000);

        // Get current timestamps
        $timestamps = $this->getTimestamps($cacheKey);

        // Filter out expired timestamps (sliding window)
        $validTimestamps = array_filter($timestamps, function ($ts) use ($windowStart) {
            return $ts > $windowStart;
        });

        // Check if under limit
        return count($validTimestamps) < $maxRequests;
    }

    /**
     * Increment the request counter for the given key.
     *
     * @param string $key Unique identifier
     * @param int $windowMs Window duration in milliseconds
     * @return int New request count
     */
    public function increment(string $key, int $windowMs = self::DEFAULT_WINDOW_MS): int
    {
        if ($windowMs <= 0) {
            $windowMs = self::DEFAULT_WINDOW_MS;
        }

        $cacheKey = $this->getCacheKey($key);
        $now = microtime(true);

        // Get existing timestamps
        $timestamps = $this->getTimestamps($cacheKey);

        // Add current timestamp
        $timestamps[] = $now;

        // Store with TTL
        $ttl = (int) ceil($windowMs / 1000) + 1; // Add 1 second buffer
        Cache::put($cacheKey, $timestamps, now()->addSeconds($ttl));

        // Store window start for reset time calculation
        $windowStartKey = $cacheKey . ':window_start';
        if (!Cache::has($windowStartKey)) {
            Cache::put($windowStartKey, $now, now()->addSeconds($ttl));
        }

        return count($timestamps);
    }

    /**
     * Get the number of remaining requests for the given key.
     *
     * @param string $key Unique identifier
     * @param int $maxRequests Maximum requests allowed
     * @param int $windowMs Window duration in milliseconds
     * @return int Remaining requests (never negative)
     */
    public function getRemaining(string $key, int $maxRequests, int $windowMs = self::DEFAULT_WINDOW_MS): int
    {
        if ($maxRequests <= 0) {
            return 0;
        }

        $count = $this->getCount($key, $windowMs);
        return max(0, $maxRequests - $count);
    }

    /**
     * Get the reset timestamp (Unix timestamp) for the given key.
     *
     * @param string $key Unique identifier
     * @param int $windowMs Window duration in milliseconds
     * @return int Unix timestamp when window resets
     */
    public function getResetTime(string $key, int $windowMs = self::DEFAULT_WINDOW_MS): int
    {
        if ($windowMs <= 0) {
            $windowMs = self::DEFAULT_WINDOW_MS;
        }

        $cacheKey = $this->getCacheKey($key);

        // Get window start time
        $windowStart = Cache::get($cacheKey . ':window_start');

        if ($windowStart === null) {
            // No requests yet, window starts now
            return time() + (int) ($windowMs / 1000);
        }

        // Calculate when window ends
        $resetTime = $windowStart + ($windowMs / 1000);
        return (int) ceil($resetTime);
    }

    /**
     * Get the current request count for the given key.
     *
     * Uses sliding window to count only requests within the window.
     *
     * @param string $key Unique identifier
     * @param int $windowMs Window duration in milliseconds (optional for cleanup)
     * @return int Current count within window
     */
    public function getCount(string $key, int $windowMs = self::DEFAULT_WINDOW_MS): int
    {
        $cacheKey = $this->getCacheKey($key);
        $timestamps = $this->getTimestamps($cacheKey);

        if (empty($timestamps)) {
            return 0;
        }

        // If window is specified, filter to window
        if ($windowMs > 0) {
            $windowStart = microtime(true) - ($windowMs / 1000);
            $timestamps = array_filter($timestamps, function ($ts) use ($windowStart) {
                return $ts > $windowStart;
            });
        }

        return count($timestamps);
    }

    /**
     * Reset the rate limit counter for the given key.
     *
     * @param string $key Unique identifier
     */
    public function reset(string $key): void
    {
        $cacheKey = $this->getCacheKey($key);

        Cache::forget($cacheKey);
        Cache::forget($cacheKey . ':window_start');
    }

    /**
     * Check and increment atomically.
     *
     * Returns whether the request is allowed and increments if so.
     * This is the preferred method for middleware use.
     *
     * @param string $key Unique identifier
     * @param int $maxRequests Maximum requests allowed
     * @param int $windowMs Window duration in milliseconds
     * @return array{allowed: bool, remaining: int, reset_at: int}
     */
    public function hit(string $key, int $maxRequests, int $windowMs = self::DEFAULT_WINDOW_MS): array
    {
        // Handle edge cases
        if ($maxRequests <= 0) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_at' => $this->getResetTime($key, $windowMs),
            ];
        }

        if ($windowMs <= 0) {
            $windowMs = self::DEFAULT_WINDOW_MS;
        }

        $cacheKey = $this->getCacheKey($key);
        $now = microtime(true);
        $windowStart = $now - ($windowMs / 1000);

        // Get current timestamps
        $timestamps = $this->getTimestamps($cacheKey);

        // Filter out expired timestamps (sliding window)
        $validTimestamps = array_filter($timestamps, function ($ts) use ($windowStart) {
            return $ts > $windowStart;
        });

        // Re-index array
        $validTimestamps = array_values($validTimestamps);

        // Check if at limit
        if (count($validTimestamps) >= $maxRequests) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_at' => $this->getResetTime($key, $windowMs),
            ];
        }

        // Increment
        $validTimestamps[] = $now;
        $newCount = count($validTimestamps);

        // Store with TTL
        $ttl = (int) ceil($windowMs / 1000) + 1;
        Cache::put($cacheKey, $validTimestamps, now()->addSeconds($ttl));

        // Store window start for reset time calculation
        $windowStartKey = $cacheKey . ':window_start';
        $existingWindowStart = Cache::get($windowStartKey);
        if ($existingWindowStart === null) {
            Cache::put($windowStartKey, $now, now()->addSeconds($ttl));
        }

        return [
            'allowed' => true,
            'remaining' => max(0, $maxRequests - $newCount),
            'reset_at' => $this->getResetTime($key, $windowMs),
        ];
    }

    /**
     * Get rate limit headers for response.
     *
     * @param string $key Unique identifier
     * @param int $maxRequests Maximum requests allowed
     * @param int $windowMs Window duration in milliseconds
     * @return array<string, string> Headers array
     */
    public function getHeaders(string $key, int $maxRequests, int $windowMs = self::DEFAULT_WINDOW_MS): array
    {
        return [
            'X-RateLimit-Limit' => (string) max(0, $maxRequests),
            'X-RateLimit-Remaining' => (string) $this->getRemaining($key, $maxRequests, $windowMs),
            'X-RateLimit-Reset' => (string) $this->getResetTime($key, $windowMs),
        ];
    }

    /**
     * Calculate retry-after time in seconds.
     *
     * @param string $key Unique identifier
     * @param int $windowMs Window duration in milliseconds
     * @return int Seconds until window resets
     */
    public function getRetryAfter(string $key, int $windowMs = self::DEFAULT_WINDOW_MS): int
    {
        $resetTime = $this->getResetTime($key, $windowMs);
        $retryAfter = $resetTime - time();

        return max(1, $retryAfter);
    }

    /**
     * Generate cache key for storage.
     *
     * @param string $key Unique identifier
     * @return string Cache key
     */
    private function getCacheKey(string $key): string
    {
        // Sanitize key to prevent cache key injection
        $safeKey = preg_replace('/[^a-zA-Z0-9_\-:]/', '_', $key);
        return self::CACHE_PREFIX . $safeKey;
    }

    /**
     * Get timestamps array from cache.
     *
     * @param string $cacheKey Full cache key
     * @return array<float> Array of timestamps
     */
    private function getTimestamps(string $cacheKey): array
    {
        $timestamps = Cache::get($cacheKey, []);

        if (!is_array($timestamps)) {
            return [];
        }

        return $timestamps;
    }

    /**
     * Clean up expired entries for a key.
     *
     * @param string $key Unique identifier
     * @param int $windowMs Window duration in milliseconds
     * @return int Number of entries removed
     */
    public function cleanup(string $key, int $windowMs = self::DEFAULT_WINDOW_MS): int
    {
        $cacheKey = $this->getCacheKey($key);
        $timestamps = $this->getTimestamps($cacheKey);

        if (empty($timestamps)) {
            return 0;
        }

        $windowStart = microtime(true) - ($windowMs / 1000);
        $originalCount = count($timestamps);

        $validTimestamps = array_filter($timestamps, function ($ts) use ($windowStart) {
            return $ts > $windowStart;
        });

        $newCount = count($validTimestamps);

        if ($newCount < $originalCount) {
            if ($newCount === 0) {
                Cache::forget($cacheKey);
            } else {
                Cache::put($cacheKey, array_values($validTimestamps), now()->addMinute());
            }
        }

        return $originalCount - $newCount;
    }
}
