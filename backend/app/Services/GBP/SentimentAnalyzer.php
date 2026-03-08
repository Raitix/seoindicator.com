<?php

namespace App\Services\GBP;

/**
 * Sentiment Analyzer using keyword-based approach.
 *
 * Analyzes text sentiment by counting positive and negative keywords
 * with support for negation detection.
 *
 * @see TASK-35-10
 * @see SPEC-035
 */
class SentimentAnalyzer
{
    /**
     * Positive sentiment keywords (50+ words).
     */
    private const POSITIVE_KEYWORDS = [
        'excellent', 'great', 'best', 'amazing', 'wonderful', 'fantastic', 'awesome',
        'superb', 'outstanding', 'perfect', 'love', 'loved', 'happy', 'pleased',
        'satisfied', 'impressed', 'recommend', 'recommended', 'helpful', 'friendly',
        'professional', 'fast', 'quick', 'easy', 'smooth', 'seamless', 'beautiful',
        'clean', 'comfortable', 'reliable', 'trustworthy', 'quality', 'value',
        'worth', 'affordable', 'reasonable', 'efficient', 'effective', 'responsive',
        'attentive', 'knowledgeable', 'skilled', 'expert', 'exceptional', 'brilliant',
        'marvelous', 'terrific', 'splendid', 'delightful', 'superior', 'incredible',
        'good', 'nice', 'fine', 'positive', 'excellent', 'super', 'top', 'leading',
        'innovative', 'creative', 'modern', 'updated', 'improved', 'better', 'best',
        // Spanish positive words
        'excelente', 'genial', 'mejor', 'increible', 'maravilloso', 'fantastico',
        'bueno', 'buena', 'perfecto', 'perfecta', 'encanta', 'encanto', 'feliz',
        'satisfecho', 'satisfecha', 'recomiendo', 'recomendado', 'util', 'amable',
        'profesional', 'rapido', 'rapida', 'facil', 'hermoso', 'hermosa', 'limpio',
        'limpia', 'confiable', 'calidad', 'valor', 'eficiente', 'eficaz',
    ];

    /**
     * Negative sentiment keywords (50+ words).
     */
    private const NEGATIVE_KEYWORDS = [
        'terrible', 'bad', 'worst', 'horrible', 'awful', 'poor', 'disappointed',
        'disappointing', 'frustrating', 'waste', 'wasted', 'useless', 'broken',
        'damaged', 'defective', 'slow', 'unresponsive', 'rude', 'unprofessional',
        'overpriced', 'expensive', 'cheap', 'unreliable', 'incompetent', 'unacceptable',
        'annoying', 'irritating', 'misleading', 'dishonest', 'scam', 'fraud', 'fake',
        'lied', 'failed', 'failure', 'regret', 'regretted', 'avoid', 'warned',
        'warning', 'dreadful', 'pathetic', 'mediocre', 'subpar', 'lacking', 'missing',
        'inaccurate', 'wrong', 'horrid', 'atrocious', 'abysmal',
        'error', 'errors', 'bug', 'bugs', 'crash', 'crashed', 'hate',
        'hated', 'dislike', 'difficult', 'confusing', 'complicated',
    ];

    /**
     * Negation words that flip sentiment.
     */
    private const NEGATION_WORDS = [
        'not', 'no', 'never', 'none', 'nothing', 'neither', 'nor', 'barely', 'hardly', 'scarcely',
        "doesn't", "don't", "didn't", "won't", "wouldn't", "can't", "cannot", "couldn't",
    ];

    /**
     * Words that are negative but become positive when negated.
     * E.g., "no problem", "no issues" = positive.
     */
    private const NEGATABLE_NEGATIVE_WORDS = [
        'problem', 'problems', 'issues', 'issue', 'trouble', 'complaint', 'complaints',
        'difficulty', 'difficulties', 'hassle', 'hassles', 'concern', 'concerns',
    ];

    /**
     * Analyze the sentiment of a text.
     *
     * @param string $text The text to analyze
     * @return string 'positive', 'negative', or 'neutral'
     */
    public function analyzeSentiment(string $text): string
    {
        // Handle empty or whitespace-only text
        $text = trim($text);
        if ($text === '') {
            return 'neutral';
        }

        // Normalize text: lowercase, strip HTML tags
        $text = strtolower(strip_tags($text));

        // Tokenize into words
        $words = $this->tokenize($text);

        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($words as $index => $word) {
            // Check for positive keywords
            if (in_array($word, self::POSITIVE_KEYWORDS, true)) {
                // Check for negation in previous 2 words
                if ($this->hasNegation($words, $index)) {
                    $negativeCount++;
                } else {
                    $positiveCount++;
                }
            }

            // Check for negative keywords
            if (in_array($word, self::NEGATIVE_KEYWORDS, true)) {
                // Check for negation in previous 2 words
                if ($this->hasNegation($words, $index)) {
                    $positiveCount++;
                } else {
                    $negativeCount++;
                }
            }

            // Check for negatable negative words (problem, issues, etc.)
            // These are negative UNLESS negated, then they become positive
            if (in_array($word, self::NEGATABLE_NEGATIVE_WORDS, true)) {
                if ($this->hasNegation($words, $index)) {
                    $positiveCount++;
                }
                // If not negated, these are neutral (not counted as negative)
            }
        }

        // Determine sentiment based on counts
        return $this->determineSentiment($positiveCount, $negativeCount);
    }

    /**
     * Tokenize text into words.
     *
     * @param string $text
     * @return array<int, string>
     */
    private function tokenize(string $text): array
    {
        // Remove punctuation and split into words
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        return $words ?: [];
    }

    /**
     * Check if there's a negation word within 3 positions before the current word.
     *
     * @param array<int, string> $words
     * @param int $currentIndex
     * @return bool
     */
    private function hasNegation(array $words, int $currentIndex): bool
    {
        // Check previous 3 words for negation (extended for "never had a problem")
        for ($i = max(0, $currentIndex - 3); $i < $currentIndex; $i++) {
            if (isset($words[$i]) && in_array($words[$i], self::NEGATION_WORDS, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine final sentiment from positive and negative counts.
     *
     * @param int $positiveCount
     * @param int $negativeCount
     * @return string
     */
    private function determineSentiment(int $positiveCount, int $negativeCount): string
    {
        if ($positiveCount > $negativeCount) {
            return 'positive';
        }

        if ($negativeCount > $positiveCount) {
            return 'negative';
        }

        return 'neutral';
    }
}
