<?php

namespace App\Services\Crawler;

use App\Models\User;
use App\Services\Crawler\Data\RateLimitResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Rate limiting service for crawler API.
 *
 * Implements four tiers of rate limiting:
 * - User daily limit: Maximum crawl jobs per user per 24 hours
 * - User concurrent limit: Maximum concurrent jobs per user
 * - Domain rate limit: Minimum delay between requests to same domain
 * - Global concurrent limit: Maximum total concurrent browser instances
 *
 * Admin users bypass user limits only (not domain/global limits).
 *
 * @see TASK-22-13
 * @see SPEC-022
 */
class RateLimiterService
{
    /**
     * Default rate limit values.
     */
    protected const DEFAULT_USER_DAILY = 10;
    protected const DEFAULT_USER_CONCURRENT = 3;
    protected const DEFAULT_DOMAIN_PER_SECOND = 1;
    protected const DEFAULT_GLOBAL_CONCURRENT = 10;
    protected const DEFAULT_WINDOW_HOURS = 24;

    /**
     * Cache key prefixes.
     */
    protected const CACHE_PREFIX = 'crawler:rate';

    /**
     * Check if user can create a new crawl job (daily limit).
     *
     * @param int $userId User ID
     * @return RateLimitResult
     */
    public function checkUserLimit(int $userId): RateLimitResult
    {
        $limit = $this->getUserDailyLimit();
        $count = $this->getUserDailyCount($userId);
        $remaining = max(0, $limit - $count);
        $resetAt = $this->getDailyResetTime();

        if ($count >= $limit) {
            $retryAfter = now()->diffInSeconds($resetAt);
            return RateLimitResult::denied($limit, $resetAt, max(1, $retryAfter));
        }

        return RateLimitResult::allowed($limit, $remaining, $resetAt);
    }

    /**
     * Check if user can have another concurrent job.
     *
     * @param int $userId User ID
     * @return RateLimitResult
     */
    public function checkConcurrentLimit(int $userId): RateLimitResult
    {
        $limit = $this->getUserConcurrentLimit();
        $count = $this->getUserConcurrentCount($userId);
        $remaining = max(0, $limit - $count);
        $resetAt = now()->addMinutes(5); // Estimate for job completion

        if ($count >= $limit) {
            return RateLimitResult::denied($limit, $resetAt, 60);
        }

        return RateLimitResult::allowed($limit, $remaining, $resetAt);
    }

    /**
     * Check if domain can be requested (per-domain rate limit).
     *
     * @param string $domain Domain to check
     * @return RateLimitResult
     */
    public function checkDomainLimit(string $domain): RateLimitResult
    {
        $domain = $this->normalizeDomain($domain);

        if (empty($domain)) {
            // Empty domain always allowed (no target yet)
            return RateLimitResult::allowed(1, 1, now()->addSecond());
        }

        $key = $this->getDomainCacheKey($domain);
        $lastRequest = Cache::get($key);

        if ($lastRequest === null) {
            return RateLimitResult::allowed(1, 1, now()->addSecond());
        }

        $elapsed = now()->timestamp - $lastRequest;
        $minInterval = $this->getDomainInterval();

        if ($elapsed < $minInterval) {
            $retryAfter = (int) ceil($minInterval - $elapsed);
            $resetAt = now()->addSeconds($retryAfter);
            return RateLimitResult::denied(1, $resetAt, $retryAfter);
        }

        return RateLimitResult::allowed(1, 1, now()->addSecond());
    }

    /**
     * Check global concurrent job limit.
     *
     * @return RateLimitResult
     */
    public function checkGlobalLimit(): RateLimitResult
    {
        $limit = $this->getGlobalConcurrentLimit();
        $count = $this->getGlobalConcurrentCount();
        $remaining = max(0, $limit - $count);
        $resetAt = now()->addMinutes(5); // Estimate for job completion

        if ($count >= $limit) {
            return RateLimitResult::denied($limit, $resetAt, 60);
        }

        return RateLimitResult::allowed($limit, $remaining, $resetAt);
    }

    /**
     * Increment the user's daily job count.
     *
     * @param int $userId User ID
     */
    public function incrementUserCount(int $userId): void
    {
        $key = $this->getUserDailyCacheKey($userId);

        // Use atomic increment
        if (Cache::has($key)) {
            Cache::increment($key);
        } else {
            $ttl = $this->getWindowTTL();
            Cache::put($key, 1, $ttl);
        }
    }

