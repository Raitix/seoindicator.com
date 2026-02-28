<?php

namespace App\Services\SEO\Checkers;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Interface for all SEO indicator checkers.
 *
 * All 17 indicator checkers must implement this interface to ensure
 * consistent behavior and enable polymorphic processing.
 *
 * @see TASK-18-3
 * @see SPEC-018
 */
interface IndicatorCheckerInterface
{
    /**
     * Get the unique identifier for this indicator.
     *
     * Should be a snake_case string (e.g., 'title_tag', 'meta_description').
     * Used for storage, lookups, and API responses.
     *
     * @return string Unique identifier
     */
    public function id(): string;

    /**
     * Get the human-readable name for this indicator.
     *
     * Used for display in UI and reports.
     *
     * @return string Human-readable name (e.g., 'Title Tag')
     */
    public function name(): string;

    /**
     * Get the category this indicator belongs to.
     *
     * Must be one of: 'content', 'technical', 'accessibility', 'performance'.
     *
     * @return string Category identifier
     */
    public function category(): string;

    /**
     * Get the weight of this indicator in the overall score.
     *
     * Must be a float between 0.0 and 1.0.
     * Weights determine how much each indicator contributes to the overall score.
     * All weights should sum to 1.0 across all indicators.
     *
     * @return float Weight between 0.0 and 1.0
     */
    public function weight(): float;

    /**
     * Perform the SEO indicator check.
     *
     * Analyzes the provided HTML/URL and returns a structured result
     * with score, details, and pass/fail status.
     *
     * @param Crawler $crawler Symfony DomCrawler with loaded HTML
     * @param string $url The URL being analyzed
     * @param array<string, mixed> $options Optional configuration options
     * @return IndicatorResult The check result with score and details
     */
    public function check(Crawler $crawler, string $url, array $options = []): IndicatorResult;

    /**
     * Get actionable suggestions based on the check result.
     *
     * Returns an array of suggestions for improving the indicator score.
     * Called after check() to provide actionable recommendations.
     *
     * @param IndicatorResult $result The result from check()
     * @return array<int, Suggestion> Array of suggestions
     */
    public function getSuggestions(IndicatorResult $result): array;
}
