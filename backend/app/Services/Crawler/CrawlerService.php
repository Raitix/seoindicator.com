<?php

namespace App\Services\Crawler;

use App\Models\CrawlJob;
use App\Models\CrawlPage;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * CrawlerService implementation for the Headless Crawler Service.
 *
 * Manages crawl jobs, tracks progress, and stores crawled content.
 * Uses database storage for persistence and job tracking.
 *
 * @see TASK-22-3
 * @see TASK-22-18
 * @see SPEC-022
 */
class CrawlerService implements CrawlerServiceInterface
{
    /**
     * @var int|null Current authenticated user ID for authorization
     */
    protected ?int $currentUserId = null;

    /**
     * @var RateLimiterService|null Rate limiter service
     */
    protected ?RateLimiterService $rateLimiter = null;

    /**
     * Set the current user for authorization.
     *
     * @param int $userId
     * @return self
     */
    public function forUser(int $userId): self
    {
        $this->currentUserId = $userId;
        return $this;
    }

    /**
     * Set the rate limiter service.
     *
     * @param RateLimiterService $rateLimiter
     * @return self
     */
    public function withRateLimiter(RateLimiterService $rateLimiter): self
    {
        $this->rateLimiter = $rateLimiter;
        return $this;
    }

    /**
     * Create a new crawl job.
     *
     * @param int $userId The user initiating the crawl
     * @param string $url The URL to crawl
     * @param array $options Crawl options (depth, waitTime, userAgent, etc.)
     * @return array The created job data
     */
    public function createJob(int $userId, string $url, array $options = []): array
    {
        $jobId = 'crawl-' . Str::uuid()->toString();
        $depth = $options['depth'] ?? 0;
        $estimatedTime = $this->calculateEstimatedTime($depth);

        // Create database record
        $crawlJob = CrawlJob::create([
            'user_id' => $userId,
            'job_id' => $jobId,
            'url' => $url,
            'status' => CrawlJob::STATUS_QUEUED,
            'depth' => $depth,
            'options' => $options['options'] ?? [],
            'pages_crawled' => 0,
            'total_size' => 0,
            'expires_at' => now()->addHours(24),
        ]);

        // Increment rate limit count for the user
        if ($this->rateLimiter !== null) {
            $this->rateLimiter->incrementUserCount($userId);
            $this->rateLimiter->incrementConcurrentCount($userId);

            // Extract and record domain if available
            $parsedUrl = parse_url($url);
            if (isset($parsedUrl['host'])) {
                $this->rateLimiter->incrementDomainCount($parsedUrl['host']);
            }
        }

        return $this->formatJobResponse($crawlJob);
    }

    /**
     * Get a crawl job by ID.
     *
     * Authorization: Only returns jobs belonging to the current user.
     * Returns null if job not found or belongs to another user.
     *
     * @param string $jobId The job identifier
     * @return array|null The job data or null if not found/unauthorized
     */
    public function getJob(string $jobId): ?array
    {
        $query = CrawlJob::where('job_id', $jobId);

        // Apply authorization if user is set
        if ($this->currentUserId !== null) {
            $query->where('user_id', $this->currentUserId);
        }

        $crawlJob = $query->first();

        if (!$crawlJob) {
            return null;
        }

        return $this->formatJobResponse($crawlJob);
    }

    /**
     * Cancel a crawl job.
     *
     * Authorization: Only cancels jobs belonging to the current user.
     *
     * @param string $jobId The job identifier
     * @return bool|null True if cancelled, false if cannot be cancelled, null if not found
     */
    public function cancelJob(string $jobId): ?bool
    {
        $query = CrawlJob::where('job_id', $jobId);

        // Apply authorization if user is set
        if ($this->currentUserId !== null) {
            $query->where('user_id', $this->currentUserId);
        }

        $crawlJob = $query->first();

        if (!$crawlJob) {
            return null;
        }

        // Can only cancel queued or running jobs
        if (!$crawlJob->canBeCancelled()) {
            return false;
        }

        $crawlJob->update([
            'status' => CrawlJob::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'completed_at' => now(),
        ]);

        return true;
    }

    /**
     * Get the crawled content for a completed job.
     *
     * Authorization: Only returns content for jobs belonging to the current user.
     *
     * @param string $jobId The job identifier
     * @return array|null The content data or null if not found/unauthorized
     */
    public function getJobContent(string $jobId): ?array
    {
        $query = CrawlJob::where('job_id', $jobId);

        // Apply authorization if user is set
        if ($this->currentUserId !== null) {
            $query->where('user_id', $this->currentUserId);
        }

        $crawlJob = $query->first();

        if (!$crawlJob) {
            return null;
        }

        // Only return content for completed jobs
        if ($crawlJob->status !== CrawlJob::STATUS_COMPLETED) {
            return null;
        }

        $pages = $crawlJob->pages()
            ->orderBy('depth')
            ->orderBy('url')
            ->get()
            ->map(fn ($page) => $this->formatPageResponse($page))
            ->toArray();

        return [
            'jobId' => $jobId,
            'pages' => $pages,
            'summary' => $this->calculateContentSummary($crawlJob, $pages),
        ];
    }

