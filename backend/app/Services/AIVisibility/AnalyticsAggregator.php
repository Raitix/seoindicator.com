<?php

namespace App\Services\AIVisibility;

use App\Models\AIVisibility\AIMentionAnalytics;
use App\Models\AIVisibility\AIQueryResult;
use App\Models\AIVisibility\AIVisibilityProject;
use App\Services\AIVisibility\Contracts\AnalyticsAggregatorInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Analytics Aggregator Service.
 *
 * Calculates mention rates, share of voice, trend analysis,
 * and sentiment breakdowns for AI visibility tracking.
 *
 * @see TASK-34-13
 * @see SPEC-034
 */
class AnalyticsAggregator implements AnalyticsAggregatorInterface
{
    /**
     * Valid period values.
     */
    private const VALID_PERIODS = ['day', 'week', 'month'];

    /**
     * Cache TTL in seconds (1 hour).
     */
    private const CACHE_TTL = 3600;

    /**
     * Trend threshold for stable direction (5%).
     */
    private const TREND_THRESHOLD = 5.0;

    /**
     * Aggregate query results into analytics table.
     *
     * @param int $projectId
     * @param string $period Period: 'day', 'week', 'month'
     * @return AIMentionAnalytics
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function aggregate(int $projectId, string $period = 'week'): AIMentionAnalytics
    {
        $this->validatePeriod($period);
        $project = $this->getProjectOrFail($projectId);

        [$periodStart, $periodEnd] = $this->getPeriodDates($period);

        // Get all query results for the period (across all platforms)
        $queryResults = AIQueryResult::where('project_id', $projectId)
            ->whereBetween('queried_at', [$periodStart, $periodEnd])
            ->get();

        // Aggregate by platform
        $platformAggregates = $queryResults->groupBy('platform')->map(function ($results, $platform) use ($projectId, $period, $periodStart, $periodEnd) {
            return $this->aggregatePlatformResults($projectId, $platform, $period, $periodStart, $periodEnd, $results);
        });

        // Return the first platform aggregate (for single-platform calls)
        // Or create a combined aggregate
        $firstAggregate = $platformAggregates->first();
        
        if (!$firstAggregate) {
            // No results - create empty aggregate
            return $this->createEmptyAggregate($projectId, 'all', $period, $periodStart, $periodEnd);
        }

        // Clear cache for this project/period
        $this->clearAnalyticsCache($projectId, $period);

        Log::info('AnalyticsAggregator: Aggregated analytics', [
            'project_id' => $projectId,
            'period' => $period,
            'total_queries' => $firstAggregate->total_queries,
        ]);

        return $firstAggregate;
    }

    /**
     * Calculate mention rate for a platform.
     * Formula: (brand_mentions / total_queries) * 100
     *
     * @param int $projectId
     * @param string $platform
     * @param string $period
     * @return float
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function calculateMentionRate(int $projectId, string $platform, string $period = 'week'): float
    {
        $this->validatePeriod($period);
        $this->getProjectOrFail($projectId);

        $cacheKey = "ai_visibility:mention_rate:{$projectId}:{$platform}:{$period}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($projectId, $platform, $period) {
            [$periodStart, $periodEnd] = $this->getPeriodDates($period);

            $totalQueries = AIQueryResult::where('project_id', $projectId)
                ->where('platform', $platform)
                ->whereBetween('queried_at', [$periodStart, $periodEnd])
                ->count();

            if ($totalQueries === 0) {
                return 0.00;
            }

            $brandMentions = AIQueryResult::where('project_id', $projectId)
                ->where('platform', $platform)
                ->where('brand_mentioned', true)
                ->whereBetween('queried_at', [$periodStart, $periodEnd])
                ->count();

            $rate = ($brandMentions / $totalQueries) * 100;

            return round(min($rate, 100.00), 2);
        });
    }

    /**
     * Calculate share of voice.
     * Formula: brand_mentions / (brand_mentions + sum(competitor_mentions)) * 100
     *
     * @param int $projectId
     * @param string $period
     * @return array{share_of_voice: float, competitor_breakdown: array}
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function calculateShareOfVoice(int $projectId, string $period = 'week'): array
    {
        $this->validatePeriod($period);
        $project = $this->getProjectOrFail($projectId);

        $cacheKey = "ai_visibility:sov:{$projectId}:{$period}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($projectId, $project, $period) {
            [$periodStart, $periodEnd] = $this->getPeriodDates($period);

            // Get brand mentions
            $brandMentions = AIQueryResult::where('project_id', $projectId)
                ->where('brand_mentioned', true)
                ->whereBetween('queried_at', [$periodStart, $periodEnd])
                ->count();

            // Get competitor mentions
            $competitorBreakdown = [];
            $totalCompetitorMentions = 0;

            $competitorNames = $project->getCompetitorNames();
            
            if (!empty($competitorNames)) {
                $results = AIQueryResult::where('project_id', $projectId)
                    ->whereBetween('queried_at', [$periodStart, $periodEnd])
                    ->get();

                foreach ($competitorNames as $name) {
                    $count = 0;
                    foreach ($results as $result) {
                        if (in_array($name, $result->competitor_mentions ?? [])) {
                            $count++;
                        }
                    }
                    $competitorBreakdown[$name] = $count;
                    $totalCompetitorMentions += $count;
                }
            }

            // Calculate share of voice
            $totalMentions = $brandMentions + $totalCompetitorMentions;

            if ($totalMentions === 0) {
                return [
                    'share_of_voice' => 0.00,
                    'competitor_breakdown' => $competitorBreakdown,
                ];
            }

            $sov = ($brandMentions / $totalMentions) * 100;

            return [
                'share_of_voice' => round(min($sov, 100.00), 2),
                'competitor_breakdown' => $competitorBreakdown,
            ];
        });
    }

    /**
     * Get trend direction and change percentage.
     * Compares current period vs previous period.
     * Returns: { direction: up|down|stable, change_percent: float }
     * Stable if change < 5%
     *
     * @param int $projectId
     * @param string $period
     * @return array{direction: string, change_percent: float, current_period: array, previous_period: array}
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function getTrend(int $projectId, string $period = 'week'): array
    {
        $this->validatePeriod($period);
        $this->getProjectOrFail($projectId);

        $cacheKey = "ai_visibility:trend:{$projectId}:{$period}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($projectId, $period) {
            [$currentStart, $currentEnd] = $this->getPeriodDates($period);
            [$previousStart, $previousEnd] = $this->getPreviousPeriodDates($period);

            // Get current period mentions
            $currentMentions = AIQueryResult::where('project_id', $projectId)
                ->where('brand_mentioned', true)
                ->whereBetween('queried_at', [$currentStart, $currentEnd])
                ->count();

            // Get previous period mentions
            $previousMentions = AIQueryResult::where('project_id', $projectId)
                ->where('brand_mentioned', true)
                ->whereBetween('queried_at', [$previousStart, $previousEnd])
                ->count();

            // Calculate change
            $changePercent = 0.00;
            $direction = 'stable';

            if ($previousMentions > 0) {
                $changePercent = (($currentMentions - $previousMentions) / $previousMentions) * 100;
                
                if (abs($changePercent) >= self::TREND_THRESHOLD) {
                    $direction = $changePercent > 0 ? 'up' : 'down';
                }
            } elseif ($currentMentions > 0) {
                // No previous data but current has data
                $direction = 'stable';
                $changePercent = 0.00;
            }

            return [
                'direction' => $direction,
                'change_percent' => round($changePercent, 2),
                'current_period' => [
                    'mentions' => $currentMentions,
                    'period_start' => $currentStart->toDateString(),
                    'period_end' => $currentEnd->toDateString(),
                ],
                'previous_period' => [
                    'mentions' => $previousMentions,
                    'period_start' => $previousStart->toDateString(),
                    'period_end' => $previousEnd->toDateString(),
                ],
            ];
        });
    }

    /**
     * Get top categories by query count.
     *
     * @param int $projectId
     * @param int $limit
     * @param string|null $period
     * @return array
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function getTopCategories(int $projectId, int $limit = 5, ?string $period = null): array
    {
        $this->validateLimit($limit);
        $this->getProjectOrFail($projectId);

        if ($period !== null) {
            $this->validatePeriod($period);
        }

        $cacheKey = "ai_visibility:categories:{$projectId}:{$limit}:" . ($period ?? 'all');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($projectId, $limit, $period) {
            $query = AIQueryResult::where('project_id', $projectId)
                ->whereNotNull('query_category');

            if ($period !== null) {
                [$periodStart, $periodEnd] = $this->getPeriodDates($period);
                $query->whereBetween('queried_at', [$periodStart, $periodEnd]);
            }

            // Use database aggregation
            $categories = $query->select('query_category', DB::raw('COUNT(*) as count'))
                ->groupBy('query_category')
                ->orderByDesc('count')
                ->limit($limit)
                ->get();

            $result = [];
            foreach ($categories as $category) {
                // Calculate mention rate for this category
                $categoryQuery = AIQueryResult::where('project_id', $projectId)
                    ->where('query_category', $category->query_category);

                if ($period !== null) {
                    [$periodStart, $periodEnd] = $this->getPeriodDates($period);
                    $categoryQuery->whereBetween('queried_at', [$periodStart, $periodEnd]);
                }

                $total = $categoryQuery->count();
                $mentions = (clone $categoryQuery)->where('brand_mentioned', true)->count();

                $result[] = [
                    'category' => $category->query_category,
                    'count' => $category->count,
                    'mention_rate' => $total > 0 ? round(($mentions / $total) * 100, 2) : 0.00,
                ];
            }

            return $result;
        });
    }

    /**
     * Get sentiment breakdown.
     * Returns: { positive, neutral, negative, mixed, average_sentiment }
     *
     * @param int $projectId
     * @param string $period
     * @return array
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function getSentimentBreakdown(int $projectId, string $period = 'week'): array
    {
        $this->validatePeriod($period);
        $this->getProjectOrFail($projectId);

        $cacheKey = "ai_visibility:sentiment:{$projectId}:{$period}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($projectId, $period) {
            [$periodStart, $periodEnd] = $this->getPeriodDates($period);

            // Use database aggregation
            $sentiments = AIQueryResult::where('project_id', $projectId)
                ->whereBetween('queried_at', [$periodStart, $periodEnd])
                ->select('sentiment', DB::raw('COUNT(*) as count'), DB::raw('AVG(sentiment_score) as avg_score'))
                ->groupBy('sentiment')
                ->get();

            $result = [
                'positive' => 0,
                'neutral' => 0,
                'negative' => 0,
                'mixed' => 0,
                'average_sentiment' => 0.00,
                'total' => 0,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'last_updated' => now()->toIso8601String(),
            ];

            $totalSentimentScore = 0;
            $totalWithScore = 0;

            foreach ($sentiments as $sentiment) {
                $type = strtolower($sentiment->sentiment ?? 'neutral');
                
                if (isset($result[$type])) {
                    $result[$type] = $sentiment->count;
                }

                $result['total'] += $sentiment->count;

                if ($sentiment->avg_score !== null) {
                    $totalSentimentScore += $sentiment->avg_score * $sentiment->count;
                    $totalWithScore += $sentiment->count;
                }
            }

            // Calculate average sentiment
            if ($totalWithScore > 0) {
                $result['average_sentiment'] = round(max(-1, min(1, $totalSentimentScore / $totalWithScore)), 2);
            }

            return $result;
        });
    }

    // ============================================================
    // Interface Implementation Methods
    // ============================================================

    /**
     * Get project by ID.
     *
     * @param int $projectId
     * @return object|null
     */
    public function getProject(int $projectId): ?object
    {
        return AIVisibilityProject::find($projectId);
    }

