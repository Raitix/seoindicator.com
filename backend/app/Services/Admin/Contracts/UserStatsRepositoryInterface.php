<?php

namespace App\Services\Admin\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface for User Stats Repository
 * 
 * Used for mocking in tests to decouple from actual implementation.
 */
interface UserStatsRepositoryInterface
{
    /**
     * Get aggregated user statistics.
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
    public function getFullStats(): array;

    /**
     * Get recent users with pagination.
     *
     * @param int $perPage Number of items per page
     * @param array $filters Optional filters (plan, is_active)
     * @return LengthAwarePaginator
     */
    public function getRecentUsers(int $perPage = 20, array $filters = []): LengthAwarePaginator;

    /**
     * Get active users with pagination.
     *
     * @param int $perPage Number of items per page
     * @param int $daysWithin Number of days to consider for "active" (default 30)
     * @param array $filters Optional filters (plan)
     * @return LengthAwarePaginator
     */
    public function getActiveUsers(int $perPage = 20, int $daysWithin = 30, array $filters = []): LengthAwarePaginator;
}
