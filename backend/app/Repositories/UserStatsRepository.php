<?php

namespace App\Repositories;

use App\Models\User;
use App\Services\Admin\Contracts\UserStatsRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Repository for User Statistics Aggregation.
 *
 * Provides efficient database queries for user statistics aggregation
 * used in the admin dashboard. Includes counts for total users, active users,
 * new registrations, growth metrics, and plan distribution.
 *
 * @see TASK-27-4
 * @see TASK-27-5
 * @see SPEC-027
 */
class UserStatsRepository implements UserStatsRepositoryInterface
{
    /**
     * Cache TTL in seconds (5 minutes).
     */
    private const CACHE_TTL = 300;

    /**
     * Cache key prefix for namespacing.
     */
    private const CACHE_PREFIX = 'admin:stats:users';

    /**
     * Get total user count.
     *
     * @return int
     */
    public function getTotalUserCount(): int
    {
        return (int) User::count();
    }

    /**
     * Get active users count (logged in within last 30 days).
     *
     * @return int
     */
    public function getActiveUsersCount(): int
    {
        return (int) User::where('last_login_at', '>=', Carbon::now()->subDays(30))
            ->whereNotNull('last_login_at')
            ->count();
    }

    /**
     * Get new registrations today count.
     *
     * @return int
     */
    public function getNewRegistrationsToday(): int
    {
        return (int) User::whereDate('created_at', Carbon::today())->count();
    }

