<?php

namespace App\Services\AI\Prompts;

/**
 * Prompt Template class for SEO indicator AI suggestions.
 *
 * Provides system prompts, user prompts, and expected JSON schemas
 * for all 10 SEO indicators. Templates are optimized for free AI models
 * with token-efficient system prompts (under 100 tokens).
 *
 * @package App\Services\AI\Prompts
 */
class PromptTemplate
{
    /**
     * Template definitions for all SEO indicators.
     *
     * Structure:
     * - system: System prompt (role/instructions for AI)
     * - user: User prompt template with {placeholder} variables
     * - schema: Expected JSON output structure
     */
    private const TEMPLATES = [
        'title' => [
            'system' => 'You are an SEO expert specializing in title tag optimization. Analyze titles for length, keyword placement, and click-through potential. Respond only with valid JSON.',
            'user' => 'Analyze this title tag for SEO:
Title: "{title}"
Page content: {content_summary}
Target keyword: {keyword}

Respond with JSON only:
{
  "score": <0-100>,
  "issues": ["<issue1>", "<issue2>"],
  "suggestions": [{"type": "improve|rewrite", "text": "<suggestion>", "reason": "<why>"}],
  "bestPractices": ["<tip1>", "<tip2>"]
}',
            'schema' => [
                'score' => 'integer 0-100',
                'issues' => 'array of strings',
                'suggestions' => 'array of {type, text, reason}',
                'bestPractices' => 'array of strings',
            ],
        ],
        'meta_description' => [
            'system' => 'You are an SEO expert for meta description optimization. Focus on length, keyword inclusion, CTA clarity, and click-through appeal. Respond only with valid JSON.',
            'user' => 'Analyze this meta description for SEO:
Description: "{description}"
Page content: {content_summary}
Target keyword: {keyword}

Include CTA/action words in suggestions. Respond with JSON only:
{
  "score": <0-100>,
  "issues": ["<issue1>"],
  "suggestions": [{"type": "improve|rewrite", "text": "<suggestion>", "reason": "<why>"}],
  "bestPractices": ["<tip1>"]
}',
            'schema' => [
                'score' => 'integer 0-100',
                'issues' => 'array of strings',
                'suggestions' => 'array of {type, text, reason}',
                'bestPractices' => 'array of strings',
            ],
        ],
        'robots' => [
            'system' => 'You are an SEO expert analyzing robots.txt files. Identify blocking issues, security risks, and optimization opportunities. Respond only with valid JSON.',
            'user' => 'Analyze this robots.txt file:
Content:
{robots_content}

Site URL: {site_url}

Respond with JSON only:
{
  "issues": ["<issue1>"],
  "suggestions": ["<suggestion1>"],
  "warnings": ["<warning1>"]
}',
            'schema' => [
                'issues' => 'array of strings',
                'suggestions' => 'array of strings',
                'warnings' => 'array of strings',
            ],
        ],
        'sitemap' => [
            'system' => 'You are an SEO expert for XML sitemap validation. Check structure, coverage, and indexing optimization. Respond only with valid JSON.',
            'user' => 'Analyze this sitemap:
URL: {sitemap_url}
Page count: {page_count}
Sample URLs: {sample_urls}

Respond with JSON only:
{
  "issues": ["<issue1>"],
  "suggestions": ["<suggestion1>"],
  "coverageAnalysis": {"indexed": <num>, "missing": <num>, "priority": ["<url1>"]}
}',
            'schema' => [
                'issues' => 'array of strings',
                'suggestions' => 'array of strings',
                'coverageAnalysis' => 'object with indexed, missing, priority',
            ],
        ],
        'headings' => [
            'system' => 'You are an SEO and accessibility expert analyzing heading structure (H1-H6). Focus on hierarchy, semantic meaning, and screen reader compatibility. Respond only with valid JSON.',
            'user' => 'Analyze this heading structure for SEO and accessibility:
{heading_structure}

Respond with JSON only:
{
  "score": <0-100>,
  "hierarchyIssues": ["<issue1>"],
  "suggestions": ["<suggestion1>"]
}',
            'schema' => [
                'score' => 'integer 0-100',
                'hierarchyIssues' => 'array of strings',
                'suggestions' => 'array of strings',
            ],
        ],
        'image_alt' => [
            'system' => 'You are an SEO expert for image alt text optimization. Identify missing alt attributes and provide descriptive suggestions for accessibility. Respond only with valid JSON.',
            'user' => 'Analyze these images for alt text:
{images}

Respond with JSON only:
{
  "missingAlts": ["<url1>"],
  "suggestionsPerImage": [{"url": "<url>", "altSuggestion": "<text>", "reason": "<why>"}],
  "score": <0-100>
}',
            'schema' => [
                'missingAlts' => 'array of URLs without alt',
                'suggestionsPerImage' => 'array of {url, altSuggestion, reason}',
                'score' => 'integer 0-100',
            ],
        ],
        'filenames' => [
            'system' => 'You are an SEO expert for filename optimization. Ensure lowercase, hyphen-separated, keyword-rich naming conventions. Respond only with valid JSON.',
            'user' => 'Analyze these filenames for SEO:
Files: {file_urls}
Types: {types}

Use kebab-case naming conventions. Respond with JSON only:
{
  "issues": ["<issue1>"],
  "suggestions": [{"current": "<name>", "suggested": "<new>", "reason": "<why>"}]
}',
            'schema' => [
                'issues' => 'array of strings',
                'suggestions' => 'array of {current, suggested, reason}',
            ],
        ],
        'url' => [
            'system' => 'You are an SEO expert for URL structure analysis. Focus on clean slugs, readability, keyword placement, and canonical issues. Respond only with valid JSON.',
            'user' => 'Analyze this URL for SEO:
URL: {url}
Page type: {page_type}
Target keyword: {keyword}

Ensure clean, readable slug structure. Respond with JSON only:
{
  "score": <0-100>,
  "issues": ["<issue1>"],
  "suggestions": [{"type": "slug|structure", "suggested": "<url>", "reason": "<why>"}]
}',
            'schema' => [
                'score' => 'integer 0-100',
                'issues' => 'array of strings',
                'suggestions' => 'array of {type, suggested, reason}',
            ],
        ],
        'links' => [
            'system' => 'You are an SEO expert for link analysis. Analyze internal, external, and broken links for link equity, crawlability, and user experience. Respond only with valid JSON.',
            'user' => 'Analyze these links for SEO:
Internal links: {internal_links}
External links: {external_links}
Broken links: {broken_links}

Respond with JSON only:
{
  "issues": ["<issue1>"],
  "suggestions": ["<suggestion1>"],
  "brokenLinkFixes": [{"url": "<broken>", "action": "remove|fix|redirect", "target": "<new>"}]
}',
            'schema' => [
                'issues' => 'array of strings',
                'suggestions' => 'array of strings',
                'brokenLinkFixes' => 'array of {url, action, target}',
            ],
        ],
        '404' => [
            'system' => 'You are an SEO expert for 404 error analysis. Focus on user experience, redirect recommendations, and crawl efficiency. Respond only with valid JSON.',
            'user' => 'Analyze these 404 errors for SEO and visitor experience:
404 URLs: {404_urls}
Referrers: {referrers}

Respond with JSON only:
{
  "causes": ["<cause1>"],
  "suggestions": ["<suggestion1>"],
  "redirectRecommendations": [{"from": "<404_url>", "to": "<target>", "type": "301|410"}]
}',
            'schema' => [
                'causes' => 'array of strings',
                'suggestions' => 'array of strings',
                'redirectRecommendations' => 'array of {from, to, type}',
            ],
        ],
    ];

