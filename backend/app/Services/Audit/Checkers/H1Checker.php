<?php

namespace App\Services\Audit\Checkers;

use App\Services\Audit\DTOs\IssueFinding;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Checker for H1 tag SEO issues.
 *
 * Detection Rules:
 * - No <h1> tag → Warning
 * - Multiple <h1> tags → Notice
 * - Empty <h1> tag → Warning
 *
 * @see TASK-14-9
 */
class H1Checker extends BaseChecker
{
    /**
     * Issue type identifier.
     *
     * @var string
     */
    protected string $issueType = 'h1_tag';

    /**
     * Checker name.
     *
     * @var string
     */
    protected string $name = 'h1_checker';

    /**
     * Checker priority.
     *
     * @var int
     */
    protected int $priority = 80;

    /**
     * Check a crawl result for H1 tag issues.
     *
     * @param mixed $result The crawl result to check
     * @return IssueFinding|null Returns an issue finding if detected, null otherwise
     */
    public function check($result): ?IssueFinding
    {
        // Handle null result
        if ($result === null) {
            return null;
        }

        // Get HTML content
        $html = $result->html ?? null;

        if (empty($html)) {
            return $this->createFinding(
                issueType: $this->issueType,
                severity: 'warning',
                description: 'Missing H1 tag - page has no HTML content',
                recommendation: 'Add a single H1 tag that describes the main topic of the page. Each page should have exactly one H1 element.',
                context: ['count' => 0, 'h1s' => []]
            );
        }

        // Parse HTML
        $crawler = new Crawler($html);

        // Extract H1 tags
        $h1s = $this->extractH1s($crawler);
        $count = count($h1s);

        // Check for missing H1
        if ($count === 0) {
            return $this->createFinding(
                issueType: $this->issueType,
                severity: 'warning',
                description: 'Missing H1 tag - page has no main heading element',
                recommendation: 'Add a single H1 tag that describes the main topic of the page. Each page should have exactly one H1 element.',
                context: ['count' => 0, 'h1s' => []]
            );
        }

        // Check for empty H1 (single empty H1)
        if ($count === 1 && $this->isEmptyH1($h1s[0])) {
            return $this->createFinding(
                issueType: $this->issueType,
                severity: 'warning',
                description: 'Empty H1 tag - H1 element exists but contains no text content',
                recommendation: 'Add descriptive text to the H1 element that describes the main topic of the page.',
                context: ['count' => 1, 'h1s' => $h1s]
            );
        }

        // Check for multiple H1s
        if ($count > 1) {
            return $this->createFinding(
                issueType: $this->issueType,
                severity: 'notice',
                description: "Multiple H1 tags found ({$count}) - best practice is to have only one H1 per page",
                recommendation: 'Use only one H1 tag per page for the main heading. Other headings should use H2-H6 elements to create a proper heading hierarchy.',
                context: ['count' => $count, 'h1s' => $h1s]
            );
        }

        // Single valid H1
        return null;
    }

    /**
     * Check if an H1 text is empty.
     *
     * @param string $h1Text The H1 text content
     * @return bool
     */
    private function isEmptyH1(string $h1Text): bool
    {
        return trim($h1Text) === '';
    }

    /**
     * Get the issue type this checker detects.
     *
     * @return string
     */
    public function getIssueType(): string
    {
        return $this->issueType;
    }

    /**
     * Get the checker name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the checker priority.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }
}
