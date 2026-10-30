<?php

namespace App\Services\Crawler;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface for Content Retrieval Service.
 *
 * Provides methods for retrieving, filtering, paginating, and exporting
 * crawl job content.
 *
 * @see TASK-22-14
 * @see SPEC-022
 */
interface ContentRetrievalServiceInterface
{
    /**
     * Get paginated content for a crawl job.
     *
     * @param string $jobId The job identifier
     * @param int $userId The user ID (for authorization)
     * @param array $filters Optional filters (depth, search)
     * @param array $options Optional parameters (page, perPage, sortBy, sortOrder, include, exclude)
     * @return array|null Returns null if job not found or unauthorized
     */
    public function getContent(string $jobId, int $userId, array $filters = [], array $options = []): ?array;

    /**
     * Get a single page from a crawl job.
     *
     * @param string $jobId The job identifier
     * @param int $pageId The page ID
     * @param int $userId The user ID (for authorization)
     * @param array $options Optional parameters (include, exclude)
     * @return array|null Returns null if page/job not found or unauthorized
     */
    public function getPage(string $jobId, int $pageId, int $userId, array $options = []): ?array;

    /**
     * Export job content as JSON.
     *
     * @param string $jobId The job identifier
     * @param int $userId The user ID (for authorization)
     * @return array|null Returns null if job not found or unauthorized
     */
    public function exportAsJson(string $jobId, int $userId): ?array;

    /**
     * Export job content as CSV.
     *
     * @param string $jobId The job identifier
     * @param int $userId The user ID (for authorization)
     * @return string|null CSV content or null if job not found/unauthorized
     */
    public function exportAsCsv(string $jobId, int $userId): ?string;

    /**
     * Export job content as combined HTML.
     *
     * @param string $jobId The job identifier
     * @param int $userId The user ID (for authorization)
     * @return string|null HTML content or null if job not found/unauthorized
     */
    public function exportAsHtml(string $jobId, int $userId): ?string;

    /**
     * Check if job belongs to user.
     *
     * @param string $jobId The job identifier
     * @param int $userId The user ID
     * @return bool
     */
    public function jobBelongsToUser(string $jobId, int $userId): bool;

    /**
     * Get job status.
     *
     * @param string $jobId The job identifier
     * @return string|null Status or null if not found
     */
    public function getJobStatus(string $jobId): ?string;

    /**
     * Check if job is complete (can retrieve content).
     *
     * @param string $jobId The job identifier
     * @return bool
     */
    public function isJobComplete(string $jobId): bool;

    /**
     * Get ETag for job content.
     *
     * @param string $jobId The job identifier
     * @param array $options Optional parameters that affect content
     * @return string|null ETag or null if job not found
     */
    public function getEtag(string $jobId, array $options = []): ?string;

    /**
     * Clear cache for job.
     *
     * @param string $jobId The job identifier
     */
    public function clearCache(string $jobId): void;
}
