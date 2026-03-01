<?php

namespace App\Services\Crawler;

use App\Models\CrawlJob;
use App\Models\CrawlPage;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * Crawler Service implementation.
 *
 * Handles job creation, status tracking, content retrieval, and job management
 * for the Headless Crawler Service.
 *
 * @see TASK-22-3
 * @see TASK-22-11
 * @see SPEC-022
 */
class CrawlerService implements CrawlerServiceInterface
{
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
        $depth = $options['depth'] ?? 0;
        $jobId = 'crawl-' . Str::uuid()->toString();

        // Calculate estimated time based on depth
        $estimatedTime = $this->calculateEstimatedTime($depth);

        $job = CrawlJob::create([
            'user_id' => $userId,
            'job_id' => $jobId,
            'url' => $url,
            'status' => CrawlJob::STATUS_QUEUED,
            'depth' => $depth,
            'options' => $options['options'] ?? [],
            'expires_at' => now()->addDays(7),
        ]);

        return [
            'jobId' => $job->job_id,
            'status' => $job->status,
            'url' => $job->url,
            'depth' => $job->depth,
            'estimatedTime' => $estimatedTime,
            'message' => 'Crawl job queued for processing',
        ];
    }

    /**
     * Get a crawl job by ID.
     *
     * @param string $jobId The job identifier
     * @return array|null The job data or null if not found/unauthorized
     */
    public function getJob(string $jobId): ?array
    {
        $job = CrawlJob::where('job_id', $jobId)->first();

        if (!$job) {
            return null;
        }

        return $this->formatJobResponse($job);
    }

    /**
     * Cancel a crawl job.
     *
     * @param string $jobId The job identifier
     * @return bool|null True if cancelled, false if cannot be cancelled, null if not found
     */
    public function cancelJob(string $jobId): ?bool
    {
        $job = CrawlJob::where('job_id', $jobId)->first();

        if (!$job) {
            return null;
        }

        if (!$job->canBeCancelled()) {
            return false;
        }

        $job->update([
            'status' => CrawlJob::STATUS_CANCELLED,
            'completed_at' => now(),
        ]);

        return true;
    }

    /**
     * Get the crawled content for a completed job.
     *
     * @param string $jobId The job identifier
     * @return array|null The content data or null if not found/unauthorized
     */
    public function getJobContent(string $jobId): ?array
    {
        $job = CrawlJob::where('job_id', $jobId)->first();

        if (!$job) {
            return null;
        }

        $pages = CrawlPage::where('crawl_job_id', $job->id)
            ->orderBy('depth')
            ->orderBy('id')
            ->get();

        $formattedPages = $pages->map(function ($page) {
            return [
                'url' => $page->url,
                'depth' => $page->depth,
                'title' => $page->title,
                'renderedHtml' => $page->rendered_html,
                'structuredText' => $page->structured_text,
                'meta' => $page->meta ?? [],
                'links' => $page->links ?? [],
                'size' => $page->size,
                'sizeFormatted' => $page->size_formatted,
                'crawledAt' => $page->crawled_at?->toIso8601String(),
            ];
        })->toArray();

        // Calculate depth distribution
        $depthDistribution = $pages->groupBy('depth')
            ->map(fn ($group) => $group->count())
            ->toArray();

        return [
            'jobId' => $job->job_id,
            'pages' => $formattedPages,
            'summary' => [
                'totalPages' => $pages->count(),
                'totalSize' => $pages->sum('size'),
                'depthDistribution' => $depthDistribution,
            ],
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
        $query = CrawlJob::where('user_id', $userId);

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply sorting
        $sortField = $filters['sort'] ?? 'created_at';
        $sortOrder = $filters['order'] ?? 'desc';
        $query->orderBy($sortField, $sortOrder);

        // Paginate
        $perPage = $filters['perPage'] ?? 15;
        $page = $filters['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Format job response for API.
     */
    protected function formatJobResponse(CrawlJob $job): array
    {
        $response = [
            'jobId' => $job->job_id,
            'status' => $job->status,
            'url' => $job->url,
            'depth' => $job->depth,
        ];

        // Add progress info for running jobs
        if ($job->status === CrawlJob::STATUS_RUNNING) {
            $response['pagesCrawled'] = $job->pages_crawled;
        }

        // Add completion info for completed jobs
        if ($job->status === CrawlJob::STATUS_COMPLETED) {
            $response['pagesCrawled'] = $job->pages_crawled;
            $response['totalSize'] = $job->total_size;
            $response['sizeFormatted'] = $job->size_formatted;
            $response['startedAt'] = $job->started_at?->toIso8601String();
            $response['completedAt'] = $job->completed_at?->toIso8601String();
            $response['duration'] = $job->duration;
            $response['contentUrl'] = $job->content_url;
        }

        // Add error info for failed jobs
        if ($job->status === CrawlJob::STATUS_FAILED) {
            $response['error'] = $job->error;
        }

        return $response;
    }

    /**
     * Calculate estimated time based on depth.
     */
    protected function calculateEstimatedTime(int $depth): string
    {
        // Rough estimate: 30s base + 15s per depth level
        $seconds = 30 + ($depth * 15);
        return $seconds . 's';
    }
}
