<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SEO Indicator Definitions
    |--------------------------------------------------------------------------
    |
    | Defines all 17 SEO indicators with their weights, categories, and
    | check criteria for the unified AI analysis prompt.
    |
    */

    'indicators' => [
        [
            'id' => 'url',
            'name' => 'URL Structure',
            'weight' => 0.08,
            'category' => 'Technical',
            'checks' => 'HTTPS protocol, URL length, keywords in URL, readability, no special characters',
        ],
        [
            'id' => 'title_tag',
            'name' => 'Title Tag',
            'weight' => 0.10,
            'category' => 'Content',
            'checks' => 'Presence, length (50-60 chars), keywords placement, uniqueness',
        ],
        [
            'id' => 'meta_description',
            'name' => 'Meta Description',
            'weight' => 0.08,
            'category' => 'Content',
            'checks' => 'Presence, length (150-160 chars), keywords, compelling copy',
        ],
        [
            'id' => 'robots_txt',
            'name' => 'Robots.txt',
            'weight' => 0.05,
            'category' => 'Technical',
            'checks' => 'File exists, proper directives, not blocking important pages',
        ],
        [
            'id' => 'sitemap_xml',
            'name' => 'XML Sitemap',
            'weight' => 0.05,
            'category' => 'Technical',
            'checks' => 'File exists, properly formatted, includes all important pages',
        ],
        [
            'id' => 'h1_headings',
            'name' => 'H1 Headings',
            'weight' => 0.10,
            'category' => 'Content',
            'checks' => 'Single H1 per page, contains keywords, describes page content',
        ],
        [
            'id' => 'filenames',
            'name' => 'SEO Filenames',
            'weight' => 0.04,
            'category' => 'Content',
            'checks' => 'Descriptive names, hyphens as separators, lowercase, keywords',
        ],
        [
            'id' => 'image_alt',
            'name' => 'Image Alt Text',
            'weight' => 0.08,
            'category' => 'Accessibility',
            'checks' => 'All images have alt text, descriptive content, keywords where relevant',
        ],
        [
            'id' => 'links',
            'name' => 'Internal/External Links',
            'weight' => 0.08,
            'category' => 'Content',
            'checks' => 'Internal linking structure, external link quality, no broken links',
        ],
        [
            'id' => '404_page',
            'name' => '404 Page',
            'weight' => 0.04,
            'category' => 'Technical',
            'checks' => 'Custom 404 page exists, helpful navigation, matches site design',
        ],
        [
            'id' => 'mobile_friendly',
            'name' => 'Mobile-Friendliness',
            'weight' => 0.08,
            'category' => 'Technical',
            'checks' => 'Responsive design, viewport meta tag, touch-friendly elements',
        ],
        [
            'id' => 'language',
            'name' => 'Language Declaration',
            'weight' => 0.03,
            'category' => 'Technical',
            'checks' => 'HTML lang attribute present, correct language code',
        ],
        [
            'id' => 'favicon',
            'name' => 'Favicon',
            'weight' => 0.02,
            'category' => 'Technical',
            'checks' => 'Favicon exists, proper format (ICO/PNG/SVG), multiple sizes',
        ],
        [
            'id' => 'canonical',
            'name' => 'Canonical URL',
            'weight' => 0.05,
            'category' => 'Technical',
            'checks' => 'Canonical tag present, correct URL, prevents duplicate content',
        ],
        [
            'id' => 'social_meta',
            'name' => 'Social Meta Tags',
            'weight' => 0.05,
            'category' => 'Content',
            'checks' => 'Open Graph tags, Twitter Card tags, proper images and descriptions',
        ],
        [
            'id' => 'aria',
            'name' => 'ARIA Labels',
            'weight' => 0.05,
            'category' => 'Accessibility',
            'checks' => 'ARIA labels on interactive elements, proper roles, screen reader support',
        ],
        [
            'id' => 'page_speed',
            'name' => 'Page Speed',
            'weight' => 0.02,
            'category' => 'Performance',
            'checks' => 'Load time, optimized images, minified CSS/JS, caching headers',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Prompt Template
    |--------------------------------------------------------------------------
    |
    | The template used to build the unified AI analysis prompt.
    | Placeholders: {url}, {title}, {meta_description}, {indicators}, {html_content}
    |
    */

    'prompt_template' => <<<'MARKDOWN'
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
- 0: Critical issues (severe problems that significantly hurt SEO)
- 1: Major improvements needed (noticeable issues)
- 2: Minor improvements possible (small optimizations)
- 3: Fully optimized (meets all criteria)

### Indicators to Analyze:

{indicators}

### Response Format (STRICT JSON)

```json
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
  "top_priorities": ["<indicator_id1>", "<indicator_id2>", "<indicator_id3>"]
}
```

Return ONLY valid JSON with no additional text or markdown formatting.
MARKDOWN,
];
