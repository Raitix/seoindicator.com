<?php

namespace App\Jobs\AIVisibility;

use App\Models\AIVisibility\AIVisibilityProject;
use App\Services\AIVisibility\Contracts\AIVisibilityServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to dispatch AI visibility query jobs for all active projects.
 *
 * Runs on a schedule to query all projects based on their tracking frequency.
 *
 * @see TASK-34-7
 * @see SPEC-034
 */
class RunAIVisibilityQueriesJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 1;

    /**
     * The tracking frequency to filter projects.
     *
     * @var string|null
     */
    public ?string $frequency;

    /**
     * Create a new job instance.
     *
     * @param string|null $frequency Filter by tracking frequency (daily, weekly, monthly)
     */
    public function __construct(?string $frequency = null)
    {
        $this->frequency = $frequency;
        $this->onQueue('ai-visibility');
        $this->middleware = [
            new \Illuminate\Queue\Middleware\WithoutOverlapping('run-ai-visibility-queries', 3600),
        ];
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
            // Get active projects for querying via the service
            $projects = $service->getActiveProjectsForQuery($this->frequency);

            if (empty($projects)) {
                Log::info('No projects due for AI visibility queries');
                return;
            }

            $dispatchedCount = 0;

            foreach ($projects as $project) {
                $platforms = $project['platforms'] ?? [];

                if (empty($platforms)) {
                    continue;
                }

                // Get the query to use
                $query = $project['default_query'] ?? $this->generateDefaultQuery($project['brand_name'] ?? '');

                // Get useFreeModel setting
                $useFreeModel = $project['use_free_model'] ?? true;

                foreach ($platforms as $platform) {
                    AIVisibilityQueryJob::dispatch(
                        $project['id'],
                        $platform,
                        $query,
                        $useFreeModel
                    )->onQueue('ai-visibility');

                    $dispatchedCount++;
                }
            }

            Log::info('Dispatched AI visibility query jobs', [
                'count' => $dispatchedCount,
            ]);
        } catch (\Exception $e) {
            Log::error('RunAIVisibilityQueriesJob failed', [
                'error' => $e->getMessage(),
                'frequency' => $this->frequency ?? 'all',
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
        Log::error('RunAIVisibilityQueriesJob failed permanently', [
            'error' => $exception->getMessage(),
            'frequency' => $this->frequency ?? 'all',
        ]);
    }

    /**
     * Generate a default query from brand name.
     *
     * @param string $brandName
     * @return string
     */
    protected function generateDefaultQuery(string $brandName): string
    {
        if (empty($brandName)) {
            return 'What are the best SEO tools?';
        }
        return "What are the best tools for SEO? Include {$brandName} if relevant.";
    }
}