    /**
     * Increment the user's concurrent job count.
     *
     * @param int $userId User ID
     */
    public function incrementConcurrentCount(int $userId): void
    {
        $key = $this->getUserConcurrentCacheKey($userId);

        if (Cache::has($key)) {
            Cache::increment($key);
        } else {
            Cache::put($key, 1, now()->addHour());
        }
    }

    /**
     * Decrement the user's concurrent job count.
     *
     * @param int $userId User ID
     */
    public function decrementConcurrentCount(int $userId): void
    {
        $key = $this->getUserConcurrentCacheKey($userId);
        $current = Cache::get($key, 0);

        if ($current > 1) {
            Cache::decrement($key);
        } else {
            Cache::forget($key);
        }
    }

    /**
     * Increment the global concurrent job count.
     */
    public function incrementGlobalCount(): void
    {
        $key = $this->getGlobalCacheKey();

        if (Cache::has($key)) {
            Cache::increment($key);
        } else {
            Cache::put($key, 1, now()->addHour());
        }
    }

    /**
     * Decrement the global concurrent job count.
     */
    public function decrementGlobalCount(): void
    {
        $key = $this->getGlobalCacheKey();
        $current = Cache::get($key, 0);

        if ($current > 1) {
            Cache::decrement($key);
        } else {
            Cache::forget($key);
        }
    }

    /**
     * Record a domain request timestamp.
     *
     * @param string $domain Domain to record
     */
    public function incrementDomainCount(string $domain): void
    {
        $domain = $this->normalizeDomain($domain);

        if (empty($domain)) {
            return;
        }

        $key = $this->getDomainCacheKey($domain);
        Cache::put($key, now()->timestamp, now()->addMinute());
    }

    /**
     * Check all rate limits for a user and domain.
     *
     * @param int $userId User ID
     * @param string|null $domain Target domain
     * @param bool $isAdmin Whether user is admin
     * @return array{allowed: bool, results: array<string, RateLimitResult>, reason: string|null}
     */
    public function checkAllLimits(int $userId, ?string $domain = null, bool $isAdmin = false): array
    {
        $results = [];
        $blockedBy = null;

        // Check user daily limit (bypassed for admins)
        if (!$isAdmin) {
            $results['daily'] = $this->checkUserLimit($userId);
            if (!$results['daily']->allowed) {
                $blockedBy = 'daily';
            }

            // Check user concurrent limit (bypassed for admins)
            $results['concurrent'] = $this->checkConcurrentLimit($userId);
            if (!$results['concurrent']->allowed && $blockedBy === null) {
                $blockedBy = 'concurrent';
            }
        }

        // Check domain rate limit (NOT bypassed for admins)
        if ($domain !== null) {
            $results['domain'] = $this->checkDomainLimit($domain);
            if (!$results['domain']->allowed && $blockedBy === null) {
                $blockedBy = 'domain';
            }
        }

        // Check global concurrent limit (NOT bypassed for admins)
        $results['global'] = $this->checkGlobalLimit();
        if (!$results['global']->allowed && $blockedBy === null) {
            $blockedBy = 'global';
        }

        $allowed = $blockedBy === null;

        return [
            'allowed' => $allowed,
            'results' => $results,
            'reason' => $blockedBy,
        ];
    }

    /**
     * Get rate limit headers for a user.
     *
     * @param int $userId User ID
     * @param bool $isAdmin Whether user is admin
     * @return array<string, int>
     */
    public function getHeaders(int $userId, bool $isAdmin = false): array
    {
        $limit = $this->getUserDailyLimit();

        if ($isAdmin) {
            // Admins show unlimited in headers
            return [
                'X-RateLimit-Limit' => PHP_INT_MAX,
                'X-RateLimit-Remaining' => PHP_INT_MAX,
                'X-RateLimit-Reset' => $this->getDailyResetTime()->timestamp,
            ];
        }

        $count = $this->getUserDailyCount($userId);
        $remaining = max(0, $limit - $count);

        return [
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => $remaining,
            'X-RateLimit-Reset' => $this->getDailyResetTime()->timestamp,
        ];
    }

    /**
     * Check if user is an admin.
     *
     * @param User $user
     * @return bool
     */
    public function isAdmin(User $user): bool
    {
        return isset($user->role) && $user->role === 'admin';
    }

