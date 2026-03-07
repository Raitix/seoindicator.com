<?php

namespace App\Services\AIVisibility\Contracts;

/**
 * Interface for Analytics Aggregator Service
 * 
 * Provides aggregated analytics data for AI Visibility tracking.
 * Used by AIVisibilityAnalyticsController to serve API responses.
 * 
 * @see TASK-34-13 (AnalyticsAggregator Service)
 * @see TASK-34-14 (Analytics API Endpoints)
 */
interface AnalyticsAggregatorInterface
{
    /**
     * Get project by ID
     *
     * @param int $projectId
     * @return object|null Project object or null if not found
     */
    public function getProject(int $projectId): ?object;

    /**
     * Get aggregated analytics for a project
     *
     * @param int $projectId
     * @param string $period Period: 'day', 'week', 'month'
     * @param string|null $platform Optional platform filter (chatgpt, perplexity, etc.)
     * @return array Analytics data with mention_rate, share_of_voice, sentiment, trend, by_platform
     */
    public function getAnalytics(int $projectId, string $period = 'week', ?string $platform = null): array;

    /**
     * Get brand vs competitors comparison
     *
     * @param int $projectId
     * @return array Comparison data with 'brand' and 'competitors' arrays
     */
    public function getComparison(int $projectId): array;

    /**
     * Get trend analysis over time
     *
     * @param int $projectId
     * @param int $days Number of days to analyze (1-365)
     * @return array Trend data with 'data' array, 'direction', and 'change_percent'
     */
    public function getTrend(int $projectId, int $days = 90): array;

    /**
     * Get paginated query history
     *
     * @param int $projectId
     * @param int $page Page number (1+)
     * @param string|null $platform Optional platform filter
     * @param bool $mentionedOnly Only return queries where brand was mentioned
     * @return array Query data with 'data' array and 'meta' object
     */
    public function getQueries(int $projectId, int $page = 1, ?string $platform = null, bool $mentionedOnly = false): array;
}
