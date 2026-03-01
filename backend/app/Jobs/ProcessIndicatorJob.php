<?php

namespace App\Jobs;

use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;
use App\Models\Site;
use App\Services\Analysis\ErrorHandlingService;
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
 * 
 * @see TASK-19-7
 * @see TASK-19-13 Error handling and recovery
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
        URLValidationService $urlValidator,
        ErrorHandlingService $errorHandler
    ): void {
        $startTime = microtime(true);

        // Check if job is cancelled
        if ($progressService->isJobCancelled($this->jobId)) {
            Log::info('Skipping indicator: job cancelled', [
                'job_id' => $this->jobId,
                'indicator_slug' => $this->indicatorSlug,
            ]);
            return;
        }

        // Check memory before processing
        if ($errorHandler->isMemoryApproachingLimit(85)) {
            $this->handleMemoryExhaustion($progressService, $errorHandler);
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
            $errorHandler->handleIndicatorFailure(
                $this->jobId,
                $this->indicatorSlug,
                new RuntimeException("Site not found"),
                $progressService,
                ['url' => null, 'site_id' => $job->site_id]
            );
            return;
        }

        // Get the processor for this indicator
        $processor = $this->getProcessor($this->indicatorSlug, $urlValidator);

        if (!$processor) {
            $errorHandler->handleIndicatorFailure(
                $this->jobId,
                $this->indicatorSlug,
                new RuntimeException("Unknown indicator: {$this->indicatorSlug}"),
                $progressService,
                ['url' => $site->url]
            );
            return;
        }

        try {
            // Process the indicator
            $result = $processor->process($site->url);

            if ($result->error) {
                // Use error handler for proper categorization and logging
                $errorHandler->handleIndicatorFailure(
                    $this->jobId,
                    $this->indicatorSlug,
                    new RuntimeException($result->error),
                    $progressService,
                    ['url' => $site->url, 'processing_time_ms' => $result->processingTimeMs]
                );
            } else {
                $progressService->markComplete(
                    $this->jobId,
                    $this->indicatorSlug,
                    $result
                );

                Log::info('Indicator completed successfully', [
                    'job_id' => $this->jobId,
                    'indicator_slug' => $this->indicatorSlug,
                    'score' => $result->score,
                    'percentage' => $result->percentage,
                    'processing_time_ms' => $result->processingTimeMs,
                ]);
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // Handle HTTP errors (including 429 rate limiting)
            $this->handleRequestException($e, $progressService, $errorHandler, $site->url);
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            // Handle connection errors (timeout, network issues)
            $this->handleConnectionException($e, $progressService, $errorHandler, $site->url);
        } catch (RuntimeException $e) {
            // Handle general runtime errors
            $errorHandler->handleIndicatorFailure(
                $this->jobId,
                $this->indicatorSlug,
                $e,
                $progressService,
                ['url' => $site->url, 'processing_time_ms' => $this->calculateProcessingTime($startTime)]
            );

            // Determine if we should retry based on error category
            $category = $errorHandler->categorizeError($e);
            if ($errorHandler->shouldRetry($category) && $this->attempts() < $this->tries) {
                $backoff = $errorHandler->getBackoffSeconds($category, $this->attempts());
                Log::info('Scheduling retry for indicator', [
                    'job_id' => $this->jobId,
                    'indicator_slug' => $this->indicatorSlug,
                    'attempt' => $this->attempts(),
                    'backoff_seconds' => $backoff,
                ]);
                // Job will be retried automatically by Laravel
                return;
            }

            // Don't throw - we want to continue processing other indicators
        } catch (\Throwable $e) {
            // Handle unexpected errors
            $errorHandler->handleIndicatorFailure(
                $this->jobId,
                $this->indicatorSlug,
                $e,
                $progressService,
                ['url' => $site->url, 'processing_time_ms' => $this->calculateProcessingTime($startTime)]
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
            $errorHandler = app(ErrorHandlingService::class);

            $errorHandler->handleIndicatorFailure(
                $this->jobId,
                $this->indicatorSlug,
                $exception ?? new RuntimeException('Unknown error'),
                $progressService
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
     * Handle Guzzle RequestException (HTTP errors).
     */
    private function handleRequestException(
        \GuzzleHttp\Exception\RequestException $e,
        ProgressService $progressService,
        ErrorHandlingService $errorHandler,
        string $url
    ): void {
        $response = $e->getResponse();
        $statusCode = $response ? $response->getStatusCode() : 0;

        // Check for rate limiting (429)
        if ($statusCode === 429) {
            $retryAfter = $response->getHeaderLine('Retry-After');
            $backoff = $retryAfter ? (int) $retryAfter : ErrorHandlingService::RATE_LIMIT_BACKOFF_SECONDS;

            Log::warning('Rate limit detected, applying backoff', [
                'job_id' => $this->jobId,
                'indicator_slug' => $this->indicatorSlug,
                'url' => $url,
                'retry_after' => $retryAfter,
                'backoff_seconds' => $backoff,
            ]);

            // If this is the first attempt, we can retry with backoff
            if ($this->attempts() < $this->tries) {
                $this->release($backoff);
                return;
            }
        }

        // Handle as a general error
        $errorHandler->handleIndicatorFailure(
            $this->jobId,
            $this->indicatorSlug,
            $e,
            $progressService,
            ['url' => $url, 'http_status' => $statusCode]
        );
    }

    /**
     * Handle Guzzle ConnectException (network/timeout errors).
     */
    private function handleConnectionException(
        \GuzzleHttp\Exception\ConnectException $e,
        ProgressService $progressService,
        ErrorHandlingService $errorHandler,
        string $url
    ): void {
        $category = $errorHandler->categorizeError($e);

        Log::warning('Connection error during indicator processing', [
            'job_id' => $this->jobId,
            'indicator_slug' => $this->indicatorSlug,
            'url' => $url,
            'category' => $category,
            'error' => $e->getMessage(),
        ]);

        // If this is a timeout and we can retry, do so
        if ($category === ErrorHandlingService::CATEGORY_TIMEOUT && $this->attempts() < $this->tries) {
            $backoff = $errorHandler->getBackoffSeconds($category, $this->attempts());
            $this->release($backoff);
            return;
        }

        // Handle as a general error
        $errorHandler->handleIndicatorFailure(
            $this->jobId,
            $this->indicatorSlug,
            $e,
            $progressService,
            ['url' => $url, 'category' => $category]
        );
    }

    /**
     * Handle memory exhaustion.
     */
    private function handleMemoryExhaustion(
        ProgressService $progressService,
        ErrorHandlingService $errorHandler
    ): void {
        $memoryUsage = $errorHandler->getMemoryUsage();
        $memoryLimit = $errorHandler->getMemoryLimit();

        Log::warning('Memory approaching limit, marking indicator as failed', [
            'job_id' => $this->jobId,
            'indicator_slug' => $this->indicatorSlug,
            'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'memory_limit_mb' => round($memoryLimit / 1024 / 1024, 2),
            'usage_percent' => round(($memoryUsage / $memoryLimit) * 100, 1),
        ]);

        $errorHandler->handleIndicatorFailure(
            $this->jobId,
            $this->indicatorSlug,
            new RuntimeException("Memory limit approaching ({$memoryUsage}/{$memoryLimit} bytes)"),
            $progressService
        );

        // Don't throw - allow job to complete gracefully so other indicators can continue
    }

    /**
     * Calculate processing time in milliseconds.
     */
    private function calculateProcessingTime(float $startTime): int
    {
        return (int) round((microtime(true) - $startTime) * 1000);
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
