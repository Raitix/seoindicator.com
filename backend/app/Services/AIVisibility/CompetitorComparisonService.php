<?php

namespace App\Services\AIVisibility;

use App\Models\AIVisibility\AIVisibilityProject;
use App\Models\AIVisibility\AIQueryResult;
use App\Models\AIVisibility\AIMentionAnalytics;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Service for competitor comparison analysis in AI Visibility Tracking.
 *
 * Provides head-to-head comparisons, competitor rankings, mention details,
 * and trend tracking for brands and their competitors.
 *
 * @see TASK-34-15
 * @see SPEC-034
 */
class CompetitorComparisonService
{
    /**
     * Cache TTL in seconds (1 hour).
     */
    private const CACHE_TTL = 3600;

    /**
     * Trend threshold percentage for detecting up/down trends.
     */
    private const TREND_THRESHOLD = 5.0;

    /**
     * Maximum context snippet length.
     */
    private const MAX_CONTEXT_LENGTH = 200;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Create a new CompetitorComparisonService instance.
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get head-to-head comparison between brand and a specific competitor.
     *
     * @param int $projectId The project ID
     * @param string $competitorName The competitor name to compare against
     * @return array Comparison data with brand and competitor stats
     * @throws ModelNotFoundException If project not found
     * @throws InvalidArgumentException If competitor not in project
     */
    public function getHeadToHead(int $projectId, string $competitorName): array
    {
        $cacheKey = $this->getCacheKey('comparison', $projectId, strtolower($competitorName));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($projectId, $competitorName) {
            $project = $this->getProjectOrFail($projectId);
            $this->validateCompetitor($project, $competitorName);

            $brandName = $project->brand_name;

            // Get all query results for this project
            $queryResults = AIQueryResult::where('project_id', $projectId)
                ->where('success', true)
                ->get();

            $totalQueries = $queryResults->count();

            // Calculate brand stats
            $brandStats = $this->calculateBrandStats($queryResults, $brandName, $totalQueries);

            // Calculate competitor stats
            $competitorStats = $this->calculateBrandStats($queryResults, $competitorName, $totalQueries);

            // Calculate co-mentions
            $coMentions = $this->calculateCoMentions($queryResults, $brandName, $competitorName);

            // Calculate advantages
            [$brandAdvantage, $competitorAdvantage] = $this->calculateAdvantages(
                $queryResults,
                $brandName,
                $competitorName
            );

            return [
                'brand' => $brandStats,
                'competitor' => $competitorStats,
                'co_mentions' => $coMentions,
                'brand_advantage' => $brandAdvantage,
                'competitor_advantage' => $competitorAdvantage,
            ];
        });
    }

    /**
     * Get competitor ranking for a project.
     *
     * @param int $projectId The project ID
     * @return array Rankings with brand position
     * @throws ModelNotFoundException If project not found
     */
    public function getCompetitorRanking(int $projectId): array
    {
        $cacheKey = $this->getCacheKey('ranking', $projectId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($projectId) {
            $project = $this->getProjectOrFail($projectId);

            $brandName = $project->brand_name;
            $competitorNames = $project->getCompetitorNames();

            // Get all query results
            $queryResults = AIQueryResult::where('project_id', $projectId)
                ->where('success', true)
                ->get();

            $totalQueries = $queryResults->count() ?: 1;

            // Calculate rates for all brands
            $allBrands = array_merge([$brandName], $competitorNames);
            $rankings = [];

            foreach ($allBrands as $brand) {
                $stats = $this->calculateBrandStats($queryResults, $brand, $totalQueries);
                $trend = $this->calculateTrend($projectId, $brand);

                $rankings[] = [
                    'name' => $brand,
                    'rate' => $stats['rate'],
                    'trend' => $trend,
                ];
            }

            // Sort by rate descending
            usort($rankings, function ($a, $b) {
                return $b['rate'] <=> $a['rate'];
            });

            // Add positions
            $brandPosition = 1;
            foreach ($rankings as $index => &$ranking) {
                $ranking['position'] = $index + 1;
                if ($ranking['name'] === $brandName) {
                    $brandPosition = $ranking['position'];
                }
            }

            return [
                'rankings' => $rankings,
                'brand_position' => $brandPosition,
            ];
        });
    }

    /**
     * Get detailed mention breakdown for a competitor.
     *
     * @param int $projectId The project ID
     * @param string $competitorName The competitor name
     * @return array Detailed mention data
     * @throws ModelNotFoundException If project not found
     * @throws InvalidArgumentException If competitor not in project
     */
    public function getCompetitorMentionDetails(int $projectId, string $competitorName): array
    {
        $cacheKey = $this->getCacheKey('details', $projectId, strtolower($competitorName));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($projectId, $competitorName) {
            $project = $this->getProjectOrFail($projectId);
            $this->validateCompetitor($project, $competitorName);

            // Get query results with mentions of this competitor
            $queryResults = AIQueryResult::where('project_id', $projectId)
                ->where('success', true)
                ->get();

            $queryCategories = [];
            $contextSnippets = [];
            $sentimentBreakdown = ['positive' => 0, 'neutral' => 0, 'negative' => 0];
            $platforms = [];

            foreach ($queryResults as $result) {
                $mentions = $result->mentions ?? [];

                foreach ($mentions as $mention) {
                    if (!isset($mention['brand'])) {
                        continue;
                    }

                    // Case-insensitive match
                    if (strcasecmp($mention['brand'], $competitorName) !== 0) {
                        continue;
                    }

                    // Collect query category
                    if ($result->query_category && !in_array($result->query_category, $queryCategories)) {
                        $queryCategories[] = $result->query_category;
                    }

                    // Collect context snippet
                    if (isset($mention['context'])) {
                        $snippet = $this->truncateContext($mention['context']);
                        if (!in_array($snippet, $contextSnippets)) {
                            $contextSnippets[] = $snippet;
                        }
                    }

                    // Count sentiment
                    $sentiment = $mention['sentiment'] ?? 'neutral';
                    if (isset($sentimentBreakdown[$sentiment])) {
                        $sentimentBreakdown[$sentiment]++;
                    }

                    // Collect platform
                    if ($result->platform && !in_array($result->platform, $platforms)) {
                        $platforms[] = $result->platform;
                    }
                }
            }

            return [
                'query_categories' => $queryCategories,
                'context_snippets' => $contextSnippets,
                'sentiment_breakdown' => $sentimentBreakdown,
                'platforms' => $platforms,
            ];
        });
    }

    /**
     * Track competitor mention trend over time.
     *
     * @param int $projectId The project ID
     * @param string $competitorName The competitor name
     * @param string $period The period: 'week' or 'month'
     * @return array Array of daily data points
     * @throws ModelNotFoundException If project not found
     * @throws InvalidArgumentException If competitor not in project or invalid period
     */
    public function trackCompetitorTrend(int $projectId, string $competitorName, string $period): array
    {
        $this->validatePeriod($period);

        $cacheKey = $this->getCacheKey('trend', $projectId, strtolower($competitorName), $period);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($projectId, $competitorName, $period) {
            $project = $this->getProjectOrFail($projectId);
            $this->validateCompetitor($project, $competitorName);

            $days = $period === 'week' ? 7 : 30;
            $dataPoints = [];

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');

                // Get queries for this specific day
                $dayQueries = AIQueryResult::where('project_id', $projectId)
                    ->where('success', true)
                    ->whereDate('queried_at', $date)
                    ->get();

                $totalQueries = $dayQueries->count() ?: 1;
                $stats = $this->calculateBrandStats($dayQueries, $competitorName, $totalQueries);

                $dataPoints[] = [
                    'date' => $date,
                    'mentions' => $stats['mentions'],
                    'rate' => $stats['rate'],
                ];
            }

            return $dataPoints;
        });
    }

    /**
     * Get project or fail with exception.
     *
     * @param int $projectId
     * @return AIVisibilityProject
     * @throws ModelNotFoundException
     */
    private function getProjectOrFail(int $projectId): AIVisibilityProject
    {
        $project = AIVisibilityProject::find($projectId);

        if (!$project) {
            throw new ModelNotFoundException("Project with ID {$projectId} not found");
        }

        return $project;
    }

    /**
     * Validate that the competitor exists in the project.
     *
     * @param AIVisibilityProject $project
     * @param string $competitorName
     * @throws InvalidArgumentException
     */
    private function validateCompetitor(AIVisibilityProject $project, string $competitorName): void
    {
        $competitorNames = $project->getCompetitorNames();

        if (empty($competitorNames)) {
            throw new InvalidArgumentException("Project has no competitors defined");
        }

        // Case-insensitive check
        $found = false;
        foreach ($competitorNames as $name) {
            if (strcasecmp($name, $competitorName) === 0) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new InvalidArgumentException("Competitor '{$competitorName}' not found in project");
        }
    }

    /**
     * Validate the period parameter.
     *
     * @param string $period
     * @throws InvalidArgumentException
     */
    private function validatePeriod(string $period): void
    {
        if (!in_array($period, ['week', 'month'])) {
            throw new InvalidArgumentException("Invalid period '{$period}'. Must be 'week' or 'month'");
        }
    }

    /**
     * Calculate stats for a specific brand.
     *
     * @param \Illuminate\Support\Collection $queryResults
     * @param string $brandName
     * @param int $totalQueries
     * @return array
     */
    private function calculateBrandStats($queryResults, string $brandName, int $totalQueries): array
    {
        $mentionCount = 0;
        $sentimentSum = 0.0;
        $sentimentCount = 0;

        foreach ($queryResults as $result) {
            $mentions = $result->mentions ?? [];

            foreach ($mentions as $mention) {
                if (!isset($mention['brand'])) {
                    continue;
                }

                // Case-insensitive match
                if (strcasecmp($mention['brand'], $brandName) === 0) {
                    $mentionCount++;

                    // Sum sentiment if available
                    if (isset($mention['sentiment'])) {
                        $sentimentValue = $this->sentimentToValue($mention['sentiment']);
                        $sentimentSum += $sentimentValue;
                        $sentimentCount++;
                    }
                }
            }
        }

        $rate = $totalQueries > 0 ? round(($mentionCount / $totalQueries) * 100, 1) : 0.0;
        $avgSentiment = $sentimentCount > 0 ? round($sentimentSum / $sentimentCount, 2) : 0.0;

        return [
            'mentions' => $mentionCount,
            'rate' => $rate,
            'sentiment' => $avgSentiment,
        ];
    }

    /**
     * Calculate co-mentions (both brand and competitor in same response).
     *
     * @param \Illuminate\Support\Collection $queryResults
     * @param string $brandName
     * @param string $competitorName
     * @return int
     */
    private function calculateCoMentions($queryResults, string $brandName, string $competitorName): int
    {
        $coMentions = 0;

        foreach ($queryResults as $result) {
            $mentions = $result->mentions ?? [];
            $hasBrand = false;
            $hasCompetitor = false;

            foreach ($mentions as $mention) {
                if (!isset($mention['brand'])) {
                    continue;
                }

                if (strcasecmp($mention['brand'], $brandName) === 0) {
                    $hasBrand = true;
                }
                if (strcasecmp($mention['brand'], $competitorName) === 0) {
                    $hasCompetitor = true;
                }
            }

            if ($hasBrand && $hasCompetitor) {
                $coMentions++;
            }
        }

        return $coMentions;
    }

    /**
     * Calculate advantages based on mention types.
     *
     * @param \Illuminate\Support\Collection $queryResults
     * @param string $brandName
     * @param string $competitorName
     * @return array [brandAdvantages, competitorAdvantages]
     */
    private function calculateAdvantages($queryResults, string $brandName, string $competitorName): array
    {
        $brandTypes = [];
        $competitorTypes = [];

        foreach ($queryResults as $result) {
            $mentions = $result->mentions ?? [];

            foreach ($mentions as $mention) {
                if (!isset($mention['brand']) || !isset($mention['type'])) {
                    continue;
                }

                $type = $mention['type'];

                if (strcasecmp($mention['brand'], $brandName) === 0) {
                    $brandTypes[$type] = ($brandTypes[$type] ?? 0) + 1;
                }
                if (strcasecmp($mention['brand'], $competitorName) === 0) {
                    $competitorTypes[$type] = ($competitorTypes[$type] ?? 0) + 1;
                }
            }
        }

        $brandAdvantage = [];
        $competitorAdvantage = [];

        // Check which types each has more of
        $allTypes = array_unique(array_merge(array_keys($brandTypes), array_keys($competitorTypes)));

        foreach ($allTypes as $type) {
            $brandCount = $brandTypes[$type] ?? 0;
            $competitorCount = $competitorTypes[$type] ?? 0;

            if ($brandCount > $competitorCount) {
                $brandAdvantage[] = $type;
            } elseif ($competitorCount > $brandCount) {
                $competitorAdvantage[] = $type;
            }
        }

        return [$brandAdvantage, $competitorAdvantage];
    }

    /**
     * Calculate trend direction based on historical analytics.
     *
     * @param int $projectId
     * @param string $brandName
     * @return string 'up', 'down', or 'stable'
     */
    private function calculateTrend(int $projectId, string $brandName): string
    {
        // Try to get analytics for trend calculation
        $currentPeriod = AIMentionAnalytics::where('project_id', $projectId)
            ->where('period_end', '>=', now()->subDays(7))
            ->orderBy('period_end', 'desc')
            ->first();

        $previousPeriod = AIMentionAnalytics::where('project_id', $projectId)
            ->where('period_end', '<', now()->subDays(7))
            ->orderBy('period_end', 'desc')
            ->first();

        if (!$currentPeriod || !$previousPeriod) {
            // Calculate from query results if no analytics
            return $this->calculateTrendFromQueries($projectId, $brandName);
        }

        $currentRate = $currentPeriod->mention_rate;
        $previousRate = $previousPeriod->mention_rate;

        if ($previousRate == 0) {
            return 'stable';
        }

        $change = (($currentRate - $previousRate) / $previousRate) * 100;

        if ($change >= self::TREND_THRESHOLD) {
            return 'up';
        } elseif ($change <= -self::TREND_THRESHOLD) {
            return 'down';
        }

        return 'stable';
    }

    /**
     * Calculate trend from query results when analytics not available.
     *
     * @param int $projectId
     * @param string $brandName
     * @return string
     */
    private function calculateTrendFromQueries(int $projectId, string $brandName): string
    {
        // Get current week queries
        $currentQueries = AIQueryResult::where('project_id', $projectId)
            ->where('success', true)
            ->where('queried_at', '>=', now()->subDays(7))
            ->get();

        // Get previous week queries
        $previousQueries = AIQueryResult::where('project_id', $projectId)
            ->where('success', true)
            ->whereBetween('queried_at', [now()->subDays(14), now()->subDays(7)])
            ->get();

        $currentTotal = $currentQueries->count() ?: 1;
        $previousTotal = $previousQueries->count() ?: 1;

        $currentStats = $this->calculateBrandStats($currentQueries, $brandName, $currentTotal);
        $previousStats = $this->calculateBrandStats($previousQueries, $brandName, $previousTotal);

        if ($previousStats['rate'] == 0) {
            return 'stable';
        }

        $change = (($currentStats['rate'] - $previousStats['rate']) / $previousStats['rate']) * 100;

        if ($change >= self::TREND_THRESHOLD) {
            return 'up';
        } elseif ($change <= -self::TREND_THRESHOLD) {
            return 'down';
        }

        return 'stable';
    }

    /**
     * Convert sentiment string to numeric value.
     *
     * @param string $sentiment
     * @return float
     */
    private function sentimentToValue(string $sentiment): float
    {
        return match (strtolower($sentiment)) {
            'positive' => 1.0,
            'neutral' => 0.0,
            'negative' => -1.0,
            default => 0.0,
        };
    }

    /**
     * Truncate context to maximum length.
     *
     * @param string $context
     * @return string
     */
    private function truncateContext(string $context): string
    {
        if (strlen($context) <= self::MAX_CONTEXT_LENGTH) {
            return $context;
        }

        return substr($context, 0, self::MAX_CONTEXT_LENGTH) . '...';
    }

    /**
     * Generate a cache key.
     *
     * @param string $type
     * @param int $projectId
     * @param string ...$identifiers
     * @return string
     */
    private function getCacheKey(string $type, int $projectId, string ...$identifiers): string
    {
        $key = "ai_visibility:{$type}:{$projectId}";

        foreach ($identifiers as $identifier) {
            $key .= ":{$identifier}";
        }

        return $key;
    }
}
