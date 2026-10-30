<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\AnalysisJob;

/**
 * Analysis Rate Limiter Service for TASK-19-14.
 *
 * Implements comprehensive rate limiting for SEO analysis jobs and API endpoints.
 * Uses Redis-backed cache for distributed rate limiting.
 *
 * Features:
 * - Job creation rate limits (per user, per site, concurrent)
 * - API rate limiting (per user for authenticated requests)
 * - Polling endpoint rate limiting (per job)
 * - Admin bypass capability
 * - Rate limit headers generation
 */
class AnalysisRateLimiterService
{
    /**
     * Job creation limits.
     */
    public const MAX_JOBS_PER_USER_PER_HOUR = 10;
    public const MAX_JOBS_PER_SITE_PER_HOUR = 5;
    public const MAX_CONCURRENT_JOBS_PER_USER = 2;

    /**
     * API rate limits.
     */
    public const API_REQUESTS_PER_MINUTE = 60;
    public const POLLING_REQUESTS_PER_MINUTE_PER_JOB = 30;

    /**
     * Cache key prefixes.
     */
    private const CACHE_PREFIX = 'analysis_rate_limit:';
    private const USER_HOURLY_PREFIX = 'user_hourly:';
    private const SITE_HOURLY_PREFIX = 'site_hourly:';
    private const API_MINUTE_PREFIX = 'api_minute:';
    private const POLLING_MINUTE_PREFIX = 'polling_minute:';

    /**
     * Admin roles that bypass rate limits.
     */
    private const ADMIN_ROLES = ['admin', 'super_admin'];

    /**
     * Check if user can create a new analysis job.
     *
     * @param int $userId
     * @param int $siteId
     * @return array{allowed: bool, reason: ?string, retry_after: ?int, limits: array}
     */
    public function canCreateJob(int $userId, int $siteId): array
    {
        // Check if user is admin (bypass rate limits)
        if ($this->isAdmin($userId)) {
            return $this->allowedResponse();
        }

        // Check concurrent jobs
        $concurrentCheck = $this->checkConcurrentJobs($userId);
        if (!$concurrentCheck['allowed']) {
            return $concurrentCheck;
        }

        // Check user hourly limit
        $userHourlyCheck = $this->checkUserHourlyLimit($userId);
        if (!$userHourlyCheck['allowed']) {
            return $userHourlyCheck;
        }

        // Check site hourly limit
        $siteHourlyCheck = $this->checkSiteHourlyLimit($siteId);
        if (!$siteHourlyCheck['allowed']) {
            return $siteHourlyCheck;
        }

        return $this->allowedResponse();
    }

    /**
     * Record a job creation for rate limiting.
     *
     * @param int $userId
     * @param int $siteId
     */
    public function recordJobCreation(int $userId, int $siteId): void
    {
        // Record user hourly
        $userKey = $this->getUserHourlyKey($userId);
        $this->incrementWithExpiry($userKey, 3600);

        // Record site hourly
        $siteKey = $this->getSiteHourlyKey($siteId);
        $this->incrementWithExpiry($siteKey, 3600);
    }

    /**
     * Check API rate limit for authenticated user.
     *
     * @param int $userId
     * @return array{allowed: bool, remaining: int, reset_at: int, retry_after: ?int}
     */
    public function checkApiRateLimit(int $userId): array
    {
        // Admin bypass
        if ($this->isAdmin($userId)) {
            return [
                'allowed' => true,
                'remaining' => PHP_INT_MAX,
                'reset_at' => time() + 60,
                'retry_after' => null,
            ];
        }

        $key = $this->getApiMinuteKey($userId);
        $result = $this->slidingWindowCheck($key, self::API_REQUESTS_PER_MINUTE, 60000);

        return $result;
    }

    /**
     * Record an API request.
     *
     * @param int $userId
     */
    public function recordApiRequest(int $userId): void
    {
        if ($this->isAdmin($userId)) {
            return;
        }

        $key = $this->getApiMinuteKey($userId);
        $this->slidingWindowIncrement($key, 60000);
    }

    /**
     * Check and record API request in one operation.
     *
     * @param int $userId
     * @return array{allowed: bool, remaining: int, reset_at: int, retry_after: ?int}
     */
    public function hitApiRateLimit(int $userId): array
    {
        // Admin bypass
        if ($this->isAdmin($userId)) {
            return [
                'allowed' => true,
                'remaining' => PHP_INT_MAX,
                'reset_at' => time() + 60,
                'retry_after' => null,
            ];
        }

        $key = $this->getApiMinuteKey($userId);
        return $this->slidingWindowHit($key, self::API_REQUESTS_PER_MINUTE, 60000);
    }