    /**
     * Get the system prompt for an indicator.
     *
     * @param string $indicator The SEO indicator name
     * @return string The system prompt, or empty string if indicator not found
     */
    public function getSystemPrompt(string $indicator): string
    {
        return self::TEMPLATES[$indicator]['system'] ?? '';
    }

    /**
     * Get the user prompt for an indicator with context variables substituted.
     *
     * @param string $indicator The SEO indicator name
     * @param array $context Key-value pairs for template substitution
     * @return string The user prompt with variables substituted, or empty string if indicator not found
     */
    public function getUserPrompt(string $indicator, array $context): string
    {
        $template = self::TEMPLATES[$indicator]['user'] ?? '';
        
        if ($template === '') {
            return '';
        }
        
        return $this->substitute($template, $context);
    }

    /**
     * Get the expected JSON output schema for an indicator.
     *
     * @param string $indicator The SEO indicator name
     * @return array The schema definition, or empty array if indicator not found
     */
    public function getExpectedSchema(string $indicator): array
    {
        return self::TEMPLATES[$indicator]['schema'] ?? [];
    }

    /**
     * Substitute placeholder variables in a template string.
     *
     * Supports {placeholder} syntax. Null values are converted to empty strings.
     * Values are passed through without sanitization (AI prompts are not executed).
     *
     * @param string $template The template string with {placeholder} variables
     * @param array $context Key-value pairs for substitution
     * @return string The template with variables substituted
     */
    private function substitute(string $template, array $context): string
    {
        $result = $template;
        
        foreach ($context as $key => $value) {
            $placeholder = '{' . $key . '}';
            $replacement = $this->formatValue($value);
            $result = str_replace($placeholder, $replacement, $result);
        }
        
        return $result;
    }

    /**
     * Format a value for template substitution.
     *
     * @param mixed $value The value to format
     * @return string The formatted value
     */
    private function formatValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        
        if (is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        
        return (string) $value;
    }
}
