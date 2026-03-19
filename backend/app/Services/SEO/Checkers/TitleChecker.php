<?php

namespace App\Services\SEO\Checkers;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Title Tag SEO Indicator Checker.
 *
 * Analyzes HTML title tags for SEO optimization including:
 * - Title existence
 * - Title length (optimal: 50-60 characters)
 * - Keyword presence
 * - Multiple title detection
 *
 * Weight: 0.10 (highest weight content indicator)
 * Category: content
 *
 * @see TASK-18-10
 * @see TEST-TASK-18-10
 */
class TitleChecker extends BaseIndicatorChecker
{
    /**
     * Optimal title length range.
     */
    private const OPTIMAL_MIN = 50;
    private const OPTIMAL_MAX = 60;

    /**
     * Good title length range (outside optimal but acceptable).
     */
    private const GOOD_MIN = 30;
    private const GOOD_MAX = 100;

    /**
     * Default weight for title tag indicator.
     */
    protected float $defaultWeight = 0.10;

    /**
     * Default category for title tag indicator.
     */
    protected string $defaultCategory = 'content';

    /**
     * Get the unique identifier for this indicator.
     */
    public function id(): string
    {
        return 'title_tag';
    }

    /**
     * Get the display name for this indicator.
     */
    public function name(): string
    {
        return 'Title Tag';
    }

    /**
     * Perform the title tag check.
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

        // Count title elements
        $titleCount = $this->countElements($crawler, 'title');

        // No title found
        if ($titleCount === 0) {
            return $this->createResult(
                score: 0,
                passed: false,
                details: [
                    'title' => null,
                    'length' => 0,
                    'length_status' => 'missing',
                    'keywords_found' => [],
                    'multiple_titles' => false,
                ]
            );
        }

        // Extract title text (first title if multiple)
        $title = $this->extractText($crawler, 'title');

        // Handle empty/whitespace-only titles
        if ($title === null || trim($title) === '') {
            return $this->createResult(
                score: 0,
                passed: false,
                details: [
                    'title' => $title ?? '',
                    'length' => 0,
                    'length_status' => 'empty',
                    'keywords_found' => [],
                    'multiple_titles' => $titleCount > 1,
                ]
            );
        }

        // Calculate length
        $length = mb_strlen($title, 'UTF-8');

        // Detect keywords (case-insensitive)
        $keywordsFound = [];
        if (!empty($keywords)) {
            $titleLower = mb_strtolower($title, 'UTF-8');
            foreach ($keywords as $keyword) {
                $keywordLower = mb_strtolower($keyword, 'UTF-8');
                if (str_contains($titleLower, $keywordLower)) {
                    $keywordsFound[] = $keyword;
                }
            }
        }

        // Determine length status
        $lengthStatus = $this->getLengthStatus($length);

        // Calculate score
        $score = $this->calculateScore($length, $keywordsFound, $keywords);

        // Determine passed status
        $passed = $score >= 2 && $lengthStatus !== 'short' && $lengthStatus !== 'long';

        return $this->createResult(
            score: $score,
            passed: $passed,
            details: [
                'title' => $title,
                'length' => $length,
                'length_status' => $lengthStatus,
                'keywords_found' => $keywordsFound,
                'multiple_titles' => $titleCount > 1,
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

        // Missing title
        if ($details['length_status'] === 'missing' || $details['length_status'] === 'empty') {
            $suggestions[] = $this->createSuggestion(
                type: 'error',
                message: 'Page is missing a title tag. Add a descriptive title to improve SEO.',
                action: 'Add a <title> tag in the <head> section',
                priority: 1
            );
            return $suggestions;
        }

        // Multiple titles warning
        if ($details['multiple_titles'] ?? false) {
            $suggestions[] = $this->createSuggestion(
                type: 'warning',
                message: 'Multiple title tags detected. Only the first one will be used by search engines.',
                action: 'Remove duplicate <title> tags, keeping only one',
                priority: 2
            );
        }

        // Length suggestions
        $length = $details['length'] ?? 0;
        $lengthStatus = $details['length_status'] ?? '';

        if ($lengthStatus === 'short') {
            $suggestions[] = $this->createSuggestion(
                type: 'warning',
                message: "Title is too short ({$length} characters). Optimal length is 50-60 characters.",
                action: 'Expand the title to include more descriptive keywords',
                priority: 1
            );
        } elseif ($lengthStatus === 'long') {
            $suggestions[] = $this->createSuggestion(
                type: 'warning',
                message: "Title is too long ({$length} characters). Optimal length is 50-60 characters.",
                action: 'Shorten the title to display properly in search results',
                priority: 1
            );
        }

        // Keyword suggestions
        $keywordsFound = $details['keywords_found'] ?? [];
        if (empty($keywordsFound) && $result->score < 3) {
            $suggestions[] = $this->createSuggestion(
                type: 'info',
                message: 'No target keywords found in the title. Consider including relevant keywords.',
                action: 'Add relevant keywords to the title naturally',
                priority: 2
            );
        }

        // Optimal title - positive feedback
        if ($result->score === 3 && !$details['multiple_titles']) {
            $suggestions[] = $this->createSuggestion(
                type: 'info',
                message: 'Title tag is well-optimized for SEO.',
                action: null,
                priority: 3
            );
        }

        return $suggestions;
    }

    /**
     * Determine the length status category.
     *
     * @param int $length Title length in characters
     * @return string 'optimal', 'good', 'short', or 'long'
     */
    private function getLengthStatus(int $length): string
    {
        if ($length >= self::OPTIMAL_MIN && $length <= self::OPTIMAL_MAX) {
            return 'optimal';
        }

        if ($length >= self::GOOD_MIN && $length <= self::GOOD_MAX) {
            if ($length < self::OPTIMAL_MIN) {
                return 'good';
            }
            return 'good';
        }

        if ($length < self::GOOD_MIN) {
            return 'short';
        }

        return 'long';
    }

    /**
     * Calculate the score based on length and keywords.
     *
     * Scoring algorithm:
     * - Score 0: No title or empty title
     * - Score 1: Title too short (<30) or too long (>100)
     * - Score 2: Good length (30-49 or 61-100) OR optimal length without keywords
     * - Score 3: Optimal length (50-60) with keywords
     *
     * @param int $length Title length
     * @param array<int, string> $keywordsFound Keywords found in title
     * @param array<int, string> $targetKeywords Target keywords to check
     * @return int Score 0-3
     */
    private function calculateScore(int $length, array $keywordsFound, array $targetKeywords): int
    {
        // No title
        if ($length === 0) {
            return 0;
        }

        $hasKeywords = !empty($keywordsFound) || empty($targetKeywords);
        $isOptimal = $length >= self::OPTIMAL_MIN && $length <= self::OPTIMAL_MAX;
        $isGood = $length >= self::GOOD_MIN && $length <= self::GOOD_MAX;
        $isVeryLong = $length > self::GOOD_MAX;

        // Optimal length with keywords
        if ($isOptimal && $hasKeywords) {
            return 3;
        }

        // Optimal length without keywords
        if ($isOptimal) {
            return 2;
        }

        // Good length
        if ($isGood) {
            return 2;
        }

        // Very long title
        if ($isVeryLong) {
            return 1;
        }

        // Too short
        return 1;
    }
}
