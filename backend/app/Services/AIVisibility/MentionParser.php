<?php

namespace App\Services\AIVisibility;

/**
 * Service for extracting brand mentions from AI responses.
 *
 * This service parses AI-generated text to identify mentions of specific brands,
 * determines the type of mention (list, comparison, recommendation, neutral),
 * and extracts surrounding context for each mention.
 *
 * @see TASK-34-4
 * @see SPEC-034
 */
class MentionParser
{
    /**
     * Patterns for detecting mention types.
     */
    private const LIST_PATTERNS = [
        'top\s+\d+',
        'best',
        'including',
        'among\s+the',
    ];

    private const COMPARISON_PATTERNS = [
        '\bvs\.?\b',
        'compared\s+to',
        'better\s+than',
        'versus',
        'alternative\s+to',
    ];

    private const RECOMMENDATION_PATTERNS = [
        'i\s+recommend',
        'you\s+should\s+use',
        'i\s+suggest',
        'try',
        'highly\s+recommend',
        'recommend\s+using',
    ];

    /**
     * Extract brand mentions from AI response.
     *
     * @param string $response The AI response text
     * @param array $targetBrands Array of brand names to search for
     * @return array Array of mentions with: brand, position, context, type
     */
    public function extractMentions(string $response, array $targetBrands): array
    {
        if (empty($response) || empty($targetBrands)) {
            return [];
        }

        $mentions = [];

        foreach ($targetBrands as $brand) {
            if (empty($brand)) {
                continue;
            }

            // Find all positions of the brand in the response
            $positions = $this->findBrandPositions($response, $brand);

            foreach ($positions as $position) {
                // Extract context around the mention
                $context = $this->extractContext($response, $position, 100);

                // Detect mention type from context
                $type = $this->detectMentionType($context);

                $mentions[] = [
                    'brand' => $brand,
                    'position' => $position,
                    'context' => $context,
                    'type' => $type,
                ];
            }
        }

        // Sort mentions by position
        usort($mentions, function ($a, $b) {
            return $a['position'] <=> $b['position'];
        });

        return $mentions;
    }

    /**
     * Find all positions of a brand in text using word boundaries.
     *
     * @param string $text The text to search in
     * @param string $brand The brand to find
     * @return array Array of byte positions
     */
    private function findBrandPositions(string $text, string $brand): array
    {
        $positions = [];
        $textLength = strlen($text);
        $brandLength = strlen($brand);
        $brandLower = mb_strtolower($brand, 'UTF-8');

        // Search through the text character by character
        for ($i = 0; $i <= $textLength - $brandLength; $i++) {
            // Check if we have a potential match at this position
            $substring = substr($text, $i, $brandLength);
            
            if (mb_strtolower($substring, 'UTF-8') === $brandLower) {
                // Check word boundary before
                $beforeOk = ($i === 0) || !$this->isWordChar($text[$i - 1]);
                
                // Check word boundary after
                $afterPos = $i + $brandLength;
                $afterOk = ($afterPos >= $textLength) || !$this->isWordChar($text[$afterPos]);
                
                if ($beforeOk && $afterOk) {
                    $positions[] = $i;
                    // Skip past this match to avoid overlapping matches
                    $i = $afterPos - 1;
                }
            }
        }

        return $positions;
    }

    /**
     * Check if a character is a word character.
     *
     * @param string $char The character to check
     * @return bool
     */
    private function isWordChar(string $char): bool
    {
        // Check for ASCII word characters
        if (ctype_alnum($char) || $char === '_') {
            return true;
        }
        
        // Check for Unicode letters and numbers
        $ord = ord($char);
        if ($ord >= 128) {
            // For multibyte characters, check if it's a letter
            if (preg_match('/^[\p{L}\p{N}]$/u', $char)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Extract context around a position in text.
     *
     * @param string $text The full text
     * @param int $position The position to center around
     * @param int $radius Number of characters on each side (default: 100)
     * @return string The surrounding context
     */
    public function extractContext(string $text, int $position, int $radius = 100): string
    {
        if (empty($text)) {
            return '';
        }

        $textLength = strlen($text);

        // Handle negative or invalid positions
        if ($position < 0) {
            $position = 0;
        }

        // Handle position beyond text length
        if ($position > $textLength) {
            $position = $textLength;
        }

        // Calculate start and end positions
        $start = max(0, $position - $radius);
        $end = min($textLength, $position + $radius);

        // Extract the context
        $context = substr($text, $start, $end - $start);

        return $context;
    }

    /**
     * Detect the type of mention based on context.
     *
     * @param string $context The context around the mention
     * @return string One of: recommendation, comparison, mention, neutral
     */
    public function detectMentionType(string $context): string
    {
        if (empty($context)) {
            return 'neutral';
        }

        $contextLower = mb_strtolower($context, 'UTF-8');

        // Check for recommendation patterns (highest priority)
        foreach (self::RECOMMENDATION_PATTERNS as $pattern) {
            if (preg_match('/' . $pattern . '/i', $contextLower)) {
                return 'recommendation';
            }
        }

        // Check for comparison patterns
        foreach (self::COMPARISON_PATTERNS as $pattern) {
            if (preg_match('/' . $pattern . '/i', $contextLower)) {
                return 'comparison';
            }
        }

        // Check for list patterns - these are regular mentions in a list context
        foreach (self::LIST_PATTERNS as $pattern) {
            if (preg_match('/' . $pattern . '/i', $contextLower)) {
                return 'mention';
            }
        }

        return 'neutral';
    }

    /**
     * Extract competitor mentions from AI response.
     *
     * This method uses the same logic as extractMentions but is named
     * separately for semantic clarity when processing competitor lists.
     *
     * @param string $response The AI response text
     * @param array $competitors Array of competitor names to search for
     * @return array Array of competitor mentions with position and context
     */
    public function extractCompetitorMentions(string $response, array $competitors): array
    {
        if (empty($response) || empty($competitors)) {
            return [];
        }

        $mentions = [];

        foreach ($competitors as $competitor) {
            if (empty($competitor)) {
                continue;
            }

            // Find all positions of the competitor in the response
            $positions = $this->findBrandPositions($response, $competitor);

            foreach ($positions as $position) {
                // Extract context around the mention
                $context = $this->extractContext($response, $position, 100);

                $mentions[] = [
                    'brand' => $competitor,
                    'position' => $position,
                    'context' => $context,
                ];
            }
        }

        // Sort mentions by position
        usort($mentions, function ($a, $b) {
            return $a['position'] <=> $b['position'];
        });

        return $mentions;
    }
}