    /**
     * Get new registrations this week count.
     *
     * @return int
     */
    public function getNewRegistrationsThisWeek(): int
    {
        return (int) User::whereBetween('created_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek(),
        ])->count();
    }

    /**
     * Get new registrations this month count.
     *
     * @return int
     */
    public function getNewRegistrationsThisMonth(): int
    {
        return (int) User::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
    }

    /**
     * Get growth rates for daily, weekly, and monthly periods.
     *
     * @return array{daily: float, weekly: float, monthly: float}
     */
    public function getGrowthRates(): array
    {
        return [
            'daily' => $this->calculateDailyGrowthRate(),
            'weekly' => $this->calculateWeeklyGrowthRate(),
            'monthly' => $this->calculateMonthlyGrowthRate(),
        ];
    }

    /**
     * Calculate daily growth rate.
     *
     * @return float
     */
    protected function calculateDailyGrowthRate(): float
    {
        $today = $this->getNewRegistrationsToday();
        $yesterday = $this->getNewRegistrationsYesterday();

        return $this->calculateGrowthPercentage($today, $yesterday);
    }

    /**
     * Calculate weekly growth rate.
     *
     * @return float
     */
    protected function calculateWeeklyGrowthRate(): float
    {
        $thisWeek = $this->getNewRegistrationsThisWeek();
        $lastWeek = $this->getNewRegistrationsLastWeek();

        return $this->calculateGrowthPercentage($thisWeek, $lastWeek);
    }

    /**
     * Calculate monthly growth rate.
     *
     * @return float
     */
    protected function calculateMonthlyGrowthRate(): float
    {
        $thisMonth = $this->getNewRegistrationsThisMonth();
        $lastMonth = $this->getNewRegistrationsLastMonth();

        return $this->calculateGrowthPercentage($thisMonth, $lastMonth);
    }

    /**
     * Calculate growth percentage with proper handling of edge cases.
     *
     * @param int $current
     * @param int $previous
     * @return float
     */
    protected function calculateGrowthPercentage(int $current, int $previous): float
    {
        if ($previous === 0) {
            return 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Get new registrations yesterday count.
     *
     * @return int
     */
    protected function getNewRegistrationsYesterday(): int
    {
        return (int) User::whereDate('created_at', Carbon::yesterday())->count();
    }

    /**
     * Get new registrations last week count.
     *
     * @return int
     */
    protected function getNewRegistrationsLastWeek(): int
    {
        return (int) User::whereBetween('created_at', [
            Carbon::now()->subWeek()->startOfWeek(),
            Carbon::now()->subWeek()->endOfWeek(),
        ])->count();
    }

    /**
     * Get new registrations last month count.
     *
     * @return int
     */
    protected function getNewRegistrationsLastMonth(): int
    {
        $lastMonth = Carbon::now()->subMonth();

        return (int) User::whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->count();
    }

    /**
     * Get users count by plan.
     *
     * @return array{free: int, pro: int, enterprise: int}
     */
    public function getUsersByPlan(): array
    {
        $distribution = User::query()
            ->select('plan', DB::raw('count(*) as count'))
            ->groupBy('plan')
            ->pluck('count', 'plan')
            ->map(fn ($count) => (int) $count)
            ->toArray();

        // Handle null plan values - count them as free
        $freeCount = $distribution['free'] ?? 0;
        if (isset($distribution[''])) {
            $freeCount += $distribution[''];
        }
        // Also handle null key (when plan is null)
        $nullCount = array_filter($distribution, fn ($key) => $key === '' || $key === null, ARRAY_FILTER_USE_KEY);
        if (!empty($nullCount)) {
            $freeCount += array_sum($nullCount);
        }

        return [
            'free' => $freeCount,
            'pro' => $distribution['pro'] ?? 0,
            'enterprise' => $distribution['enterprise'] ?? 0,
        ];
    }

    /**
     * Get full user statistics.
     *
     * Returns all user statistics in the format specified by SPEC-027 and TASK-27-5.
     *
     * @return array{
     *     total_users: int,
     *     active_users: int,
     *     new_today: int,
     *     new_this_week: int,
     *     new_this_month: int,
     *     growth_rates: array{daily: float, weekly: float, monthly: float},
     *     users_by_plan: array{free: int, pro: int, enterprise: int}
     * }
     */
    public function getFullStats(): array
    {
        return [
            'total_users' => $this->getTotalUserCount(),
            'active_users' => $this->getActiveUsersCount(),
            'new_today' => $this->getNewRegistrationsToday(),
            'new_this_week' => $this->getNewRegistrationsThisWeek(),
            'new_this_month' => $this->getNewRegistrationsThisMonth(),
            'growth_rates' => $this->getGrowthRates(),
            'users_by_plan' => $this->getUsersByPlan(),
        ];
    }

    /**
     * Get recent users with pagination.
     *
     * @param int $perPage Number of items per page
     * @param array $filters Optional filters (plan, is_active)
     * @return LengthAwarePaginator
     */
    public function getRecentUsers(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $perPage = min(max(1, $perPage), 100);

        $query = User::query()
            ->orderBy('created_at', 'desc')
            ->select(['id', 'name', 'created_at', 'plan', 'last_login_at']);

        // Apply plan filter
        if (isset($filters['plan'])) {
            $query->where('plan', $filters['plan']);
        }

        // Apply is_active filter
        if (isset($filters['is_active'])) {
            if ($filters['is_active']) {
                $query->where('last_login_at', '>=', Carbon::now()->subDays(30));
            } else {
                $query->where(function ($q) {
                    $q->whereNull('last_login_at')
                        ->orWhere('last_login_at', '<', Carbon::now()->subDays(30));
                });
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * Get active users with pagination.
     *
     * @param int $perPage Number of items per page
     * @param int $daysWithin Number of days to consider for "active" (default 30)
     * @param array $filters Optional filters (plan)
     * @return LengthAwarePaginator
     */
    public function getActiveUsers(int $perPage = 20, int $daysWithin = 30, array $filters = []): LengthAwarePaginator
    {
        $perPage = min(max(1, $perPage), 100);

        $query = User::query()
            ->where('last_login_at', '>=', Carbon::now()->subDays($daysWithin))
            ->whereNotNull('last_login_at')
            ->orderBy('last_login_at', 'desc')
            ->select(['id', 'name', 'last_login_at', 'plan']);

        // Apply plan filter
        if (isset($filters['plan'])) {
            $query->where('plan', $filters['plan']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get cached full stats.
     *
     * @return array
     */
    public function getCachedFullStats(): array
    {
        return Cache::remember(self::CACHE_PREFIX . ':full', self::CACHE_TTL, function () {
            return $this->getFullStats();
        });
    }

    /**
     * Clear cached stats.
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_PREFIX . ':full');
    }
}
