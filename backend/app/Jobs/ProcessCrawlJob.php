<?php

namespace App\Jobs;

use App\Events\AuditCompleted;
use App\Events\AuditFailed;
use App\Events\AuditProgress;
use App\Events\AuditStarted;
use App\Models\Audit;
use App\Models\AuditIssue;
use App\Services\Audit\DTOs\CrawlResult;
use App\Services\Audit\DTOs\IssueFinding;
use App\Services\Audit\IssueDetector;
use App\Services\Audit\SiteCrawler;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * ProcessCrawlJob - Queue job for processing SEO audit crawls.
 *
 * Orchestrates the full audit workflow:
 * - Updates audit status throughout lifecycle
 * - Processes URLs from SiteCrawler
 * - Detects issues with IssueDetector
 * - Tracks progress in cache
 * - Dispatches events for external listeners
 *
 * @see TASK-14-7
 */
class ProcessCrawlJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 3600;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public array $backoff = [10, 30, 60];

    /**
     * The audit ID being processed.
     */
    public int $auditId;

    /**
     * The base URL being crawled.
     */
    public string $baseUrl;

    /**
     * Progress tracking data.
     */
    protected array $progress = [
        'pages_crawled' => 0,
        'issues_found' => 0,
        'current_url' => null,
        'errors' => [],
    ];

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
     * Calculate the number of seconds to wait before retrying.
     */
    public function backoff(): array
    {
        return $this->backoff;
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(2);
    }

    /**
     * Execute the job.
     */
    public function handle(SiteCrawler $crawler, IssueDetector $detector): void
    {
        // Load the audit
        $audit = Audit::find($this->auditId);

        if (!$audit) {
            throw new RuntimeException("Audit not found: {$this->auditId}");
        }

        // Check if audit can be processed
        if (in_array($audit->status, ['running', 'completed'])) {
            Log::info('ProcessCrawlJob: Audit already processed or in progress', [
                'auditId' => $this->auditId,
                'status' => $audit->status,
            ]);
            return;
        }

        // Validate base URL
        if (empty($this->baseUrl) || !filter_var($this->baseUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException("Invalid base URL: {$this->baseUrl}");
        }

        try {
            // Start the audit
            $this->startAudit($audit);

            // Initialize progress
            $this->initializeProgress();

            // Start the crawl
            $crawler->startCrawl($this->auditId, $this->baseUrl);

            // Process the crawl queue
            $this->processCrawlQueue($audit, $crawler, $detector);

            // Complete the audit
            $this->completeAudit($audit);

        } catch (Throwable $e) {
            $this->handleError($audit, $e);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        $audit = Audit::find($this->auditId);

        if (!$audit) {
            return;
        }

        // Update audit status
        $audit->status = 'failed';
        $audit->error_message = $exception->getMessage();
        $audit->completed_at = now();
        $audit->save();

        // Clear progress cache
        $this->clearProgressCache();

        // Dispatch failure event
        AuditFailed::dispatch($audit, $exception->getMessage());

        Log::error('ProcessCrawlJob: Audit failed', [
            'auditId' => $this->auditId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ["audit:{$this->auditId}"];
    }

    // =========================================================================
    // Protected Methods
    // =========================================================================

    /**
     * Start the audit.
     */
    protected function startAudit(Audit $audit): void
    {
        $audit->status = 'running';
        $audit->started_at = now();
        $audit->save();

        // Dispatch started event
        AuditStarted::dispatch($audit);

        Log::info('ProcessCrawlJob: Audit started', [
            'auditId' => $this->auditId,
            'baseUrl' => $this->baseUrl,
        ]);
    }

    /**
     * Initialize progress tracking.
     */
    protected function initializeProgress(): void
    {
        $this->progress = [
            'audit_id' => $this->auditId,
            'pages_crawled' => 0,
            'issues_found' => 0,
            'current_url' => null,
            'started_at' => now()->toISOString(),
            'last_updated' => now()->toISOString(),
            'error_count' => 0,
            'errors' => [],
        ];

        $this->saveProgress();
    }

    /**
     * Process the crawl queue.
     */
    protected function processCrawlQueue(Audit $audit, SiteCrawler $crawler, IssueDetector $detector): void
    {
        $startTime = time();
        $memoryLimit = $this->getMemoryLimit();
        $progressUpdateInterval = 10; // Update every 10 pages

        /** @var CrawlResult $crawlResult */
        foreach ($crawler->processQueue() as $crawlResult) {
            // Check memory limit
            if ($this->shouldStopForMemory($memoryLimit)) {
                Log::warning('ProcessCrawlJob: Memory limit approaching, stopping gracefully', [
                    'auditId' => $this->auditId,
                    'memoryUsage' => memory_get_usage(true),
                    'memoryLimit' => $memoryLimit,
                ]);
                break;
            }

            // Check time limit
            if ($this->shouldStopForTime($startTime)) {
                Log::warning('ProcessCrawlJob: Time limit approaching, stopping gracefully', [
                    'auditId' => $this->auditId,
                    'elapsedTime' => time() - $startTime,
                ]);
                break;
            }

            // Check if job was cancelled
            if ($this->batch()?->cancelled()) {
                Log::info('ProcessCrawlJob: Batch cancelled, stopping', [
                    'auditId' => $this->auditId,
                ]);
                break;
            }

            // Process the crawl result
            $this->processCrawlResult($audit, $crawlResult, $detector);

            // Update progress periodically
            if ($this->progress['pages_crawled'] % $progressUpdateInterval === 0) {
                $this->saveProgress();
                $this->dispatchProgressEvent($audit);
            }
        }

        // Final progress save
        $this->saveProgress();
    }

    /**
     * Process a single crawl result.
     */
    protected function processCrawlResult(Audit $audit, CrawlResult $crawlResult, IssueDetector $detector): void
    {
        // Update current URL
        $this->progress['current_url'] = $crawlResult->url;

        try {
            // Detect issues (skip error pages)
            $issues = [];
            if ($crawlResult->isSuccess() && !$crawlResult->isError()) {
                $issues = $detector->detect($crawlResult);
            }

            // Store issues in database
            if (!empty($issues)) {
                $this->storeIssues($audit, $issues);
                $this->progress['issues_found'] += count($issues);
            }

            // Increment pages crawled
            $this->progress['pages_crawled']++;

        } catch (Throwable $e) {
            // Log but continue processing
            Log::warning('ProcessCrawlJob: Error processing crawl result', [
                'auditId' => $this->auditId,
                'url' => $crawlResult->url,
                'error' => $e->getMessage(),
            ]);

            $this->progress['error_count']++;
            $this->progress['errors'][] = [
                'url' => $crawlResult->url,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Store issues in the database.
     */
    protected function storeIssues(Audit $audit, array $issues): void
    {
        foreach ($issues as $issue) {
            if (!$issue instanceof IssueFinding) {
                continue;
            }

            AuditIssue::create([
                'audit_id' => $audit->id,
                'url' => $issue->url,
                'issue_type_id' => $issue->issueTypeId,
                'severity' => $issue->severity,
                'description' => $issue->message,
                'recommendation' => $issue->suggestion,
                'status' => 'open',
            ]);
        }
    }

    /**
     * Complete the audit.
     */
    protected function completeAudit(Audit $audit): void
    {
        // Update audit record
        $audit->status = 'completed';
        $audit->pages_crawled = $this->progress['pages_crawled'];
        $audit->issues_found = $this->progress['issues_found'];
        $audit->completed_at = now();
        $audit->save();

        // Clear progress cache
        $this->clearProgressCache();

        // Dispatch completed event
        AuditCompleted::dispatch($audit);

        Log::info('ProcessCrawlJob: Audit completed', [
            'auditId' => $this->auditId,
            'pagesCrawled' => $this->progress['pages_crawled'],
            'issuesFound' => $this->progress['issues_found'],
            'duration' => $audit->started_at->diffInSeconds($audit->completed_at),
        ]);
    }

    /**
     * Handle an error during processing.
     */
    protected function handleError(Audit $audit, Throwable $e): void
    {
        Log::error('ProcessCrawlJob: Crawl job failed', [
            'auditId' => $this->auditId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        // Don't mark as failed yet - the failed() method will handle that
        // This allows for retry logic
    }

    /**
     * Save progress to cache.
     */
    protected function saveProgress(): void
    {
        $this->progress['last_updated'] = now()->toISOString();

        try {
            Cache::put(
                "audit:progress:{$this->auditId}",
                $this->progress,
                86400 // 24 hours
            );
        } catch (Throwable $e) {
            // Continue without cache if it fails
            Log::warning('ProcessCrawlJob: Failed to save progress to cache', [
                'auditId' => $this->auditId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clear progress cache.
     */
    protected function clearProgressCache(): void
    {
        try {
            Cache::forget("audit:progress:{$this->auditId}");
        } catch (Throwable $e) {
            // Ignore cache errors
        }
    }

    /**
     * Dispatch progress event.
     */
    protected function dispatchProgressEvent(Audit $audit): void
    {
        AuditProgress::dispatch($audit, $this->progress);
    }

    /**
     * Get memory limit in bytes.
     */
    protected function getMemoryLimit(): int
    {
        $limit = config('audit.memory_limit', 512); // MB
        return $limit * 1024 * 1024;
    }

    /**
     * Check if we should stop for memory reasons.
     */
    protected function shouldStopForMemory(int $limit): bool
    {
        $usage = memory_get_usage(true);
        $threshold = $limit * 0.8; // 80% threshold

        return $usage > $threshold;
    }

    /**
     * Check if we should stop for time reasons.
     */
    protected function shouldStopForTime(int $startTime): bool
    {
        $elapsed = time() - $startTime;
        $buffer = 60; // Stop 60 seconds before timeout

        return $elapsed > ($this->timeout - $buffer);
    }
}
