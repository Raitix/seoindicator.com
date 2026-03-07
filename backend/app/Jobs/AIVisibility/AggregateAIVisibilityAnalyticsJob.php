<?php

namespace App\Jobs\AIVisibility;

use App\Models\AIVisibility\AIQueryResult;
use App\Models\AIVisibility\AIMentionAnalytics;
use App\Models\AIVisibility\AIVisibilityProject;
use App\Services\AIVisibility\Contracts\AIVisibilityServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job to aggregate AI visibility query results into analytics.
 *
 * Calculates mention rates, share of voice, and sentiment scores.
 *
 * @see TASK-34-7
 * @see SPEC-034
 */
class AggregateAIVisibilityAnalyticsJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('ai-visibility');
    }

    /**
     * Execute the job.
     *
     * @param AIVisibilityServiceInterface $service
     * @return void
     */
    public function handle(AIVisibilityServiceInterface $service): void
    {
        Log::info('AggregateAIVisibilityAnalyticsJob starting');

        try {
            // Get unprocessed query results
            $results = $this->getUnprocessedQueryResults();

            if ($results->isEmpty()) {
                Log::info('No unprocessed query results to aggregate');
                return;
            }

            $processedIds = [];

            // Group by project and platform
            $groupedResults = $results->groupBy(function ($result) {
                return $result->project_id . '_' . $result->platform;
            });

            foreach ($groupedResults as $key => $group) {
                $first = $group->first();
                $projectId = $first->project_id;
                $platform = $first->platform;

                try {
                    $this->aggregateResults($projectId, $platform, $group);
                    $processedIds = array_merge($processedIds, $group->pluck('id')->toArray());
                } catch (\Exception $e) {
                    Log::error('Failed to aggregate results for project', [
                        'project_id' => $projectId,
                        'platform' => $platform,
                        'error' => $e->getMessage(),
                    ]);
                    // Continue processing other groups
                }
            }

            // Mark results as processed
            $this->markResultsProcessed($processedIds);

            Log::info('AggregateAIVisibilityAnalyticsJob completed', [
                'processed_count' => count($processedIds),
            ]);
        } catch (\Exception $e) {
            Log::error('AggregateAIVisibilityAnalyticsJob failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('AggregateAIVisibilityAnalyticsJob failed permanently', [
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [
            new \Illuminate\Queue\Middleware\WithoutOverlapping('aggregate-ai-visibility-analytics', 3600),
        ];
    }

    /**
     * Get unprocessed query results from today.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getUnprocessedQueryResults(): \Illuminate\Support\Collection
    {
        return AIQueryResult::query()
            ->where('success', true)
            ->whereNotNull('response_text')
            ->where('queried_at', '>=', now()->startOfDay())
            ->orderBy('queried_at')
            ->get();
    }

    /**
     * Aggregate results for a project and platform.
     *
     * @param int $projectId
     * @param string $platform
     * @param \Illuminate\Support\Collection $results
     * @return array
     */
    protected function aggregateResults(int $projectId, string $platform, \Illuminate\Support\Collection $results): array
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
     * Mark results as processed.
     *
     * @param array $ids
     * @return void
     */
    protected function markResultsProcessed(array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        // For now, we don't have a processed flag on results
        // The aggregation is based on the queried_at date
    }
}
