<?php

namespace App\Services\SEO\Checkers;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Meta Description SEO Indicator Checker.
 *
 * Analyzes HTML meta description tags for SEO optimization including:
 * - Description existence
 * - Description length (optimal: 150-160 characters)
 * - Keyword presence
 * - Auto-generated detection
 * - Multiple description detection
 * - Title comparison for duplicates
 *
 * Weight: 0.08
 * Category: content
 *
 * @see TASK-18-11
 * @see TEST-TASK-18-11
 */
class MetaDescriptionChecker extends BaseIndicatorChecker
{
    /**
     * Optimal description length range.
     */
    private const OPTIMAL_MIN = 150;
    private const OPTIMAL_MAX = 160;

    /**
     * Acceptable description length range (outside optimal but acceptable).
     */
    private const ACCEPTABLE_MIN = 120;
    private const ACCEPTABLE_MAX = 170;

    /**
     * Default weight for meta description indicator.
     */
    protected float $defaultWeight = 0.08;

    /**
     * Default category for meta description indicator.
     */
    protected string $defaultCategory = 'content';

    /**
     * Patterns that suggest auto-generated descriptions.
     */
    private const AUTO_GENERATED_PATTERNS = [
        '/automatically generated/i',
        '/auto[\s-]?generated/i',
        '/post excerpt/i',
        '/this is a post excerpt/i',
        '/this is the description for/i',
        '/page \d+ of \d+/i',
        '/posted on/i',
        '/\.{3}$/', // Trailing ellipsis suggests truncation
        '/^Page \d+/',
        '/Lorem ipsum/i',
    ];

    /**
     * Get the unique identifier for this indicator.
     */
    public function id(): string
    {
        return 'meta_description';
    }

    /**
     * Get the display name for this indicator.
     */
    public function name(): string
    {
        return 'Meta Description';
    }

    /**
     * Perform the meta description check.
     *
     * @param Crawler $crawler Symfony DomCrawler with loaded HTML
     * @param string $url The URL being analyzed
     * @param array<string, mixed> $options Optional configuration (keywords array)
     * @return IndicatorResult The check result
     */
    protected function performCheck(Crawler $crawler, string $url, array $options = []): IndicatorResult
    {
        // Get keywords from options
        $keywords = $options['keywords'] ?? [];

        // Count meta description elements (case-insensitive name matching)
        $descriptionCount = $this->countMetaDescriptions($crawler);

        // No description found
        if ($descriptionCount === 0) {
            return $this->createResult(
                score: 1,
                passed: false,
                details: [
                    'exists' => false,
                    'content' => null,
                    'char_count' => 0,
                    'is_optimal_length' => false,
                    'is_acceptable_length' => false,
                    'keywords_found' => [],
                    'duplicate_count' => 0,
                    'is_auto_generated' => false,
                    'matches_title' => false,
                ]
            );
        }

        // Extract description content (first one if multiple, case-insensitive)
        $content = $this->extractMetaDescriptionContent($crawler);

        // Handle empty/whitespace-only descriptions
        if ($content === null || trim($content) === '') {
            return $this->createResult(
                score: 1,
                passed: false,
                details: [
                    'exists' => true,
                    'content' => $content ?? '',
                    'char_count' => 0,
                    'is_optimal_length' => false,
                    'is_acceptable_length' => false,
                    'keywords_found' => [],
                    'duplicate_count' => $descriptionCount,
                    'is_auto_generated' => false,
                    'matches_title' => false,
                ]
            );
        }

        // Sanitize content (remove script tags and other HTML)
        $content = $this->sanitizeContent($content);

        // Calculate character count
        $charCount = mb_strlen($content, 'UTF-8');

        // Determine length status
        $isOptimalLength = $charCount >= self::OPTIMAL_MIN && $charCount <= self::OPTIMAL_MAX;
        $isAcceptableLength = $charCount >= self::ACCEPTABLE_MIN && $charCount <= self::ACCEPTABLE_MAX;

        // Detect keywords (case-insensitive, with partial matching)
        $keywordsFound = [];
        if (!empty($keywords)) {
            $contentLower = mb_strtolower($content, 'UTF-8');
            foreach ($keywords as $keyword) {
                $keywordLower = mb_strtolower($keyword, 'UTF-8');
                // Exact match
                if (str_contains($contentLower, $keywordLower)) {
                    $keywordsFound[] = $keyword;
                    continue;
                }
                // Partial match: check if any word from the keyword is in the content
                $keywordWords = preg_split('/[\s\-_]+/', $keywordLower);
                foreach ($keywordWords as $word) {
                    if (mb_strlen($word, 'UTF-8') >= 2 && str_contains($contentLower, $word)) {
                        $keywordsFound[] = $keyword;
                        break;
                    }
                }
            }
        }

        // Check for auto-generated content
        $isAutoGenerated = $this->detectAutoGenerated($content);

        // Check if description matches title
        $title = $this->extractText($crawler, 'title');
        $matchesTitle = $title !== null && mb_strtolower($content, 'UTF-8') === mb_strtolower($title, 'UTF-8');

        // Calculate score
        $score = $this->calculateScore($charCount, $keywordsFound, $keywords, $isAcceptableLength, $isOptimalLength);

        // Determine passed status
        $passed = $score >= 2 && !$isAutoGenerated;

        return $this->createResult(
            score: $score,
            passed: $passed,
            details: [
                'exists' => true,
                'content' => $content,
                'char_count' => $charCount,
                'is_optimal_length' => $isOptimalLength,
                'is_acceptable_length' => $isAcceptableLength,
                'keywords_found' => $keywordsFound,
                'duplicate_count' => $descriptionCount,
                'is_auto_generated' => $isAutoGenerated,
                'matches_title' => $matchesTitle,
            ]
        );
    }

