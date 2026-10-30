<?php

namespace App\Services;

use App\Repositories\AnalysisHistoryRepository;
use App\Services\CostTrackingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * DashboardAggregator Service
 *
 * Aggregates data for the user dashboard view.
 * Provides summary statistics, recent analyses, cost summary, and quick insights.
 *
 * @see TASK-25-15
 * @see SPEC-025
 */
class DashboardAggregator
{
    /**
     * Cache TTL in seconds (1 minute).
     */
    protected const CACHE_TTL = 60;

    /**
     * Recent analyses limit.
     */
    protected const RECENT_LIMIT = 10;

    /**
     * Create a new DashboardAggregator instance.
     *
     * @param AnalysisHistoryRepository $analysisRepository
     * @param CostTrackingService $costService
     */
    public function __construct(
        protected AnalysisHistoryRepository $analysisRepository,
        protected CostTrackingService $costService
    ) {}

    /**
     * Get full dashboard data for a user.
     *
     * @param int $userId
     * @return array
     */
    public function getDashboardData(int $userId): array
    {
        $cacheKey = "dashboard.{$userId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            return [
                'stats' => $this->getStats($userId),
                'costSummary' => $this->getCostSummary($userId),
                'recentAnalyses' => $this->getRecentAnalyses($userId),
                'quickInsights' => $this->getQuickInsights($userId),
            ];
        });
    }

    /**
     * Get stats for a user.
     *
     * @param int $userId
     * @return array{totalAnalyses: int, thisMonth: int, thisWeek: int, today: int}
     */
    public function getStats(int $userId): array
    {
        $cacheKey = "dashboard.stats.{$userId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            // Get all stats from repository
            $repositoryStats = $this->analysisRepository->getStats($userId);

            return [
                'totalAnalyses' => $repositoryStats['total'] ?? 0,
                'thisMonth' => $repositoryStats['this_month'] ?? 0,
                'thisWeek' => $repositoryStats['this_week'] ?? 0,
                'today' => $repositoryStats['today'] ?? 0,
            ];
        });
    }

    /**
     * Get recent analyses for a user.
     *
     * @param int $userId
     * @return array<int, array{id: int, toolName: string, inputUrl: string, status: string, analyzedAt: string|null}>
     */
    public function getRecentAnalyses(int $userId): array
    {
        $cacheKey = "dashboard.recent.{$userId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            $analyses = $this->analysisRepository->getRecent($userId, self::RECENT_LIMIT);

            return collect($analyses)->map(function ($analysis) {
                return [
                    'id' => $analysis->id,
                    'toolName' => $analysis->tool_name,
                    'inputUrl' => $analysis->input_url,
                    'status' => $analysis->status,
                    'analyzedAt' => $analysis->analyzed_at?->toIso8601String(),
                ];
            })->toArray();
        });
    }

    /**
     * Get cost summary for a user.
     *
     * @param int $userId
     * @return array{totalCost: float, thisMonthCost: float, currency: string}
     */
    public function getCostSummary(int $userId): array
    {
        $cacheKey = "dashboard.costs.{$userId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            // Get total cost
            $totalSummary = $this->costService->getUserCostSummary($userId, null);

            // Get this month's cost
            $monthSummary = $this->costService->getUserCostSummary($userId, 'month');

            return [
                'totalCost' => round((float) ($totalSummary['total'] ?? 0), 6),
                'thisMonthCost' => round((float) ($monthSummary['total'] ?? 0), 6),
                'currency' => 'USD',
            ];
        });
    }

    /**
     * Get quick insights for a user.
     *
     * @param int $userId
     * @return array{mostUsedTool: string|null, avgAnalysisTime: float|null, successRate: float|null}
     */
    public function getQuickInsights(int $userId): array
    {
        $cacheKey = "dashboard.insights.{$userId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            $stats = $this->analysisRepository->getStats($userId);

            // Most used tool
            $mostUsedTool = null;
            if (!empty($stats['by_tool'])) {
                $byTool = $stats['by_tool'];
                arsort($byTool);
                
                // If there's a tie, return alphabetically first
                $maxCount = max($byTool);
                $topTools = array_keys(array_filter($byTool, fn($count) => $count === $maxCount));
                sort($topTools);
                $mostUsedTool = $topTools[0] ?? null;
            }

            // Average analysis time
            $avgAnalysisTime = null;
            if (isset($stats['avg_duration_ms']) && $stats['avg_duration_ms'] > 0) {
                $avgAnalysisTime = (float) $stats['avg_duration_ms'];
            }

            // Success rate
            $successRate = null;
            if (isset($stats['success_rate'])) {
                $successRate = (float) $stats['success_rate'];
            }

            return [
                'mostUsedTool' => $mostUsedTool,
                'avgAnalysisTime' => $avgAnalysisTime,
                'successRate' => $successRate,
            ];
        });
    }

    /**
     * Clear the dashboard cache for a user.
     *
     * @param int $userId
     * @return void
     */
    public function clearCache(int $userId): void
    {
        Cache::forget("dashboard.{$userId}");
        Cache::forget("dashboard.stats.{$userId}");
        Cache::forget("dashboard.recent.{$userId}");
        Cache::forget("dashboard.costs.{$userId}");
        Cache::forget("dashboard.insights.{$userId}");
    }
}