    /**
     * Check polling rate limit for a specific job.
     *
     * @param int $userId
     * @param int $jobId
     * @return array{allowed: bool, remaining: int, reset_at: int, retry_after: ?int}
     */
    public function checkPollingRateLimit(int $userId, int $jobId): array
    {
        // Admin bypass
        if ($this->isAdmin($userId)) {
            return [
                'allowed' => true,
                'remaining' => PHP_INT_MAX,
                'reset_at' => time() + 60,
                'retry_after' => null,
            ];
        }

        $key = $this->getPollingMinuteKey($jobId);
        return $this->slidingWindowCheck($key, self::POLLING_REQUESTS_PER_MINUTE_PER_JOB, 60000);
    }

    /**
     * Check and record polling request in one operation.
     *
     * @param int $userId
     * @param int $jobId
     * @return array{allowed: bool, remaining: int, reset_at: int, retry_after: ?int}
     */
    public function hitPollingRateLimit(int $userId, int $jobId): array
    {
        // Admin bypass
        if ($this->isAdmin($userId)) {
            return [
                'allowed' => true,
                'remaining' => PHP_INT_MAX,
                'reset_at' => time() + 60,
                'retry_after' => null,
            ];
        }

        $key = $this->getPollingMinuteKey($jobId);
        return $this->slidingWindowHit($key, self::POLLING_REQUESTS_PER_MINUTE_PER_JOB, 60000);
    }

    /**
     * Get rate limit headers for job creation.
     *
     * @param int $userId
     * @param int $siteId
     * @return array<string, string>
     */
    public function getJobCreationHeaders(int $userId, int $siteId): array
    {
        if ($this->isAdmin($userId)) {
            return [
                'X-RateLimit-Limit' => 'unlimited',
                'X-RateLimit-Remaining' => 'unlimited',
                'X-RateLimit-Reset' => (string) (time() + 3600),
            ];
        }

        $userHourly = $this->getUserHourlyCount($userId);
        $siteHourly = $this->getSiteHourlyCount($siteId);

        $userRemaining = max(0, self::MAX_JOBS_PER_USER_PER_HOUR - $userHourly);
        $siteRemaining = max(0, self::MAX_JOBS_PER_SITE_PER_HOUR - $siteHourly);

        // Return the most restrictive limit
        $remaining = min($userRemaining, $siteRemaining);
        $limit = min(self::MAX_JOBS_PER_USER_PER_HOUR, self::MAX_JOBS_PER_SITE_PER_HOUR);
        $resetAt = strtotime('next hour');

        return [
            'X-RateLimit-Limit' => (string) $limit,
            'X-RateLimit-Remaining' => (string) $remaining,
            'X-RateLimit-Reset' => (string) $resetAt,
        ];
    }

    /**
     * Get rate limit headers for API requests.
     *
     * @param int $userId
     * @return array<string, string>
     */
    public function getApiHeaders(int $userId): array
    {
        $result = $this->checkApiRateLimit($userId);

        return [
            'X-RateLimit-Limit' => (string) self::API_REQUESTS_PER_MINUTE,
            'X-RateLimit-Remaining' => (string) $result['remaining'],
            'X-RateLimit-Reset' => (string) $result['reset_at'],
        ];
    }

    /**
     * Get rate limit headers for polling requests.
     *
     * @param int $userId
     * @param int $jobId
     * @return array<string, string>
     */
    public function getPollingHeaders(int $userId, int $jobId): array
    {
        $result = $this->checkPollingRateLimit($userId, $jobId);

        return [
            'X-RateLimit-Limit' => (string) self::POLLING_REQUESTS_PER_MINUTE_PER_JOB,
            'X-RateLimit-Remaining' => (string) $result['remaining'],
            'X-RateLimit-Reset' => (string) $result['reset_at'],
        ];
    }

    /**
     * Build a rate limit exceeded response.
     *
     * @param string $message
     * @param int $retryAfter
     * @param int $resetAt
     * @param string $limitType
     * @return array{success: bool, error: array, status: int, headers: array}
     */
    public function buildRateLimitResponse(string $message, int $retryAfter, int $resetAt, string $limitType = 'api'): array
    {
        $limit = match ($limitType) {
            'polling' => self::POLLING_REQUESTS_PER_MINUTE_PER_JOB,
            'job_user_hourly' => self::MAX_JOBS_PER_USER_PER_HOUR,
            'job_site_hourly' => self::MAX_JOBS_PER_SITE_PER_HOUR,
            'job_concurrent' => self::MAX_CONCURRENT_JOBS_PER_USER,
            default => self::API_REQUESTS_PER_MINUTE,
        };

        return [
            'success' => false,
            'error' => [
                'code' => 'RATE_LIMIT_EXCEEDED',
                'message' => $message,
                'retry_after' => $retryAfter,
            ],
            'status' => 429,
            'headers' => [
                'X-RateLimit-Limit' => (string) $limit,
                'X-RateLimit-Remaining' => '0',
                'X-RateLimit-Reset' => (string) $resetAt,
                'Retry-After' => (string) $retryAfter,
            ],
        ];
    }

