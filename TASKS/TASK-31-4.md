# TASK-31-4: UnifiedPromptService for Prompt Building

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Specification
SPEC-31: Unified AI Prompt + Context Management

## Description
Create the `UnifiedPromptService` that builds a consolidated prompt containing all 17 SEO indicators in a single request. The prompt includes analysis requirements, JSON schema specification, and HTML content.

## Component Location
- **Service:** `backend/app/Services/AI/UnifiedPromptService.php`
- **Config:** `backend/config/ai-prompts.php`
- **Tests:** `backend/tests/Unit/Services/AI/UnifiedPromptServiceTest.php`

## Core Functionalities

| Method | Description |
|--------|-------------|
| `buildPrompt(string $url, string $html, array $options): string` | Build complete unified prompt |
| `getPromptTemplate(): string` | Return the prompt template |
| `getIndicatorDefinitions(): array` | Return all 17 indicator definitions |
| `getJsonSchema(): array` | Return the expected response schema |

## Prompt Structure

```markdown
# SEO Analysis Request

Analyze the following webpage for SEO quality. Return a JSON response with ALL indicator scores and an overall summary.

## Page Information
- URL: {url}
- Title: {title}
- Meta Description: {meta_description}

## HTML Content
```html
{truncated_html}
```

## Analysis Requirements

Score each indicator from 1-3 stars and provide specific suggestions:

### Indicators to Analyze:
1. **URL Structure** (weight: 0.08)
   - Check: HTTPS, length, keywords, readability
   
2. **Title Tag** (weight: 0.10)
   - Check: Presence, length (50-60 chars), keywords

[... all 17 indicators listed with criteria ...]

### Response Format (STRICT JSON)

```json
{
  "overall_score": 0-100,
  "overall_summary": "2-3 sentence executive summary",
  "category_scores": { "content": 0-100, "technical": 0-100, ... },
  "indicators": [{ "id": "...", "score": 1-3, "passed": bool, ... }],
  "top_priorities": [{ "indicator": "...", "message": "..." }]
}
```
```

## Indicator Definitions (17 Total)

| ID | Name | Weight | Category |
|----|------|--------|----------|
| `url` | URL Structure | 0.08 | Technical |
| `title_tag` | Title Tag | 0.10 | Content |
| `meta_description` | Meta Description | 0.08 | Content |
| `robots_txt` | Robots.txt | 0.05 | Technical |
| `sitemap_xml` | XML Sitemap | 0.05 | Technical |
| `h1_headings` | H1 Headings | 0.10 | Content |
| `filenames` | SEO Filenames | 0.04 | Content |
| `image_alt` | Image Alt Text | 0.08 | Accessibility |
| `links` | Internal/External Links | 0.08 | Content |
| `404_page` | 404 Page | 0.04 | Technical |
| `mobile_friendly` | Mobile-Friendliness | 0.08 | Technical |
| `language` | Language Declaration | 0.03 | Technical |
| `favicon` | Favicon | 0.02 | Technical |
| `canonical` | Canonical URL | 0.05 | Technical |
| `social_meta` | Social Meta Tags | 0.05 | Content |
| `aria` | ARIA Labels | 0.05 | Accessibility |
| `page_speed` | Page Speed | 0.02 | Performance |

## Implementation Details

```php
class UnifiedPromptService
{
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
    
    private function extractPageInfo(string $html): array
    {
        // Use DOM crawler to extract title and meta description
        $crawler = new Crawler($html);
        
        return [
            'title' => $crawler->filter('title')->text(''),
            'meta_description' => $crawler->filter('meta[name="description"]')->attr('content') ?? '',
        ];
    }
    
    private function formatIndicatorList(): string
    {
        $indicators = config('ai-prompts.indicators');
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
}
```

## Acceptance Criteria
- [ ] Prompt contains all 17 indicator definitions
- [ ] JSON schema included in prompt
- [ ] Page info extracted correctly (title, meta)
- [ ] Indicator weights match specification
- [ ] Prompt template is configurable
- [ ] HTML content properly escaped

## Dependencies
- None (can run in parallel with 31-2 and 31-3)

## Effort Estimate
3 hours

## Comments
_Agent log_
