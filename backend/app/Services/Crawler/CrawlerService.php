<?php

namespace App\Services\Crawler;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Basic CrawlerService implementation.
 *
 * This is a placeholder implementation that will be replaced by the full
 * implementation from TASK-22-3. It provides basic functionality for
 * the CrawlController to work with.
 *
 * @see TASK-22-11
 * @see TASK-22-3
 * @see SPEC-022
 */
class CrawlerService implements CrawlerServiceInterface
{
    /**
     * @var array In-memory storage for testing purposes
     */
    protected array $jobs = [];

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
        $jobId = 'crawl-' . \Illuminate\Support\Str::uuid()->toString();

        $depth = $options['depth'] ?? 0;
        $estimatedTime = $this->calculateEstimatedTime($depth);

        $job = [
            'jobId' => $jobId,
            'status' => 'queued',
            'url' => $url,
            'depth' => $depth,
            'estimatedTime' => $estimatedTime,
            'message' => 'Crawl job queued for processing',
            'user_id' => $userId,
            'options' => $options,
            'pagesCrawled' => 0,
            'totalSize' => 0,
            'sizeFormatted' => '0 B',
            'startedAt' => null,
            'completedAt' => null,
            'duration' => null,
            'contentUrl' => "/api/v1/crawler/jobs/{$jobId}/content",
            'error' => null,
            'created_at' => now()->toIso8601String(),
        ];

        $this->jobs[$jobId] = $job;

        return $job;
    }

    /**
     * Get a crawl job by ID.
     *
     * @param string $jobId The job identifier
     * @return array|null The job data or null if not found/unauthorized
     */
    public function getJob(string $jobId): ?array
    {
        return $this->jobs[$jobId] ?? null;
    }

    /**
     * Cancel a crawl job.
     *
     * @param string $jobId The job identifier
     * @return bool|null True if cancelled, false if cannot be cancelled, null if not found
     */
    public function cancelJob(string $jobId): ?bool
    {
        if (!isset($this->jobs[$jobId])) {
            return null;
        }

        $job = $this->jobs[$jobId];

        // Can only cancel queued or running jobs
        if (!in_array($job['status'], ['queued', 'running'])) {
            return false;
        }

        $this->jobs[$jobId]['status'] = 'cancelled';

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
        if (!isset($this->jobs[$jobId])) {
            return null;
        }

        $job = $this->jobs[$jobId];

        // Only return content for completed jobs
        if ($job['status'] !== 'completed') {
            return null;
        }

        return [
            'jobId' => $jobId,
            'pages' => $job['pages'] ?? [],
            'summary' => $job['summary'] ?? [
                'totalPages' => 0,
                'totalSize' => 0,
                'depthDistribution' => [],
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
        $page = $filters['page'] ?? 1;
        $perPage = min($filters['perPage'] ?? 15, 100);
        $status = $filters['status'] ?? null;

        // Filter jobs by user
        $userJobs = array_filter($this->jobs, function ($job) use ($userId, $status) {
            $matchesUser = ($job['user_id'] ?? null) === $userId;
            $matchesStatus = !$status || ($job['status'] ?? null) === $status;
            return $matchesUser && $matchesStatus;
        });

        // Sort by created_at descending
        usort($userJobs, function ($a, $b) {
            return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
        });

        // Paginate
        $total = count($userJobs);
        $offset = ($page - 1) * $perPage;
        $items = array_slice($userJobs, $offset, $perPage);

        // Remove sensitive data
        $items = array_map(function ($job) {
            unset($job['user_id']);
            unset($job['pages']);
            unset($job['summary']);
            return $job;
        }, $items);

        return new LengthAwarePaginator(
            array_values($items),
            $total,
            $perPage,
            $page
        );
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
}