    /**
     * Get user's daily job count.
     *
     * @param int $userId
     * @return int
     */
    public function getUserDailyCount(int $userId): int
    {
        return (int) Cache::get($this->getUserDailyCacheKey($userId), 0);
    }

    /**
     * Get user's concurrent job count.
     *
     * @param int $userId
     * @return int
     */
    public function getUserConcurrentCount(int $userId): int
    {
        return (int) Cache::get($this->getUserConcurrentCacheKey($userId), 0);
    }

    /**
     * Get global concurrent job count.
     *
     * @return int
     */
    public function getGlobalConcurrentCount(): int
    {
        return (int) Cache::get($this->getGlobalCacheKey(), 0);
    }

    // ========================================
    // Configuration Getters
    // ========================================

    /**
     * Get user daily limit from config.
     */
    protected function getUserDailyLimit(): int
    {
        return (int) config('crawler.rate_limits.user_daily', self::DEFAULT_USER_DAILY);
    }

    /**
     * Get user concurrent limit from config.
     */
    protected function getUserConcurrentLimit(): int
    {
        return (int) config('crawler.rate_limits.user_concurrent', self::DEFAULT_USER_CONCURRENT);
    }

    /**
     * Get domain rate interval in seconds.
     */
    protected function getDomainInterval(): float
    {
        $perSecond = (float) config('crawler.rate_limits.domain_per_second', self::DEFAULT_DOMAIN_PER_SECOND);
        return $perSecond > 0 ? 1.0 / $perSecond : 1.0;
    }

    /**
     * Get global concurrent limit from config.
     */
    protected function getGlobalConcurrentLimit(): int
    {
        return (int) config('crawler.rate_limits.global_concurrent', self::DEFAULT_GLOBAL_CONCURRENT);
    }

    /**
     * Get window duration in hours.
     */
    protected function getWindowHours(): int
    {
        return (int) config('crawler.rate_limits.window_hours', self::DEFAULT_WINDOW_HOURS);
    }

    // ========================================
    // Cache Key Generators
    // ========================================

    /**
     * Get cache key for user daily count.
     *
     * @param int $userId
     * @return string
     */
    protected function getUserDailyCacheKey(int $userId): string
    {
        return self::CACHE_PREFIX . ":user:{$userId}:daily";
    }

    /**
     * Get cache key for user concurrent count.
     *
     * @param int $userId
     * @return string
     */
    protected function getUserConcurrentCacheKey(int $userId): string
    {
        return self::CACHE_PREFIX . ":user:{$userId}:concurrent";
    }

    /**
     * Get cache key for domain rate limit.
     *
     * @param string $domain
     * @return string
     */
    protected function getDomainCacheKey(string $domain): string
    {
        return self::CACHE_PREFIX . ":domain:{$domain}";
    }

    /**
     * Get cache key for global concurrent count.
     *
     * @return string
     */
    protected function getGlobalCacheKey(): string
    {
        return self::CACHE_PREFIX . ":global";
    }

    // ========================================
    // Helper Methods
    // ========================================

    /**
     * Get the daily reset time (end of day UTC).
     *
     * @return Carbon
     */
    protected function getDailyResetTime(): Carbon
    {
        return now()->utc()->endOfDay();
    }

    /**
     * Get TTL for daily cache key.
     *
     * @return Carbon
     */
    protected function getWindowTTL(): Carbon
    {
        return now()->addHours($this->getWindowHours());
    }

    /**
     * Normalize a domain for consistent caching.
     *
     * @param string $domain
     * @return string
     */
    protected function normalizeDomain(string $domain): string
    {
        // Strip protocol if present
        $domain = preg_replace('#^https?://#i', '', $domain);

        // Strip path/query/fragment
        $domain = preg_replace('#[/\?#].*$#', '', $domain);

        // Strip port
        $domain = preg_replace('#:\d+$#', '', $domain);

        // Lowercase and trim
        return strtolower(trim($domain));
    }

    /**
     * Clean up stale concurrent counts for a user.
     * Called when a job completes or crashes.
     *
     * @param int $userId
     */
    public function cleanupUserConcurrent(int $userId): void
    {
        $this->decrementConcurrentCount($userId);
    }

    /**
     * Reset all rate limits for a user (admin function).
     *
     * @param int $userId
     */
    public function resetUserLimits(int $userId): void
    {
        Cache::forget($this->getUserDailyCacheKey($userId));
        Cache::forget($this->getUserConcurrentCacheKey($userId));
    }
}
