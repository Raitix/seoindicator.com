<?php

namespace App\Services;

/**
 * Calculates opportunity scores for suggested keywords.
 *
 * The scoring algorithm helps users prioritize which keywords to track
 * based on potential value indicators:
 * - Long-tail keywords (3+ words): +10, 5+ words: additional +5
 * - Question prefixes (what, how, why, etc.): +15
 * - Commercial intent (best, top, review, compare): +10
 *
 * Base score: 50
 * Max score: 100
 *
 * @see TASK-5-3
 * @see SPEC-005
 */
class OpportunityScorer
{
    /**
     * Base score for all keywords.
     */
    protected const BASE_SCORE = 50;

    /**
     * Maximum allowed score.
     */
    protected const MAX_SCORE = 100;

    /**
     * Bonus for long-tail keywords (3+ words).
     */
    protected const LONG_TAIL_BONUS = 10;

    /**
     * Additional bonus for very long keywords (5+ words).
     */
    protected const VERY_LONG_TAIL_BONUS = 5;

    /**
     * Bonus for question prefix keywords.
     */
    protected const QUESTION_BONUS = 15;

    /**
     * Bonus for commercial intent keywords.
     */
    protected const COMMERCIAL_BONUS = 10;

    /**
     * Question prefixes that trigger bonus.
     */
    protected const QUESTION_PREFIXES = [
        'what is',
        'how to',
        'why',
        'when',
        'where',
        'who',
        'which',
    ];

    /**
     * Commercial intent keywords that trigger bonus.
     */
    protected const COMMERCIAL_KEYWORDS = [
        'best',
        'top',
        'review',
        'reviews',
        'compare',
        'comparison',
        'vs',
        'versus',
    ];

    /**
     * Calculate the opportunity score for a keyword.
     *
     * @param string $keyword
     * @return int Score between 50-100
     */
    public function calculate(string $keyword): int
    {
        // Normalize keyword
        $keyword = trim($keyword);
        $keywordLower = strtolower($keyword);

        // Handle empty or whitespace-only keyword
        if (empty($keywordLower)) {
            return self::BASE_SCORE;
        }

        // Start with base score
        $score = self::BASE_SCORE;

        // Apply long-tail bonus
        $wordCount = $this->countWords($keyword);
        if ($wordCount >= 3) {
            $score += self::LONG_TAIL_BONUS;
        }
        if ($wordCount >= 5) {
            $score += self::VERY_LONG_TAIL_BONUS;
        }

        // Apply question prefix bonus
        if ($this->hasQuestionPrefix($keywordLower)) {
            $score += self::QUESTION_BONUS;
        }

        // Apply commercial intent bonus
        if ($this->hasCommercialIntent($keywordLower)) {
            $score += self::COMMERCIAL_BONUS;
        }

        // Cap at maximum score
        return min($score, self::MAX_SCORE);
    }

    /**
     * Make the scorer invokable.
     *
     * @param string $keyword
     * @return int
     */
    public function __invoke(string $keyword): int
    {
        return $this->calculate($keyword);
    }

    /**
     * Count words in a keyword string.
     *
     * Handles multiple spaces between words.
     *
     * @param string $keyword
     * @return int
     */
    protected function countWords(string $keyword): int
    {
        // Normalize multiple spaces and trim
        $normalized = preg_replace('/\s+/', ' ', trim($keyword));
        
        if (empty($normalized)) {
            return 0;
        }

        return count(explode(' ', $normalized));
    }

    /**
     * Check if keyword starts with a question prefix.
     *
     * @param string $keywordLower Lowercase keyword
     * @return bool
     */
    protected function hasQuestionPrefix(string $keywordLower): bool
    {
        foreach (self::QUESTION_PREFIXES as $prefix) {
            if (str_starts_with($keywordLower, $prefix . ' ') || $keywordLower === $prefix) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if keyword contains commercial intent terms.
     *
     * @param string $keywordLower Lowercase keyword
     * @return bool
     */
    protected function hasCommercialIntent(string $keywordLower): bool
    {
        foreach (self::COMMERCIAL_KEYWORDS as $term) {
            // Check for whole word match (not part of another word)
            if (preg_match('/\b' . preg_quote($term, '/') . '\b/', $keywordLower)) {
                return true;
            }
        }
        return false;
    }
}