    /**
     * Get actionable suggestions based on the check result.
     *
     * @param IndicatorResult $result The result from check()
     * @return array<int, Suggestion> Array of suggestions
     */
    public function getSuggestions(IndicatorResult $result): array
    {
        $suggestions = [];
        $details = $result->details;

        // Missing description
        if (!$details['exists']) {
            $suggestions[] = $this->createSuggestion(
                type: 'error',
                message: 'Page is missing a meta description. Add a descriptive meta description to improve SEO.',
                action: 'Add a <meta name="description" content="Your description"> tag in the <head> section',
                priority: 1
            );
            return $suggestions;
        }

        // Empty description
        if ($details['char_count'] === 0) {
            $suggestions[] = $this->createSuggestion(
                type: 'error',
                message: 'Meta description is empty. Add meaningful content to improve SEO.',
                action: 'Add descriptive content to the meta description',
                priority: 1
            );
            return $suggestions;
        }

        // Multiple descriptions warning
        if ($details['duplicate_count'] > 1) {
            $suggestions[] = $this->createSuggestion(
                type: 'warning',
                message: 'Multiple meta description tags detected. Only the first one will be used by search engines.',
                action: 'Remove duplicate meta description tags, keeping only one',
                priority: 2
            );
        }

        // Matches title warning
        if ($details['matches_title'] ?? false) {
            $suggestions[] = $this->createSuggestion(
                type: 'warning',
                message: 'Meta description is identical to the title tag. Create a unique description for better SEO.',
                action: 'Write a unique meta description that expands on the title',
                priority: 1
            );
        }

        // Auto-generated content warning
        if ($details['is_auto_generated'] ?? false) {
            $suggestions[] = $this->createSuggestion(
                type: 'warning',
                message: 'Meta description appears to be auto-generated. Create a custom description for better SEO.',
                action: 'Write a unique, descriptive meta description tailored to the page content',
                priority: 1
            );
        }

        // Length suggestions
        $charCount = $details['char_count'] ?? 0;

        if ($charCount < self::ACCEPTABLE_MIN) {
            $suggestions[] = $this->createSuggestion(
                type: 'warning',
                message: "Meta description is too short ({$charCount} characters). Optimal length is 150-160 characters.",
                action: 'Expand the description to include more descriptive content',
                priority: 1
            );
        } elseif ($charCount > self::ACCEPTABLE_MAX) {
            $suggestions[] = $this->createSuggestion(
                type: 'warning',
                message: "Meta description is too long ({$charCount} characters). Optimal length is 150-160 characters.",
                action: 'Shorten the description to display properly in search results',
                priority: 1
            );
        } elseif (!$details['is_optimal_length']) {
            // In acceptable range but not optimal
            if ($charCount < self::OPTIMAL_MIN) {
                $suggestions[] = $this->createSuggestion(
                    type: 'info',
                    message: "Meta description length ({$charCount} characters) is acceptable but could be improved. Optimal is 150-160 characters.",
                    action: 'Consider expanding the description to reach the optimal length',
                    priority: 2
                );
            } else {
                $suggestions[] = $this->createSuggestion(
                    type: 'info',
                    message: "Meta description length ({$charCount} characters) is acceptable but could be improved. Optimal is 150-160 characters.",
                    action: 'Consider shortening the description to reach the optimal length',
                    priority: 2
                );
            }
        }

        // Keyword suggestions
        $keywordsFound = $details['keywords_found'] ?? [];
        if (empty($keywordsFound) && $result->score < 3) {
            $suggestions[] = $this->createSuggestion(
                type: 'info',
                message: 'No target keywords found in the meta description. Consider including relevant keywords.',
                action: 'Add relevant keywords to the meta description naturally',
                priority: 2
            );
        }

        // Optimal description - positive feedback
        if ($result->score === 3 && !$details['is_auto_generated'] && $details['duplicate_count'] === 1 && !$details['matches_title']) {
            $suggestions[] = $this->createSuggestion(
                type: 'info',
                message: 'Meta description is well-optimized for SEO.',
                action: null,
                priority: 3
            );
        }

        return $suggestions;
    }

