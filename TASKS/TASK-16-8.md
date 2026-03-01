# TASK-16-8: Social Media Preview Tool

## STATUS
`To Develop`

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

### [2026-03-01] Agent: PreTester
**Tests Complete - Ready for Developer**

Created comprehensive test suite for Social Media Preview tool:

**Backend Tests** (`backend/tests/Unit/Services/Tools/SocialMediaPreviewTest.php`):
- 55+ test cases covering:
  - Open Graph extraction (OG-001 to OG-010)
  - Twitter Card extraction (TC-001 to TC-006)
  - Preview generation for all 3 platforms (PG-001 to PG-006)
  - Missing tag detection (MT-001 to MT-005)
  - Image validation and warnings (IV-001 to IV-006)
  - Output structure validation (OS-001 to OS-004)
  - Edge cases (EC-001 to EC-006)
  - Security tests - XSS, SSRF, injection (SC-001 to SC-006)
  - Error handling (EH-001 to EH-005)

**Frontend Tests** (`frontend/__tests__/components/tools/SocialMediaPreview.test.tsx`):
- 40+ test cases covering:
  - Form component validation (FM-001 to FM-008)
  - Platform tabs component (PT-001 to PT-005)
  - Preview card component (PC-001 to PC-007)
  - Missing tags list component (MT-001 to MT-003)
  - Warnings list component (WL-001 to WL-003)
  - Results component (RC-001 to RC-008)
  - Accessibility tests (AC-001 to AC-005)
  - Edge cases (EC-001 to EC-006)

**Test Documentation**: `tests/TEST-16-8.md`

**Key Implementation Notes**:
1. Tag extraction uses Cheerio (via Symfony DomCrawler in Laravel)
2. Relative URLs must be normalized to absolute
3. Twitter preview falls back to OG tags when missing
4. SSRF protection required for localhost, private IPs, cloud metadata endpoints
5. XSS sanitization required for all extracted content

Status updated to `To Develop`.

### [2026-02-27] Agent: SpecSystemAnalyst
Created task. Phase 3 tool. The preview rendering is frontend-heavy. Consider caching image dimensions for performance.
