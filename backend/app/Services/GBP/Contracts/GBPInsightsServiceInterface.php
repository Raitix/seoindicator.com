<?php

namespace App\Services\GBP\Contracts;

use App\Models\BusinessLocation;
use Illuminate\Support\Collection;

/**
 * Domain contract for syncing and aggregating Google Business Profile insights.
 *
 * This contract is intentionally business-location centric. The existing
 * LocalSEO\GBPInsightsService controller adapter can keep its legacy int-based
 * methods and delegate into this service during the Developer pass.
 *
 * @see TASK-35-9
 * @see SPEC-035
 */
interface GBPInsightsServiceInterface
{
    /**
     * Fetch insights from GBP and upsert daily snapshots for a location.
     *
     * Expected storage columns align with TASK-35-3 model coverage:
     * - views
     * - searches
     * - calls
     * - directions
     * - website_clicks
     * - photos_views
     * - insights_date
     * - raw_response
     *
     * @param BusinessLocation $location
     * @param array{start?: string, end?: string}|null $dateRange
     */
    public function syncInsights(BusinessLocation $location, ?array $dateRange = null): void;

    /**
     * Return stored daily snapshots for the requested date range.
     *
     * The returned rows should expose the raw stored metrics plus derived helper
     * fields used by downstream consumers:
     * - queries  => searches
     * - actions  => calls + directions + website_clicks
     *
     * @param BusinessLocation $location
     * @param array{start: string, end: string} $dateRange
     * @return Collection<int, array<string, mixed>>
     */
    public function getInsights(BusinessLocation $location, array $dateRange): Collection;

    /**
     * Build a trend summary for the most recent time window.
     *
     * @param BusinessLocation $location
     * @param int $days
     * @return array<string, mixed>
     */
    public function getInsightsTrend(BusinessLocation $location, int $days = 90): array;

    /**
     * Aggregate total metrics across a date range.
     *
     * @param BusinessLocation $location
     * @param array{start: string, end: string} $dateRange
     * @return array<string, int|float>
     */
    public function getTotalMetrics(BusinessLocation $location, array $dateRange): array;

    /**
     * Identify the best-performing day for a recent time window.
     *
     * @param BusinessLocation $location
     * @param int $days
     * @return array<string, mixed>|null
     */
    public function getTopPerformingDay(BusinessLocation $location, int $days = 30): ?array;
}
