<?php

namespace App\Services\AIVisibility;

use App\Services\AIVisibility\Contracts\AIVisibilityServiceInterface;
use App\Services\AIVisibility\Exceptions\QueryLimitExceededException;
use App\Models\AIVisibility\AIVisibilityProject;
use App\Models\AIVisibility\AIQueryResult;
use App\Models\AIVisibility\AIMentionAnalytics;
use App\Models\AIVisibility\AIVisibilityAlert;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Psr\Log\LoggerInterface;
use Carbon\Carbon;

/**
 * AI Visibility Service
 * 
 * Orchestrates project management, query execution, and analytics retrieval
 * for AI visibility tracking (Generative Engine Optimization).
 * 
 * @task TASK-34-5
 * @spec SPEC-034
 */
class AIVisibilityService implements AIVisibilityServiceInterface
{
    protected AIQueryEngine $queryEngine;
    protected MentionParser $mentionParser;
    protected LoggerInterface $logger;

    /**
     * Create a new AIVisibilityService instance.
     *
     * @param AIQueryEngine $queryEngine
     * @param MentionParser $mentionParser
     * @param LoggerInterface $logger
     */
    public function __construct(
        AIQueryEngine $queryEngine,
        MentionParser $mentionParser,
        LoggerInterface $logger
    ) {
        $this->queryEngine = $queryEngine;
        $this->mentionParser = $mentionParser;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function createProject(int $userId, array $data): AIVisibilityProject
    {
        // Validate data
        $this->validateProjectData($data);

        return DB::transaction(function () use ($userId, $data) {
            // Create project
            $project = new AIVisibilityProject();
            $project->user_id = $userId;
            $project->brand_name = $data['brand_name'];
            $project->keywords = $data['keywords'] ?? [];
            $project->competitors = $data['competitors'] ?? [];
            $project->query_templates = $data['query_templates'] ?? null;
            $project->tracking_frequency = $data['tracking_frequency'] ?? 'weekly';
            $project->is_active = $data['is_active'] ?? true;
            $project->save();

            $this->logger->info('AI Visibility project created', [
                'project_id' => $project->id,
                'user_id' => $userId,
                'brand_name' => $project->brand_name,
            ]);

            // Dispatch event
            Event::dispatch('ai-visibility.project.created', [$project]);

            return $project;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function updateProject(int $projectId, array $data): AIVisibilityProject
    {
        // Find project
        $project = AIVisibilityProject::findOrFail($projectId);

        // Validate data if competitors provided
        if (isset($data['competitors'])) {
            if (!is_array($data['competitors'])) {
                throw ValidationException::withMessages([
                    'competitors' => 'The competitors must be an array.',
                ]);
            }
            if (count($data['competitors']) > 3) {
                throw ValidationException::withMessages([
                    'competitors' => 'You can add a maximum of 3 competitors.',
                ]);
            }
        }

        return DB::transaction(function () use ($project, $data) {
            // Update allowed fields only
            $allowedFields = ['brand_name', 'keywords', 'competitors', 'query_templates', 'tracking_frequency', 'is_active'];
            
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $project->$field = $data[$field];
                }
            }
            
            $project->save();

            // Clear cached analytics
            $this->clearProjectCache($project->id);

            $this->logger->info('AI Visibility project updated', [
                'project_id' => $project->id,
                'user_id' => $project->user_id,
            ]);

            // Dispatch event
            Event::dispatch('ai-visibility.project.updated', [$project]);

            return $project;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function deleteProject(int $projectId, int $userId): bool
    {
        // Find project
        $project = AIVisibilityProject::findOrFail($projectId);

        // Validate ownership
        if (!$project->belongsToUser($userId)) {
            throw new AuthorizationException('You do not own this project.');
        }

        // Check if already deleted
        if ($project->trashed()) {
            return false;
        }

        return DB::transaction(function () use ($project) {
            $projectId = $project->id;

            // Delete related data
            AIQueryResult::where('project_id', $projectId)->delete();
            AIMentionAnalytics::where('project_id', $projectId)->delete();
            AIVisibilityAlert::where('project_id', $projectId)->delete();

            // Clear cache
            $this->clearProjectCache($projectId);

            // Delete based on strategy
            $strategy = config('ai-visibility.delete_strategy', 'soft');
            
            if ($strategy === 'hard') {
                $project->forceDelete();
            } else {
                $project->delete();
            }

            $this->logger->info('AI Visibility project deleted', [
                'project_id' => $projectId,
                'user_id' => $project->user_id,
            ]);

            // Dispatch event
            Event::dispatch('ai-visibility.project.deleted', [$projectId]);

            return true;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function runQueries(int $projectId): array
    {
        // Find project
        $project = AIVisibilityProject::findOrFail($projectId);

        // Check if project is active
        if (!$project->is_active) {
            throw new \RuntimeException('Project is not active.');
        }

        // Check query limit
        $userId = $project->user_id;
        $platforms = config('ai-visibility.platforms', ['chatgpt', 'perplexity']);
        $queryCount = count($platforms);

        if (!$this->canRunQueries($userId, $queryCount)) {
            $limit = $this->getQueryLimit($userId);
            $used = $this->getQueryUsage($userId);
            $tier = $this->getUserTier($userId);

            throw new QueryLimitExceededException($limit, $used, $tier);
        }

        // Generate queries
        $queries = $this->generateQueries($project);

        $jobIds = [];

        DB::transaction(function () use ($project, $platforms, $queries, &$jobIds, $userId) {
            foreach ($platforms as $platform) {
                $jobId = $this->dispatchQueryJob($project, $platform, $queries);
                $jobIds[] = $jobId;

                // Create tracking record
                $this->createQueryTrackingRecord($project, $platform, $queries);
            }

            // Update last queried timestamp
            $project->last_queried_at = now();
            $project->save();

            // Increment query count
            $this->incrementQueryUsage($userId, count($platforms));

            // Clear analytics cache
            $this->clearProjectCache($project->id);

            $this->logger->info('AI Visibility queries dispatched', [
                'project_id' => $project->id,
                'platforms' => $platforms,
                'job_ids' => $jobIds,
            ]);
        });

        return $jobIds;
    }

    /**
     * {@inheritdoc}
     */
    public function getAnalytics(int $projectId, string $period = 'week'): array
    {
        // Validate period
        if (!in_array($period, ['day', 'week', 'month'])) {
            throw ValidationException::withMessages([
                'period' => 'Invalid period. Must be day, week, or month.',
            ]);
        }

        // Find project
        $project = AIVisibilityProject::findOrFail($projectId);

        // Cache key
        $cacheKey = "ai_visibility_analytics_{$projectId}_{$period}";
        $cacheTtl = $this->getCacheTtl();

        return Cache::remember($cacheKey, $cacheTtl, function () use ($project, $period) {
            $periodStart = $this->getPeriodStart($period);
            $periodEnd = now();

            // Get query results for period
            $results = AIQueryResult::where('project_id', $project->id)
                ->where('success', true)
                ->where('queried_at', '>=', $periodStart)
                ->get();

            // Calculate metrics
            $totalQueries = $results->count();
            $brandMentions = 0;
            $competitorMentions = [];
            $positiveCount = 0;
            $neutralCount = 0;
            $negativeCount = 0;

            // Initialize platform stats
            $platformStats = [];
            foreach (config('ai-visibility.platforms', ['chatgpt', 'perplexity']) as $platform) {
                $platformStats[$platform] = [
                    'queries' => 0,
                    'mentions' => 0,
                ];
            }

            // Process results
            foreach ($results as $result) {
                $platform = $result->platform;
                if (isset($platformStats[$platform])) {
                    $platformStats[$platform]['queries']++;
                }

                // Count brand mentions
                $mentions = $result->mentions ?? [];
                foreach ($mentions as $mention) {
                    if (isset($mention['brand'])) {
                        if (strcasecmp($mention['brand'], $project->brand_name) === 0) {
                            $brandMentions++;
                            if (isset($platformStats[$platform])) {
                                $platformStats[$platform]['mentions']++;
                            }
                        } else {
                            // Competitor mention
                            $compName = $mention['brand'];
                            if (!isset($competitorMentions[$compName])) {
                                $competitorMentions[$compName] = 0;
                            }
                            $competitorMentions[$compName]++;
                        }
                    }
                }

                // Count sentiment
                $sentiment = $result->sentiment ?? 'neutral';
                match ($sentiment) {
                    'positive' => $positiveCount++,
                    'negative' => $negativeCount++,
                    default => $neutralCount++,
                };
            }

            // Calculate mention rate by platform
            $mentionRateByPlatform = [];
            foreach ($platformStats as $platform => $stats) {
                $mentionRateByPlatform[$platform] = $stats['queries'] > 0
                    ? round(($stats['mentions'] / $stats['queries']) * 100, 1)
                    : 0.0;
            }

            // Calculate share of voice
            $totalMentions = $brandMentions + array_sum($competitorMentions);
            $shareOfVoice = $totalMentions > 0
                ? round(($brandMentions / $totalMentions) * 100, 2)
                : 0.0;

            // Determine trend direction
            $trendDirection = $this->calculateTrendDirection($project->id, $period);

            return [
                'mention_rate_by_platform' => $mentionRateByPlatform,
                'share_of_voice' => $shareOfVoice,
                'sentiment_breakdown' => [
                    'positive' => $positiveCount,
                    'neutral' => $neutralCount,
                    'negative' => $negativeCount,
                ],
                'trend_direction' => $trendDirection,
                'total_queries' => $totalQueries,
                'brand_mentions' => $brandMentions,
                'competitor_mentions' => $competitorMentions,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'last_updated' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getCompetitorComparison(int $projectId): array
    {
        // Find project
        $project = AIVisibilityProject::findOrFail($projectId);

        // Cache key
        $cacheKey = "ai_visibility_comparison_{$projectId}";
        $cacheTtl = $this->getCacheTtl();

        return Cache::remember($cacheKey, $cacheTtl, function () use ($project) {
            // Get all results
            $results = AIQueryResult::where('project_id', $project->id)
                ->where('success', true)
                ->get();

            // Count brand mentions
            $brandMentions = 0;
            $brandSentiment = ['positive' => 0, 'neutral' => 0, 'negative' => 0];
            $totalQueries = $results->count();

            foreach ($results as $result) {
                $mentions = $result->mentions ?? [];
                foreach ($mentions as $mention) {
                    if (isset($mention['brand']) && strcasecmp($mention['brand'], $project->brand_name) === 0) {
                        $brandMentions++;
                        $sentiment = $result->sentiment ?? 'neutral';
                        match ($sentiment) {
                            'positive' => $brandSentiment['positive']++,
                            'negative' => $brandSentiment['negative']++,
                            default => $brandSentiment['neutral']++,
                        };
                    }
                }
            }

            // Count competitor mentions
            $competitors = [];
            $competitorNames = $project->getCompetitorNames();

            foreach ($competitorNames as $name) {
                $competitors[$name] = [
                    'name' => $name,
                    'mention_count' => 0,
                    'mention_rate' => 0.0,
                    'sentiment' => ['positive' => 0, 'neutral' => 0, 'negative' => 0],
                ];
            }

            foreach ($results as $result) {
                $mentions = $result->mentions ?? [];
                foreach ($mentions as $mention) {
                    if (isset($mention['brand'])) {
                        $brand = $mention['brand'];
                        foreach ($competitorNames as $compName) {
                            if (strcasecmp($brand, $compName) === 0) {
                                $competitors[$compName]['mention_count']++;
                                $sentiment = $result->sentiment ?? 'neutral';
                                match ($sentiment) {
                                    'positive' => $competitors[$compName]['sentiment']['positive']++,
                                    'negative' => $competitors[$compName]['sentiment']['negative']++,
                                    default => $competitors[$compName]['sentiment']['neutral']++,
                                };
                            }
                        }
                    }
                }
            }

            // Calculate mention rates
            $brandMentionRate = $totalQueries > 0 ? round(($brandMentions / $totalQueries) * 100, 1) : 0.0;

            foreach ($competitors as $name => $data) {
                $competitors[$name]['mention_rate'] = $totalQueries > 0
                    ? round(($data['mention_count'] / $totalQueries) * 100, 1)
                    : 0.0;
            }

            // Determine winner
            $winner = $project->brand_name;
            $highestRate = $brandMentionRate;
            $isTie = false;

            foreach ($competitors as $comp) {
                if ($comp['mention_rate'] > $highestRate) {
                    $highestRate = $comp['mention_rate'];
                    $winner = $comp['name'];
                    $isTie = false;
                } elseif ($comp['mention_rate'] === $highestRate && $comp['mention_rate'] > 0) {
                    $isTie = true;
                }
            }

            // Calculate gap
            $gap = $highestRate - $brandMentionRate;

            return [
                'brand' => [
                    'name' => $project->brand_name,
                    'mention_count' => $brandMentions,
                    'mention_rate' => $brandMentionRate,
                    'sentiment' => $brandSentiment,
                ],
                'competitors' => array_values($competitors),
                'winner' => $isTie ? 'tie' : $winner,
                'gap_percentage' => abs($gap),
            ];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function checkAlerts(int $projectId): array
    {
        // Find project
        $project = AIVisibilityProject::findOrFail($projectId);

        // Get current period analytics
        $currentAnalytics = $this->getAnalytics($projectId, 'week');

        // Get previous period analytics for comparison
        $previousStart = now()->subWeeks(2);
        $previousEnd = now()->subWeek();

        $previousResults = AIQueryResult::where('project_id', $project->id)
            ->where('success', true)
            ->whereBetween('queried_at', [$previousStart, $previousEnd])
            ->get();

        // If no previous data, no alerts
        if ($previousResults->isEmpty()) {
            return [];
        }

        // Count previous mentions
        $previousBrandMentions = 0;
        foreach ($previousResults as $result) {
            $mentions = $result->mentions ?? [];
            foreach ($mentions as $mention) {
                if (isset($mention['brand']) && strcasecmp($mention['brand'], $project->brand_name) === 0) {
                    $previousBrandMentions++;
                }
            }
        }

        $alerts = [];
        $thresholds = config('ai-visibility.alert_thresholds', [
            'mention_spike' => 20,
            'mention_drop' => 20,
            'competitor_gain' => 15,
        ]);

        // Check for mention spike
        $currentMentions = $currentAnalytics['brand_mentions'];
        if ($previousBrandMentions > 0) {
            $changePercent = (($currentMentions - $previousBrandMentions) / $previousBrandMentions) * 100;

            if ($changePercent >= $thresholds['mention_spike']) {
                $alert = $this->createAlert(
                    $project->id,
                    AIVisibilityAlert::TYPE_MENTION_SPIKE,
                    $thresholds['mention_spike'],
                    $previousBrandMentions,
                    $currentMentions,
                    $changePercent
                );
                if ($alert) {
                    $alerts[] = $alert;
                }
            } elseif ($changePercent <= -$thresholds['mention_drop']) {
                $alert = $this->createAlert(
                    $project->id,
                    AIVisibilityAlert::TYPE_MENTION_DROP,
                    $thresholds['mention_drop'],
                    $previousBrandMentions,
                    $currentMentions,
                    abs($changePercent)
                );
                if ($alert) {
                    $alerts[] = $alert;
                }
            }
        }

        // Check for competitor gain
        $comparison = $this->getCompetitorComparison($projectId);
        foreach ($comparison['competitors'] as $competitor) {
            if ($competitor['mention_rate'] > $comparison['brand']['mention_rate']) {
                $gap = $competitor['mention_rate'] - $comparison['brand']['mention_rate'];
                if ($gap >= $thresholds['competitor_gain']) {
                    $alert = $this->createAlert(
                        $project->id,
                        AIVisibilityAlert::TYPE_COMPETITOR_GAIN,
                        $thresholds['competitor_gain'],
                        $comparison['brand']['mention_rate'],
                        $competitor['mention_rate'],
                        $gap,
                        $competitor['name']
                    );
                    if ($alert) {
                        $alerts[] = $alert;
                    }
                }
            }
        }

        return $alerts;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryLimit(int $userId): int
    {
        $tier = $this->getUserTier($userId);
        $limits = config('ai-visibility.query_limits', [
            'free' => 5,
            'pro' => 50,
            'business' => 200,
        ]);

        $limit = $limits[$tier] ?? $limits['free'];

        // null means unlimited
        if ($limit === null) {
            return PHP_INT_MAX;
        }

        return $limit;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryUsage(int $userId): int
    {
        // Count queries from this month only
        $monthStart = now()->startOfMonth();

        // Get all projects for user
        $projectIds = AIVisibilityProject::where('user_id', $userId)->pluck('id');

        return AIQueryResult::whereIn('project_id', $projectIds)
            ->where('queried_at', '>=', $monthStart)
            ->count();
    }

    /**
     * {@inheritdoc}
     */
    public function getRemainingQueries(int $userId): int
    {
        return max(0, $this->getQueryLimit($userId) - $this->getQueryUsage($userId));
    }

    /**
     * {@inheritdoc}
     */
    public function canRunQueries(int $userId, int $count = 1): bool
    {
        if ($count < 0) {
            throw ValidationException::withMessages([
                'count' => 'Count must be non-negative.',
            ]);
        }

        if ($count === 0) {
            return true;
        }

        return $this->getRemainingQueries($userId) >= $count;
    }

    /**
     * Validate project data.
     *
     * @param array $data
     * @throws ValidationException
     */
    protected function validateProjectData(array $data): void
    {
        $errors = [];

        // Brand name is required
        if (empty($data['brand_name'])) {
            $errors['brand_name'] = 'The brand name is required.';
        }

        // Keywords must be an array
        if (isset($data['keywords']) && !is_array($data['keywords'])) {
            $errors['keywords'] = 'The keywords must be an array.';
        }

        // Competitors must be an array with max 3 items
        if (isset($data['competitors'])) {
            if (!is_array($data['competitors'])) {
                $errors['competitors'] = 'The competitors must be an array.';
            } elseif (count($data['competitors']) > 3) {
                $errors['competitors'] = 'You can add a maximum of 3 competitors.';
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Get query limits configuration.
     *
     * @return array
     */
    protected function getQueryLimitConfig(): array
    {
        return config('ai-visibility.query_limits', [
            'free' => 5,
            'pro' => 50,
            'business' => 200,
        ]);
    }

    /**
     * Get cache TTL.
     *
     * @return int
     */
    protected function getCacheTtl(): int
    {
        return config('ai-visibility.cache_ttl', 3600);
    }

    /**
     * Get user's subscription tier.
     *
     * @param int $userId
     * @return string
     */
    protected function getUserTier(int $userId): string
    {
        // Check if user has a plan column
        $user = User::find($userId);
        
        if ($user && isset($user->plan)) {
            return strtolower($user->plan);
        }

        // Default to free tier
        return 'free';
    }

    /**
     * Generate queries from templates.
     *
     * @param AIVisibilityProject $project
     * @return array
     */
    protected function generateQueries(AIVisibilityProject $project): array
    {
        $templates = $project->query_templates ?? config('ai-visibility.default_query_templates', [
            'What are the best tools for {category}?',
            'Can you recommend a good {category} tool?',
            'How does {brand} compare to competitors?',
        ]);

        $queries = [];
        $brand = $project->brand_name;
        $competitors = $project->getCompetitorNames();

        foreach ($templates as $template) {
            $query = $template;
            $query = str_replace('{brand}', $brand, $query);
            $query = str_replace('{competitors}', implode(', ', $competitors), $query);
            $queries[] = $query;
        }

        return $queries;
    }

    /**
     * Dispatch a query job.
     *
     * @param AIVisibilityProject $project
     * @param string $platform
     * @param array $queries
     * @return string Job ID
     */
    protected function dispatchQueryJob(AIVisibilityProject $project, string $platform, array $queries): string
    {
        // Generate a unique job ID
        $jobId = uniqid('ai_query_', true);

        // Dispatch job via queue
        // Note: The actual job class will be created in TASK-34-7
        // For now, we dispatch a closure that will be handled later
        Queue::push(function () use ($project, $platform, $queries, $jobId) {
            // This will be replaced with the actual job in TASK-34-7
            Log::info('AI Query job dispatched', [
                'job_id' => $jobId,
                'project_id' => $project->id,
                'platform' => $platform,
                'queries' => $queries,
            ]);
        });

        return $jobId;
    }

    /**
     * Create a query tracking record.
     *
     * @param AIVisibilityProject $project
     * @param string $platform
     * @param array $queries
     */
    protected function createQueryTrackingRecord(AIVisibilityProject $project, string $platform, array $queries): void
    {
        // Create a placeholder result record
        AIQueryResult::create([
            'project_id' => $project->id,
            'platform' => $platform,
            'query_text' => implode('; ', $queries),
            'success' => false,
            'queried_at' => now(),
        ]);
    }

    /**
     * Increment query usage for a user.
     *
     * @param int $userId
     * @param int $count
     */
    protected function incrementQueryUsage(int $userId, int $count): void
    {
        // Query usage is tracked via AIQueryResult records
        // This method exists for future use if we need separate tracking
    }

    /**
     * Clear project cache.
     *
     * @param int $projectId
     */
    protected function clearProjectCache(int $projectId): void
    {
        Cache::forget("ai_visibility_analytics_{$projectId}_day");
        Cache::forget("ai_visibility_analytics_{$projectId}_week");
        Cache::forget("ai_visibility_analytics_{$projectId}_month");
        Cache::forget("ai_visibility_comparison_{$projectId}");
    }

    /**
     * Get period start date.
     *
     * @param string $period
     * @return Carbon
     */
    protected function getPeriodStart(string $period): Carbon
    {
        return match ($period) {
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            default => now()->subWeek(),
        };
    }

    /**
     * Calculate trend direction.
     *
     * @param int $projectId
     * @param string $period
     * @return string
     */
    protected function calculateTrendDirection(int $projectId, string $period): string
    {
        // Get current period mentions
        $currentStart = $this->getPeriodStart($period);
        $currentMentions = AIQueryResult::where('project_id', $projectId)
            ->where('success', true)
            ->where('queried_at', '>=', $currentStart)
            ->count();

        // Get previous period mentions
        $previousStart = match ($period) {
            'day' => now()->subDays(2),
            'week' => now()->subWeeks(2),
            'month' => now()->subMonths(2),
            default => now()->subWeeks(2),
        };

        $previousMentions = AIQueryResult::where('project_id', $projectId)
            ->where('success', true)
            ->whereBetween('queried_at', [$previousStart, $currentStart])
            ->count();

        if ($previousMentions === 0) {
            return 'stable';
        }

        $change = (($currentMentions - $previousMentions) / $previousMentions) * 100;

        if ($change >= 10) {
            return 'up';
        } elseif ($change <= -10) {
            return 'down';
        }

        return 'stable';
    }

    /**
     * Create an alert if not duplicate.
     *
     * @param int $projectId
     * @param string $type
     * @param float $threshold
     * @param float $previousValue
     * @param float $currentValue
     * @param float $changePercent
     * @param string|null $competitorName
     * @return AIVisibilityAlert|null
     */
    protected function createAlert(
        int $projectId,
        string $type,
        float $threshold,
        float $previousValue,
        float $currentValue,
        float $changePercent,
        ?string $competitorName = null
    ): ?AIVisibilityAlert {
        // Check for duplicate alert in last 24 hours
        $existingAlert = AIVisibilityAlert::where('project_id', $projectId)
            ->where('alert_type', $type)
            ->where('triggered_at', '>=', now()->subDay())
            ->first();

        if ($existingAlert) {
            return null;
        }

        return DB::transaction(function () use (
            $projectId,
            $type,
            $threshold,
            $previousValue,
            $currentValue,
            $changePercent,
            $competitorName
        ) {
            $alert = AIVisibilityAlert::create([
                'project_id' => $projectId,
                'alert_type' => $type,
                'threshold' => $threshold,
                'previous_value' => $previousValue,
                'current_value' => $currentValue,
                'change_percent' => $changePercent,
                'triggered_at' => now(),
                'is_read' => false,
                'is_resolved' => false,
            ]);

            $this->logger->info('AI Visibility alert created', [
                'alert_id' => $alert->id,
                'project_id' => $projectId,
                'type' => $type,
                'change_percent' => $changePercent,
                'competitor' => $competitorName,
            ]);

            return $alert;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveProjectsForQuery(?string $frequency = null): array
    {
        $query = AIVisibilityProject::query()
            ->where('is_active', true);

        // Filter by frequency if specified
        if ($frequency) {
            $query->where('tracking_frequency', $frequency);
        }

        // Filter by due date based on tracking frequency
        $query->where(function ($q) {
            $now = now();

            // Daily: last_queried_at is null or more than 1 day ago
            $q->orWhere(function ($subQ) use ($now) {
                $subQ->where('tracking_frequency', 'daily')
                    ->where(function ($dateQ) use ($now) {
                        $dateQ->whereNull('last_queried_at')
                            ->orWhere('last_queried_at', '<', $now->copy()->subDay());
                    });
            });

            // Weekly: last_queried_at is null or more than 7 days ago
            $q->orWhere(function ($subQ) use ($now) {
                $subQ->where('tracking_frequency', 'weekly')
                    ->where(function ($dateQ) use ($now) {
                        $dateQ->whereNull('last_queried_at')
                            ->orWhere('last_queried_at', '<', $now->copy()->subWeek());
                    });
            });

            // Monthly: last_queried_at is null or more than 30 days ago
            $q->orWhere(function ($subQ) use ($now) {
                $subQ->where('tracking_frequency', 'monthly')
                    ->where(function ($dateQ) use ($now) {
                        $dateQ->whereNull('last_queried_at')
                            ->orWhere('last_queried_at', '<', $now->copy()->subMonth());
                    });
            });
        });

        $projects = $query->get();

        return $projects->map(function ($project) {
            return [
                'id' => $project->id,
                'brand_name' => $project->brand_name,
                'platforms' => config('ai-visibility.platforms', ['chatgpt', 'perplexity']),
                'default_query' => $this->getDefaultQueryForProject($project),
                'use_free_model' => true,
            ];
        })->toArray();
    }

    /**
     * Get the default query for a project.
     *
     * @param AIVisibilityProject $project
     * @return string
     */
    protected function getDefaultQueryForProject(AIVisibilityProject $project): string
    {
        // Use query templates if configured
        $templates = $project->query_templates;

        if (!empty($templates) && is_array($templates) && isset($templates[0])) {
            return $this->interpolateTemplate($templates[0], $project);
        }

        // Fall back to brand name query
        return "What are the best tools for SEO? Include {$project->brand_name} if relevant.";
    }

    /**
     * Interpolate a query template with project data.
     *
     * @param string $template
     * @param AIVisibilityProject $project
     * @return string
     */
    protected function interpolateTemplate(string $template, AIVisibilityProject $project): string
    {
        $replacements = [
            '{brand}' => $project->brand_name,
            '{category}' => 'SEO',
            '{competitors}' => implode(', ', $project->getCompetitorNames()),
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getUnprocessedQueryResults(): \Illuminate\Support\Collection
    {
        return AIQueryResult::query()
            ->where('success', true)
            ->whereNotNull('response_text')
            ->where('queried_at', '>=', now()->startOfDay())
            ->orderBy('queried_at')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function aggregateResults(int $projectId, string $platform, \Illuminate\Support\Collection $results): array
    {
        $project = AIVisibilityProject::find($projectId);
        $brandName = $project?->brand_name ?? '';

        $totalQueries = $results->count();
        $brandMentions = 0;
        $competitorMentions = [];
        $positiveMentions = 0;
        $neutralMentions = 0;
        $negativeMentions = 0;

        foreach ($results as $result) {
            $mentions = $result->mentions ?? [];

            foreach ($mentions as $mention) {
                $mentionBrand = $mention['brand'] ?? null;

                if ($mentionBrand) {
                    if (strcasecmp($mentionBrand, $brandName) === 0) {
                        $brandMentions++;
                    } else {
                        if (!isset($competitorMentions[$mentionBrand])) {
                            $competitorMentions[$mentionBrand] = 0;
                        }
                        $competitorMentions[$mentionBrand]++;
                    }
                }
            }

            // Count sentiment from result
            $sentiment = $result->sentiment ?? 'neutral';
            match ($sentiment) {
                'positive' => $positiveMentions++,
                'negative' => $negativeMentions++,
                default => $neutralMentions++,
            };
        }

        // Calculate metrics
        $mentionRate = $totalQueries > 0 ? round(($brandMentions / $totalQueries) * 100, 2) : 0;
        $totalMentions = $brandMentions + array_sum($competitorMentions);
        $shareOfVoice = $totalMentions > 0 ? round(($brandMentions / $totalMentions) * 100, 2) : 0;
        $totalSentiment = $positiveMentions + $neutralMentions + $negativeMentions;
        $sentimentScore = $totalSentiment > 0
            ? round(($positiveMentions - $negativeMentions) / $totalSentiment, 2)
            : 0;

        // Store or update analytics
        $analytics = AIMentionAnalytics::updateOrCreate(
            [
                'project_id' => $projectId,
                'platform' => $platform,
                'period_start' => now()->startOfDay(),
                'period_end' => now()->endOfDay(),
            ],
            [
                'total_queries' => $totalQueries,
                'brand_mentions' => $brandMentions,
                'mention_rate' => $mentionRate,
                'positive_mentions' => $positiveMentions,
                'neutral_mentions' => $neutralMentions,
                'negative_mentions' => $negativeMentions,
                'competitor_stats' => $competitorMentions,
            ]
        );

        return [
            'project_id' => $projectId,
            'platform' => $platform,
            'total_queries' => $totalQueries,
            'brand_mentions' => $brandMentions,
            'mention_rate' => $mentionRate,
            'share_of_voice' => $shareOfVoice,
            'sentiment_score' => $sentimentScore,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function markResultsProcessed(array $ids): void
    {
        // For now, we don't have a processed flag on results
        // The aggregation is based on the queried_at date
    }

    /**
     * {@inheritdoc}
     */
    public function getLatestAnalytics(): \Illuminate\Support\Collection
    {
        return AIMentionAnalytics::query()
            ->where('period_start', '>=', now()->subDay()->startOfDay())
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getAlertThresholds(): array
    {
        return [
            'mention_rate_drop' => config('ai-visibility.alert_thresholds.mention_drop', 0.1),
            'mention_rate_critical' => config('ai-visibility.alert_thresholds.mention_critical', 0.5),
            'share_of_voice_drop' => config('ai-visibility.alert_thresholds.share_of_voice_drop', 0.1),
            'sentiment_change' => config('ai-visibility.alert_thresholds.sentiment_change', 0.3),
        ];
    }
}
