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

            $query = AIQueryResult::where('project_id', $projectId)
                ->whereBetween('queried_at', [$periodStart, $periodEnd]);

            // If platform is 'all', don't filter by platform
            if ($platform !== 'all') {
                $query->where('platform', $platform);
            }

            $totalQueries = $query->count();

            if ($totalQueries === 0) {
                return 0.00;
            }

            $brandMentionsQuery = AIQueryResult::where('project_id', $projectId)
                ->where('brand_mentioned', true)
                ->whereBetween('queried_at', [$periodStart, $periodEnd]);

            // If platform is 'all', don't filter by platform
            if ($platform !== 'all') {
                $brandMentionsQuery->where('platform', $platform);
            }

            $brandMentions = $brandMentionsQuery->count();

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

            // Try to get brand mentions from AIMention table first (per-mention tracking)
            $brandMentions = \App\Models\AIVisibility\AIMention::where('project_id', $projectId)
                ->where('entity_type', 'brand')
                ->whereBetween('mentioned_at', [$periodStart, $periodEnd])
                ->count();

            // Fallback to AIQueryResult for backward compatibility
            if ($brandMentions === 0) {
                $brandMentions = AIQueryResult::where('project_id', $projectId)
                    ->where('brand_mentioned', true)
                    ->whereBetween('queried_at', [$periodStart, $periodEnd])
                    ->count();
            }

            // Get competitor mentions from AIMention table first
            $competitorBreakdown = [];
            $totalCompetitorMentions = 0;

            $competitorMentionsData = \App\Models\AIVisibility\AIMention::where('project_id', $projectId)
                ->where('entity_type', 'competitor')
                ->whereBetween('mentioned_at', [$periodStart, $periodEnd])
                ->select('entity_name', DB::raw('COUNT(*) as count'))
                ->groupBy('entity_name')
                ->get();

            foreach ($competitorMentionsData as $mention) {
                $competitorBreakdown[$mention->entity_name] = $mention->count;
                $totalCompetitorMentions += $mention->count;
            }

            // Fallback to AIQueryResult for backward compatibility
            if ($totalCompetitorMentions === 0) {
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
                        if ($count > 0) {
                            $competitorBreakdown[$name] = $count;
                            $totalCompetitorMentions += $count;
                        }
                    }
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
     * Returns: { direction: up|down|stable, change_percent: float, data: array }
     * Stable if change < 5%
     *
     * @param int $projectId
     * @param int $days Number of days to look back (default: 90, max: 365)
     * @return array{direction: string, change_percent: float, data: array}
     * @throws ModelNotFoundException
     */
    public function getTrend(int $projectId, int $days = 90): array
    {
        $project = $this->getProjectOrFail($projectId);

        // Cap days at 365
        $days = min($days, 365);

        $cacheKey = "ai_visibility:trend:{$projectId}:{$days}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($projectId, $days) {
            // Get data points for each day
            $data = [];
            for ($i = $days; $i >= 0; $i--) {
                $date = now()->subDays($i)->toDateString();
                
                // Check AIQueryResult first
                $mentions = AIQueryResult::where('project_id', $projectId)
                    ->where('brand_mentioned', true)
                    ->whereDate('queried_at', $date)
                    ->count();
                
                // Fallback to AIMention table (per-mention tracking)
                if ($mentions === 0) {
                    $mentions = \App\Models\AIVisibility\AIMention::where('project_id', $projectId)
                        ->where('entity_type', 'brand')
                        ->whereDate('mentioned_at', $date)
                        ->count();
                }

                $data[] = [
                    'date' => $date,
                    'mentions' => $mentions,
                ];
            }

            // Calculate overall direction by comparing recent vs earlier
            $recentMentions = array_sum(array_column(array_slice($data, -7), 'mentions'));
            $earlierMentions = array_sum(array_column(array_slice($data, 0, 7), 'mentions'));

            $direction = 'stable';
            $changePercent = 0.00;

            if ($earlierMentions > 0) {
                $changePercent = (($recentMentions - $earlierMentions) / $earlierMentions) * 100;
                
                if (abs($changePercent) >= self::TREND_THRESHOLD) {
                    $direction = $changePercent > 0 ? 'up' : 'down';
                }
            } elseif ($recentMentions > 0) {
                // No earlier data but recent has data
                $direction = 'stable';
                $changePercent = 0.00;
            }

            return [
                'data' => $data,
                'direction' => $direction,
                'change_percent' => round($changePercent, 2),
            ];
        });
    }

    /**
     * Get trend by period string (for internal use).
     *
     * @param int $projectId
     * @param string $period
     * @return array{direction: string, change_percent: float, current_period: array, previous_period: array}
     */
    public function getTrendByPeriod(int $projectId, string $period = 'week'): array
    {
        $this->validatePeriod($period);
        $this->getProjectOrFail($projectId);

        $cacheKey = "ai_visibility:trend_period:{$projectId}:{$period}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($projectId, $period) {
            [$currentStart, $currentEnd] = $this->getPeriodDates($period);
            [$previousStart, $previousEnd] = $this->getPreviousPeriodDates($period);

            // Get current period mentions from AIQueryResult first
            $currentMentions = AIQueryResult::where('project_id', $projectId)
                ->where('brand_mentioned', true)
                ->whereBetween('queried_at', [$currentStart, $currentEnd])
                ->count();
            
            // Fallback to AIMention table
            if ($currentMentions === 0) {
                $currentMentions = \App\Models\AIVisibility\AIMention::where('project_id', $projectId)
                    ->where('entity_type', 'brand')
                    ->whereBetween('mentioned_at', [$currentStart, $currentEnd])
                    ->count();
            }

            // Get previous period mentions from AIQueryResult first
            $previousMentions = AIQueryResult::where('project_id', $projectId)
                ->where('brand_mentioned', true)
                ->whereBetween('queried_at', [$previousStart, $previousEnd])
                ->count();
            
            // Fallback to AIMention table
            if ($previousMentions === 0) {
                $previousMentions = \App\Models\AIVisibility\AIMention::where('project_id', $projectId)
                    ->where('entity_type', 'brand')
                    ->whereBetween('mentioned_at', [$previousStart, $previousEnd])
                    ->count();
            }

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

            // Try AIMention table first (per-mention tracking)
            $aimentionSentiments = \App\Models\AIVisibility\AIMention::where('project_id', $projectId)
                ->where('entity_type', 'brand')
                ->whereBetween('mentioned_at', [$periodStart, $periodEnd])
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

            // If we have AIMention data, use it
            if ($aimentionSentiments->isNotEmpty()) {
                foreach ($aimentionSentiments as $sentiment) {
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
            } else {
                // Fallback to AIQueryResult for backward compatibility
                $sentiments = AIQueryResult::where('project_id', $projectId)
                    ->whereBetween('queried_at', [$periodStart, $periodEnd])
                    ->select('sentiment', DB::raw('COUNT(*) as count'), DB::raw('AVG(sentiment_score) as avg_score'))
                    ->groupBy('sentiment')
                    ->get();

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
        $trend = $this->getTrendByPeriod($projectId, $period);
        $sentiment = $this->getSentimentBreakdown($projectId, $period);

        // Get total queries count
        [$periodStart, $periodEnd] = $this->getPeriodDates($period);
        
        $query = AIQueryResult::where('project_id', $projectId)
            ->whereBetween('queried_at', [$periodStart, $periodEnd]);
        
        // If platform is specified and not 'all', filter by platform
        if ($platform !== null && $platform !== 'all') {
            $query->where('platform', $platform);
        }
        
        $totalQueries = $query->count();

        // Get total mentions count from AIQueryResult
        $mentionQuery = AIQueryResult::where('project_id', $projectId)
            ->where('brand_mentioned', true)
            ->whereBetween('queried_at', [$periodStart, $periodEnd]);
        
        if ($platform !== null && $platform !== 'all') {
            $mentionQuery->where('platform', $platform);
        }
        
        $mentionCount = $mentionQuery->count();
        
        // Fallback to AIMention table if no mentions from AIQueryResult
        if ($mentionCount === 0) {
            $aimentionQuery = \App\Models\AIVisibility\AIMention::where('project_id', $projectId)
                ->where('entity_type', 'brand')
                ->whereBetween('mentioned_at', [$periodStart, $periodEnd]);
            
            if ($platform !== null && $platform !== 'all') {
                $aimentionQuery->where('platform', $platform);
            }
            
            $mentionCount = $aimentionQuery->count();
        }

        // Get by_platform breakdown
        $platforms = ['chatgpt', 'perplexity'];
        $byPlatform = [];
        
        foreach ($platforms as $p) {
            $byPlatform[] = [
                'platform' => $p,
                'mention_rate' => $this->calculateMentionRate($projectId, $p, $period),
            ];
        }

        return [
            'project_id' => $projectId,
            'period' => $period,
            'total_queries' => $totalQueries,
            'mention_count' => $mentionCount,
            'mention_rate' => $mentionRate,
            'share_of_voice' => $sov['share_of_voice'],
            'competitor_breakdown' => $sov['competitor_breakdown'],
            'sentiment' => [
                'breakdown' => [
                    'positive' => $sentiment['positive'],
                    'neutral' => $sentiment['neutral'],
                    'negative' => $sentiment['negative'],
                    'mixed' => $sentiment['mixed'],
                ],
                'score' => $sentiment['average_sentiment'],
            ],
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

        // Count brand mentions from AIMention table (per-mention tracking)
        $brandMentions = \App\Models\AIVisibility\AIMention::where('project_id', $projectId)
            ->where('entity_type', 'brand')
            ->where('mentioned_at', '>=', now()->subMonth())
            ->count();

        // Also check AIQueryResult for backward compatibility
        $brandMentionsFromQuery = AIQueryResult::where('project_id', $projectId)
            ->where('brand_mentioned', true)
            ->where('queried_at', '>=', now()->subMonth())
            ->count();

        // Use the higher count (either from AIMention or AIQueryResult)
        $brandMentions = max($brandMentions, $brandMentionsFromQuery);

        $brandData = [
            'name' => $project->brand_name,
            'mentions' => $brandMentions,
        ];

        // Get competitor mentions from AIMention table
        $competitorMentions = \App\Models\AIVisibility\AIMention::where('project_id', $projectId)
            ->where('entity_type', 'competitor')
            ->where('mentioned_at', '>=', now()->subMonth())
            ->select('entity_name', DB::raw('COUNT(*) as count'))
            ->groupBy('entity_name')
            ->get();

        $competitors = [];
        foreach ($competitorMentions as $mention) {
            $competitors[] = [
                'name' => $mention->entity_name,
                'mentions' => $mention->count,
            ];
        }

        // Also check AIQueryResult.competitor_mentions for backward compatibility
        $queryResults = AIQueryResult::where('project_id', $projectId)
            ->where('queried_at', '>=', now()->subMonth())
            ->whereNotNull('competitor_mentions')
            ->get();

        $competitorMentionsFromArray = [];
        foreach ($queryResults as $result) {
            foreach ($result->competitor_mentions ?? [] as $competitor) {
                $competitorMentionsFromArray[$competitor] = ($competitorMentionsFromArray[$competitor] ?? 0) + 1;
            }
        }

        // Merge counts, taking the higher value
        foreach ($competitorMentionsFromArray as $name => $count) {
            $existingIndex = array_search($name, array_column($competitors, 'name'));
            if ($existingIndex !== false) {
                $competitors[$existingIndex]['mentions'] = max($competitors[$existingIndex]['mentions'], $count);
            } else {
                $competitors[] = [
                    'name' => $name,
                    'mentions' => $count,
                ];
            }
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
            
            // Check AIQueryResult first
            $mentions = AIQueryResult::where('project_id', $projectId)
                ->where('brand_mentioned', true)
                ->whereDate('queried_at', $date)
                ->count();
            
            // Fallback to AIMention table (per-mention tracking)
            if ($mentions === 0) {
                $mentions = \App\Models\AIVisibility\AIMention::where('project_id', $projectId)
                    ->where('entity_type', 'brand')
                    ->whereDate('mentioned_at', $date)
                    ->count();
            }

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
