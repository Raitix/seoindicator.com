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
        'top \d+',
        'best',
        'including',
        'among the',
    ];

    private const COMPARISON_PATTERNS = [
        '\bvs\.?\b',
        'compared to',
        'better than',
        'versus',
        'alternative to',
    ];

    private const RECOMMENDATION_PATTERNS = [
        'i recommend',
        'you should use',
        'i suggest',
        'try',
        'highly recommend',
        'recommend using',
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

            // Escape special regex characters in brand name
            $escapedBrand = preg_quote($brand, '/');

            // Build regex pattern with word boundaries
            // Use Unicode word boundaries when possible
            $pattern = '/\b' . $escapedBrand . '\b/iu';

            // Find all matches with positions
            if (preg_match_all($pattern, $response, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $matchedText = $match[0];
                    $position = $match[1];

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
        }

        // Sort mentions by position
        usort($mentions, function ($a, $b) {
            return $a['position'] <=> $b['position'];
        });

        return $mentions;
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
     * @return string One of: list, comparison, recommendation, neutral
     */
    public function detectMentionType(string $context): string
    {
        if (empty($context)) {
            return 'neutral';
        }

        $contextLower = mb_strtolower($context, 'UTF-8');

        // Check for list patterns (highest priority)
        foreach (self::LIST_PATTERNS as $pattern) {
            if (preg_match('/' . $pattern . '/i', $contextLower)) {
                return 'list';
            }
        }

        // Check for comparison patterns
        foreach (self::COMPARISON_PATTERNS as $pattern) {
            if (preg_match('/' . $pattern . '/i', $contextLower)) {
                return 'comparison';
            }
        }

        // Check for recommendation patterns
        foreach (self::RECOMMENDATION_PATTERNS as $pattern) {
            if (preg_match('/' . $pattern . '/i', $contextLower)) {
                return 'recommendation';
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

            // Escape special regex characters in competitor name
            $escapedCompetitor = preg_quote($competitor, '/');

            // Build regex pattern with word boundaries
            $pattern = '/\b' . $escapedCompetitor . '\b/iu';

            // Find all matches with positions
            if (preg_match_all($pattern, $response, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $matchedText = $match[0];
                    $position = $match[1];

                    // Extract context around the mention
                    $context = $this->extractContext($response, $position, 100);

                    $mentions[] = [
                        'brand' => $competitor,
                        'position' => $position,
                        'context' => $context,
                    ];
                }
            }
        }

        // Sort mentions by position
        usort($mentions, function ($a, $b) {
            return $a['position'] <=> $b['position'];
        });

        return $mentions;
    }
}
