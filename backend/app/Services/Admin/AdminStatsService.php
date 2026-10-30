<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Cache;

/**
 * Admin Stats Service.
 *
 * Main orchestrator service that aggregates all admin dashboard metrics
 * with appropriate caching for each metric type.
 *
 * Cache TTLs:
 * - User stats: 5 minutes (300s)
 * - Usage stats: 5 minutes (300s)
 * - Credit stats: 1 minute (60s)
 * - Health stats: 30 seconds
 *
 * @see TASK-27-18
 * @see SPEC-027
 */
class AdminStatsService
{
    /**
     * Cache TTL constants (in seconds).
     */
    private const USER_CACHE_TTL = 300;
    private const USAGE_CACHE_TTL = 300;
    private const CREDIT_CACHE_TTL = 60;
    private const HEALTH_CACHE_TTL = 30;

    public function __construct(
        protected UserAggregationService $userAggregator,
        protected UsageAggregationService $usageAggregator,
        protected CreditAggregationService $creditAggregator,
        protected HealthCheckService $healthChecker
    ) {}

    /**
     * Get user statistics with caching.
     *
     * @return array
     */
    public function getUserStats(): array
    {
        try {
            return Cache::remember('admin.stats.users', self::USER_CACHE_TTL, function () {
                return $this->userAggregator->getStats();
            });
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get usage statistics with caching.
     *
     * @return array
     */
    public function getUsageStats(): array
    {
        try {
            return Cache::remember('admin.stats.usage', self::USAGE_CACHE_TTL, function () {
                return $this->usageAggregator->getStats();
            });
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get credit statistics with caching.
     *
     * @return array
     */
    public function getCreditStats(): array
    {
        try {
            return Cache::remember('admin.stats.credits', self::CREDIT_CACHE_TTL, function () {
                return $this->creditAggregator->getStats();
            });
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get health statistics with caching.
     *
     * @return array
     */
    public function getHealthStats(): array
    {
        try {
            return Cache::remember('admin.stats.health', self::HEALTH_CACHE_TTL, function () {
                return $this->healthChecker->getStats();
            });
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get a cached metric with custom TTL.
     *
     * @param string $key
     * @param callable $calculator
     * @param int $ttl
     * @return mixed
     */
    public function getCachedMetric(string $key, callable $calculator, int $ttl = 300): mixed
    {
        return Cache::remember($key, $ttl, $calculator);
    }

    /**
     * Refresh all caches.
     *
     * @return void
     */
    public function refreshAllCaches(): void
    {
        $this->refreshUserCache();
        $this->refreshUsageCache();
        $this->refreshCreditCache();
        $this->refreshHealthCache();
    }

    /**
     * Refresh user stats cache.
     *
     * @return void
     */
    public function refreshUserCache(): void
    {
        Cache::forget('admin.stats.users');
    }

    /**
     * Refresh usage stats cache.
     *
     * @return void
     */
    public function refreshUsageCache(): void
    {
        Cache::forget('admin.stats.usage');
    }

    /**
     * Refresh credit stats cache.
     *
     * @return void
     */
    public function refreshCreditCache(): void
    {
        Cache::forget('admin.stats.credits');
    }

    /**
     * Refresh health stats cache.
     *
     * @return void
     */
    public function refreshHealthCache(): void
    {
        Cache::forget('admin.stats.health');
    }

    /**
     * Get all stats at once.
     *
     * @return array
     */
    public function getAllStats(): array
    {
        return [
            'users' => $this->getUserStats(),
            'usage' => $this->getUsageStats(),
            'credits' => $this->getCreditStats(),
            'health' => $this->getHealthStats(),
        ];
    }
}
