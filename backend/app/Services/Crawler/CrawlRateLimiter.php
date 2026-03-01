<?php

namespace App\Services\Crawler;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Rate limiting service for crawler API.
 *
 * Implements three tiers of rate limiting:
 * - Daily job limit: 10 per user (configurable)
 * - Concurrent job limit: 3 per user (configurable)
 * - Per-domain limit: 1 request/second (configurable)
 *
 * Admin users bypass daily and concurrent limits.
 *
 * @see TASK-22-12
 */
class CrawlRateLimiter
{
    /**
     * Default rate limit configuration.
     */
    protected const DEFAULT_DAILY_LIMIT = 10;
    protected const DEFAULT_CONCURRENT_LIMIT = 3;
    protected const DEFAULT_DOMAIN_RATE = 1; // requests per second

    /**
     * Check if user can create a new crawl job.
     *
     * Returns array with:
     * - allowed: bool
     * - remaining: int (daily remaining)
     * - retryAfter: int|null (seconds until reset if blocked)
     * - reason: string|null (reason for blocking)
     */
    public function canCreateJob(User $user): array
    {
        // Admin bypasses limits
        if ($this->isAdmin($user)) {
            return [
                'allowed' => true,
                'remaining' => PHP_INT_MAX,
                'retryAfter' => null,
                'reason' => null,
            ];
        }

        // Check daily limit
        $dailyResult = $this->checkDailyLimit($user);
        if (!$dailyResult['allowed']) {
            return $dailyResult;
        }

        // Check concurrent limit
        $concurrentResult = $this->checkConcurrentLimit($user);
        if (!$concurrentResult['allowed']) {
            return $concurrentResult;
        }

        return [
            'allowed' => true,
            'remaining' => $dailyResult['remaining'],
            'retryAfter' => null,
            'reason' => null,
        ];
    }

    /**
     * Check if user can request a specific domain.
     */
    public function canRequestDomain(User $user, string $domain): array
    {
        // Domain limits apply to all users (including admins)
        // This protects target servers from being overwhelmed
        $key = $this->getDomainCacheKey($domain);
        $lastRequest = Cache::get($key);

        if ($lastRequest === null) {
            return ['allowed' => true];
        }

        $elapsed = now()->timestamp - $lastRequest;
        $minInterval = $this->getDomainRateInterval();

        if ($elapsed < $minInterval) {
            return [
                'allowed' => false,
                'retryAfter' => $minInterval - $elapsed,
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Record a domain request timestamp.
     */
    public function recordDomainRequest(User $user, string $domain): void
    {
        $key = $this->getDomainCacheKey($domain);
        Cache::put($key, now()->timestamp, now()->addMinute());
    }

    /**
     * Increment the user's daily job count.
     */
    public function incrementJobCount(User $user): void
    {
        $key = $this->getDailyCacheKey($user);
        $current = Cache::get($key, 0);
        Cache::put($key, $current + 1, now()->endOfDay());
    }

    /**
     * Increment the user's concurrent job count.
     */
    public function incrementConcurrentJobs(User $user): void
    {
        $key = $this->getConcurrentCacheKey($user);
        $current = Cache::get($key, 0);
        Cache::put($key, $current + 1, now()->addHour());
    }

    /**
     * Decrement the user's concurrent job count.
     */
    public function decrementConcurrentJobs(User $user): void
    {
        $key = $this->getConcurrentCacheKey($user);
        $current = Cache::get($key, 0);
        $new = max(0, $current - 1);
        
        if ($new > 0) {
            Cache::put($key, $new, now()->addHour());
        } else {
            Cache::forget($key);
        }
    }

    /**
     * Get rate limit headers for response.
     */
    public function getHeaders(User $user): array
    {
        $dailyLimit = $this->getDailyLimit();
        $dailyUsed = $this->getDailyCount($user);
        $remaining = max(0, $dailyLimit - $dailyUsed);

        return [
            'X-RateLimit-Limit' => $dailyLimit,
            'X-RateLimit-Remaining' => $remaining,
            'X-RateLimit-Reset' => now()->endOfDay()->timestamp,
        ];
    }

    /**
     * Check daily limit.
     */
    protected function checkDailyLimit(User $user): array
    {
        $limit = $this->getDailyLimit();
        $used = $this->getDailyCount($user);
        $remaining = $limit - $used;

        if ($remaining <= 0) {
            $secondsUntilReset = now()->diffInSeconds(now()->endOfDay());

            return [
                'allowed' => false,
                'remaining' => 0,
                'retryAfter' => $secondsUntilReset,
                'reason' => 'Daily crawl limit exceeded. Please try again tomorrow.',
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $remaining,
            'retryAfter' => null,
            'reason' => null,
        ];
    }

    /**
     * Check concurrent limit.
     */
    protected function checkConcurrentLimit(User $user): array
    {
        $limit = $this->getConcurrentLimit();
        $current = $this->getConcurrentCount($user);

        if ($current >= $limit) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'retryAfter' => 60, // Estimate 1 minute for job completion
                'reason' => 'Maximum concurrent crawl jobs reached. Please wait for existing jobs to complete.',
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $limit - $current,
            'retryAfter' => null,
            'reason' => null,
        ];
    }

    /**
     * Get daily job count for user.
     */
    public function getDailyCount(User $user): int
    {
        return Cache::get($this->getDailyCacheKey($user), 0);
    }

    /**
     * Get concurrent job count for user.
     */
    public function getConcurrentCount(User $user): int
    {
        return Cache::get($this->getConcurrentCacheKey($user), 0);
    }

    /**
     * Get daily limit from config.
     */
    protected function getDailyLimit(): int
    {
        return config('crawler.rate_limits.daily_jobs', self::DEFAULT_DAILY_LIMIT);
    }

    /**
     * Get concurrent limit from config.
     */
    protected function getConcurrentLimit(): int
    {
        return config('crawler.rate_limits.concurrent_jobs', self::DEFAULT_CONCURRENT_LIMIT);
    }

    /**
     * Get domain rate interval (seconds between requests).
     */
    protected function getDomainRateInterval(): float
    {
        $perSecond = config('crawler.rate_limits.per_domain_per_second', self::DEFAULT_DOMAIN_RATE);
        return 1.0 / $perSecond;
    }

    /**
     * Get cache key for daily count.
     */
    protected function getDailyCacheKey(User $user): string
    {
        return "crawl_daily_limit:{$user->id}:" . now()->format('Y-m-d');
    }

    /**
     * Get cache key for concurrent count.
     */
    protected function getConcurrentCacheKey(User $user): string
    {
        return "crawl_concurrent:{$user->id}";
    }

    /**
     * Get cache key for domain rate limit.
     */
    protected function getDomainCacheKey(string $domain): string
    {
        return "crawl_domain:" . strtolower($domain);
    }

    /**
     * Check if user is an admin.
     */
    protected function isAdmin(User $user): bool
    {
        return $user->role === 'admin';
    }
}
