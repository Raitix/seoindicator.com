<?php

namespace App\Services\Crawler;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Crawl Queue Service for multi-page crawling.
 *
 * Manages queue operations including priority queuing, duplicate detection,
 * retry logic, and concurrent processing support.
 *
 * @see TASK-22-10
 * @see SPEC-022
 */
class CrawlQueueService
{
    /**
     * In-memory queue storage (for testing/development).
     */
    protected array $queues = [];

    /**
     * In-memory item storage by ID.
     */
    protected array $items = [];

    /**
     * URL normalizer for duplicate detection.
     */
    protected UrlNormalizer $urlNormalizer;

    /**
     * Configuration values.
     */
    protected int $maxConcurrentPages;
    protected int $retryAttempts;
    protected int $lockTimeout;
    protected string $dedupMethod;

    /**
     * Create a new CrawlQueueService instance.
     */
    public function __construct(?UrlNormalizer $urlNormalizer = null)
    {
        $this->urlNormalizer = $urlNormalizer ?? new UrlNormalizer();
        $this->maxConcurrentPages = config('crawler.max_concurrent_pages', 3);
        $this->retryAttempts = config('crawler.queue_retry_attempts', 2);
        $this->lockTimeout = config('crawler.queue_lock_timeout', 300);
        $this->dedupMethod = config('crawler.queue_dedup_method', 'set');
    }

    // =========================================================================
    // Configuration Getters
    // =========================================================================

    /**
     * Get max concurrent pages setting.
     */
    public function getMaxConcurrentPages(): int
    {
        return $this->maxConcurrentPages;
    }

    /**
     * Get retry attempts setting.
     */
    public function getRetryAttempts(): int
    {
        return $this->retryAttempts;
    }

    /**
     * Get lock timeout setting.
     */
    public function getLockTimeout(): int
    {
        return $this->lockTimeout;
    }

    /**
     * Get dedup method setting.
     */
    public function getDedupMethod(): string
    {
        return $this->dedupMethod;
    }

    /**
     * Get the logger.
     */
    public function getLogger(): LoggerInterface
    {
        return Log::getLogger();
    }

    // =========================================================================
    // Queue Operations
    // =========================================================================

    /**
     * Add links to the queue.
     *
     * @param string $jobId The job identifier
     * @param array $links Array of links to add
     * @param int $depth The depth for these links
     * @param string|null $parentUrl The parent URL
     */
    public function addToQueue(string $jobId, array $links, int $depth, ?string $parentUrl = null): void
    {
        if (empty($links)) {
            return;
        }

        if (!isset($this->queues[$jobId])) {
            $this->queues[$jobId] = [];
        }

        $seenUrls = $this->getSeenUrls($jobId);

        foreach ($links as $link) {
            $url = $link['url'] ?? null;

            if (empty($url)) {
                continue;
            }

            // Normalize URL for duplicate detection
            $normalizedUrl = $this->urlNormalizer->normalize($url);

            // Skip duplicates
            if (isset($seenUrls[$normalizedUrl])) {
                continue;
            }

            // Generate unique ID
            $itemId = (string) Str::uuid();

            // Calculate priority based on depth
            $priority = $this->calculatePriority($depth);

            // Create queue item
            $item = [
                'id' => $itemId,
                'crawl_job_id' => $jobId,
                'url' => $normalizedUrl,
                'depth' => $depth,
                'priority' => $priority,
                'status' => 'pending',
                'parent_url' => $parentUrl ?? ($link['parent_url'] ?? null),
                'attempts' => 0,
                'error' => null,
                'locked_at' => null,
                'locked_by' => null,
                'created_at' => $this->getTimestamp(),
                'updated_at' => $this->getTimestamp(),
            ];

            // Store in queue and items
            $this->queues[$jobId][$itemId] = $item;
            $this->items[$itemId] = $item;

            // Mark URL as seen
            $seenUrls[$normalizedUrl] = true;
        }

        $this->setSeenUrls($jobId, $seenUrls);

        Log::debug('CrawlQueueService: Links added to queue', [
            'jobId' => $jobId,
            'count' => count($links),
            'depth' => $depth,
        ]);
    }