    /**
     * Get aggregated analytics for a project.
     *
     * @param int $projectId
     * @param string $period
     * @param string|null $platform
     * @return array
     */
    public function getAnalytics(int $projectId, string $period = 'week', ?string $platform = null): array
    {
        $this->validatePeriod($period);
        $project = $this->getProjectOrFail($projectId);

        $mentionRate = $this->calculateMentionRate($projectId, $platform ?? 'all', $period);
        $sov = $this->calculateShareOfVoice($projectId, $period);
        $trend = $this->getTrend($projectId, $period);
        $sentiment = $this->getSentimentBreakdown($projectId, $period);

        // Get by_platform breakdown
        $platforms = ['chatgpt', 'perplexity'];
        $byPlatform = [];
        
        foreach ($platforms as $p) {
            $byPlatform[$p] = [
                'mention_rate' => $this->calculateMentionRate($projectId, $p, $period),
            ];
        }

        return [
            'project_id' => $projectId,
            'period' => $period,
            'mention_rate' => $mentionRate,
            'share_of_voice' => $sov['share_of_voice'],
            'competitor_breakdown' => $sov['competitor_breakdown'],
            'sentiment' => $sentiment,
            'trend' => $trend,
            'by_platform' => $byPlatform,
        ];
    }

    /**
     * Get brand vs competitors comparison.
     *
     * @param int $projectId
     * @return array
     */
    public function getComparison(int $projectId): array
    {
        $project = $this->getProjectOrFail($projectId);
        $sov = $this->calculateShareOfVoice($projectId, 'month');

        $brandData = [
            'name' => $project->brand_name,
            'mentions' => AIQueryResult::where('project_id', $projectId)
                ->where('brand_mentioned', true)
                ->where('queried_at', '>=', now()->subMonth())
                ->count(),
        ];

        $competitors = [];
        foreach ($sov['competitor_breakdown'] as $name => $mentions) {
            $competitors[] = [
                'name' => $name,
                'mentions' => $mentions,
            ];
        }

        return [
            'brand' => $brandData,
            'competitors' => $competitors,
        ];
    }

