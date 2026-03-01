<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * AI Rate Limiter Service (TASK-21-10).
 *
 * Implements sliding window rate limiting for AI suggestion endpoints.
 * Provides per-user and per-IP rate limiting with configurable thresholds.
 *
 * Features:
 * - Sliding window algorithm for accurate rate limiting
 * - Per-user limits for authenticated users
 * - Per-IP limits for anonymous requests
 * - Configurable limits via config/ai.php
 * - Admin whitelist support
 *
 * @see https://seoindicator.com/docs/rate-limiting
 */
class AIRateLimiter
{
    /**
     * Default window duration in seconds (1 hour).
     */
    private const DEFAULT_WINDOW_SECONDS = 3600;

    /**
     * Check if the identifier is within the rate limit.
     *
     * @param string $identifier The unique identifier (e.g., "user:123" or "ip:127.0.0.1")
     * @param string $type The rate limit type ('user' or 'ip')
     * @return bool True if within limit, false if exceeded
     */
    public function checkLimit(string $identifier, string $type): bool
    {
        $key = $this->getCacheKey($identifier);
        $timestamps = $this->getValidTimestamps($key, $type);
        $limit = $this->getLimit($type);

        return count($timestamps) < $limit;
    }

    /**
     * Get the number of remaining requests for the identifier.
     *
     * @param string $identifier The unique identifier
     * @param string $type The rate limit type ('user' or 'ip')
     * @return int Number of remaining requests (minimum 0)
     */
    public function getRemaining(string $identifier, string $type): int
    {
        $key = $this->getCacheKey($identifier);
        $timestamps = $this->getValidTimestamps($key, $type);
        $limit = $this->getLimit($type);

        $remaining = $limit - count($timestamps);

        return max(0, $remaining);
    }

    /**
     * Get the Unix timestamp when the rate limit window resets.
     *
     * Returns the timestamp of the oldest request + window duration,
     * or current time + window if no requests exist.
     *
     * @param string $identifier The unique identifier
     * @param string $type The rate limit type ('user' or 'ip')
     * @return int Unix timestamp of reset time
     */
    public function getResetTime(string $identifier, string $type): int
    {
        $key = $this->getCacheKey($identifier);
        $timestamps = $this->getValidTimestamps($key, $type);
        $windowSeconds = $this->getWindowSeconds($type);

        if (empty($timestamps)) {
            return time() + $windowSeconds;
        }

        // Find the oldest timestamp
        $oldestTimestamp = null;
        foreach ($timestamps as $timestamp) {
            $ts = $this->parseTimestamp($timestamp);
            if ($ts !== null && ($oldestTimestamp === null || $ts < $oldestTimestamp)) {
                $oldestTimestamp = $ts;
            }
        }

        if ($oldestTimestamp === null) {
            return time() + $windowSeconds;
        }

        // Reset time is oldest timestamp + window (convert Carbon to Unix timestamp)
        return $oldestTimestamp->getTimestamp() + $windowSeconds;
    }

    /**
     * Increment the request count for the identifier.
     *
     * Adds the current timestamp to the sliding window.
     *
     * @param string $identifier The unique identifier
     * @param string $type The rate limit type ('user' or 'ip')
     * @return void
     */
    public function increment(string $identifier, string $type): void
    {
        $key = $this->getCacheKey($identifier);
        $windowSeconds = $this->getWindowSeconds($type);

        // Get existing timestamps
        $timestamps = $this->getValidTimestamps($key, $type);

        // Add current timestamp
        $timestamps[] = Carbon::now()->toIso8601String();

        // Store updated timestamps with TTL = window + buffer
        Cache::put($key, $timestamps, $windowSeconds + 60);
    }

    /**
     * Get the rate limit for the specified type.
     *
     * @param string $type The rate limit type ('user' or 'ip')
     * @return int Maximum requests per window
     */
    public function getLimit(string $type): int
    {
        $config = config("ai.rate_limits.{$type}");

        if ($config && isset($config['limit'])) {
            $limit = (int) $config['limit'];
            // Handle negative limits gracefully
            return max(0, $limit);
        }

        // Fallback defaults
        return $type === 'user' ? 20 : 10;
    }

    /**
     * Get the window duration in seconds for the specified type.
     *
     * @param string $type The rate limit type ('user' or 'ip')
     * @return int Window duration in seconds
     */
    protected function getWindowSeconds(string $type): int
    {
        $config = config("ai.rate_limits.{$type}");

        if ($config && isset($config['window_seconds'])) {
            return (int) $config['window_seconds'];
        }

        return self::DEFAULT_WINDOW_SECONDS;
    }

    /**
     * Get the cache key for the identifier.
     *
     * @param string $identifier The unique identifier
     * @return string Cache key
     */
    protected function getCacheKey(string $identifier): string
    {
        return "ai_rate_limit:{$identifier}";
    }

    /**
     * Get valid (non-expired) timestamps from cache.
     *
     * Filters out timestamps older than the window duration.
     *
     * @param string $key The cache key
     * @param string $type The rate limit type
     * @return array<string> Array of valid ISO 8601 timestamps
     */
    protected function getValidTimestamps(string $key, string $type): array
    {
        $timestamps = Cache::get($key, []);
        $windowSeconds = $this->getWindowSeconds($type);
        $windowStart = Carbon::now()->subSeconds($windowSeconds);

        return array_filter($timestamps, function ($timestamp) use ($windowStart) {
            $ts = $this->parseTimestamp($timestamp);
            return $ts !== null && $ts > $windowStart;
        });
    }

    /**
     * Parse a timestamp string to a Carbon instance.
     *
     * Handles both ISO 8601 strings and other formats.
     *
     * @param mixed $timestamp The timestamp to parse
     * @return Carbon|null Parsed Carbon instance or null if invalid
     */
    protected function parseTimestamp(mixed $timestamp): ?Carbon
    {
        if (!is_string($timestamp)) {
            return null;
        }

        try {
            return Carbon::parse($timestamp);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Clear the rate limit for an identifier (for testing/admin purposes).
     *
     * @param string $identifier The unique identifier
     * @return void
     */
    public function clear(string $identifier): void
    {
        $key = $this->getCacheKey($identifier);
        Cache::forget($key);
    }

    /**
     * Check if admin whitelist is enabled.
     *
     * When enabled, admin users bypass rate limiting.
     *
     * @return bool
     */
    public function isAdminWhitelistEnabled(): bool
    {
        return (bool) config('ai.rate_limits.admin_whitelist', true);
    }

    /**
     * Get the retry-after time in seconds.
     *
     * Calculates how long until the oldest request expires from the window.
     *
     * @param string $identifier The unique identifier
     * @param string $type The rate limit type ('user' or 'ip')
     * @return int Seconds until retry is possible
     */
    public function getRetryAfter(string $identifier, string $type): int
    {
        $resetTime = $this->getResetTime($identifier, $type);
        $retryAfter = $resetTime - time();

        return max(1, $retryAfter);
    }
}