    /**
     * Get the next pending item from the queue.
     * Locks the item to prevent concurrent processing.
     *
     * @param string $jobId The job identifier
     * @return array|null The next item or null if none available
     */
    public function getNextItem(string $jobId): ?array
    {
        if (!isset($this->queues[$jobId])) {
            return null;
        }

        // Filter pending items that are not locked
        $pendingItems = array_filter($this->queues[$jobId], function ($item) {
            return $item['status'] === 'pending' && $item['locked_at'] === null;
        });

        if (empty($pendingItems)) {
            return null;
        }

        // Sort by priority (desc) then by created_at (asc)
        usort($pendingItems, function ($a, $b) {
            if ($a['priority'] !== $b['priority']) {
                return $b['priority'] <=> $a['priority']; // Higher priority first
            }
            return $a['created_at'] <=> $b['created_at']; // Older first
        });

        $item = $pendingItems[0];

        // Lock the item (set locked_at, but keep status as pending)
        // This allows the caller to process it and call markProcessing when ready
        $this->lockItem($item['id']);

        return $this->items[$item['id']];
    }

    /**
     * Lock an item for processing.
     *
     * @param string $itemId The item identifier
     * @param string|null $workerId Optional worker identifier
     */
    protected function lockItem(string $itemId, ?string $workerId = null): void
    {
        $item = $this->getItem($itemId);

        if (!$item) {
            return;
        }

        $jobId = $item['crawl_job_id'];

        // Update item - lock it but don't change status yet
        $item['locked_at'] = $this->getTimestamp();
        $item['locked_by'] = $workerId;
        $item['updated_at'] = $this->getTimestamp();

        // Store updated item
        $this->items[$itemId] = $item;
        $this->queues[$jobId][$itemId] = $item;
    }

    /**
     * Mark an item as being processed.
     *
     * @param string $itemId The item identifier
     * @param string|null $workerId Optional worker identifier
     * @throws \InvalidArgumentException If item not found
     */
    public function markProcessing(string $itemId, ?string $workerId = null): void
    {
        $item = $this->getItem($itemId);

        if (!$item) {
            throw new \InvalidArgumentException("Item not found: {$itemId}");
        }

        $jobId = $item['crawl_job_id'];

        // Update item
        $item['status'] = 'processing';
        $item['locked_at'] = $this->getTimestamp();
        $item['locked_by'] = $workerId;
        $item['attempts'] = ($item['attempts'] ?? 0) + 1;
        $item['updated_at'] = $this->getTimestamp();

        // Store updated item
        $this->items[$itemId] = $item;
        $this->queues[$jobId][$itemId] = $item;
    }

    /**
     * Mark an item as completed.
     *
     * @param string $itemId The item identifier
     * @throws \InvalidArgumentException If item not found
     */
    public function markCompleted(string $itemId): void
    {
        $item = $this->getItem($itemId);

        if (!$item) {
            throw new \InvalidArgumentException("Item not found: {$itemId}");
        }

        $jobId = $item['crawl_job_id'];

        // Update item
        $item['status'] = 'completed';
        $item['locked_at'] = null;
        $item['locked_by'] = null;
        $item['updated_at'] = $this->getTimestamp();

        // Store updated item
        $this->items[$itemId] = $item;
        $this->queues[$jobId][$itemId] = $item;
    }

    /**
     * Mark an item as failed.
     *
     * @param string $itemId The item identifier
     * @param string $error The error message
     * @throws \InvalidArgumentException If item not found
     */
    public function markFailed(string $itemId, string $error): void
    {
        $item = $this->getItem($itemId);

        if (!$item) {
            throw new \InvalidArgumentException("Item not found: {$itemId}");
        }

        $jobId = $item['crawl_job_id'];

        // Clear lock
        $item['locked_at'] = null;
        $item['locked_by'] = null;
        $item['error'] = $error;
        $item['updated_at'] = $this->getTimestamp();

        // Check if we should retry
        $shouldRetry = $this->shouldRetry($item, $error);

        if ($shouldRetry && $item['attempts'] < $this->retryAttempts) {
            // Put back in pending for retry
            $item['status'] = 'pending';
        } else {
            // Mark as failed permanently
            $item['status'] = 'failed';
        }

        // Store updated item
        $this->items[$itemId] = $item;
        $this->queues[$jobId][$itemId] = $item;

        Log::debug('CrawlQueueService: Item marked as failed', [
            'itemId' => $itemId,
            'error' => $error,
            'attempts' => $item['attempts'],
            'status' => $item['status'],
        ]);
    }

