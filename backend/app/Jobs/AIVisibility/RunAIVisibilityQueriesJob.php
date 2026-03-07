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
    protected ?string $frequency;

    /**
     * Create a new job instance.
     *
     * @param string|null $frequency Filter by tracking frequency (daily, weekly, monthly)
     */
    public function __construct(?string $frequency = null)
    {
        $this->frequency = $frequency;
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
        Log::info('RunAIVisibilityQueriesJob starting', [
            'frequency' => $this->frequency ?? 'all',
        ]);

        try {
            // Get active projects for querying
            $projects = $this->getActiveProjectsForQuery($service);

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
                $query = $project['default_query'] ?? $this->generateDefaultQuery($project['brand_name']);

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

            Log::info('RunAIVisibilityQueriesJob completed', [
                'dispatched' => $dispatchedCount,
                'frequency' => $this->frequency,
            ]);
        } catch (\Exception $e) {
            Log::error('RunAIVisibilityQueriesJob failed', [
                'error' => $e->getMessage(),
                'frequency' => $this->frequency,
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
            'frequency' => $this->frequency,
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
            new \Illuminate\Queue\Middleware\WithoutOverlapping('run-ai-visibility-queries', 3600),
        ];
    }

    /**
     * Get active projects that are due for querying.
     *
     * @param AIVisibilityServiceInterface $service
     * @return array
     */
    protected function getActiveProjectsForQuery(AIVisibilityServiceInterface $service): array
    {
        $query = AIVisibilityProject::query()
            ->where('is_active', true);

        // Filter by frequency if specified
        if ($this->frequency) {
            $query->where('tracking_frequency', $this->frequency);
        }

        // Filter by due date based on tracking frequency
        $query->where(function ($q) {
            $now = now();

            // Daily: last_queried_at is null or more than 1 day ago
            $q->orWhere(function ($subQ) use ($now) {
                $subQ->where('tracking_frequency', 'daily')
                    ->where(function ($dateQ) use ($now) {
                        $dateQ->whereNull('last_queried_at')
                            ->orWhere('last_queried_at', '<', $now->subDay());
                    });
            });

            // Weekly: last_queried_at is null or more than 7 days ago
            $q->orWhere(function ($subQ) use ($now) {
                $subQ->where('tracking_frequency', 'weekly')
                    ->where(function ($dateQ) use ($now) {
                        $dateQ->whereNull('last_queried_at')
                            ->orWhere('last_queried_at', '<', $now->subWeek());
                    });
            });

            // Monthly: last_queried_at is null or more than 30 days ago
            $q->orWhere(function ($subQ) use ($now) {
                $subQ->where('tracking_frequency', 'monthly')
                    ->where(function ($dateQ) use ($now) {
                        $dateQ->whereNull('last_queried_at')
                            ->orWhere('last_queried_at', '<', $now->subMonth());
                    });
            });
        });

        $projects = $query->get();

        return $projects->map(function ($project) {
            return [
                'id' => $project->id,
                'brand_name' => $project->brand_name,
                'platforms' => $this->getPlatformsForProject($project),
                'default_query' => $this->getDefaultQuery($project),
                'use_free_model' => true,
            ];
        })->toArray();
    }

    /**
     * Get the platforms configured for a project.
     *
     * @param AIVisibilityProject $project
     * @return array
     */
    protected function getPlatformsForProject(AIVisibilityProject $project): array
    {
        // Get platforms from config
        $defaultPlatforms = config('ai-visibility.platforms', ['chatgpt', 'perplexity']);

        return $defaultPlatforms;
    }

    /**
     * Get the default query for a project.
     *
     * @param AIVisibilityProject $project
     * @return string
     */
    protected function getDefaultQuery(AIVisibilityProject $project): string
    {
        // Use query templates if configured
        $templates = $project->query_templates;

        if (!empty($templates) && is_array($templates)) {
            $template = $templates[0] ?? null;
            if ($template) {
                return $this->interpolateTemplate($template, $project);
            }
        }

        // Fall back to brand name query
        return $this->generateDefaultQuery($project->brand_name);
    }

    /**
     * Generate a default query from brand name.
     *
     * @param string $brandName
     * @return string
     */
    protected function generateDefaultQuery(string $brandName): string
    {
        return "What are the best tools for SEO? Include {$brandName} if relevant.";
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
}
