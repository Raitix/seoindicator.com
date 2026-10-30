<?php

namespace App\Jobs;

use App\Events\AuditCompleted;
use App\Events\AuditFailed;
use App\Events\AuditProgress;
use App\Events\AuditStarted;
use App\Models\Audit;
use App\Models\Site;
use App\Services\Audit\IssueDetector;
use App\Services\Audit\SiteCrawler;
use App\Services\Audit\DTOs\CrawlResult;
use App\Services\Audit\DTOs\IssueFinding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Queue job that processes SEO audit crawl requests.
 *
 * Orchestrates the full audit workflow:
 * - Starts crawling via SiteCrawler
 * - Detects issues via IssueDetector
 * - Tracks progress in cache
 * - Updates audit status throughout lifecycle
 * - Dispatches events for external listeners
 *
 * @see TASK-14-7
 */
class ProcessCrawlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The audit ID to process.
     */
    public int $auditId;

    /**
     * The base URL to crawl.
     */
    public string $baseUrl;

    /**
     * Maximum number of attempts.
     */
    public int $tries = 3;

    /**
     * Maximum execution time in seconds.
     */
    public int $timeout = 3600;

    /**
     * Memory limit percentage threshold (stop at 80% of limit).
     */
    protected float $memoryThreshold = 0.8;

    /**
     * Number of pages between progress updates.
     */
    protected int $progressUpdateInterval = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(int $auditId, string $baseUrl)
    {
        $this->auditId = $auditId;
        $this->baseUrl = $baseUrl;
        $this->onQueue('audits');
    }

    /**
     * Execute the job.
     */
    public function handle(SiteCrawler $crawler, IssueDetector $detector): void
    {
        try {
            // Validate inputs
            if (empty($this->baseUrl)) {
                throw new RuntimeException('Base URL cannot be empty');
            }

            if (!$this->isValidUrl($this->baseUrl)) {
                throw new RuntimeException("Invalid base URL: {$this->baseUrl}");
            }

            // Load audit
            $audit = Audit::find($this->auditId);
            if (!$audit) {
                throw new RuntimeException("Audit not found: {$this->auditId}");
            }

            // Check if audit can be processed
            // Allow resuming if audit is running but has cached progress (interrupted job)
            $cachedProgress = $this->getProgress();
            $canResume = $audit->status === 'running' && $cachedProgress !== null;
            
            if (in_array($audit->status, ['running', 'completed']) && !$canResume) {
                Log::info('ProcessCrawlJob: Audit already processed, skipping', [
                    'auditId' => $this->auditId,
                    'status' => $audit->status,
                ]);
                return;
            }

            // Initialize progress tracking (restores from cache if available)
            $progress = $this->getInitialProgress();
            $this->saveProgress($progress);

            // Update status to running
            $audit->status = 'running';
            $audit->started_at = now();
            $audit->save();

            // Dispatch AuditStarted event
            AuditStarted::dispatch($audit);

            Log::info('ProcessCrawlJob: Started', [
                'auditId' => $this->auditId,
                'baseUrl' => $this->baseUrl,
            ]);

            // Start crawling
            $crawler->startCrawl($this->auditId, $this->baseUrl);

            // Track statistics (restore from cached progress if resuming)
            $pagesCrawled = $progress['pages_crawled'] ?? 0;
            $issuesFound = $progress['issues_found'] ?? 0;
            $errorCount = $progress['error_count'] ?? 0;
            $errors = $progress['errors'] ?? [];

            // Process crawl results
            foreach ($crawler->processQueue() as $crawlResult) {
                // Check memory limit
                if ($this->isMemoryLimitReached()) {
                    Log::warning('ProcessCrawlJob: Memory limit reached, stopping', [
                        'auditId' => $this->auditId,
                        'memoryUsage' => memory_get_usage(true),
                    ]);
                    break;
                }

                // Check execution time
                if ($this->isTimeLimitReached()) {
                    Log::warning('ProcessCrawlJob: Time limit approaching, stopping', [
                        'auditId' => $this->auditId,
                    ]);
                    break;
                }

                // Skip if not a valid CrawlResult
                if (!($crawlResult instanceof CrawlResult)) {
                    Log::warning('ProcessCrawlJob: Invalid crawl result, skipping', [
                        'auditId' => $this->auditId,
                    ]);
                    continue;
                }

                // Track current URL
                $progress['current_url'] = $crawlResult->url;

                // Check for page errors
                if ($crawlResult->isError()) {
                    $errorCount++;
                    $errors[] = [
                        'url' => $crawlResult->url,
                        'error' => $crawlResult->errorMessage ?? 'Unknown error',
                    ];
                    // Don't count error pages as successfully crawled
                    continue;
                }

                // Count successful pages
                $pagesCrawled++;

                // Detect issues on successful pages
                try {
                    // Detect issues
                    $findings = $detector->detect($crawlResult);
                    $issueCount = count($findings);
                    $issuesFound += $issueCount;

                    // Store issues in database
                    foreach ($findings as $finding) {
                        $this->createIssueFromFinding($finding, $audit, $crawlResult->url);
                    }
                } catch (Throwable $e) {
                    Log::warning('ProcessCrawlJob: IssueDetector failed for URL', [
                        'url' => $crawlResult->url,
                        'error' => $e->getMessage(),
                    ]);
                    // Continue processing other pages
                }
                $progress['pages_crawled'] = $pagesCrawled;
                $progress['issues_found'] = $issuesFound;
                $progress['error_count'] = $errorCount;
                $progress['errors'] = array_slice($errors, -10); // Keep last 10 errors
                $progress['last_updated'] = now()->toISOString();

                // Update progress every N pages
                if ($pagesCrawled % $this->progressUpdateInterval === 0) {
                    $this->saveProgress($progress);
                    AuditProgress::dispatch($audit, $progress);
                }
            }

            // Final update
            $audit->pages_crawled = $pagesCrawled;
            $audit->issues_found = $issuesFound;
            $audit->status = 'completed';
            $audit->completed_at = now();
            $audit->save();

            // Clear progress cache
            $this->clearProgress();

            // Dispatch AuditCompleted event
            AuditCompleted::dispatch($audit);

            Log::info('ProcessCrawlJob: Completed', [
                'auditId' => $this->auditId,
                'pagesCrawled' => $pagesCrawled,
                'issuesFound' => $issuesFound,
            ]);

        } catch (Throwable $e) {
            Log::error('Crawl job failed', [
                'auditId' => $this->auditId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry logic
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('ProcessCrawlJob: Job failed permanently', [
            'auditId' => $this->auditId,
            'error' => $exception->getMessage(),
        ]);

        $audit = Audit::find($this->auditId);
        if (!$audit) {
            return;
        }

        // Update audit status
        $audit->status = 'failed';
        $audit->error_message = $exception->getMessage();
        $audit->completed_at = now();
        $audit->save();

        // Dispatch AuditFailed event
        AuditFailed::dispatch($audit, $exception->getMessage());
    }

    /**
     * Get the backoff time between retries.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60]; // 10s, 30s, 60s between retries
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(2);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ["audit:{$this->auditId}"];
    }

    // =========================================================================
    // Progress Tracking
    // =========================================================================

    /**
     * Get initial progress data, restoring from cache if available.
     */
    protected function getInitialProgress(): array
    {
        // Try to restore from cache first (for resume support)
        $cached = $this->getProgress();
        if ($cached !== null && isset($cached['audit_id']) && $cached['audit_id'] === $this->auditId) {
            Log::info('ProcessCrawlJob: Resuming from cached progress', [
                'auditId' => $this->auditId,
                'cachedPagesCrawled' => $cached['pages_crawled'] ?? 0,
            ]);
            return $cached;
        }

        return [
            'audit_id' => $this->auditId,
            'pages_crawled' => 0,
            'issues_found' => 0,
            'current_url' => null,
            'started_at' => now()->toISOString(),
            'last_updated' => now()->toISOString(),
            'error_count' => 0,
            'errors' => [],
        ];
    }

    /**
     * Save progress to cache.
     */
    protected function saveProgress(array $progress): void
    {
        try {
            Cache::put(
                "audit:progress:{$this->auditId}",
                $progress,
                now()->addHours(24)
            );
        } catch (Throwable $e) {
            Log::warning('ProcessCrawlJob: Failed to save progress to cache', [
                'auditId' => $this->auditId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get progress from cache.
     */
    protected function getProgress(): ?array
    {
        try {
            return Cache::get("audit:progress:{$this->auditId}");
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Clear progress from cache.
     */
    protected function clearProgress(): void
    {
        try {
            Cache::forget("audit:progress:{$this->auditId}");
        } catch (Throwable $e) {
            // Ignore cache errors
        }
    }

    // =========================================================================
    // Resource Management
    // =========================================================================

    /**
     * Check if memory limit is approaching.
     */
    protected function isMemoryLimitReached(): bool
    {
        $memoryLimit = ini_get('memory_limit');
        if ($memoryLimit === '-1') {
            return false;
        }

        $limitBytes = $this->parseMemoryLimit($memoryLimit);
        $usedBytes = memory_get_usage(true);
        $thresholdBytes = $limitBytes * $this->memoryThreshold;

        return $usedBytes >= $thresholdBytes;
    }

    /**
     * Check if time limit is approaching.
     */
    protected function isTimeLimitReached(): bool
    {
        // Stop 5 minutes before timeout to allow graceful shutdown
        $safetyMargin = 300;
        return time() >= ($this->startTime ?? time()) + $this->timeout - $safetyMargin;
    }

    /**
     * Parse memory limit string to bytes.
     */
    protected function parseMemoryLimit(string $memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);
        $last = strtolower($memoryLimit[strlen($memoryLimit) - 1]);
        $value = (int) $memoryLimit;

        switch ($last) {
            case 'g':
                $value *= 1024;
                // no break
            case 'm':
                $value *= 1024;
                // no break
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Validate URL format.
     */
    protected function isValidUrl(string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Create an AuditIssue from an IssueFinding.
     */
    protected function createIssueFromFinding(IssueFinding $finding, Audit $audit, string $url): void
    {
        try {
            $audit->issues()->create([
                'url' => $url,
                'issue_type' => $finding->issueType,
                'severity' => $finding->getNormalizedSeverity(),
                'description' => $finding->description,
                'recommendation' => $finding->recommendation,
                'context' => $finding->context,
                'status' => 'open',
            ]);
        } catch (Throwable $e) {
            Log::warning('ProcessCrawlJob: Failed to create issue', [
                'auditId' => $this->auditId,
                'issueType' => $finding->issueType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
