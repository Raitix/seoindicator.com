<?php

namespace App\Services\LocalSEO\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @Task TASK-35-14
 * @Description GBP Insights Service Interface for mocking in tests
 */
interface GBPInsightsServiceInterface
{
    /**
     * Check if the GBP connection is active.
     *
     * @param int $locationId
     * @return bool
     */
    public function getConnectionStatus(int $locationId): bool;

    /**
     * Get insights for a location within a date range
     *
     * @param int $locationId
     * @param string $startDate
     * @param string $endDate
     * @return array{
     *     daily_breakdown: array<array{date: string, queries: int, views: int, actions: int}>,
     *     totals: array{queries: int, views: int, actions: int}
     * }
     */
    public function getInsights(int $locationId, string $startDate, string $endDate): array;

    /**
     * Get trend analysis for a location
     *
     * @param int $locationId
     * @param int $days Number of days for trend analysis (default 30)
     * @return array{
     *     wow: array<string, array{change: float, direction: string}>,
     *     mom: array<string, array{change: float, direction: string}>
     * }
     */
    public function getTrend(int $locationId, int $days = 30): array;

    /**
     * Get the latest insights for a location.
     *
     * @param int $locationId
     * @return object|null
     */
    public function getLatestInsights(int $locationId): ?object;

    /**
     * Trigger a background sync of insights from GBP API
     *
     * @param int $locationId
     * @return string Job ID for status tracking
     */
    public function syncInsights(int $locationId): string;
}
