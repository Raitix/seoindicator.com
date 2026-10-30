<?php

namespace App\Services\Audit\Checkers;

use App\Services\Audit\DTOs\IssueFinding;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Checker for title tag SEO issues.
 *
 * Detection Rules:
 * - Missing <title> tag → Critical
 * - Empty <title> tag → Critical
 * - Title > 60 chars → Warning (truncated in SERPs)
 * - Title < 30 chars → Notice (too short)
 *
 * @see TASK-14-9
 */
class TitleTagChecker extends BaseChecker
{
    /**
     * Minimum recommended title length.
     */
    private const MIN_LENGTH = 30;

    /**
     * Maximum recommended title length.
     */
    private const MAX_LENGTH = 60;

    /**
     * Issue type identifier.
     *
     * @var string
     */
    protected string $issueType = 'title_tag';

    /**
     * Checker name.
     *
     * @var string
     */
    protected string $name = 'title_tag_checker';

    /**
     * Checker priority.
     *
     * @var int
     */
    protected int $priority = 100;

    /**
     * Check a crawl result for title tag issues.
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
                severity: 'critical',
                description: 'Missing title tag - page has no HTML content',
                recommendation: 'Add a descriptive title tag to the page head section. Optimal length is 50-60 characters.',
                context: ['title' => null, 'length' => 0]
            );
        }

        // Parse HTML
        $crawler = new Crawler($html);

        // Extract title
        $title = $this->extractTitle($crawler);

        // Check for missing or empty title
        if ($title === null) {
            return $this->createFinding(
                issueType: $this->issueType,
                severity: 'critical',
                description: 'Missing title tag - page has no title element in the head section',
                recommendation: 'Add a descriptive title tag to the page head section. Optimal length is 50-60 characters.',
                context: ['title' => null, 'length' => 0]
            );
        }

        // Calculate length (using mb_strlen for multibyte character support)
        $length = mb_strlen($title);

        // Check for empty title
        if ($length === 0) {
            return $this->createFinding(
                issueType: $this->issueType,
                severity: 'critical',
                description: 'Empty title tag - title element exists but contains no text',
                recommendation: 'Add descriptive text to the title tag. Optimal length is 50-60 characters.',
                context: ['title' => '', 'length' => 0]
            );
        }

        // Check title length
        if ($length > self::MAX_LENGTH) {
            return $this->createFinding(
                issueType: $this->issueType,
                severity: 'warning',
                description: "Title tag is too long ({$length} characters) - will be truncated in search results",
                recommendation: "Shorten the title to {$this->getOptimalLengthText()}. Current length: {$length} characters.",
                context: ['title' => $title, 'length' => $length]
            );
        }

        if ($length < self::MIN_LENGTH) {
            return $this->createFinding(
                issueType: $this->issueType,
                severity: 'notice',
                description: "Title tag is too short ({$length} characters) - may not provide enough context",
                recommendation: "Expand the title to {$this->getOptimalLengthText()}. Current length: {$length} characters.",
                context: ['title' => $title, 'length' => $length]
            );
        }

        // Title is optimal
        return null;
    }

    /**
     * Get optimal length description text.
     *
     * @return string
     */
    private function getOptimalLengthText(): string
    {
        return self::MIN_LENGTH . '-' . self::MAX_LENGTH . ' characters';
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
