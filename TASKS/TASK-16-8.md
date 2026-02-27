# TASK-16-8: Social Media Preview Tool

## STATUS
`To Write Tests`

## PRIORITY
`Medium`

## RISK
`Low`

## Related
- Feature: [FEAT-016](../FEATURES/FEAT-016.md)
- Specification: [SPEC-016](../SPECIFICATIONS/SPEC-016.md)

---

## Description
Implement the Social Media Preview tool that analyzes Open Graph and Twitter Card tags. Shows how the page will appear when shared on Facebook, Twitter, LinkedIn, and other social platforms.

---

## Acceptance Criteria
- [ ] Extract all Open Graph tags (og:title, og:description, og:image, og:url, og:type)
- [ ] Extract all Twitter Card tags (twitter:card, twitter:site, twitter:title, etc.)
- [ ] Generate Facebook share preview
- [ ] Generate Twitter share preview
- [ ] Generate LinkedIn share preview
- [ ] Detect missing required tags
- [ ] Validate image dimensions and URL
- [ ] Return structured JSON with previews
- [ ] Frontend component with platform tabs
- [ ] Unit tests for tag extraction

---

## Technical Details

### Input
```json
{
  "url": "https://example.com/page"
}
```

### Output Structure
```json
{
  "openGraph": {
    "title": "Example Page Title",
    "description": "This is a meta description.",
    "image": "https://example.com/og-image.jpg",
    "url": "https://example.com/page",
    "type": "website",
    "siteName": "Example Site"
  },
  "twitterCard": {
    "card": "summary_large_image",
    "site": "@example",
    "title": "Example Page Title",
    "description": "This is a meta description.",
    "image": "https://example.com/twitter-image.jpg"
  },
  "previews": {
    "facebook": {
      "title": "Example Page Title",
      "description": "This is a meta description.",
      "image": "https://example.com/og-image.jpg",
      "domain": "example.com"
    },
    "twitter": {
      "card": "summary_large_image",
      "title": "Example Page Title",
      "description": "This is a meta description.",
      "image": "https://example.com/twitter-image.jpg"
    },
    "linkedin": {
      "title": "Example Page Title",
      "description": "This is a meta description.",
      "image": "https://example.com/og-image.jpg"
    }
  },
  "missing": [
    "og:image:width",
    "og:image:height",
    "twitter:creator"
  ],
  "warnings": [
    {
      "field": "og:image",
      "message": "Image URL should use HTTPS"
    }
  ]
}
```

### Preview Rendering
- Facebook: Card with image, title, description, domain
- Twitter: Based on card type (summary vs summary_large_image)
- LinkedIn: Similar to Facebook preview

### Required Tags
| Platform | Required Tags |
|----------|---------------|
| Facebook | og:title, og:description, og:image, og:url |
| Twitter | twitter:card, twitter:title, twitter:description |
| LinkedIn | og:title, og:description, og:image |

---

## Dependencies
- TASK-16-1 (Tools Infrastructure)

---

## Effort Estimate
**Medium** (4-6 hours)

---

## Comments
_Agent communication log_

### [2026-02-27] Agent: SpecSystemAnalyst
Created task. Phase 3 tool. The preview rendering is frontend-heavy. Consider caching image dimensions for performance.
