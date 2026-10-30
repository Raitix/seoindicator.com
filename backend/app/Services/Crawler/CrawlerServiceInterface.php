<?php

namespace App\Services\Crawler;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface for the Crawler Service.
 *
 * Defines the contract for the headless crawler service that handles
 * job creation, status tracking, content retrieval, and job management.
 *
 * @see TASK-22-3
 * @see SPEC-022
 */
interface CrawlerServiceInterface
{
    /**
     * Create a new crawl job.
     *
     * @param int $userId The user initiating the crawl
     * @param string $url The URL to crawl
     * @param array $options Crawl options (depth, waitTime, userAgent, etc.)
     * @return array The created job data
     */
    public function createJob(int $userId, string $url, array $options = []): array;

    /**
     * Get a crawl job by ID.
     *
     * @param string $jobId The job identifier
     * @return array|null The job data or null if not found/unauthorized
     */
    public function getJob(string $jobId): ?array;

    /**
     * Cancel a crawl job.
     *
     * @param string $jobId The job identifier
     * @return bool|null True if cancelled, false if cannot be cancelled, null if not found
     */
    public function cancelJob(string $jobId): ?bool;

    /**
     * Get the crawled content for a completed job.
     *
     * @param string $jobId The job identifier
     * @return array|null The content data or null if not found/unauthorized
     */
    public function getJobContent(string $jobId): ?array;

    /**
     * List crawl jobs for a user with optional filters.
     *
     * @param int $userId The user ID
     * @param array $filters Optional filters (status, page, perPage, sort, order)
     * @return LengthAwarePaginator Paginated list of jobs
     */
    public function listJobs(int $userId, array $filters = []): LengthAwarePaginator;
}
