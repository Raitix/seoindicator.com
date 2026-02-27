# TASK-16-2: Meta Tag Analyzer Tool

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Related
- Feature: [FEAT-016](../FEATURES/FEAT-016.md)
- Specification: [SPEC-016](../SPECIFICATIONS/SPEC-016.md)

---

## Description
Implement the Meta Tag Analyzer tool that extracts and analyzes title tags, meta descriptions, and other meta tags from a given URL. Provides length warnings, SERP preview, and optimization recommendations.

---

## Acceptance Criteria
- [ ] Extract title tag and display character count with warning if >60 chars
- [ ] Extract meta description and display character count with warning if >160 chars
- [ ] Extract canonical URL if present
- [ ] Extract meta robots directives (noindex, nofollow, etc.)
- [ ] Extract Open Graph tags (og:title, og:description, og:image)
- [ ] Generate Google SERP preview snippet
- [ ] Display warnings for missing/empty tags
- [ ] Show duplicate tag detection if multiple title/description tags
- [ ] Return structured JSON result with all extracted data
- [ ] Frontend component with clean UI showing results
- [ ] Unit tests for extraction logic

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
  "title": {
    "value": "Example Page Title",
    "length": 19,
    "warning": null
  },
  "description": {
    "value": "This is a meta description for the page.",
    "length": 41,
    "warning": null
  },
  "canonical": "https://example.com/page",
  "robots": "index, follow",
  "openGraph": {
    "title": "Example Page Title",
    "description": "This is a meta description.",
    "image": "https://example.com/og-image.jpg"
  },
  "warnings": [
    "Meta description is too short (recommended: 120-160 characters)"
  ],
  "serpPreview": {
    "title": "Example Page Title",
    "url": "https://example.com/page",
    "description": "This is a meta description for the page."
  }
}
```

### Warning Rules
| Check | Warning Condition |
|-------|-------------------|
| Title length | < 30 or > 60 characters |
| Description length | < 120 or > 160 characters |
| Missing title | Title tag not found |
| Missing description | Meta description not found |
| Duplicate title | Multiple title tags |
| Duplicate description | Multiple meta descriptions |

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
Created task. This is the first concrete tool implementation. Follow the base class pattern from TASK-16-1.
