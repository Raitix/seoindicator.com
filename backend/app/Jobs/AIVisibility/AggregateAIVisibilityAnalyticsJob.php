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
            $results = $service->getUnprocessedQueryResults();

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
                    $service->aggregateResults($projectId, $platform, $group);
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
            $service->markResultsProcessed($processedIds);

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
}
