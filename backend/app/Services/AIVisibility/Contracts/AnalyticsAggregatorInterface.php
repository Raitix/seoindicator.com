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
     * Aggregate query results into analytics table
     *
     * @param int $projectId
     * @param string $period Period: 'day', 'week', 'month'
     * @return object The created/updated analytics model
     */
    public function aggregate(int $projectId, string $period = 'week'): object;

    /**
     * Calculate mention rate for a platform
     *
     * @param int $projectId
     * @param string $platform Platform: 'chatgpt', 'perplexity', etc.
     * @param string $period Period: 'day', 'week', 'month'
     * @return float Mention rate as percentage (0-100)
     */
    public function calculateMentionRate(int $projectId, string $platform, string $period = 'week'): float;

    /**
     * Calculate share of voice
     *
     * @param int $projectId
     * @param string $period Period: 'day', 'week', 'month'
     * @return array{share_of_voice: float, competitor_breakdown: array}
     */
    public function calculateShareOfVoice(int $projectId, string $period = 'week'): array;

    /**
     * Get trend data over time
     *
     * @param int $projectId
     * @param int $days Number of days to look back (default: 90, max: 365)
     * @return array{data: array, direction: string, change_percent: float}
     */
    public function getTrend(int $projectId, int $days = 90): array;

    /**
     * Get top categories by query count
     *
     * @param int $projectId
     * @param int $limit Maximum number of categories to return
     * @param string|null $period Optional period filter
     * @return array Array of categories with count and mention_rate
     */
    public function getTopCategories(int $projectId, int $limit = 5, ?string $period = null): array;

    /**
     * Get sentiment breakdown
     *
     * @param int $projectId
     * @param string $period Period: 'day', 'week', 'month'
     * @return array{positive: int, neutral: int, negative: int, mixed: int, average_sentiment: float, total: int}
     */
    public function getSentimentBreakdown(int $projectId, string $period = 'week'): array;

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