    /**
     * Get trend analysis over time.
     *
     * @param int $projectId
     * @param int $days
     * @return array
     */
    public function getTrendAnalysis(int $projectId, int $days = 90): array
    {
        $project = $this->getProjectOrFail($projectId);

        $data = [];
        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $mentions = AIQueryResult::where('project_id', $projectId)
                ->where('brand_mentioned', true)
                ->whereDate('queried_at', $date)
                ->count();

            $data[] = [
                'date' => $date,
                'mentions' => $mentions,
            ];
        }

        // Calculate overall direction
        $recentMentions = array_sum(array_column(array_slice($data, -7), 'mentions'));
        $earlierMentions = array_sum(array_column(array_slice($data, 0, 7), 'mentions'));

        $direction = 'stable';
        $changePercent = 0;

        if ($earlierMentions > 0) {
            $changePercent = (($recentMentions - $earlierMentions) / $earlierMentions) * 100;
            
            if (abs($changePercent) >= self::TREND_THRESHOLD) {
                $direction = $changePercent > 0 ? 'up' : 'down';
            }
        }

        return [
            'data' => $data,
            'direction' => $direction,
            'change_percent' => round($changePercent, 2),
        ];
    }

    /**
     * Get paginated query history.
     *
     * @param int $projectId
     * @param int $page
     * @param string|null $platform
     * @param bool $mentionedOnly
     * @return array
     */
    public function getQueries(int $projectId, int $page = 1, ?string $platform = null, bool $mentionedOnly = false): array
    {
        $project = $this->getProjectOrFail($projectId);

        $query = AIQueryResult::where('project_id', $projectId)
            ->orderByDesc('queried_at');

        if ($platform !== null) {
            $query->where('platform', $platform);
        }

        if ($mentionedOnly) {
            $query->where('brand_mentioned', true);
        }

        $perPage = 20;
        $results = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
        ];
    }

    // ============================================================
    // Private Helper Methods
    // ============================================================

    /**
     * Validate period value.
     *
     * @param string $period
     * @throws ValidationException
     */
    private function validatePeriod(string $period): void
    {
        if (!in_array($period, self::VALID_PERIODS)) {
            throw ValidationException::withMessages([
                'period' => "Invalid period. Must be one of: " . implode(', ', self::VALID_PERIODS),
            ]);
        }
    }

    /**
     * Validate limit value.
     *
     * @param int $limit
     * @throws ValidationException
     */
    private function validateLimit(int $limit): void
    {
        if ($limit <= 0) {
            throw ValidationException::withMessages([
                'limit' => 'Limit must be a positive integer.',
            ]);
        }
    }

    /**
     * Get project or throw exception.
     *
     * @param int $projectId
     * @return AIVisibilityProject
     * @throws ModelNotFoundException
     */
    private function getProjectOrFail(int $projectId): AIVisibilityProject
    {
        $project = AIVisibilityProject::find($projectId);

        if (!$project) {
            throw (new ModelNotFoundException())->setModel(AIVisibilityProject::class, $projectId);
        }

        return $project;
    }

    /**
     * Get period start and end dates.
     *
     * @param string $period
     * @return array{0: Carbon, 1: Carbon}
     */
    private function getPeriodDates(string $period): array
    {
        $now = now();

        return match ($period) {
            'day' => [$now->copy()->subDay()->startOfDay(), $now->copy()->endOfDay()],
            'week' => [$now->copy()->subWeek()->startOfDay(), $now->copy()->endOfDay()],
            'month' => [$now->copy()->subMonth()->startOfDay(), $now->copy()->endOfDay()],
            default => [$now->copy()->subWeek()->startOfDay(), $now->copy()->endOfDay()],
        };
    }

    /**
     * Get previous period dates.
     *
     * @param string $period
     * @return array{0: Carbon, 1: Carbon}
     */
    private function getPreviousPeriodDates(string $period): array
    {
        $now = now();

        return match ($period) {
            'day' => [$now->copy()->subDays(2)->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'week' => [$now->copy()->subWeeks(2)->startOfDay(), $now->copy()->subWeek()->endOfDay()],
            'month' => [$now->copy()->subMonths(2)->startOfDay(), $now->copy()->subMonth()->endOfDay()],
            default => [$now->copy()->subWeeks(2)->startOfDay(), $now->copy()->subWeek()->endOfDay()],
        };
    }

    /**
     * Aggregate results for a specific platform.
     *
     * @param int $projectId
     * @param string $platform
     * @param string $period
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @param Collection $results
     * @return AIMentionAnalytics
     */
    private function aggregatePlatformResults(
        int $projectId,
        string $platform,
        string $period,
        Carbon $periodStart,
        Carbon $periodEnd,
        Collection $results
    ): AIMentionAnalytics {
        $totalQueries = $results->count();
        $brandMentions = $results->where('brand_mentioned', true)->count();

        // Calculate sentiment breakdown
        $positive = $results->where('sentiment', 'positive')->count();
        $neutral = $results->where('sentiment', 'neutral')->count();
        $negative = $results->where('sentiment', 'negative')->count();
        $mixed = $results->where('sentiment', 'mixed')->count();

        // Calculate average sentiment
        $sentimentScores = $results->whereNotNull('sentiment_score')->pluck('sentiment_score');
        $averageSentiment = $sentimentScores->isNotEmpty()
            ? round($sentimentScores->avg(), 2)
            : 0.00;

        // Calculate mention rate
        $mentionRate = $totalQueries > 0 ? round(($brandMentions / $totalQueries) * 100, 2) : 0.00;

        // Get competitor mentions
        $competitorMentions = [];
        foreach ($results as $result) {
            foreach ($result->competitor_mentions ?? [] as $competitor) {
                $competitorMentions[$competitor] = ($competitorMentions[$competitor] ?? 0) + 1;
            }
        }

        // Upsert analytics record
        return AIMentionAnalytics::updateOrCreate(
            [
                'project_id' => $projectId,
                'platform' => $platform,
                'period' => $period,
            ],
            [
                'total_queries' => $totalQueries,
                'brand_mentions' => $brandMentions,
                'mention_rate' => $mentionRate,
                'positive_mentions' => $positive,
                'neutral_mentions' => $neutral,
                'negative_mentions' => $negative,
                'mixed_mentions' => $mixed,
                'average_sentiment' => $averageSentiment,
                'competitor_mentions' => $competitorMentions,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'aggregated_at' => now(),
            ]
        );
    }

    /**
     * Create an empty aggregate record.
     *
     * @param int $projectId
     * @param string $platform
     * @param string $period
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @return AIMentionAnalytics
     */
    private function createEmptyAggregate(
        int $projectId,
        string $platform,
        string $period,
        Carbon $periodStart,
        Carbon $periodEnd
    ): AIMentionAnalytics {
        return AIMentionAnalytics::updateOrCreate(
            [
                'project_id' => $projectId,
                'platform' => $platform,
                'period' => $period,
            ],
            [
                'total_queries' => 0,
                'brand_mentions' => 0,
                'mention_rate' => 0.00,
                'positive_mentions' => 0,
                'neutral_mentions' => 0,
                'negative_mentions' => 0,
                'mixed_mentions' => 0,
                'average_sentiment' => 0.00,
                'competitor_mentions' => [],
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'aggregated_at' => now(),
            ]
        );
    }

    /**
     * Clear analytics cache for a project/period.
     *
     * @param int $projectId
     * @param string $period
     */
    private function clearAnalyticsCache(int $projectId, string $period): void
    {
        $keys = [
            "ai_visibility:analytics:{$projectId}:{$period}",
            "ai_visibility:mention_rate:{$projectId}:chatgpt:{$period}",
            "ai_visibility:mention_rate:{$projectId}:perplexity:{$period}",
            "ai_visibility:sov:{$projectId}:{$period}",
            "ai_visibility:trend:{$projectId}:{$period}",
            "ai_visibility:sentiment:{$projectId}:{$period}",
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}
