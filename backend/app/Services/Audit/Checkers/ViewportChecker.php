<?php

namespace App\Services\Audit\Checkers;

use App\Services\Audit\DTOs\IssueFinding;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Checker for viewport meta tag issues.
 *
 * Detection Rules:
 * - Missing viewport meta → Warning
 * - Viewport without width=device-width → Warning
 *
 * @see TASK-14-9
 */
class ViewportChecker extends BaseChecker
{
    /**
     * Required viewport content pattern.
     */
    private const REQUIRED_PATTERN = 'width=device-width';

    /**
     * Issue type identifier.
     *
     * @var string
     */
    protected string $issueType = 'viewport';

    /**
     * Checker name.
     *
     * @var string
     */
    protected string $name = 'viewport_checker';

    /**
     * Checker priority.
     *
     * @var int
     */
    protected int $priority = 70;

    /**
     * Check a crawl result for viewport meta tag issues.
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
                description: 'Missing viewport meta tag - page has no HTML content',
                recommendation: 'Add a viewport meta tag: <meta name="viewport" content="width=device-width, initial-scale=1">. This is essential for mobile-friendly design.',
                context: ['content' => null]
            );
        }

        // Parse HTML
        $crawler = new Crawler($html);

        // Check if viewport meta exists
        if (!$this->hasViewport($crawler)) {
            return $this->createFinding(
                issueType: $this->issueType,
                severity: 'warning',
                description: 'Missing viewport meta tag - page is not mobile-optimized',
                recommendation: 'Add a viewport meta tag: <meta name="viewport" content="width=device-width, initial-scale=1">. This is essential for mobile-friendly design.',
                context: ['content' => null]
            );
        }

        // Extract viewport content
        $content = $this->extractViewportContent($crawler);

        // Check for empty content
        if ($content === null || trim($content) === '') {
            return $this->createFinding(
                issueType: $this->issueType,
                severity: 'warning',
                description: 'Invalid viewport meta tag - content attribute is missing or empty',
                recommendation: 'Add proper content to the viewport meta tag: <meta name="viewport" content="width=device-width, initial-scale=1">.',
                context: ['content' => $content]
            );
        }

        // Check for width=device-width
        if (!$this->hasValidWidth($content)) {
            return $this->createFinding(
                issueType: $this->issueType,
                severity: 'warning',
                description: 'Invalid viewport meta tag - missing width=device-width setting',
                recommendation: 'Update the viewport meta tag to include width=device-width: <meta name="viewport" content="width=device-width, initial-scale=1">.',
                context: ['content' => $content]
            );
        }

        // Viewport is valid
        return null;
    }

    /**
     * Extract viewport meta content.
     *
     * @param Crawler $crawler The DOM crawler
     * @return string|null The viewport content or null if not found
     */
    private function extractViewportContent(Crawler $crawler): ?string
    {
        // Use XPath for case-insensitive matching
        $viewportNode = $crawler->filterXPath('//head/meta[translate(@name, "VIEWPORT", "viewport") = "viewport"]')->first();

        if ($viewportNode->count() === 0) {
            return null;
        }

        return $viewportNode->attr('content');
    }

    /**
     * Check if viewport content has valid width setting.
     *
     * @param string $content The viewport content
     * @return bool
     */
    private function hasValidWidth(string $content): bool
    {
        // Normalize content (handle extra spaces)
        $normalizedContent = preg_replace('/\s+/', '', strtolower($content));
        $requiredPattern = str_replace(' ', '', strtolower(self::REQUIRED_PATTERN));

        return str_contains($normalizedContent, $requiredPattern);
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