    /**
     * Mark an item as skipped.
     *
     * @param string $itemId The item identifier
     * @param string $reason The skip reason
     * @throws \InvalidArgumentException If item not found
     */
    public function markSkipped(string $itemId, string $reason): void
    {
        $item = $this->getItem($itemId);

        if (!$item) {
            throw new \InvalidArgumentException("Item not found: {$itemId}");
        }

        $jobId = $item['crawl_job_id'];

        // Update item
        $item['status'] = 'skipped';
        $item['locked_at'] = null;
        $item['locked_by'] = null;
        $item['error'] = $reason;
        $item['updated_at'] = $this->getTimestamp();

        // Store updated item
        $this->items[$itemId] = $item;
        $this->queues[$jobId][$itemId] = $item;
    }

    /**
     * Get queue statistics.
     *
     * @param string $jobId The job identifier
     * @return array Queue statistics
     */
    public function getQueueStats(string $jobId): array
    {
        if (!isset($this->queues[$jobId])) {
            return [
                'total' => 0,
                'pending' => 0,
                'processing' => 0,
                'completed' => 0,
                'failed' => 0,
                'skipped' => 0,
            ];
        }

        $stats = [
            'total' => count($this->queues[$jobId]),
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        foreach ($this->queues[$jobId] as $item) {
            $status = $item['status'] ?? 'pending';
            if (isset($stats[$status])) {
                $stats[$status]++;
            }
        }

        return $stats;
    }

    /**
     * Get all items in a queue.
     *
     * @param string $jobId The job identifier
     * @return array Queue items
     */
    public function getQueue(string $jobId): array
    {
        if (!isset($this->queues[$jobId])) {
            return [];
        }

        return array_values($this->queues[$jobId]);
    }

    /**
     * Get a specific item by ID.
     *
     * @param string $itemId The item identifier
     * @return array|null The item or null if not found
     */
    public function getItem(string $itemId): ?array
    {
        return $this->items[$itemId] ?? null;
    }

    /**
     * Cleanup completed items from queue.
     *
     * @param string $jobId The job identifier
     */
    public function cleanupQueue(string $jobId): void
    {
        if (!isset($this->queues[$jobId])) {
            return;
        }

        // Remove completed items
        $this->queues[$jobId] = array_filter(
            $this->queues[$jobId],
            fn($item) => $item['status'] !== 'completed'
        );

        // Reindex array
        $this->queues[$jobId] = array_combine(
            array_column($this->queues[$jobId], 'id'),
            $this->queues[$jobId]
        );
    }

    /**
     * Clear all items from a queue.
     *
     * @param string $jobId The job identifier
     */
    public function clearQueue(string $jobId): void
    {
        if (isset($this->queues[$jobId])) {
            // Remove from items index
            foreach ($this->queues[$jobId] as $item) {
                unset($this->items[$item['id']]);
            }

            // Clear queue
            unset($this->queues[$jobId]);
        }
    }

    // =========================================================================
    // Protected Helper Methods
    // =========================================================================

    /**
     * Calculate priority based on depth.
     *
     * @param int $depth The crawl depth
     * @return int Priority value (0-100)
     */
    protected function calculatePriority(int $depth): int
    {
        return match (true) {
            $depth === 0 => 100, // High priority
            $depth === 1 => 66,  // Medium priority
            $depth === 2 => 33,  // Low priority
            default => max(0, 33 - ($depth - 2) * 10), // Lower for deeper levels
        };
    }

    /**
     * Determine if an item should be retried.
     *
     * @param array $item The item
     * @param string $error The error message
     * @return bool True if should retry
     */
    protected function shouldRetry(array $item, string $error): bool
    {
        // Don't retry on client errors (4xx)
        if (preg_match('/HTTP\s*4\d{2}/i', $error)) {
            return false;
        }

        // Don't retry on connection-level failures (timeouts, network errors)
        // These indicate infrastructure issues that won't resolve quickly
        if (preg_match('/(timeout|connection|network|dns)/i', $error)) {
            return false;
        }

        // Check max attempts
        if ($item['attempts'] >= $this->retryAttempts) {
            return false;
        }

        return true;
    }

    /**
     * Get a timestamp with microsecond precision.
     */
    protected function getTimestamp(): string
    {
        return now()->format('Y-m-d\TH:i:s.uP');
    }

    /**
     * Get seen URLs for a job.
     */
    protected function getSeenUrls(string $jobId): array
    {
        // Use a static cache for seen URLs per job
        static $seenUrlsCache = [];
        return $seenUrlsCache[$jobId] ?? [];
    }

    /**
     * Set seen URLs for a job.
     */
    protected function setSeenUrls(string $jobId, array $seenUrls): void
    {
        static $seenUrlsCache = [];
        $seenUrlsCache[$jobId] = $seenUrls;
    }
}
