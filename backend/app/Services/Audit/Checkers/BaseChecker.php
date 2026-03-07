<?php

namespace App\Services\Audit\Checkers;

use App\Services\Audit\Contracts\CheckerInterface;
use App\Services\Audit\DTOs\IssueFinding;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Abstract base class for SEO issue checkers.
 *
 * Provides common utility methods for extracting page elements
 * and creating issue findings.
 *
 * @see TASK-14-8
 */
abstract class BaseChecker implements CheckerInterface
{
    /**
     * Check a page for issues.
     *
     * @param mixed $result The crawl result to check
     * @return IssueFinding|null Returns an issue finding if detected, null otherwise
     */
    abstract public function check($result): ?IssueFinding;

    /**
     * Get the issue type this checker detects.
     *
     * @return string The issue type identifier
     */
    public function getIssueType(): string
    {
        return $this->issueType ?? 'unknown_issue';
    }

    /**
     * Get the checker name.
     *
     * @return string The unique checker name
     */
    public function getName(): string
    {
        return $this->name ?? static::class;
    }

    /**
     * Get the checker priority.
     *
     * @return int Priority value (0 = default)
     */
    public function getPriority(): int
    {
        return $this->priority ?? 0;
    }

    /**
     * Extract the page title from HTML.
     *
     * @param Crawler $crawler The DOM crawler
     * @return string|null The title text, empty string if empty, or null if not found
     */
    protected function extractTitle(Crawler $crawler): ?string
    {
        $titleNode = $crawler->filter('head > title')->first();

        if ($titleNode->count() === 0) {
            return null;
        }

        return trim($titleNode->text(null, true));
    }

    /**
     * Extract the meta description from HTML.
     *
     * @param Crawler $crawler The DOM crawler
     * @return string|null The meta description or null if not found
     */
    protected function extractMetaDescription(Crawler $crawler): ?string
    {
        $metaNode = $crawler->filter('head > meta[name="description"], head > meta[NAME="Description"], head > meta[name="Description"]')->first();

        if ($metaNode->count() === 0) {
            return null;
        }

        $content = $metaNode->attr('content');

        if ($content === null || $content === '') {
            return null;
        }

        return trim($content);
    }

    /**
     * Extract all H1 headings from HTML.
     *
     * @param Crawler $crawler The DOM crawler
     * @return array<string> Array of H1 text contents
     */
    protected function extractH1s(Crawler $crawler): array
    {
        $h1s = [];

        $crawler->filter('h1')->each(function (Crawler $node) use (&$h1s) {
            $text = trim($node->text(null, true));
            if ($text !== '') {
                $h1s[] = $text;
            }
        });

        return $h1s;
    }

    /**
     * Check if the page has a viewport meta tag.
     *
     * @param Crawler $crawler The DOM crawler
     * @return bool True if viewport tag exists
     */
    protected function hasViewport(Crawler $crawler): bool
    {
        // Use XPath for case-insensitive matching
        $viewportNodes = $crawler->filterXPath('//head/meta[translate(@name, "VIEWPORT", "viewport") = "viewport"]');
        return $viewportNodes->count() > 0;
    }

    /**
     * Check if meta description element exists (regardless of content).
     *
     * @param Crawler $crawler The DOM crawler
     * @return bool True if meta description element exists
     */
    protected function hasMetaDescription(Crawler $crawler): bool
    {
        $metaNode = $crawler->filter('head > meta[name="description"], head > meta[NAME="Description"], head > meta[name="Description"]');
        return $metaNode->count() > 0;
    }

    /**
     * Extract the raw content attribute from meta description.
     *
     * @param Crawler $crawler The DOM crawler
     * @return string|null The raw content attribute or null if element not found
     */
    protected function extractMetaDescriptionContent(Crawler $crawler): ?string
    {
        $metaNode = $crawler->filter('head > meta[name="description"], head > meta[NAME="Description"], head > meta[name="Description"]')->first();

        if ($metaNode->count() === 0) {
            return null;
        }

        // Return raw content attribute (may be empty string or null)
        return $metaNode->attr('content');
    }

    /**
     * Extract all images from HTML.
     *
     * @param Crawler $crawler The DOM crawler
     * @return array<array{src: string, alt: string|null}> Array of image data
     */
    protected function extractImages(Crawler $crawler): array
    {
        $images = [];

        $crawler->filter('img')->each(function (Crawler $node) use (&$images) {
            $src = $node->attr('src');
            $alt = $node->attr('alt');

            if ($src !== null) {
                $images[] = [
                    'src' => $src,
                    'alt' => $alt,
                ];
            }
        });

        return $images;
    }

    /**
     * Extract canonical URL from HTML.
     *
     * @param Crawler $crawler The DOM crawler
     * @return string|null The canonical URL or null if not found
     */
    protected function extractCanonical(Crawler $crawler): ?string
    {
        $linkNode = $crawler->filter('head > link[rel="canonical"]')->first();

        if ($linkNode->count() === 0) {
            return null;
        }

        return $linkNode->attr('href');
    }

    /**
     * Create an issue finding DTO.
     *
     * @param string $issueType The issue type identifier
     * @param string $severity The severity level
     * @param string $description Human-readable description
     * @param string $recommendation Suggested fix
     * @param array $context Additional context
     * @return IssueFinding
     */
    protected function createFinding(
        string $issueType,
        string $severity,
        string $description,
        string $recommendation,
        array $context = []
    ): IssueFinding {
        return new IssueFinding(
            issueType: $issueType,
            severity: $severity,
            description: $description,
            recommendation: $recommendation,
            context: $context
        );
    }
}
