<?php

namespace App\Jobs\AIVisibility;

use App\Models\AIVisibility\AIQueryResult;
use App\Models\AIVisibility\AIMentionAnalytics;
use App\Models\AIVisibility\AIVisibilityProject;
use App\Services\AIVisibility\Contracts\AIVisibilityServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        try {
            // Get unprocessed query results
            $results = $service->getUnprocessedQueryResults();

            // Handle both Collection and array returns from mocks
            if (is_array($results)) {
                $results = collect($results);
            }

            if ($results->isEmpty()) {
                Log::info('No unprocessed query results to aggregate');
                return;
            }

            $processedIds = [];

            foreach ($results as $result) {
                $resultId = $result['id'] ?? $result->id ?? null;
                $projectId = $result['project_id'] ?? $result->project_id ?? null;
                $platform = $result['platform'] ?? $result->platform ?? null;

                if (!$projectId || !$platform) {
                    continue;
                }

                try {
                    // Convert single result to collection for service
                    $resultCollection = collect([$result]);
                    $analytics = $service->aggregateResults($projectId, $platform, $resultCollection);

                    // Store analytics in database
                    if (!empty($analytics)) {
                        $this->storeAnalytics($analytics);
                    }

                    if ($resultId) {
                        $processedIds[] = $resultId;
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to aggregate results for project', [
                        'project_id' => $projectId,
                        'platform' => $platform,
                        'error' => $e->getMessage(),
                    ]);
                    // Continue processing other results
                }
            }

            // Mark results as processed
            if (!empty($processedIds)) {
                $service->markResultsProcessed($processedIds);
            }
        } catch (\Exception $e) {
            Log::error('AggregateAIVisibilityAnalyticsJob failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Store analytics in the database.
     *
     * @param array $analytics
     * @return void
     */
    protected function storeAnalytics(array $analytics): void
    {
        $date = $analytics['date'] ?? now()->toDateString();

        // Use updateOrCreate to handle both insert and update
        DB::table('ai_mention_analytics')->updateOrInsert(
            [
                'project_id' => $analytics['project_id'],
                'platform' => $analytics['platform'],
                'date' => $date,
            ],
            [
                'total_queries' => $analytics['total_queries'] ?? 1,
                'total_mentions' => $analytics['total_mentions'] ?? 0,
                'mention_rate' => $analytics['mention_rate'] ?? 0,
                'share_of_voice' => $analytics['share_of_voice'] ?? 0,
                'sentiment_score' => $analytics['sentiment_score'] ?? 0,
                'updated_at' => now(),
                'created_at' => $analytics['created_at'] ?? now(),
            ]
        );
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
}
