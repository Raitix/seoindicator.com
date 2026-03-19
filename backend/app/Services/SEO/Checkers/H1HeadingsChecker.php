<?php

namespace App\Services\SEO\Checkers;

use Symfony\Component\DomCrawler\Crawler;

/**
 * H1 Headings SEO Indicator Checker.
 *
 * Analyzes H1 heading tags for SEO optimization including:
 * - H1 existence
 * - H1 count (single H1 best practice)
 * - H1 length (optimal: 20-70 characters)
 * - Keyword presence
 * - Multiple H1 detection (warning)
 *
 * Weight: 0.08
 * Category: content
 *
 * @see TASK-18-14
 * @see TEST-TASK-18-14
 */
class H1HeadingsChecker extends BaseIndicatorChecker
{
    /**
     * Optimal H1 length range.
     */
    private const OPTIMAL_MIN = 20;
    private const OPTIMAL_MAX = 70;

    /**
     * Default weight for H1 headings indicator.
     */
    protected float $defaultWeight = 0.08;

    /**
     * Default category for H1 headings indicator.
     */
    protected string $defaultCategory = 'content';

    /**
     * Get the display name for this indicator.
     */
    public function name(): string
    {
        return 'H1 Headings';
    }

    /**
     * Perform the H1 headings check.
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

        // Extract all H1 elements
        $headings = $this->extractAllText($crawler, 'h1');

        // Count H1 elements
        $count = count($headings);

        // Determine if H1 exists
        $exists = $count > 0;

        // Check if single H1
        $isSingle = $count === 1;

        // Get first H1 text for analysis
        $firstH1 = $headings[0] ?? null;

        // Check for empty/whitespace-only H1
        $isEmpty = false;
        if ($firstH1 !== null) {
            $isEmpty = trim($firstH1) === '';
        }

        // Calculate length of first H1 (0 if missing or empty)
        $length = 0;
        $isOptimalLength = false;

        if ($firstH1 !== null && !$isEmpty) {
            $length = mb_strlen(trim($firstH1), 'UTF-8');
            $isOptimalLength = $length >= self::OPTIMAL_MIN && $length <= self::OPTIMAL_MAX;
        }

        // Detect keywords in H1s (case-insensitive)
        $keywordsFound = [];
        $hasKeywords = false;

        if (!empty($keywords) && $exists) {
            foreach ($headings as $heading) {
                if (trim($heading) === '') {
                    continue;
                }
                $headingLower = mb_strtolower($heading, 'UTF-8');
                foreach ($keywords as $keyword) {
                    $keywordLower = mb_strtolower($keyword, 'UTF-8');
                    if (str_contains($headingLower, $keywordLower)) {
                        if (!in_array($keyword, $keywordsFound, true)) {
                            $keywordsFound[] = $keyword;
                        }
                    }
                }
            }
            $hasKeywords = !empty($keywordsFound);
        }

        // If no keywords provided, consider it as not having keywords requirement
        if (empty($keywords)) {
            $hasKeywords = true; // No keyword requirement means this check passes
        }

        // Calculate score
        $score = $this->calculateScore(
            exists: $exists,
            isEmpty: $isEmpty,
            isSingle: $isSingle,
            isOptimalLength: $isOptimalLength,
            hasKeywords: $hasKeywords,
            count: $count,
            length: $length
        );

        // Determine passed status
        $passed = $score >= 2 && $isSingle && !$isEmpty;

        return $this->createResult(
            score: $score,
            passed: $passed,
            details: [
                'exists' => $exists,
                'count' => $count,
                'headings' => $headings,
                'isSingle' => $isSingle,
                'length' => $length,
                'isOptimalLength' => $isOptimalLength,
                'hasKeywords' => $hasKeywords,
                'keywords_found' => $keywordsFound,
                'empty' => $isEmpty,
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

        // Missing H1
        if (!$details['exists']) {
            $suggestions[] = $this->createSuggestion(
                type: 'error',
                message: 'Page is missing an H1 heading. Add a descriptive H1 to improve SEO.',
                action: 'Add an <h1> tag as the main heading of your page',
                priority: 1
            );
            return $suggestions;
        }

        // Empty H1
        if ($details['empty'] ?? false) {
            $suggestions[] = $this->createSuggestion(
                type: 'error',
                message: 'H1 heading is empty. Add descriptive content to your H1.',
                action: 'Add text content to the <h1> tag',
                priority: 1
            );
            return $suggestions;
        }

        // Multiple H1s warning
        if (!$details['isSingle']) {
            $count = $details['count'] ?? 0;
            $suggestions[] = $this->createSuggestion(
                type: 'warning',
                message: "Multiple H1 headings detected ({$count}). Best practice is to use only one H1 per page.",
                action: 'Consolidate multiple H1s into a single main heading, use H2-H6 for subheadings',
                priority: 2
            );
        }

        // Length suggestions (only if single H1)
        if ($details['isSingle']) {
            $length = $details['length'] ?? 0;

            if ($length < self::OPTIMAL_MIN) {
                $suggestions[] = $this->createSuggestion(
                    type: 'warning',
                    message: "H1 is too short ({$length} characters). Optimal length is 20-70 characters.",
                    action: 'Expand the H1 to include more descriptive content',
                    priority: 1
                );
            } elseif ($length > self::OPTIMAL_MAX) {
                $suggestions[] = $this->createSuggestion(
                    type: 'warning',
                    message: "H1 is too long ({$length} characters). Optimal length is 20-70 characters.",
                    action: 'Shorten the H1 to be more concise',
                    priority: 1
                );
            }
        }

        // Keyword suggestions
        $keywordsFound = $details['keywords_found'] ?? [];
        if (empty($keywordsFound) && $result->score < 3 && $details['isSingle']) {
            $suggestions[] = $this->createSuggestion(
                type: 'info',
                message: 'No target keywords found in the H1 heading. Consider including relevant keywords.',
                action: 'Add relevant keywords to the H1 naturally',
                priority: 2
            );
        }

        // Perfect H1 - positive feedback
        if ($result->score === 3 && $details['isSingle'] && $details['isOptimalLength']) {
            $suggestions[] = $this->createSuggestion(
                type: 'info',
                message: 'H1 heading is well-optimized for SEO.',
                action: null,
                priority: 3
            );
        }

        return $suggestions;
    }

    /**
     * Calculate the score based on H1 analysis.
     *
     * Scoring algorithm:
     * - Score 0: Not used (H1 analysis always returns at least 1)
     * - Score 1: No H1, empty H1, multiple H1s, or very short H1 (< 20 chars) without keywords
     * - Score 2: Single H1 with too long (> 70), or optimal length without keywords
     * - Score 3: Single H1 with optimal length (20-70) and keywords
     *
     * @param bool $exists Whether H1 exists
     * @param bool $isEmpty Whether H1 is empty
     * @param bool $isSingle Whether there's only one H1
     * @param bool $isOptimalLength Whether H1 length is optimal (20-70)
     * @param bool $hasKeywords Whether H1 contains keywords
     * @param int $count Number of H1 elements
     * @param int $length Length of first H1
     * @return int Score 1-3
     */
    private function calculateScore(
        bool $exists,
        bool $isEmpty,
        bool $isSingle,
        bool $isOptimalLength,
        bool $hasKeywords,
        int $count,
        int $length
    ): int {
        // No H1 or empty H1
        if (!$exists || $isEmpty) {
            return 1;
        }

        // Multiple H1s
        if (!$isSingle) {
            return 1;
        }

        // Perfect: single H1, optimal length, with keywords
        if ($isOptimalLength && $hasKeywords) {
            return 3;
        }

        // Good: single H1 with optimal length but no keywords
        if ($isOptimalLength) {
            return 2;
        }

        // Good: single H1 with keywords but suboptimal length
        if ($hasKeywords) {
            return 2;
        }

        // Single H1 without keywords and suboptimal length
        // Too long (> 70): score 2 (structure is correct, just verbose)
        // Too short (< 20): score 1 (not enough content)
        if ($length > self::OPTIMAL_MAX) {
            return 2;
        }

        // Too short without keywords
        return 1;
    }
}
