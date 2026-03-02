<?php

namespace App\Services\Crawler;

use App\Models\CrawlJob;
use App\Models\CrawlPage;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use InvalidArgumentException;

/**
 * Size Limiter Service for Headless Crawler.
 *
 * Tracks and enforces the 3MB total content cap per crawl job,
 * preventing resource exhaustion from oversized crawl results.
 *
 * @see TASK-22-9
 * @see SPEC-022
 */
class SizeLimiterService
{
    /**
     * Maximum total content size per job (bytes).
     * Default: 3MB (3,145,728 bytes)
     */
    private int $maxContentSize;

    /**
     * Maximum size for a single page (bytes).
     * Default: 10MB (10,485,760 bytes)
     */
    private int $maxPageSize;

    /**
     * Warning threshold (0.0-1.0).
     * Default: 0.8 (80%)
     */
    private float $warningThreshold;

    /**
     * Create a new SizeLimiterService instance.
     */
    public function __construct()
    {
        // Use null coalescing with defaults for null-safe config access
        $this->maxContentSize = config('crawler.max_content_size') ?? (3 * 1024 * 1024);
        $this->maxPageSize = config('crawler.max_page_size') ?? (10 * 1024 * 1024);
        $this->warningThreshold = config('crawler.warning_threshold') ?? 0.8;
    }

    /**
     * Check if adding content would exceed the size limit.
     *
     * @param string $jobId The crawl job ID
     * @param int $additionalSize The size of content to add (bytes)
     * @return array{canProceed: bool, currentSize: int, maxSize: int, remaining: int, percentUsed: float, warning: bool, pageExceedsLimit: bool}
     */
    public function checkLimit(string $jobId, int $additionalSize): array
    {
        $job = CrawlJob::where('job_id', $jobId)->first();
        
        // If job doesn't exist, return conservative defaults (deny operation)
        if (!$job) {
            return [
                'canProceed' => false,
                'currentSize' => 0,
                'maxSize' => $this->maxContentSize,
                'remaining' => $this->maxContentSize,
                'percentUsed' => 0.0,
                'warning' => false,
                'pageExceedsLimit' => $additionalSize > $this->maxPageSize,
            ];
        }
        
        $currentSize = $job->total_size;
        $remaining = max(0, $this->maxContentSize - $currentSize);
        
        // Calculate percentage with full precision, then compare with threshold
        $percentUsed = $this->maxContentSize > 0 
            ? ($currentSize / $this->maxContentSize) * 100 
            : 0.0;
        
        // Check if page exceeds individual page limit
        $pageExceedsLimit = $additionalSize > $this->maxPageSize;
        
        // Check if adding content would exceed total limit
        $wouldExceedLimit = ($currentSize + $additionalSize) > $this->maxContentSize;
        
        // Can proceed if: page doesn't exceed page limit AND doesn't exceed total limit
        $canProceed = !$pageExceedsLimit && !$wouldExceedLimit;
        
        // Warning if current usage is at or above threshold percentage
        // Use floor for threshold to handle rounding consistently
        $warningBytes = (int) floor($this->maxContentSize * $this->warningThreshold);
        
        // Also warn if we can't proceed due to size limits (even if below threshold percentage)
        $warning = $currentSize >= $warningBytes || ($wouldExceedLimit && !$pageExceedsLimit);

        return [
            'canProceed' => $canProceed,
            'currentSize' => $currentSize,
            'maxSize' => $this->maxContentSize,
            'remaining' => $remaining,
            'percentUsed' => round($percentUsed, 1),
            'warning' => $warning,
            'pageExceedsLimit' => $pageExceedsLimit,
        ];
    }

    /**
     * Add size to the job's total content size.
     *
     * @param string $jobId The crawl job ID
     * @param int $size The size to add (bytes)
     * @throws RuntimeException If job not found
     * @throws InvalidArgumentException If size is negative
     */
    public function addSize(string $jobId, int $size): void
    {
        if ($size < 0) {
            throw new InvalidArgumentException('Size cannot be negative');
        }

        $job = CrawlJob::where('job_id', $jobId)->first();

        if (!$job) {
            throw new RuntimeException("Crawl job not found: {$jobId}");
        }

        $previousSize = $job->total_size;
        $newSize = $previousSize + $size;

        $job->update(['total_size' => $newSize]);

        // Check if we're crossing the warning threshold
        $previousPercent = $this->maxContentSize > 0 
            ? ($previousSize / $this->maxContentSize) * 100 
            : 0;
        $newPercent = $this->maxContentSize > 0 
            ? ($newSize / $this->maxContentSize) * 100 
            : 0;
        $warningPercent = $this->warningThreshold * 100;

        if ($previousPercent < $warningPercent && $newPercent >= $warningPercent) {
            Log::warning('Crawl job approaching size limit', [
                'job_id' => $jobId,
                'current_size' => $newSize,
                'max_size' => $this->maxContentSize,
                'percent_used' => round($newPercent, 1),
            ]);
        }
    }