    /**
     * List crawl jobs for a user with optional filters.
     *
     * @param int $userId The user ID
     * @param array $filters Optional filters (status, page, perPage, sort, order)
     * @return LengthAwarePaginator Paginated list of jobs
     */
    public function listJobs(int $userId, array $filters = []): LengthAwarePaginator
    {
        $page = $filters['page'] ?? 1;
        $perPage = min($filters['perPage'] ?? 15, 100);
        $status = $filters['status'] ?? null;

        $query = CrawlJob::where('user_id', $userId);

        if ($status) {
            $query->where('status', $status);
        }

        $query->orderBy('created_at', 'desc');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        // Transform items to response format as objects
        $items = collect($paginator->items())
            ->map(fn ($job) => (object) $this->formatJobListResponse($job))
            ->toArray();

        return new LengthAwarePaginator(
            $items,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage()
        );
    }

    /**
     * Format a crawl job for list response (includes snake_case keys for compatibility).
     *
     * @param CrawlJob $job
     * @return array
     */
    protected function formatJobListResponse(CrawlJob $job): array
    {
        return [
            'job_id' => $job->job_id,
            'jobId' => $job->job_id,
            'status' => $job->status,
            'url' => $job->url,
            'depth' => $job->depth,
            'pages_crawled' => $job->pages_crawled,
            'pagesCrawled' => $job->pages_crawled,
            'total_size' => $job->total_size,
            'totalSize' => $job->total_size,
            'size_formatted' => $job->size_formatted,
            'sizeFormatted' => $job->size_formatted,
            'created_at' => $job->created_at?->toIso8601String(),
        ];
    }

    /**
     * Update the status of a crawl job.
     *
     * @param string $jobId The job identifier
     * @param string $status The new status
     * @return bool True if updated, false if not found
     */
    public function updateStatus(string $jobId, string $status): bool
    {
        $crawlJob = CrawlJob::where('job_id', $jobId)->first();

        if (!$crawlJob) {
            return false;
        }

        $data = ['status' => $status];

        // Set timestamps based on status
        if ($status === CrawlJob::STATUS_RUNNING) {
            $data['started_at'] = now();
        } elseif (in_array($status, [CrawlJob::STATUS_COMPLETED, CrawlJob::STATUS_FAILED])) {
            $data['completed_at'] = now();
        }

        $crawlJob->update($data);

        return true;
    }

    /**
     * Store a page result for a crawl job.
     *
     * @param string $jobId The job identifier
     * @param array $pageData The page data (url, depth, title, html, text, meta, links, size)
     * @return CrawlPage|null The created page or null if job not found
     */
    public function storePageResult(string $jobId, array $pageData): ?CrawlPage
    {
        $crawlJob = CrawlJob::where('job_id', $jobId)->first();

        if (!$crawlJob) {
            return null;
        }

        return CrawlPage::create([
            'crawl_job_id' => $crawlJob->id,
            'url' => $pageData['url'],
            'depth' => $pageData['depth'] ?? 0,
            'title' => $pageData['title'] ?? null,
            'rendered_html' => $pageData['html'] ?? null,
            'structured_text' => $pageData['text'] ?? null,
            'meta' => $pageData['meta'] ?? null,
            'links' => $pageData['links'] ?? null,
            'size' => $pageData['size'] ?? 0,
            'crawled_at' => now(),
        ]);
    }

    /**
     * Mark a crawl job as failed.
     *
     * @param string $jobId The job identifier
     * @param string $error The error message
     * @return bool True if updated, false if not found
     */
    public function markJobFailed(string $jobId, string $error): bool
    {
        $crawlJob = CrawlJob::where('job_id', $jobId)->first();

        if (!$crawlJob) {
            return false;
        }

        // Truncate error if too long (TEXT field max is 65535 bytes)
        if (strlen($error) > 65535) {
            $error = substr($error, 0, 65532) . '...';
        }

        $crawlJob->update([
            'status' => CrawlJob::STATUS_FAILED,
            'error' => $error,
            'completed_at' => now(),
        ]);

        return true;
    }

    /**
     * Increment the pages crawled count for a job.
     *
     * @param string $jobId The job identifier
     * @return bool True if updated, false if not found
     */
    public function incrementPagesCrawled(string $jobId): bool
    {
        $crawlJob = CrawlJob::where('job_id', $jobId)->first();

        if (!$crawlJob) {
            return false;
        }

        $crawlJob->increment('pages_crawled');

        return true;
    }

