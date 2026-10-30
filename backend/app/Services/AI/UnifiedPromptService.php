<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\UnifiedPromptServiceInterface;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

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
     * Cached indicator definitions.
     */
    private ?array $indicatorsCache = null;

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
        $pageInfo = $this->extractPageInfo($html);

        return Str::replace([
            '{url}',
            '{title}',
            '{meta_description}',
            '{indicators}',
            '{html_content}',
        ], [
            $url,
            $pageInfo['title'] ?? 'Unknown',
            $pageInfo['meta_description'] ?? 'Not set',
            $this->formatIndicatorList(),
            $html,
        ], $this->getPromptTemplate());
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
     * Get the prompt template.
     *
     * @return string The prompt template with placeholders
     */
    public function getPromptTemplate(): string
    {
        return config('ai-prompts.prompt_template', $this->getDefaultTemplate());
    }

    /**
     * Get all 17 indicator definitions.
     *
     * @return array Array of indicator definitions with id, name, weight, category, checks
     */
    public function getIndicatorDefinitions(): array
    {
        if ($this->indicatorsCache !== null) {
            return $this->indicatorsCache;
        }

        $this->indicatorsCache = config('ai-prompts.indicators', $this->getDefaultIndicators());

        return $this->indicatorsCache;
    }

    /**
     * Get the JSON schema for the expected AI response.
     *
     * @return array The JSON schema definition
     */
    public function getJsonSchema(): array
    {
        return [
            'overall_score' => [
                'type' => 'integer',
                'min' => 0,
                'max' => 100,
                'description' => 'Overall SEO score from 0-100',
            ],
            'overall_summary' => [
                'type' => 'string',
                'min_length' => 50,
                'max_length' => 500,
                'description' => 'Executive summary of SEO health',
            ],
            'category_scores' => [
                'type' => 'object',
                'description' => 'Scores broken down by category',
                'properties' => [
                    'content' => ['type' => 'integer', 'min' => 0, 'max' => 100],
                    'technical' => ['type' => 'integer', 'min' => 0, 'max' => 100],
                    'accessibility' => ['type' => 'integer', 'min' => 0, 'max' => 100],
                    'performance' => ['type' => 'integer', 'min' => 0, 'max' => 100],
                ],
            ],
            'indicators' => [
                'type' => 'array',
                'description' => 'Array of all 17 indicator results',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'string'],
                        'name' => ['type' => 'string'],
                        'score' => ['type' => 'integer', 'min' => 0, 'max' => 3],
                        'passed' => ['type' => 'boolean'],
                        'details' => ['type' => 'object'],
                        'suggestions' => ['type' => 'array', 'items' => ['type' => 'string']],
                    ],
                ],
            ],
            'top_priorities' => [
                'type' => 'array',
                'description' => 'Top 3 priority indicators needing attention',
                'items' => ['type' => 'string'],
                'max_items' => 3,
            ],
        ];
    }

    /**
     * Extract page information from HTML.
     *
     * @param string $html The HTML content
     * @return array Extracted page info (title, meta_description)
     */
    private function extractPageInfo(string $html): array
    {
        $info = [
            'title' => null,
            'meta_description' => null,
        ];

        if (empty($html)) {
            return $info;
        }

        try {
            $crawler = new Crawler($html);

            // Extract title
            $titleNode = $crawler->filter('title');
            if ($titleNode->count() > 0) {
                $info['title'] = trim($titleNode->text(''));
            }

            // Extract meta description
            $metaNode = $crawler->filter('meta[name="description"]');
            if ($metaNode->count() > 0) {
                $info['meta_description'] = trim($metaNode->attr('content') ?? '');
            }
        } catch (\Exception $e) {
            // If parsing fails, return defaults
        }

        return $info;
    }

    /**
     * Format the indicator list for the prompt.
     *
     * @return string Formatted indicator list
     */
    private function formatIndicatorList(): string
    {
        $indicators = $this->getIndicatorDefinitions();
        $output = '';

        foreach ($indicators as $index => $indicator) {
            $output .= sprintf(
                "%d. **%s** (weight: %.2f)\n   - Check: %s\n\n",
                $index + 1,
                $indicator['name'],
                $indicator['weight'],
                $indicator['checks']
            );
        }

        return $output;
    }

    /**
     * Get the default prompt template if config is missing.
     *
     * @return string Default template
     */
    private function getDefaultTemplate(): string
    {
        return <<<'MARKDOWN'
# SEO Analysis Request

Analyze the following webpage for SEO quality. Return a JSON response with ALL indicator scores and an overall summary.

## Page Information
- URL: {url}
- Title: {title}
- Meta Description: {meta_description}

## HTML Content
```html
{html_content}
```

## Analysis Requirements

Score each indicator from 0-3 stars:
- 0: Critical issues
- 1: Major improvements needed
- 2: Minor improvements possible
- 3: Fully optimized

### Indicators to Analyze:

{indicators}

### Response Format (STRICT JSON)

```json
{
  "overall_score": 0-100,
  "overall_summary": "Executive summary",
  "category_scores": { "content": 0-100, "technical": 0-100, "accessibility": 0-100, "performance": 0-100 },
  "indicators": [{ "id": "...", "name": "...", "score": 0-3, "passed": true/false, "details": {}, "suggestions": [] }],
  "top_priorities": ["id1", "id2", "id3"]
}
```

Return ONLY valid JSON.
MARKDOWN;
    }

    /**
     * Get default indicators if config is missing.
     *
     * @return array Default indicators
     */
    private function getDefaultIndicators(): array
    {
        return [
            ['id' => 'url', 'name' => 'URL Structure', 'weight' => 0.08, 'category' => 'Technical', 'checks' => 'HTTPS, length, keywords, readability'],
            ['id' => 'title_tag', 'name' => 'Title Tag', 'weight' => 0.10, 'category' => 'Content', 'checks' => 'Presence, length (50-60 chars), keywords'],
            ['id' => 'meta_description', 'name' => 'Meta Description', 'weight' => 0.08, 'category' => 'Content', 'checks' => 'Presence, length (150-160 chars), keywords'],
            ['id' => 'robots_txt', 'name' => 'Robots.txt', 'weight' => 0.05, 'category' => 'Technical', 'checks' => 'File exists, proper directives'],
            ['id' => 'sitemap_xml', 'name' => 'XML Sitemap', 'weight' => 0.05, 'category' => 'Technical', 'checks' => 'File exists, properly formatted'],
            ['id' => 'h1_headings', 'name' => 'H1 Headings', 'weight' => 0.10, 'category' => 'Content', 'checks' => 'Single H1, contains keywords'],
            ['id' => 'filenames', 'name' => 'SEO Filenames', 'weight' => 0.04, 'category' => 'Content', 'checks' => 'Descriptive names, hyphens, lowercase'],
            ['id' => 'image_alt', 'name' => 'Image Alt Text', 'weight' => 0.08, 'category' => 'Accessibility', 'checks' => 'All images have alt text'],
            ['id' => 'links', 'name' => 'Internal/External Links', 'weight' => 0.08, 'category' => 'Content', 'checks' => 'Internal linking, external link quality'],
            ['id' => '404_page', 'name' => '404 Page', 'weight' => 0.04, 'category' => 'Technical', 'checks' => 'Custom 404 page exists'],
            ['id' => 'mobile_friendly', 'name' => 'Mobile-Friendliness', 'weight' => 0.08, 'category' => 'Technical', 'checks' => 'Responsive design, viewport meta'],
            ['id' => 'language', 'name' => 'Language Declaration', 'weight' => 0.03, 'category' => 'Technical', 'checks' => 'HTML lang attribute present'],
            ['id' => 'favicon', 'name' => 'Favicon', 'weight' => 0.02, 'category' => 'Technical', 'checks' => 'Favicon exists, proper format'],
            ['id' => 'canonical', 'name' => 'Canonical URL', 'weight' => 0.05, 'category' => 'Technical', 'checks' => 'Canonical tag present, correct URL'],
            ['id' => 'social_meta', 'name' => 'Social Meta Tags', 'weight' => 0.05, 'category' => 'Content', 'checks' => 'Open Graph, Twitter Card tags'],
            ['id' => 'aria', 'name' => 'ARIA Labels', 'weight' => 0.05, 'category' => 'Accessibility', 'checks' => 'ARIA labels on interactive elements'],
            ['id' => 'page_speed', 'name' => 'Page Speed', 'weight' => 0.02, 'category' => 'Performance', 'checks' => 'Load time, optimized assets'],
        ];
    }
}
