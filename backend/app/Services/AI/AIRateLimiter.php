<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * AI Rate Limiter Service.
 *
 * Implements sliding window rate limiting for AI suggestion endpoints.
 * Provides per-user and per-IP rate limiting with configurable thresholds.
 *
 * @see TASK-21-10: Add Rate Limiting
 */
class AIRateLimiter
{
    /**
     * Cache key prefix for rate limit storage.
     */
    private const KEY_PREFIX = 'ai_rate_limit:';

    /**
     * Default window duration in seconds (1 hour).
     */
    private const DEFAULT_WINDOW_SECONDS = 3600;

    /**
     * Check if an identifier is within rate limits.
     *
     * @param string $identifier The identifier (e.g., "user:123" or "ip:127.0.0.1")
     * @param string $type The type of limit ('user' or 'ip')
     * @return bool True if within limits, false if exceeded
     */
    public function checkLimit(string $identifier, string $type): bool
    {
        $limit = $this->getLimit($type);
        
        // Zero limit means always blocked
        if ($limit <= 0) {
            return false;
        }
        
        $timestamps = $this->getValidTimestamps($identifier, $type);
        
        return count($timestamps) < $limit;
    }

    /**
     * Get the remaining requests for an identifier.
     *
     * @param string $identifier The identifier
     * @param string $type The type of limit ('user' or 'ip')
     * @return int Number of remaining requests (never negative)
     */
    public function getRemaining(string $identifier, string $type): int
    {
        $limit = $this->getLimit($type);
        $timestamps = $this->getValidTimestamps($identifier, $type);
        $used = count($timestamps);
        
        return max(0, $limit - $used);
    }

    /**
     * Get the Unix timestamp when the rate limit window resets.
     *
     * @param string $identifier The identifier
     * @param string $type The type of limit ('user' or 'ip')
     * @return int Unix timestamp of reset time
     */
    public function getResetTime(string $identifier, string $type): int
    {
        $timestamps = $this->getValidTimestamps($identifier, $type);
        $windowSeconds = $this->getWindowSeconds($type);
        
        if (empty($timestamps)) {
            // No requests yet, reset is one window from now
            return time() + $windowSeconds;
        }
        
        // Find the oldest valid timestamp
        $oldestTimestamp = min(array_map(function ($ts) {
            return Carbon::parse($ts)->timestamp;
        }, $timestamps));
        
        // Reset is one window after the oldest request
        return $oldestTimestamp + $windowSeconds;
    }

    /**
     * Increment the request count for an identifier.
     *
     * @param string $identifier The identifier
     * @param string $type The type of limit ('user' or 'ip')
     */
    public function increment(string $identifier, string $type): void
    {
        $key = $this->getCacheKey($identifier, $type);
        $windowSeconds = $this->getWindowSeconds($type);
        
        // Get existing timestamps
        $timestamps = Cache::get($key, []);
        
        // Add current timestamp
        $timestamps[] = Carbon::now()->toIso8601String();
        
        // Store with TTL equal to window duration
        Cache::put($key, $timestamps, $windowSeconds);
    }

    /**
     * Get the configured limit for a type.
     *
     * @param string $type The type ('user' or 'ip')
     * @return int The limit
     */
    public function getLimit(string $type): int
    {
        $limit = config("ai.rate_limits.{$type}.limit", $this->getDefaultLimit($type));
        
        // Ensure non-negative
        return max(0, (int) $limit);
    }

    /**
     * Get the window duration in seconds for a type.
     *
     * @param string $type The type ('user' or 'ip')
     * @return int Window duration in seconds
     */
    public function getWindowSeconds(string $type): int
    {
        return (int) config("ai.rate_limits.{$type}.window_seconds", self::DEFAULT_WINDOW_SECONDS);
    }

    /**
     * Get valid (non-expired) timestamps for an identifier.
     *
     * @param string $identifier The identifier
     * @param string $type The type
     * @return array Array of valid timestamps
     */
    private function getValidTimestamps(string $identifier, string $type): array
    {
        $key = $this->getCacheKey($identifier, $type);
        $timestamps = Cache::get($key, []);
        
        if (empty($timestamps)) {
            return [];
        }
        
        $windowSeconds = $this->getWindowSeconds($type);
        $windowStart = Carbon::now()->subSeconds($windowSeconds);
        
        // Filter out expired timestamps and invalid entries
        return array_values(array_filter($timestamps, function ($timestamp) use ($windowStart) {
            if (!is_string($timestamp)) {
                return false;
            }
            
            try {
                $parsed = Carbon::parse($timestamp);
                return $parsed->isAfter($windowStart);
            } catch (\Exception $e) {
                return false;
            }
        }));
    }

    /**
     * Build the cache key for an identifier.
     *
     * @param string $identifier The identifier
     * @param string $type The type
     * @return string Cache key
     */
    private function getCacheKey(string $identifier, string $type): string
    {
        // Sanitize identifier - remove null bytes and other problematic characters
        $sanitized = preg_replace('/[\x00-\x1F]/', '', $identifier);
        
        return self::KEY_PREFIX . $sanitized;
    }

    /**
     * Get the default limit for a type when config is missing.
     *
     * @param string $type The type
     * @return int Default limit
     */
    private function getDefaultLimit(string $type): int
    {
        return match ($type) {
            'ip' => 10,
            'user' => 20,
            default => 20,
        };
    }
}
