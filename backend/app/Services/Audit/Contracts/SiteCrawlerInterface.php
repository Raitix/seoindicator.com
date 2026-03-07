<?php

namespace App\Services\Audit\Contracts;

use App\Services\Audit\DTOs\CrawlResult;
use App\Services\Audit\DTOs\CrawlState;
use Generator;

/**
 * Interface for SiteCrawler service.
 *
 * @see TASK-14-5
 */
interface SiteCrawlerInterface
{
    /**
     * Start a new crawl for an audit.
     *
     * @param int $auditId The audit ID
     * @param string $baseUrl The base URL to crawl
     * @throws \InvalidArgumentException If the URL is invalid
     */
    public function startCrawl(int $auditId, string $baseUrl): void;

    /**
     * Enqueue a URL for crawling.
     *
     * @param string $url The URL to enqueue
     * @param int $depth The crawl depth (0 = homepage)
     */
    public function enqueueUrl(string $url, int $depth = 0): void;

    /**
     * Process the crawl queue and yield results.
     *
     * @return Generator<int, CrawlResult>
     */
    public function processQueue(): Generator;

    /**
     * Fetch a single page.
     *
     * @param string $url The URL to fetch
     * @return CrawlResult The crawl result
     */
    public function fetchPage(string $url): CrawlResult;

    /**
     * Extract links from HTML content.
     *
     * @param string $html The HTML content
     * @param string $baseUrl The base URL for resolving relative URLs
     * @return array{internal: array, external: array}
     */
    public function extractLinks(string $html, string $baseUrl): array;

    /**
     * Check if a URL should be crawled.
     *
     * @param string $url The URL to check
     * @return bool True if the URL should be crawled
     */
    public function shouldCrawl(string $url): bool;

    /**
     * Cancel the current crawl.
     */
    public function cancelCrawl(): void;

    /**
     * Get the current crawl state.
     *
     * @return CrawlState|null The crawl state or null if no crawl is active
     */
    public function getStatus(): ?CrawlState;

    /**
     * Get the current queue size.
     *
     * @return int The number of pending URLs in the queue
     */
    public function getQueueSize(): int;

    /**
     * Check if a URL has been crawled.
     *
     * @param string $url The URL to check
     * @return bool True if the URL has been crawled
     */
    public function hasCrawled(string $url): bool;
}