    /**
     * Check if user has admin role.
     *
     * @param int $userId
     * @return bool
     */
    protected function isAdmin(int $userId): bool
    {
        $user = User::find($userId);
        
        if (!$user) {
            return false;
        }

        return in_array($user->role, self::ADMIN_ROLES, true);
    }

    /**
     * Check concurrent jobs limit.
     *
     * @param int $userId
     * @return array{allowed: bool, reason: ?string, retry_after: ?int, limits: array}
     */
    protected function checkConcurrentJobs(int $userId): array
    {
        $runningJobs = AnalysisJob::where('user_id', $userId)
            ->whereIn('status', [AnalysisJob::STATUS_PENDING, AnalysisJob::STATUS_RUNNING])
            ->count();

        if ($runningJobs >= self::MAX_CONCURRENT_JOBS_PER_USER) {
            return [
                'allowed' => false,
                'reason' => 'Maximum concurrent jobs limit reached. Please wait for existing jobs to complete.',
                'retry_after' => null, // Unknown - depends on job duration
                'limits' => [
                    'type' => 'concurrent',
                    'limit' => self::MAX_CONCURRENT_JOBS_PER_USER,
                    'current' => $runningJobs,
                ],
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Check user hourly limit.
     *
     * @param int $userId
     * @return array{allowed: bool, reason: ?string, retry_after: ?int, limits: array}
     */
    protected function checkUserHourlyLimit(int $userId): array
    {
        $count = $this->getUserHourlyCount($userId);

        if ($count >= self::MAX_JOBS_PER_USER_PER_HOUR) {
            $retryAfter = $this->getSecondsUntilNextHour();

            return [
                'allowed' => false,
                'reason' => 'Hourly job limit reached. Please try again later.',
                'retry_after' => $retryAfter,
                'limits' => [
                    'type' => 'user_hourly',
                    'limit' => self::MAX_JOBS_PER_USER_PER_HOUR,
                    'current' => $count,
                    'reset_at' => time() + $retryAfter,
                ],
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Check site hourly limit.
     *
     * @param int $siteId
     * @return array{allowed: bool, reason: ?string, retry_after: ?int, limits: array}
     */
    protected function checkSiteHourlyLimit(int $siteId): array
    {
        $count = $this->getSiteHourlyCount($siteId);

        if ($count >= self::MAX_JOBS_PER_SITE_PER_HOUR) {
            $retryAfter = $this->getSecondsUntilNextHour();

            return [
                'allowed' => false,
                'reason' => 'Hourly job limit for this site reached. Please try again later.',
                'retry_after' => $retryAfter,
                'limits' => [
                    'type' => 'site_hourly',
                    'limit' => self::MAX_JOBS_PER_SITE_PER_HOUR,
                    'current' => $count,
                    'reset_at' => time() + $retryAfter,
                ],
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Get user's hourly job count.
     *
     * @param int $userId
     * @return int
     */
    protected function getUserHourlyCount(int $userId): int
    {
        // Use database query for accuracy (counts jobs in last hour)
        return AnalysisJob::where('user_id', $userId)
            ->where('created_at', '>=', now()->subHour())
            ->count();
    }

    /**
     * Get site's hourly job count.
     *
     * @param int $siteId
     * @return int
     */
    protected function getSiteHourlyCount(int $siteId): int
    {
        return AnalysisJob::where('site_id', $siteId)
            ->where('created_at', '>=', now()->subHour())
            ->count();
    }

    /**
     * Get seconds until the next hour.
     *
     * @return int
     */
    protected function getSecondsUntilNextHour(): int
    {
        $now = time();
        $nextHour = strtotime('next hour', $now);
        return $nextHour - $now;
    }

    /**
     * Generate cache key for user hourly limit.
     */
    protected function getUserHourlyKey(int $userId): string
    {
        $hour = date('Y-m-d-H');
        return self::CACHE_PREFIX . self::USER_HOURLY_PREFIX . $userId . ':' . $hour;
    }

    /**
     * Generate cache key for site hourly limit.
     */
    protected function getSiteHourlyKey(int $siteId): string
    {
        $hour = date('Y-m-d-H');
        return self::CACHE_PREFIX . self::SITE_HOURLY_PREFIX . $siteId . ':' . $hour;
    }

    /**
     * Generate cache key for API minute limit.
     */
    protected function getApiMinuteKey(int $userId): string
    {
        return self::CACHE_PREFIX . self::API_MINUTE_PREFIX . $userId;
    }

    /**
     * Generate cache key for polling minute limit.
     */
    protected function getPollingMinuteKey(int $jobId): string
    {
        return self::CACHE_PREFIX . self::POLLING_MINUTE_PREFIX . $jobId;
    }

    /**
     * Increment a counter with expiry.
     *
     * @param string $key
     * @param int $ttlSeconds
     */
    protected function incrementWithExpiry(string $key, int $ttlSeconds): void
    {
        $current = (int) Cache::get($key, 0);
        Cache::put($key, $current + 1, now()->addSeconds($ttlSeconds));
    }

    /**
     * Sliding window check without incrementing.
     *
     * @param string $key
     * @param int $maxRequests
     * @param int $windowMs
     * @return array{allowed: bool, remaining: int, reset_at: int, retry_after: ?int}
     */
    protected function slidingWindowCheck(string $key, int $maxRequests, int $windowMs): array
    {
        $timestamps = $this->getTimestamps($key);
        $now = microtime(true);
        $windowStart = $now - ($windowMs / 1000);

        // Filter to valid timestamps
        $validTimestamps = array_filter($timestamps, fn($ts) => $ts > $windowStart);
        $count = count($validTimestamps);
        $remaining = max(0, $maxRequests - $count);

        // Calculate reset time
        if (empty($validTimestamps)) {
            $resetAt = time() + (int) ($windowMs / 1000);
        } else {
            $oldestTimestamp = min($validTimestamps);
            $resetAt = (int) ceil($oldestTimestamp + ($windowMs / 1000));
        }

        if ($count >= $maxRequests) {
            $retryAfter = max(1, $resetAt - time());
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_at' => $resetAt,
                'retry_after' => $retryAfter,
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $remaining,
            'reset_at' => $resetAt,
            'retry_after' => null,
        ];
    }

    /**
     * Sliding window increment.
     *
     * @param string $key
     * @param int $windowMs
     */
    protected function slidingWindowIncrement(string $key, int $windowMs): void
    {
        $timestamps = $this->getTimestamps($key);
        $now = microtime(true);
        $windowStart = $now - ($windowMs / 1000);

        // Filter to valid timestamps
        $validTimestamps = array_filter($timestamps, fn($ts) => $ts > $windowStart);
        $validTimestamps[] = $now;

        $ttl = (int) ceil($windowMs / 1000) + 1;
        Cache::put($key, array_values($validTimestamps), now()->addSeconds($ttl));
    }

    /**
     * Sliding window check and increment in one operation.
     *
     * @param string $key
     * @param int $maxRequests
     * @param int $windowMs
     * @return array{allowed: bool, remaining: int, reset_at: int, retry_after: ?int}
     */
    protected function slidingWindowHit(string $key, int $maxRequests, int $windowMs): array
    {
        $timestamps = $this->getTimestamps($key);
        $now = microtime(true);
        $windowStart = $now - ($windowMs / 1000);

        // Filter to valid timestamps
        $validTimestamps = array_filter($timestamps, fn($ts) => $ts > $windowStart);
        $validTimestamps = array_values($validTimestamps);
        $count = count($validTimestamps);

        // Calculate reset time
        if (empty($validTimestamps)) {
            $resetAt = time() + (int) ($windowMs / 1000);
        } else {
            $oldestTimestamp = min($validTimestamps);
            $resetAt = (int) ceil($oldestTimestamp + ($windowMs / 1000));
        }

        if ($count >= $maxRequests) {
            $retryAfter = max(1, $resetAt - time());
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_at' => $resetAt,
                'retry_after' => $retryAfter,
            ];
        }

        // Increment
        $validTimestamps[] = $now;
        $newCount = count($validTimestamps);

        $ttl = (int) ceil($windowMs / 1000) + 1;
        Cache::put($key, $validTimestamps, now()->addSeconds($ttl));

        return [
            'allowed' => true,
            'remaining' => max(0, $maxRequests - $newCount),
            'reset_at' => $resetAt,
            'retry_after' => null,
        ];
    }

    /**
     * Get timestamps from cache.
     *
     * @param string $key
     * @return array
     */
    protected function getTimestamps(string $key): array
    {
        $timestamps = Cache::get($key, []);
        return is_array($timestamps) ? $timestamps : [];
    }

    /**
     * Build an allowed response.
     *
     * @return array{allowed: bool, reason: ?string, retry_after: ?int, limits: array}
     */
    protected function allowedResponse(): array
    {
        return [
            'allowed' => true,
            'reason' => null,
            'retry_after' => null,
            'limits' => [],
        ];
    }

    /**
     * Reset rate limits for a user (for testing).
     *
     * @param int $userId
     */
    public function resetUserLimits(int $userId): void
    {
        Cache::forget($this->getApiMinuteKey($userId));
    }

    /**
     * Reset rate limits for a job (for testing).
     *
     * @param int $jobId
     */
    public function resetPollingLimits(int $jobId): void
    {
        Cache::forget($this->getPollingMinuteKey($jobId));
    }
}
