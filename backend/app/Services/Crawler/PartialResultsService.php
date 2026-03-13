<?php

namespace App\Services\Crawler;

use App\Models\CrawlJob;
use Carbon\Carbon;

/**
 * Partial Results Service for TASK-22-17.
 *
 * Stores and retrieves partial crawl results for jobs that were
 * interrupted by timeout or cancellation.
 *
 * @see TASK-22-17
 * @see SPEC-022
 */
class PartialResultsService
{
    /**
     * Store partial results for a crawl job.
     *
     * @param CrawlJob $crawlJob The crawl job
     * @param array $pages Array of crawled pages
     * @param string $reason Reason for partial results ('timeout' or 'cancelled')
     * @param int|null $totalUrls Total URLs expected (for calculating not_crawled)
     * @param bool $merge Whether to merge with existing results
     * @return bool True if stored successfully
     */
    public function storePartialResults(
        CrawlJob $crawlJob,
        array $pages,
        string $reason,
        ?int $totalUrls = null,
        bool $merge = false
    ): bool {
        // Safely get existing results using getAttribute for mock compatibility
        $existingResults = $crawlJob->getAttribute('partial_results');
        $existingResults = is_array($existingResults) ? $existingResults : ['pages' => []];
        $existingPages = $existingResults['pages'] ?? [];

        if ($merge) {
            $pages = array_merge($existingPages, $pages);
        }

        // Filter to only completed pages for count
        $completedPages = array_filter($pages, fn($p) => ($p['status'] ?? '') === 'completed');
        $pagesCrawled = count($completedPages);

        // Calculate total size
        $totalSize = $this->calculateTotalSize($pages);

        // Calculate not crawled
        $notCrawled = $totalUrls !== null ? max(0, $totalUrls - $pagesCrawled) : null;

        // Build reason message
        $reasonMessage = $this->buildReasonMessage($reason, config('crawler.timeouts.job', 300));

        $partialResults = [
            'pages' => $pages,
            'pages_crawled' => $pagesCrawled,
            'total_size' => $totalSize,
            'reason' => $reasonMessage,
            'stored_at' => Carbon::now()->toDateTimeString(),
        ];

        if ($notCrawled !== null) {
            $partialResults['not_crawled'] = $notCrawled;
        }

        // Determine new status
        $newStatus = $reason === 'cancelled' ? CrawlJob::STATUS_CANCELLED : CrawlJob::STATUS_COMPLETED;

        // Build update data
        $updateData = [
            'partial_results' => $partialResults,
            'status' => $newStatus,
        ];

        // Add cancelled_at if cancelled
        if ($reason === 'cancelled') {
            $updateData['cancelled_at'] = Carbon::now();
        }

        $crawlJob->update($updateData);

        return true;
    }

    /**
     * Get partial content for a job.
     *
     * @param CrawlJob $crawlJob The crawl job
     * @return array|null Partial content or null
     */
    public function getPartialContent(CrawlJob $crawlJob): ?array
    {
        return $crawlJob->partial_results;
    }

    /**
     * Build partial response for API.
     *
     * @param CrawlJob $crawlJob The crawl job
     * @param string $reason Reason for partial results
     * @return array Response data
     */
    public function buildPartialResponse(CrawlJob $crawlJob, string $reason): array
    {
        $status = $reason === 'cancelled' ? 'cancelled' : 'completed';

        $response = [
            'jobId' => $crawlJob->job_id,
            'status' => $status,
            'pagesCrawled' => $crawlJob->pages_crawled ?? 0,
            'totalSize' => $crawlJob->total_size ?? 0,
            'reason' => $this->buildReasonMessage($reason),
            'partialContentUrl' => $this->generatePartialContentUrl($crawlJob->job_id),
        ];

        if ($status === 'cancelled' && $crawlJob->cancelled_at) {
            $response['cancelledAt'] = $crawlJob->cancelled_at->toIso8601String();
        }

        return $response;
    }

    /**
     * Generate partial content URL for a job.
     *
     * @param string $jobId The job identifier
     * @return string The content URL
     */
    public function generatePartialContentUrl(string $jobId): string
    {
        return "/api/v1/crawler/jobs/{$jobId}/content";
    }

    /**
     * Calculate total size of pages.
     *
     * @param array $pages Array of pages
     * @return int Total size in bytes
     */
    public function calculateTotalSize(array $pages): int
    {
        return array_sum(array_map(fn($p) => $p['size'] ?? 0, $pages));
    }

    /**
     * Format size for display.
     *
     * @param int $bytes Size in bytes
     * @return string Formatted size
     */
    public function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $bytes;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return number_format($size, 1) . ' ' . $units[$unitIndex];
    }

    /**
     * Build reason message.
     *
     * @param string $reason Reason code
     * @param int|null $timeout Timeout value (for timeout message)
     * @return string Human-readable reason
     */
    public function buildReasonMessage(string $reason, ?int $timeout = null): string
    {
        return match ($reason) {
            'timeout' => "Job timeout reached" . ($timeout ? " ({$timeout}s)" : ''),
            'cancelled' => 'User requested cancellation',
            default => $reason,
        };
    }

    /**
     * Get pages summary (completed, failed, pending counts).
     *
     * @param array $pages Array of pages
     * @return array Summary counts
     */
    public function getPagesSummary(array $pages): array
    {
        $summary = [
            'completed' => 0,
            'failed' => 0,
            'pending' => 0,
        ];

        foreach ($pages as $page) {
            $status = $page['status'] ?? 'pending';
            if (isset($summary[$status])) {
                $summary[$status]++;
            }
        }

        return $summary;
    }

    /**
     * Validate partial results data structure.
     *
     * @param array $data Partial results data
     * @return bool True if valid
     */
    public function validatePartialData(array $data): bool
    {
        if (!isset($data['pages']) || !is_array($data['pages'])) {
            return false;
        }

        return true;
    }

    /**
     * Serialize pages for storage.
     *
     * @param array $pages Array of pages
     * @return string JSON string
     */
    public function serializePages(array $pages): string
    {
        return json_encode(['pages' => $pages], JSON_PRETTY_PRINT);
    }

    /**
     * Deserialize pages from storage.
     *
     * @param string $json JSON string
     * @return array Decoded data
     */
    public function deserializePages(string $json): array
    {
        return json_decode($json, true) ?? ['pages' => []];
    }

    /**
     * Generate summary for API response.
     *
     * @param array $partialResults Partial results data
     * @param string $jobId Job identifier
     * @return array Summary array
     */
    public function generateSummary(array $partialResults, string $jobId): array
    {
        return [
            'jobId' => $jobId,
            'pagesCrawled' => $partialResults['pages_crawled'] ?? 0,
            'totalSize' => $partialResults['total_size'] ?? 0,
            'reason' => $partialResults['reason'] ?? 'Unknown',
            'partialContentUrl' => $this->generatePartialContentUrl($jobId),
        ];
    }
}
