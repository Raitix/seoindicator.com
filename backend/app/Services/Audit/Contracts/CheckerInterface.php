<?php

namespace App\Services\Audit\Contracts;

use App\Services\Audit\DTOs\CrawlResult;
use App\Services\Audit\DTOs\IssueFinding;

/**
 * Interface for SEO issue checkers.
 *
 * Each checker analyzes a specific aspect of a page
 * and returns findings if issues are detected.
 *
 * @see TASK-14-8
 */
interface CheckerInterface
{
    /**
     * Check a crawl result for issues.
     *
     * @param CrawlResult $result The crawl result to analyze
     * @return IssueFinding|null Returns an issue finding if detected, null otherwise
     */
    public function check(CrawlResult $result): ?IssueFinding;

    /**
     * Get the issue type this checker detects.
     *
     * @return string The issue type identifier (e.g., 'missing_title_tag')
     */
    public function getIssueType(): string;

    /**
     * Get the checker name.
     *
     * @return string The unique checker name
     */
    public function getName(): string;

    /**
     * Get the checker priority.
     *
     * Higher priority checkers run first.
     *
     * @return int Priority value (0 = default, higher = earlier)
     */
    public function getPriority(): int;
}
