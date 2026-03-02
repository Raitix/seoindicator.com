<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * User Aggregation Service for Admin Dashboard.
 *
 * Aggregates user statistics, growth rates, and plan distribution.
 *
 * @see TASK-27-18
 * @see SPEC-027
 */
class UserAggregationService
{
    /**
     * Get all aggregated user metrics.
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'total' => $this->getTotalCount(),
            'active' => $this->getActiveCount(),
            'newThisMonth' => $this->getNewThisMonthCount(),
            'newThisWeek' => $this->getNewThisWeekCount(),
            'newToday' => $this->getNewTodayCount(),
            'growthRate' => $this->getGrowthRates(),
            'byPlan' => $this->getPlanDistribution(),
        ];
    }

    /**
     * Get total user count.
     *
     * @return int
     */
    protected function getTotalCount(): int
    {
        return User::count();
    }

    /**
     * Get active users count (logged in within last 30 days).
     *
     * @return int
     */
    protected function getActiveCount(): int
    {
        return User::where('last_login_at', '>=', now()->subDays(30))->count();
    }

    /**
     * Get new users this month count.
     *
     * @return int
     */
    protected function getNewThisMonthCount(): int
    {
        return User::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    /**
     * Get new users this week count.
     *
     * @return int
     */
    protected function getNewThisWeekCount(): int
    {
        return User::whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ])->count();
    }

    /**
     * Get new users today count.
     *
     * @return int
     */
    protected function getNewTodayCount(): int
    {
        return User::whereDate('created_at', today())->count();
    }

    /**
     * Get growth rates for monthly, weekly, and daily periods.
     *
     * @return array
     */
    protected function getGrowthRates(): array
    {
        return [
            'monthly' => $this->calculateMonthlyGrowthRate(),
            'weekly' => $this->calculateWeeklyGrowthRate(),
            'daily' => $this->calculateDailyGrowthRate(),
        ];
    }

    /**
     * Calculate monthly growth rate.
     *
     * @return float
     */
    protected function calculateMonthlyGrowthRate(): float
    {
        $thisMonth = $this->getNewThisMonthCount();
        $lastMonth = User::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        if ($lastMonth === 0) {
            return 0.0;
        }

        return round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2);
    }

    /**
     * Calculate weekly growth rate.
     *
     * @return float
     */
    protected function calculateWeeklyGrowthRate(): float
    {
        $thisWeek = $this->getNewThisWeekCount();
        $lastWeek = User::whereBetween('created_at', [
            now()->subWeek()->startOfWeek(),
            now()->subWeek()->endOfWeek(),
        ])->count();

        if ($lastWeek === 0) {
            return 0.0;
        }

        return round((($thisWeek - $lastWeek) / $lastWeek) * 100, 2);
    }

    /**
     * Calculate daily growth rate.
     *
     * @return float
     */
    protected function calculateDailyGrowthRate(): float
    {
        $today = $this->getNewTodayCount();
        $yesterday = User::whereDate('created_at', now()->subDay())->count();

        if ($yesterday === 0) {
            return 0.0;
        }

        return round((($today - $yesterday) / $yesterday) * 100, 2);
    }

    /**
     * Get plan distribution.
     *
     * @return array
     */
    protected function getPlanDistribution(): array
    {
        $distribution = User::query()
            ->select('plan', DB::raw('count(*) as count'))
            ->groupBy('plan')
            ->pluck('count', 'plan')
            ->toArray();

        // Ensure all plan types are represented
        return [
            'free' => $distribution['free'] ?? 0,
            'pro' => $distribution['pro'] ?? 0,
            'enterprise' => $distribution['enterprise'] ?? 0,
        ];
    }

    /**
     * Get recent users with pagination.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getRecentUsers(int $perPage = 15): LengthAwarePaginator
    {
        $perPage = min(max(1, $perPage), 100);

        return User::query()
            ->orderBy('created_at', 'desc')
            ->select(['id', 'name', 'email', 'role', 'plan', 'created_at', 'last_login_at'])
            ->paginate($perPage);
    }

    /**
     * Get active users with pagination (logged in within last 30 days).
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getActiveUsers(int $perPage = 15): LengthAwarePaginator
    {
        $perPage = min(max(1, $perPage), 100);

        return User::query()
            ->where('last_login_at', '>=', now()->subDays(30))
            ->orderBy('last_login_at', 'desc')
            ->select(['id', 'name', 'email', 'role', 'plan', 'created_at', 'last_login_at'])
            ->paginate($perPage);
    }
}
