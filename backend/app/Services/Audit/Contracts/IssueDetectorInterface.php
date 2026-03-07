<?php

namespace App\Services\Audit\Contracts;

use App\Services\Audit\DTOs\CrawlResult;
use App\Services\Audit\DTOs\IssueFinding;

/**
 * Interface for IssueDetector service.
 *
 * @see TASK-14-8
 */
interface IssueDetectorInterface
{
    /**
     * Register a checker.
     *
     * @param string $name The checker name
     * @param CheckerInterface $checker The checker instance
     * @return self Returns $this for chaining
     */
    public function registerChecker(string $name, CheckerInterface $checker): self;

    /**
     * Register multiple checkers.
     *
     * @param array<string, CheckerInterface> $checkers Associative array of name => checker
     */
    public function registerMany(array $checkers): void;

    /**
     * Get available checkers.
     *
     * @return array<string> Array of checker names
     */
    public function getAvailableChecks(): array;

    /**
     * Analyze a crawl result and create audit issues.
     *
     * @param CrawlResult $crawlResult The crawl result to analyze
     * @param int $auditId The audit ID to associate issues with
     * @return array<int> Array of created issue IDs
     */
    public function analyze(CrawlResult $crawlResult, int $auditId): array;

    /**
     * Run a single checker by name.
     *
     * @param string $checkName The checker name
     * @param CrawlResult $crawlResult The crawl result to check
     * @return IssueFinding|null The finding or null if no issue
     */
    public function runCheck(string $checkName, CrawlResult $crawlResult): ?IssueFinding;

    /**
     * Detect SEO issues in a crawl result.
     *
     * @param CrawlResult $crawlResult The crawl result to analyze
     * @return array<int, IssueFinding> Array of detected issues
     */
    public function detect(CrawlResult $crawlResult): array;

    /**
     * Get the issue type ID for a given issue name.
     *
     * @param string $issueName The issue name
     * @return int|null The issue type ID or null if not found
     */
    public function getIssueTypeId(string $issueName): ?int;

    /**
     * Register a custom issue detector.
     *
     * @param string $name The detector name
     * @param callable $detector The detector function
     */
    public function registerDetector(string $name, callable $detector): void;
}