    /**
     * Add to the total size for a job.
     *
     * @param string $jobId The job identifier
     * @param int $size The size to add in bytes
     * @return bool True if updated, false if not found
     */
    public function addTotalSize(string $jobId, int $size): bool
    {
        $crawlJob = CrawlJob::where('job_id', $jobId)->first();

        if (!$crawlJob) {
            return false;
        }

        $crawlJob->increment('total_size', $size);

        return true;
    }

    /**
     * Calculate estimated time based on crawl depth.
     *
     * @param int $depth
     * @return string
     */
    protected function calculateEstimatedTime(int $depth): string
    {
        $seconds = 30 + ($depth * 15);
        return $seconds . 's';
    }

    /**
     * Format a crawl job for API response.
     *
     * @param CrawlJob $job
     * @param bool $stripSensitive Whether to remove sensitive data
     * @return array
     */
    protected function formatJobResponse(CrawlJob $job, bool $stripSensitive = false): array
    {
        $response = [
            'jobId' => $job->job_id,
            'status' => $job->status,
            'url' => $job->url,
            'depth' => $job->depth,
            'estimatedTime' => $this->calculateEstimatedTime($job->depth),
            'message' => $this->getStatusMessage($job->status),
            'pagesCrawled' => $job->pages_crawled,
            'totalSize' => $job->total_size,
            'sizeFormatted' => $job->size_formatted,
            'startedAt' => $job->started_at?->toIso8601String(),
            'completedAt' => $job->completed_at?->toIso8601String(),
            'duration' => $job->duration,
            'contentUrl' => $job->content_url,
            'error' => $job->error,
            'created_at' => $job->created_at?->toIso8601String(),
        ];

        if (!$stripSensitive) {
            $response['user_id'] = $job->user_id;
            $response['options'] = $job->options;
        }

        return $response;
    }

    /**
     * Format a crawl page for API response.
     *
     * @param CrawlPage $page
     * @return array
     */
    protected function formatPageResponse(CrawlPage $page): array
    {
        return [
            'url' => $page->url,
            'depth' => $page->depth,
            'title' => $page->title,
            'text' => $page->structured_text,
            'meta' => $page->meta,
            'links' => $page->links,
            'size' => $page->size,
            'sizeFormatted' => $page->size_formatted,
            'error' => $page->error,
            'crawledAt' => $page->crawled_at?->toIso8601String(),
        ];
    }

    /**
     * Calculate content summary for a job.
     *
     * @param CrawlJob $job
     * @param array $pages
     * @return array
     */
    protected function calculateContentSummary(CrawlJob $job, array $pages): array
    {
        $depthDistribution = [];
        $totalSize = 0;

        foreach ($pages as $page) {
            $depth = $page['depth'] ?? 0;
            $depthDistribution[$depth] = ($depthDistribution[$depth] ?? 0) + 1;
            $totalSize += $page['size'] ?? 0;
        }

        return [
            'totalPages' => count($pages),
            'totalSize' => $totalSize,
            'depthDistribution' => $depthDistribution,
        ];
    }

    /**
     * Get a human-readable status message.
     *
     * @param string $status
     * @return string
     */
    protected function getStatusMessage(string $status): string
    {
        return match ($status) {
            CrawlJob::STATUS_QUEUED => 'Crawl job queued for processing',
            CrawlJob::STATUS_RUNNING => 'Crawl job is in progress',
            CrawlJob::STATUS_COMPLETED => 'Crawl job completed successfully',
            CrawlJob::STATUS_FAILED => 'Crawl job failed',
            CrawlJob::STATUS_CANCELLED => 'Crawl job was cancelled',
            default => 'Unknown status',
        };
    }

    /**
     * Helper method to add a test job (for testing purposes).
     *
     * @param array $jobData
     * @return string The job ID
     */
    public function addTestJob(array $jobData): string
    {
        $jobId = $jobData['jobId'] ?? ('crawl-' . Str::uuid()->toString());

        CrawlJob::create([
            'user_id' => $jobData['user_id'] ?? null,
            'job_id' => $jobId,
            'url' => $jobData['url'] ?? 'https://example.com',
            'status' => $jobData['status'] ?? 'queued',
            'depth' => $jobData['depth'] ?? 0,
            'options' => $jobData['options'] ?? [],
            'pages_crawled' => $jobData['pages_crawled'] ?? 0,
            'total_size' => $jobData['total_size'] ?? 0,
            'expires_at' => now()->addHours(24),
        ]);

        return $jobId;
    }

    /**
     * Helper method to clear all jobs (for testing purposes).
     */
    public function clearJobs(): void
    {
        CrawlPage::truncate();
        CrawlJob::truncate();
    }

    /**
     * Helper method to get all jobs (for testing purposes).
     *
     * @return array
     */
    public function getAllJobs(): array
    {
        return CrawlJob::all()->map(fn ($job) => $this->formatJobResponse($job))->toArray();
    }
}
