# TASK-21-4: Create Prompt Templates for All Indicators

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Specification
[SPEC-021](../SPECIFICATIONS/SPEC-021.md) - AI Suggestions via OpenRouter/Vercel Gateway

## Description
Create comprehensive prompt templates for all 10 SEO indicators. Each template includes system instructions, user prompt structure, and expected JSON output schema. Templates must be optimized for free AI models with clear, concise instructions.

---

## Acceptance Criteria

### AC-1: PromptTemplate Class Created
- [ ] File exists at `backend/app/Services/AI/Prompts/PromptTemplate.php`
- [ ] Method `getSystemPrompt(string $indicator): string`
- [ ] Method `getUserPrompt(string $indicator, array $context): string`
- [ ] Method `getExpectedSchema(string $indicator): array`
- [ ] Template variable substitution support

### AC-2: Title Indicator Prompt
- [ ] System prompt defines SEO expert role
- [ ] User prompt accepts: title, content_summary, keyword
- [ ] Expected output: score (0-100), issues[], suggestions[], best_practices[]
- [ ] Optimized for token efficiency

### AC-3: Meta Description Indicator Prompt
- [ ] System prompt for meta description optimization
- [ ] User prompt accepts: description, content_summary, keyword
- [ ] Expected output: score, issues[], suggestions[], best_practices[]
- [ ] Includes CTA guidance

### AC-4: Robots.txt Indicator Prompt
- [ ] System prompt for robots.txt analysis
- [ ] User prompt accepts: robots_content, site_url
- [ ] Expected output: issues[], suggestions[], warnings[]
- [ ] Directive explanations

### AC-5: Sitemap Indicator Prompt
- [ ] System prompt for sitemap validation
- [ ] User prompt accepts: sitemap_url, page_count, sample_urls
- [ ] Expected output: issues[], suggestions[], coverage_analysis
- [ ] XML structure guidance

### AC-6: Headings Indicator Prompt
- [ ] System prompt for heading structure analysis
- [ ] User prompt accepts: heading_structure (H1-H6)
- [ ] Expected output: score, hierarchy_issues[], suggestions[]
- [ ] Accessibility considerations

### AC-7: Image Alt Text Indicator Prompt
- [ ] System prompt for alt text analysis
- [ ] User prompt accepts: images[] (url, alt, context)
- [ ] Expected output: missing_alts[], suggestions_per_image[]
- [ ] Descriptive guidance

### AC-8: Filenames Indicator Prompt
- [ ] System prompt for filename SEO
- [ ] User prompt accepts: file_urls[], types[]
- [ ] Expected output: issues[], suggestions[]
- [ ] Naming convention patterns

### AC-9: URL Indicator Prompt
- [ ] System prompt for URL structure analysis
- [ ] User prompt accepts: url, page_type, keyword
- [ ] Expected output: score, issues[], suggestions[]
- [ ] Slug optimization guidance

### AC-10: Links Indicator Prompt
- [ ] System prompt for link analysis
- [ ] User prompt accepts: internal_links[], external_links[], broken_links[]
- [ ] Expected output: issues[], suggestions[], broken_link_fixes[]
- [ ] Link equity guidance

### AC-11: 404 Indicator Prompt
- [ ] System prompt for 404 error analysis
- [ ] User prompt accepts: 404_urls[], referrers[]
- [ ] Expected output: causes[], suggestions[], redirect_recommendations[]
- [ ] User experience considerations

### AC-12: Unit Tests
- [ ] Test file at `backend/tests/Unit/Services/AI/Prompts/PromptTemplateTest.php`
- [ ] Tests for each indicator template
- [ ] Tests for variable substitution
- [ ] Tests for output schema validation
- [ ] All tests passing

---

## Dependencies
- TASK-21-1 (AI Service Structure and Interfaces)

---

## Effort Estimate
**Medium** (5-6 hours)

- Template class: 1 hour
- 10 indicator prompts: 3 hours
- Tests: 2 hours

---

## Technical Notes

### Prompt Template Structure
```php
// backend/app/Services/AI/Prompts/PromptTemplate.php
class PromptTemplate
{
    private const TEMPLATES = [
        'title' => [
            'system' => 'You are an SEO expert specializing in title tag optimization...',
            'user' => 'Analyze this title tag for SEO: "{title}"
                Page content summary: {content_summary}
                Target keyword: {keyword}
                
                Respond with JSON only:',
            'schema' => [
                'score' => 'integer 0-100',
                'issues' => 'array of strings',
                'suggestions' => 'array of {type, text, reason}',
                'best_practices' => 'array of strings',
            ],
        ],
        // ... other indicators
    ];
    
    public function getSystemPrompt(string $indicator): string
    {
        return self::TEMPLATES[$indicator]['system'] ?? '';
    }
    
    public function getUserPrompt(string $indicator, array $context): string
    {
        $template = self::TEMPLATES[$indicator]['user'] ?? '';
        return $this->substitute($template, $context);
    }
}
```

### Token Efficiency Guidelines
1. Keep system prompts under 100 tokens
2. Use JSON-only response requirement
3. Avoid examples in prompts (use schema instead)
4. Use placeholder variables for dynamic content

---

## Comments
_Agent log_

(No entries yet)

---
*Created by SpecSystemAnalyst on 2026-03-01*