    /**
     * Get the current total size for a job.
     *
     * @param string $jobId The crawl job ID
     * @return int The total size in bytes (0 if job not found)
     */
    public function getTotalSize(string $jobId): int
    {
        $job = CrawlJob::where('job_id', $jobId)->first();

        return $job ? $job->total_size : 0;
    }

    /**
     * Get the remaining capacity for a job.
     *
     * @param string $jobId The crawl job ID
     * @return int The remaining capacity in bytes
     */
    public function getRemaining(string $jobId): int
    {
        $currentSize = $this->getTotalSize($jobId);
        
        return max(0, $this->maxContentSize - $currentSize);
    }

    /**
     * Enforce size limits on content.
     *
     * Returns null if:
     * - Content is accepted (fits within limits)
     * - Content is rejected (exceeds limits)
     * - Content is empty/null
     *
     * NEVER returns truncated content - this would break SEO analysis.
     *
     * @param string $jobId The crawl job ID
     * @param string|null $content The content to check
     * @param string|null $url Optional URL for logging/recording skipped pages
     * @return null Always returns null (accept or reject, never truncate)
     */
    public function enforceLimit(string $jobId, ?string $content, ?string $url = null): ?string
    {
        // Handle null/empty content
        if ($content === null || $content === '') {
            return null;
        }

        $contentSize = $this->calculateContentSize($content);
        $check = $this->checkLimit($jobId, $contentSize);

        // If page exceeds page size limit, reject and log
        if ($check['pageExceedsLimit']) {
            Log::warning('Page content exceeds page size limit', [
                'job_id' => $jobId,
                'url' => $url,
                'size' => $contentSize,
                'max_page_size' => $this->maxPageSize,
            ]);

            // Record skipped page if URL provided
            if ($url) {
                $this->recordSkippedPage($jobId, $url, $contentSize);
            }

            return null;
        }

        // If would exceed total limit, reject (but don't truncate)
        if (!$check['canProceed']) {
            Log::warning('Page content rejected - would exceed total size limit', [
                'job_id' => $jobId,
                'url' => $url,
                'size' => $contentSize,
                'current_size' => $check['currentSize'],
                'remaining' => $check['remaining'],
            ]);

            return null;
        }

        // Content accepted - return null (caller can proceed)
        return null;
    }

    /**
     * Calculate the byte size of content.
     *
     * Uses mb_strlen with '8bit' encoding to correctly count bytes
     * for UTF-8 multibyte characters (Japanese, emoji, etc.).
     *
     * @param string|null $content The content to measure
     * @return int The size in bytes
     */
    public function calculateContentSize(?string $content): int
    {
        if ($content === null || $content === '') {
            return 0;
        }

        // Use mb_strlen with 8bit encoding for accurate byte count
        return mb_strlen($content, '8bit');
    }

    /**
     * Record a skipped page due to size limits.
     *
     * @param string $jobId The crawl job ID
     * @param string $url The URL that was skipped
     * @param int $size The size of the page
     */
    private function recordSkippedPage(string $jobId, string $url, int $size): void
    {
        $job = CrawlJob::where('job_id', $jobId)->first();

        if (!$job) {
            return;
        }

        CrawlPage::create([
            'crawl_job_id' => $job->id,
            'url' => $url,
            'depth' => 0,
            'error' => "Page size ({$size} bytes) exceeds page size limit ({$this->maxPageSize} bytes)",
            'crawled_at' => now(),
        ]);
    }

    /**
     * Get the configured maximum content size.
     *
     * @return int Max content size in bytes
     */
    public function getMaxContentSize(): int
    {
        return $this->maxContentSize;
    }

    /**
     * Get the configured maximum page size.
     *
     * @return int Max page size in bytes
     */
    public function getMaxPageSize(): int
    {
        return $this->maxPageSize;
    }

    /**
     * Get the configured warning threshold.
     *
     * @return float Warning threshold (0.0-1.0)
     */
    public function getWarningThreshold(): float
    {
        return $this->warningThreshold;
    }
}
