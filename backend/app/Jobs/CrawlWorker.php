<?php

namespace App\Jobs;

use App\Models\CrawlJob;
use App\Services\Crawler\CrawlerService;
use App\Services\Crawler\TimeoutHandlerService;
use App\Services\Crawler\CancellationService;
use App\Services\Crawler\PartialResultsService;
use App\Services\Crawler\CrawlFailureAlertService;
use App\Services\PlaywrightRendererService;
use App\Services\UrlValidatorService;
use App\Exceptions\SSRFException;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Crawl Worker Job.
 *
 * Queue job that processes crawl jobs, rendering JavaScript-heavy pages
 * and extracting SEO-relevant content using Playwright.
 *
 * @see TASK-22-15
 * @see TASK-22-17
 * @see SPEC-022
 *
 * @property string $jobId The crawl job identifier
 * @property int $timeout Maximum execution time in seconds
 * @property int $tries Number of retry attempts
 * @property int $maxExceptions Maximum exceptions before failing
 * @property array $backoff Seconds to wait between retries
 */
class CrawlWorker implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    /**
     * The crawl job identifier.
     */
    public string $jobId;

    /**
     * Maximum execution time in seconds.
     */
    public int $timeout = 300;

    /**
     * Number of retry attempts (configured in crawler.queue.retry_attempts).
     */
    public int $tries;

    /**
     * Maximum exceptions before failing.
     */
    public int $maxExceptions;

    /**
     * Seconds to wait between retries (calculated dynamically).
     */
    public array $backoff;

    /**
     * Memory limit in MB.
     */
    protected int $memoryLimit = 256;

    /**
     * The browser instance.
     */
    protected mixed $browser = null;

    /**
     * Pages crawled counter.
     */
    protected int $pagesCrawled = 0;

    /**
     * Total size in bytes.
     */
    protected int $totalSize = 0;

    /**
     * Start time for duration calculation.
     */
    protected float $startTime;

    /**
     * Crawled pages data for partial results.
     */
    protected array $crawledPages = [];

    /**
     * Create a new job instance.
     *
     * @param string $jobId The crawl job identifier
     */
    public function __construct(string $jobId)
    {
        $this->jobId = $jobId;
        $this->timeout = config('crawler.timeouts.job', 300);
        
        // Configurable retry mechanism (TASK-22-16)
        $this->tries = config('crawler.queue.retry_attempts', 2);
        $this->maxExceptions = $this->tries;
        
        // Exponential backoff: 60s, 120s, 240s...
        $this->backoff = $this->calculateBackoff();
        
        $this->onQueue('crawler');
    }

    /**
     * Calculate exponential backoff intervals.
     *
     * @return array
     */
    protected function calculateBackoff(): array
    {
        $backoff = [];
        $baseDelay = config('crawler.queue.retry_delay', 60);
        
        for ($i = 0; $i < $this->tries - 1; $i++) {
            $backoff[] = $baseDelay * pow(2, $i);
        }
        
        return $backoff ?: [60];
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->jobId;
    }

    /**
     * Execute the job.
     *
     * @param CrawlerService $crawler
     * @param PlaywrightRendererService $renderer
     * @param UrlValidatorService $urlValidator
     * @param TimeoutHandlerService $timeoutHandler
     * @param CancellationService $cancellationService
     * @param PartialResultsService $partialResultsService
     * @return void
     */
    public function handle(
        CrawlerService $crawler,
        PlaywrightRendererService $renderer,
        UrlValidatorService $urlValidator,
        TimeoutHandlerService $timeoutHandler,
        CancellationService $cancellationService,
        PartialResultsService $partialResultsService
    ): void {
        $this->startTime = microtime(true);

        // Set memory limit (use @ to suppress warnings if current usage exceeds limit)
        // In production, this ensures we have enough memory for crawling
        // In tests, this may fail silently if memory is already allocated
        @ini_set('memory_limit', $this->memoryLimit . 'M');

        // Retrieve the job
        $crawlJob = $crawler->getCrawlJob($this->jobId);

        if (!$crawlJob) {
            Log::warning('CrawlWorker: Job not found', [
                'jobId' => $this->jobId,
            ]);
            return;
        }

        // Check if job is available for processing
        if (!in_array($crawlJob->status, [CrawlJob::STATUS_QUEUED])) {
            Log::warning('CrawlWorker: Job not available for processing', [
                'jobId' => $this->jobId,
                'status' => $crawlJob->status,
            ]);
            return;
        }

        $url = $crawlJob->url;
        $depth = $crawlJob->depth;
        $options = $crawlJob->options ?? [];

        // Start timeout tracking
        $timeoutHandler->startTracking($this->jobId, [
            'timeout' => $options['timeout'] ?? config('crawler.timeouts.job', 300),
            'grace_period' => config('crawler.timeouts.grace_period', 30),
            'page_timeout' => config('crawler.timeouts.page', 30),
        ]);

        Log::info('CrawlWorker: Starting crawl job', [
            'jobId' => $this->jobId,
            'url' => $this->sanitizeUrlForLog($url),
            'depth' => $depth,
        ]);

        try {
            // Step 1: Validate URL (SSRF check)
            $urlValidator->validate($url);

            // Step 2: Update status to running
            $crawler->updateStatus($this->jobId, CrawlJob::STATUS_RUNNING);

            // Step 3: Launch browser
            $this->browser = $renderer->launchBrowser([
                'timeout' => config('crawler.timeouts.browser', 60) * 1000,
            ]);

            // Step 4: Crawl pages (starting with depth 0)
            $this->crawlPages(
                $crawler,
                $renderer,
                $timeoutHandler,
                $cancellationService,
                $url,
                $depth
            );

            // Step 5: Mark job as completed
            $duration = round(microtime(true) - $this->startTime);

            $crawler->updateStatus($this->jobId, CrawlJob::STATUS_COMPLETED);

            // Reset failure count on successful completion (TASK-22-16)
            try {
                $alertService = app(CrawlFailureAlertService::class);
                $alertService->resetFailureCount();
            } catch (\Exception $e) {
                Log::warning('CrawlWorker: Failed to reset failure count', [
                    'jobId' => $this->jobId,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('CrawlWorker: Crawl job completed', [
                'jobId' => $this->jobId,
                'pagesCrawled' => $this->pagesCrawled,
                'duration' => $duration . 's',
            ]);
        } catch (SSRFException $e) {
            Log::error('CrawlWorker: SSRF protection triggered', [
                'jobId' => $this->jobId,
                'url' => $this->sanitizeUrlForLog($url),
                'reason' => $e->getReason(),
            ]);

            $crawler->markJobFailed($this->jobId, $e->getMessage());
        } catch (Throwable $e) {
            Log::error('CrawlWorker: Job failed', [
                'jobId' => $this->jobId,
                'url' => $this->sanitizeUrlForLog($url),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $crawler->markJobFailed($this->jobId, $e->getMessage());
        } finally {
            // Always close browser
            if ($this->browser) {
                $renderer->closeBrowser($this->browser);
                $this->browser = null;
            }

            // Stop timeout tracking
            $timeoutHandler->stopTracking($this->jobId);

            // Clear cancellation flag
            $cancellationService->clearCancellationFlag($this->jobId);

            // Clear rate limit tokens
            $this->clearRateLimitTokens($url);

            // Clear URL locks
            $this->clearUrlLocks();
        }
    }

    /**
     * Crawl pages starting from the given URL.
     *
     * @param CrawlerService $crawler
     * @param PlaywrightRendererService $renderer
     * @param TimeoutHandlerService $timeoutHandler
     * @param CancellationService $cancellationService
     * @param string $startUrl
     * @param int $maxDepth
     * @return void
     */
    protected function crawlPages(
        CrawlerService $crawler,
        PlaywrightRendererService $renderer,
        TimeoutHandlerService $timeoutHandler,
        CancellationService $cancellationService,
        string $startUrl,
        int $maxDepth
    ): void {
        $queue = [[$startUrl, 0]]; // [url, depth]
        $visited = [];
        $totalUrls = 1;

        while (!empty($queue)) {
            // Check for cancellation
            if ($cancellationService->shouldCancel($this->jobId)) {
                Log::info('CrawlWorker: Job cancelled, stopping crawl', [
                    'jobId' => $this->jobId,
                    'pagesCrawled' => $this->pagesCrawled,
                ]);

                $this->handleCancellation($crawler, $cancellationService);
                break;
            }

            // Check for timeout
            if (!$timeoutHandler->canContinueCrawling($this->jobId)) {
                Log::warning('CrawlWorker: Job timeout exceeded', [
                    'jobId' => $this->jobId,
                    'elapsed' => $timeoutHandler->getElapsedSeconds($this->jobId),
                    'timeout' => $timeoutHandler->getDefaultTimeout(),
                    'pagesCrawled' => $this->pagesCrawled,
                ]);

                $this->handleTimeout($crawler, $timeoutHandler);
                break;
            }

            // Check if approaching timeout (start graceful shutdown)
            if ($timeoutHandler->shouldStartGracefulShutdown($this->jobId)) {
                Log::info('CrawlWorker: Approaching timeout, starting graceful shutdown', [
                    'jobId' => $this->jobId,
                    'remaining' => $timeoutHandler->getRemainingSeconds($this->jobId),
                ]);
            }

            // Check if we have time for next page
            if (!$timeoutHandler->hasTimeForNextPage($this->jobId)) {
                Log::info('CrawlWorker: Not enough time for next page, finishing', [
                    'jobId' => $this->jobId,
                    'remaining' => $timeoutHandler->getRemainingSeconds($this->jobId),
                ]);

                $this->handleTimeout($crawler, $timeoutHandler);
                break;
            }

            // Check memory
            $this->checkMemory();

            [$url, $currentDepth] = array_shift($queue);
            $urlHash = md5($url);

            // Skip already visited
            if (isset($visited[$urlHash])) {
                continue;
            }
            $visited[$urlHash] = true;

            // Skip if exceeds max depth
            if ($currentDepth > $maxDepth) {
                continue;
            }

            try {
                $pageStartTime = microtime(true);

                // Render the page
                $result = $renderer->renderPage($url, [
                    'timeout' => config('crawler.timeouts.page', 30) * 1000,
                ]);

                $pageDuration = round(microtime(true) - $pageStartTime);

                // Check if page timed out
                if ($timeoutHandler->isPageTimeout($pageDuration)) {
                    Log::warning('CrawlWorker: Page timeout, skipping', [
                        'jobId' => $this->jobId,
                        'url' => $this->sanitizeUrlForLog($url),
                        'duration' => $pageDuration,
                    ]);

                    continue;
                }

                // Store the result
                $crawler->storePageResult($this->jobId, [
                    'url' => $url,
                    'depth' => $currentDepth,
                    'title' => $result['meta']['title'] ?? null,
                    'html' => $result['html'],
                    'text' => $result['text'],
                    'meta' => $result['meta'],
                    'links' => $result['links'],
                    'size' => $result['size'],
                ]);

                // Track crawled page for partial results
                $this->crawledPages[] = [
                    'url' => $url,
                    'depth' => $currentDepth,
                    'status' => 'completed',
                    'size' => $result['size'],
                ];

                // Update stats
                $this->pagesCrawled++;
                $this->totalSize += $result['size'];

                $crawler->updateStats($this->jobId, [
                    'pages_crawled' => $this->pagesCrawled,
                    'total_size' => $this->totalSize,
                ]);

                // Queue links for further crawling
                if ($currentDepth < $maxDepth && !empty($result['links'])) {
                    foreach ($result['links'] as $link) {
                        if ($link['internal'] ?? false) {
                            $queue[] = [$link['url'], $currentDepth + 1];
                            $totalUrls++;
                        }
                    }
                }

                // Force garbage collection between pages
                gc_collect_cycles();

            } catch (Throwable $e) {
                Log::warning('CrawlWorker: Failed to crawl page', [
                    'jobId' => $this->jobId,
                    'url' => $this->sanitizeUrlForLog($url),
                    'error' => $e->getMessage(),
                ]);

                // Store error result
                $crawler->storePageResult($this->jobId, [
                    'url' => $url,
                    'depth' => $currentDepth,
                    'error' => $e->getMessage(),
                ]);

                // Track failed page
                $this->crawledPages[] = [
                    'url' => $url,
                    'depth' => $currentDepth,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }
    }

    /**
     * Handle job cancellation.
     *
     * @param CrawlerService $crawler
     * @param CancellationService $cancellationService
     * @return void
     */
    protected function handleCancellation(
        CrawlerService $crawler,
        CancellationService $cancellationService
    ): void {
        $crawlJob = $crawler->getCrawlJob($this->jobId);

        if ($crawlJob && !empty($this->crawledPages)) {
            // Store partial results
            $partialResultsService = app(PartialResultsService::class);
            $partialResultsService->storePartialResults(
                $crawlJob,
                $this->crawledPages,
                'cancelled'
            );
        } else {
            // Just update status
            $crawler->updateStatus($this->jobId, CrawlJob::STATUS_CANCELLED);
        }

        Log::info('CrawlWorker: Job cancelled by user', [
            'jobId' => $this->jobId,
            'pagesCrawled' => $this->pagesCrawled,
            'reason' => 'User requested cancellation',
        ]);
    }

    /**
     * Handle job timeout.
     *
     * @param CrawlerService $crawler
     * @param TimeoutHandlerService $timeoutHandler
     * @return void
     */
    protected function handleTimeout(
        CrawlerService $crawler,
        TimeoutHandlerService $timeoutHandler
    ): void {
        $crawlJob = $crawler->getCrawlJob($this->jobId);

        if ($crawlJob && !empty($this->crawledPages)) {
            // Store partial results
            $partialResultsService = app(PartialResultsService::class);
            $partialResultsService->storePartialResults(
                $crawlJob,
                $this->crawledPages,
                'timeout'
            );
        } else {
            // Just update status
            $crawler->updateStatus($this->jobId, CrawlJob::STATUS_COMPLETED);
        }

        $timeoutHandler->logTimeoutEvent($this->jobId);
    }

    /**
     * Clear rate limit tokens for a domain.
     *
     * @param string $url
     * @return void
     */
    protected function clearRateLimitTokens(string $url): void
    {
        $domain = parse_url($url, PHP_URL_HOST);
        if ($domain) {
            Cache::forget("crawler:rate:{$domain}");
        }
    }

    /**
     * Clear URL locks for this job.
     *
     * @return void
     */
    protected function clearUrlLocks(): void
    {
        // Clear any URL locks created during crawling
        // This is a simple implementation - could be enhanced to track specific locks
        Cache::forget("crawler:url_locks:{$this->jobId}");
    }

    /**
     * Check if the job has been cancelled.
     *
     * @param CrawlerService $crawler
     * @return bool
     */
    protected function isCancelled(CrawlerService $crawler): bool
    {
        $job = $crawler->getCrawlJob($this->jobId);

        return $job && $job->status === CrawlJob::STATUS_CANCELLED;
    }

    /**
     * Check memory usage and fail if exceeded.
     *
     * @return void
     * @throws \Exception If memory limit exceeded
     */
    protected function checkMemory(): void
    {
        $usage = memory_get_usage(true) / 1024 / 1024;

        if ($usage > $this->memoryLimit * 0.9) {
            throw new \Exception("Memory limit exceeded: {$usage}MB used of {$this->memoryLimit}MB limit");
        }
    }

    /**
     * Handle a job failure.
     *
     * @param Throwable $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        Log::error('CrawlWorker: Job failed permanently', [
            'jobId' => $this->jobId,
            'error' => $exception->getMessage(),
        ]);

        // Update job status
        $job = CrawlJob::where('job_id', $this->jobId)->first();

        if ($job) {
            $job->update([
                'status' => CrawlJob::STATUS_FAILED,
                'error' => substr($exception->getMessage(), 0, 65535),
                'completed_at' => now(),
            ]);

            // Record failure and send alert if threshold exceeded (TASK-22-16)
            try {
                $alertService = app(CrawlFailureAlertService::class);
                $alertService->recordFailure($job, $exception);
            } catch (\Exception $e) {
                Log::error('CrawlWorker: Failed to record failure alert', [
                    'jobId' => $this->jobId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Sanitize a URL for logging (remove credentials).
     *
     * @param string $url
     * @return string
     */
    protected function sanitizeUrlForLog(string $url): string
    {
        return preg_replace('/^(https?:\/\/)([^:]+):([^@]+)@/', '$1$2:***@', $url);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags(): array
    {
        return ['crawl', 'job:' . $this->jobId];
    }
}
