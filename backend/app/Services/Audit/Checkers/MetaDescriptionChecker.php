<?php

namespace App\Services\Audit\Checkers;

use App\Services\Audit\DTOs\IssueFinding;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Checker for meta description SEO issues.
 *
 * Detection Rules:
 * - Missing <meta name="description"> → Warning
 * - Empty content attribute → Warning
 * - Description > 160 chars → Notice (truncated)
 * - Description < 50 chars → Notice (too short)
 *
 * @see TASK-14-9
 */
class MetaDescriptionChecker extends BaseChecker
{
    /**
     * Minimum recommended description length.
     */
    private const MIN_LENGTH = 50;

    /**
     * Maximum recommended description length.
     */
    private const MAX_LENGTH = 160;

    /**
     * Issue type identifier.
     *
     * @var string
     */
    protected string $issueType = 'meta_description';

    /**
     * Checker name.
     *
     * @var string
     */
    protected string $name = 'meta_description_checker';

    /**
     * Checker priority.
     *
     * @var int
     */
    protected int $priority = 90;

    /**
     * Check a crawl result for meta description issues.
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
                description: 'Missing meta description - page has no HTML content',
                recommendation: 'Add a meta description tag with compelling copy. Optimal length is 150-160 characters.',
                context: ['description' => null, 'length' => 0]
            );
        }

        // Parse HTML
        $crawler = new Crawler($html);

        // Extract meta description
        $description = $this->extractMetaDescription($crawler);

        // Check for missing meta description
        if ($description === null) {
            return $this->createFinding(
                issueType: $this->issueType,
                severity: 'warning',
                description: 'Missing meta description - page has no meta description element',
                recommendation: 'Add a meta description tag with compelling copy. Optimal length is 150-160 characters.',
                context: ['description' => null, 'length' => 0]
            );
        }

        // Calculate length (using mb_strlen for multibyte character support)
        $length = mb_strlen($description);

        // Check for empty description
        if ($length === 0) {
            return $this->createFinding(
                issueType: $this->issueType,
                severity: 'warning',
                description: 'Empty meta description - meta element exists but content attribute is empty',
                recommendation: 'Add descriptive text to the meta description. Optimal length is 150-160 characters.',
                context: ['description' => '', 'length' => 0]
            );
        }

        // Check description length
        if ($length > self::MAX_LENGTH) {
            return $this->createFinding(
                issueType: $this->issueType,
                severity: 'notice',
                description: "Meta description is too long ({$length} characters) - will be truncated in search results",
                recommendation: "Shorten the description to {$this->getOptimalLengthText()}. Current length: {$length} characters.",
                context: ['description' => $description, 'length' => $length]
            );
        }

        if ($length < self::MIN_LENGTH) {
            return $this->createFinding(
                issueType: $this->issueType,
                severity: 'notice',
                description: "Meta description is too short ({$length} characters) - may not provide enough context",
                recommendation: "Expand the description to {$this->getOptimalLengthText()}. Current length: {$length} characters.",
                context: ['description' => $description, 'length' => $length]
            );
        }

        // Description is optimal
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
