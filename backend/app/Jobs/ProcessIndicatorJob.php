<?php

namespace App\Jobs;

use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;
use App\Models\Site;
use App\Services\Analysis\Indicators\HeadingsProcessor;
use App\Services\Analysis\Indicators\ImagesProcessor;
use App\Services\Analysis\Indicators\LinksProcessor;
use App\Services\Analysis\Indicators\MetaTagsProcessor;
use App\Services\ProgressService;
use App\Services\URLValidationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Process a single indicator for an analysis job.
 *
 * Handles indicator processing with timeout, memory limits, and error recovery.
 */
class ProcessIndicatorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Job ID for the analysis job.
     */
    public int $jobId;

    /**
     * Indicator slug to process.
     */
    public string $indicatorSlug;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * Maximum number of seconds the job can run.
     */
    public int $timeout = 30;

    /**
     * Maximum memory in MB.
     */
    public int $memory = 256;

    /**
     * Number of seconds to wait before retrying.
     */
    public array $backoff = [10, 30];

    /**
     * Create a new job instance.
     */
    public function __construct(int $jobId, string $indicatorSlug)
    {
        $this->jobId = $jobId;
        $this->indicatorSlug = $indicatorSlug;
        $this->onQueue('analysis');
    }

    /**
     * Execute the job.
     */
    public function handle(
        ProgressService $progressService,
        URLValidationService $urlValidator
    ): void {
        // Check if job is cancelled
        if ($progressService->isJobCancelled($this->jobId)) {
            Log::info('Skipping indicator: job cancelled', [
                'job_id' => $this->jobId,
                'indicator_slug' => $this->indicatorSlug,
            ]);
            return;
        }

        // Mark indicator as running
        $progressService->markRunning($this->jobId, $this->indicatorSlug);

        // Get job and site details
        $job = AnalysisJob::find($this->jobId);
        if (!$job) {
            throw new RuntimeException("Analysis job {$this->jobId} not found");
        }

        $site = Site::find($job->site_id);
        if (!$site) {
            throw new RuntimeException("Site {$job->site_id} not found");
        }

        // Get the processor for this indicator
        $processor = $this->getProcessor($this->indicatorSlug, $urlValidator);

        if (!$processor) {
            $progressService->markFailed(
                $this->jobId,
                $this->indicatorSlug,
                "Unknown indicator: {$this->indicatorSlug}"
            );
            return;
        }

        try {
            // Process the indicator
            $result = $processor->process($site->url);

            if ($result->error) {
                $progressService->markFailed(
                    $this->jobId,
                    $this->indicatorSlug,
                    $result->error
                );
            } else {
                $progressService->markComplete(
                    $this->jobId,
                    $this->indicatorSlug,
                    $result
                );
            }
        } catch (RuntimeException $e) {
            Log::error('Indicator processing failed', [
                'job_id' => $this->jobId,
                'indicator_slug' => $this->indicatorSlug,
                'error' => $e->getMessage(),
            ]);

            $progressService->markFailed(
                $this->jobId,
                $this->indicatorSlug,
                $e->getMessage()
            );

            // Don't throw - we want to continue processing other indicators
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('ProcessIndicatorJob failed', [
            'job_id' => $this->jobId,
            'indicator_slug' => $this->indicatorSlug,
            'error' => $exception?->getMessage(),
        ]);

        // Mark indicator as failed
        try {
            $progressService = app(ProgressService::class);
            $progressService->markFailed(
                $this->jobId,
                $this->indicatorSlug,
                $exception?->getMessage() ?? 'Unknown error'
            );
        } catch (\Throwable $e) {
            Log::error('Failed to mark indicator as failed', [
                'job_id' => $this->jobId,
                'indicator_slug' => $this->indicatorSlug,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the processor instance for an indicator.
     */
    protected function getProcessor(string $slug, URLValidationService $urlValidator)
    {
        return match ($slug) {
            'meta_tags' => new MetaTagsProcessor($urlValidator),
            'headings' => new HeadingsProcessor($urlValidator),
            'images' => new ImagesProcessor($urlValidator),
            'links' => new LinksProcessor($urlValidator),
            default => null,
        };
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'analysis-job:' . $this->jobId,
            'indicator:' . $this->indicatorSlug,
        ];
    }
}
