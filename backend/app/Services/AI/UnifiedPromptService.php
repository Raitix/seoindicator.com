<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\UnifiedPromptServiceInterface;

/**
 * Service for building unified AI analysis prompts.
 *
 * Constructs consolidated prompts that request all SEO indicators
 * in a single AI call, reducing API costs and improving speed.
 *
 * @see TASK-31-4
 * @see TASK-31-7
 * @see SPEC-31
 */
class UnifiedPromptService implements UnifiedPromptServiceInterface
{
    /**
     * Token overhead for the system prompt and response schema.
     */
    private const TOKEN_OVERHEAD = 3400;

    /**
     * Build a consolidated prompt for unified AI analysis.
     *
     * @param string $url The URL being analyzed
     * @param string $html The HTML content to analyze
     * @param array $options Optional parameters
     * @return string The built prompt
     */
    public function buildPrompt(string $url, string $html, array $options = []): string
    {
        $systemPrompt = $this->getSystemPrompt();
        $userPrompt = $this->getUserPrompt($url, $html);

        return $systemPrompt . "\n\n" . $userPrompt;
    }

    /**
     * Get the estimated token overhead for the prompt.
     *
     * @return int Token overhead
     */
    public function getTokenOverhead(): int
    {
        return self::TOKEN_OVERHEAD;
    }

    /**
     * Get the system prompt for unified analysis.
     *
     * @return string The system prompt
     */
    private function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an SEO analysis expert. Analyze the provided HTML content and return a comprehensive SEO analysis in JSON format.

Your response must be valid JSON with the following structure:
{
    "overall_score": <integer 0-100>,
    "overall_summary": "<string 50-500 chars describing overall SEO health>",
    "category_scores": {
        "content": <integer 0-100>,
        "technical": <integer 0-100>,
        "accessibility": <integer 0-100>,
        "performance": <integer 0-100>
    },
    "indicators": [
        {
            "id": "<indicator_id>",
            "name": "<indicator name>",
            "score": <integer 0-3>,
            "passed": <boolean>,
            "details": {},
            "suggestions": ["<suggestion1>", "<suggestion2>"]
        }
    ],
    "top_priorities": ["<indicator_id1>", "<indicator_id2>"]
}

Analyze these 17 SEO indicators:
1. url - URL structure and readability
2. title_tag - Title tag presence and quality
3. meta_description - Meta description presence and quality
4. headings - H1-H6 heading structure
5. image_alt - Image alt text presence
6. links - Internal and external link quality
7. canonical - Canonical URL presence
8. robots_meta - Robots meta tag configuration
9. structured_data - Schema.org structured data
10. mobile_friendly - Mobile responsiveness indicators
11. page_speed - Performance indicators
12. content_quality - Content length and quality
13. keyword_usage - Keyword presence and density
14. social_tags - Open Graph and Twitter cards
15. security - HTTPS and security headers
16. accessibility - ARIA labels and accessibility
17. internal_linking - Internal link structure

Score each indicator:
- 0: Critical issues
- 1: Major improvements needed
- 2: Minor improvements possible
- 3: Fully optimized

PROMPT;
    }

    /**
     * Get the user prompt with URL and HTML content.
     *
     * @param string $url The URL being analyzed
     * @param string $html The HTML content
     * @return string The user prompt
     */
    private function getUserPrompt(string $url, string $html): string
    {
        return <<<PROMPT
Analyze the following webpage for SEO:

URL: {$url}

HTML Content:
```html
{$html}
```

Return ONLY valid JSON with no additional text.
PROMPT;
    }
}