    /**
     * Count meta description elements (case-insensitive name attribute).
     *
     * @param Crawler $crawler The DOM crawler
     * @return int Number of meta description elements
     */
    private function countMetaDescriptions(Crawler $crawler): int
    {
        try {
            $count = 0;
            $crawler->filter('meta[name]')->each(function (Crawler $node) use (&$count) {
                $name = $node->attr('name');
                if ($name !== null && mb_strtolower($name, 'UTF-8') === 'description') {
                    $count++;
                }
            });
            return $count;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Extract meta description content (case-insensitive name attribute).
     *
     * @param Crawler $crawler The DOM crawler
     * @return string|null The content attribute value or null
     */
    private function extractMetaDescriptionContent(Crawler $crawler): ?string
    {
        try {
            foreach ($crawler->filter('meta[name]') as $node) {
                $name = $node->getAttribute('name');
                if ($name !== null && mb_strtolower($name, 'UTF-8') === 'description') {
                    return $node->getAttribute('content');
                }
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Sanitize content by removing potentially dangerous HTML.
     *
     * @param string $content The content to sanitize
     * @return string Sanitized content
     */
    private function sanitizeContent(string $content): string
    {
        // Remove script tags and their content
        $content = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $content);
        
        // Remove other HTML tags but keep text
        $content = strip_tags($content);
        
        // Decode HTML entities
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $content;
    }

    /**
     * Detect if the description appears to be auto-generated.
     *
     * @param string $content The description content
     * @return bool True if auto-generated patterns detected
     */
    private function detectAutoGenerated(string $content): bool
    {
        foreach (self::AUTO_GENERATED_PATTERNS as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Calculate the score based on length and keywords.
     *
     * Scoring algorithm:
     * - Score 3: Optimal length (150-160)
     * - Score 2: Acceptable length (120-170) but not optimal
     * - Score 1: Poor length (<120 or >170) or missing/empty
     *
     * Note: Keywords enhance the result but don't override length-based scoring.
     *
     * @param int $charCount Description length
     * @param array<int, string> $keywordsFound Keywords found in description
     * @param array<int, string> $targetKeywords Target keywords to check
     * @param bool $isAcceptableLength Whether length is in acceptable range
     * @param bool $isOptimalLength Whether length is in optimal range
     * @return int Score 1-3
     */
    private function calculateScore(
        int $charCount,
        array $keywordsFound,
        array $targetKeywords,
        bool $isAcceptableLength,
        bool $isOptimalLength
    ): int {
        // No content
        if ($charCount === 0) {
            return 1;
        }

        // Optimal length (150-160) - excellent
        if ($isOptimalLength) {
            return 3;
        }

        // Acceptable length (120-170) - good
        if ($isAcceptableLength) {
            return 2;
        }

        // Poor length (too short or too long)
        return 1;
    }
}
