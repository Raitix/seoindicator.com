<?php

namespace App\Services\Crawler;

use App\Models\CrawlJob;
use Carbon\Carbon;

/**
 * Partial Results Service for Crawl Jobs.
 *
 * Handles storage and retrieval of partial crawl results
 * when jobs are cancelled or time out.
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
     * @param array $pages Pages that were successfully crawled
     * @param string $reason Reason for partial results ('timeout' or 'cancelled')
     * @param int|null $totalUrls Total URLs planned to crawl
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
        $existingResults = $crawlJob->partial_results ?? [];

        if ($merge && !empty($existingResults['pages'])) {
            $pages = array_merge($existingResults['pages'], $pages);
        }

        // Filter to only successfully crawled pages
        $successfulPages = array_filter($pages, function ($page) {
            return ($page['status'] ?? 'completed') === 'completed';
        });

        // Calculate counts
        $pagesCrawled = count($successfulPages);
        $totalSize = $this->calculateTotalSize($successfulPages);
        $notCrawled = $totalUrls ? max(0, $totalUrls - $pagesCrawled) : 0;

        $partialResults = [
            'pages' => $successfulPages,
            'pages_crawled' => $pagesCrawled,
            'total_size' => $totalSize,
            'reason' => $this->buildReasonMessage($reason),
            'not_crawled' => $notCrawled,
            'stored_at' => Carbon::now()->toIso8601String(),
        ];

        // Determine final status
        $status = $reason === 'cancelled'
            ? CrawlJob::STATUS_CANCELLED
            : CrawlJob::STATUS_COMPLETED;

        $updateData = [
            'partial_results' => $partialResults,
            'status' => $status,
            'completed_at' => Carbon::now(),
        ];

        if ($reason === 'cancelled') {
            $updateData['cancelled_at'] = Carbon::now();
        }

        $crawlJob->update($updateData);

        return true;
    }

    /**
     * Build a partial response for API.
     *
     * @param CrawlJob $crawlJob The crawl job
     * @param string $reason Reason for partial results
     * @return array Partial response data
     */
    public function buildPartialResponse(CrawlJob $crawlJob, string $reason): array
    {
        $response = [
            'jobId' => $crawlJob->job_id,
            'status' => $reason === 'cancelled' ? 'cancelled' : 'completed',
            'pagesCrawled' => $crawlJob->pages_crawled,
            'totalSize' => $crawlJob->total_size,
            'reason' => $this->buildReasonMessage($reason),
            'partialContentUrl' => $this->generatePartialContentUrl($crawlJob->job_id),
        ];

        if ($reason === 'cancelled') {
            $response['cancelledAt'] = $crawlJob->cancelled_at?->toIso8601String();
        } else {
            $response['completedAt'] = $crawlJob->completed_at?->toIso8601String();
        }

        return $response;
    }

    /**
     * Generate the partial content URL for a job.
     *
     * @param string $jobId The job identifier
     * @return string The content URL
     */
    public function generatePartialContentUrl(string $jobId): string
    {
        return "/api/v1/crawler/jobs/{$jobId}/content";
    }

    /**
     * Get partial content for a job.
     *
     * @param CrawlJob $crawlJob The crawl job
     * @return array|null Partial content or null
     */
    public function getPartialContent(CrawlJob $crawlJob): ?array
    {
        $partialResults = $crawlJob->partial_results;

        if (!$partialResults) {
            return null;
        }

        return [
            'jobId' => $crawlJob->job_id,
            'pages' => $partialResults['pages'] ?? [],
            'summary' => [
                'pagesCrawled' => $partialResults['pages_crawled'] ?? 0,
                'totalSize' => $partialResults['total_size'] ?? 0,
                'reason' => $partialResults['reason'] ?? null,
            ],
        ];
    }

    /**
     * Calculate total size of pages.
     *
     * @param array $pages Pages to calculate size for
     * @return int Total size in bytes
     */
    public function calculateTotalSize(array $pages): int
    {
        return array_sum(array_map(function ($page) {
            return $page['size'] ?? 0;
        }, $pages));
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
     * @param string $reason The reason code
     * @param int|null $timeout The timeout value if applicable
     * @return string Human-readable reason message
     */
    public function buildReasonMessage(string $reason, ?int $timeout = null): string
    {
        return match ($reason) {
            'timeout' => $timeout
                ? "Job timeout reached after {$timeout} seconds"
                : 'Job timeout reached',
            'cancelled' => 'User requested cancellation',
            default => $reason,
        };
    }

    /**
     * Get pages summary.
     *
     * @param array $pages Pages to summarize
     * @return array Summary by status
     */
    public function getPagesSummary(array $pages): array
    {
        $summary = [
            'completed' => 0,
            'failed' => 0,
            'pending' => 0,
        ];

        foreach ($pages as $page) {
            $status = $page['status'] ?? 'completed';
            if (isset($summary[$status])) {
                $summary[$status]++;
            }
        }

        return $summary;
    }

    /**
     * Validate partial results data.
     *
     * @param array $data Data to validate
     * @return bool True if valid
     */
    public function validatePartialData(array $data): bool
    {
        if (!isset($data['pages']) || !is_array($data['pages'])) {
            return false;
        }

        if (!isset($data['reason'])) {
            return false;
        }

        return true;
    }

    /**
     * Serialize pages for storage.
     *
     * @param array $pages Pages to serialize
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
     * @return array API summary
     */
    public function generateSummary(array $partialResults, string $jobId): array
    {
        return [
            'jobId' => $jobId,
            'pagesCrawled' => $partialResults['pages_crawled'] ?? 0,
            'totalSize' => $partialResults['total_size'] ?? 0,
            'reason' => $partialResults['reason'] ?? null,
            'partialContentUrl' => $this->generatePartialContentUrl($jobId),
        ];
    }
}
