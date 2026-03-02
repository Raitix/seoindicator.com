<?php

namespace App\Jobs;

use App\Models\CrawlJob;
use App\Services\Crawler\CrawlerService;
use App\Services\PlaywrightRendererService;
use App\Services\UrlValidatorService;
use App\Exceptions\SSRFException;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
     * Number of retry attempts.
     */
    public int $tries = 1;

    /**
     * Maximum exceptions before failing.
     */
    public int $maxExceptions = 1;

    /**
     * Seconds to wait between retries.
     */
    public array $backoff = [60];

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
     * Create a new job instance.
     *
     * @param string $jobId The crawl job identifier
     */
    public function __construct(string $jobId)
    {
        $this->jobId = $jobId;
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
     * @return void
     */
    public function handle(
        $crawler,
        $renderer,
        $urlValidator
    ): void {
        $this->startTime = microtime(true);

        // Set memory limit
        ini_set('memory_limit', $this->memoryLimit . 'M');

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
                'timeout' => $this->timeout * 1000,
            ]);

            // Step 4: Crawl pages (starting with depth 0)
            $this->crawlPages($crawler, $renderer, $url, $depth);

            // Step 5: Mark job as completed
            $duration = round(microtime(true) - $this->startTime);

            $crawler->updateStatus($this->jobId, CrawlJob::STATUS_COMPLETED);

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
        }
    }

    /**
     * Crawl pages starting from the given URL.
     *
     * @param CrawlerService $crawler
     * @param PlaywrightRendererService $renderer
     * @param string $startUrl
     * @param int $maxDepth
     * @return void
     */
    protected function crawlPages(
        CrawlerService $crawler,
        PlaywrightRendererService $renderer,
        string $startUrl,
        int $maxDepth
    ): void {
        $queue = [[$startUrl, 0]]; // [url, depth]
        $visited = [];

        while (!empty($queue)) {
            // Check for cancellation
            if ($this->isCancelled($crawler)) {
                Log::info('CrawlWorker: Job cancelled, stopping crawl', [
                    'jobId' => $this->jobId,
                ]);
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
                // Render the page
                $result = $renderer->renderPage($url, [
                    'timeout' => 30000, // 30s per page
                ]);

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
            }
        }
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
