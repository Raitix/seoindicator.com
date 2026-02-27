# TEST-16-2: Meta Tag Analyzer Tests

## STATUS
`Tests Coverage In Progress`

## Related
- Task: [TASK-16-2](../TASKS/TASK-16-2.md)
- Feature: [FEAT-016](../FEATURES/FEAT-016.md)

---

## Test Coverage Summary

| Category | Tests | Assertions | Status |
|----------|-------|------------|--------|
| Title Tag Extraction | 12 | 12 | ✅ |
| Meta Description | 11 | 11 | ✅ |
| Canonical URL | 8 | 8 | ✅ |
| Meta Robots | 10 | 10 | ✅ |
| Open Graph Tags | 14 | 14 | ✅ |
| Warning Generation | 12 | 12 | ✅ |
| SERP Preview | 8 | 8 | ✅ |
| Edge Cases | 15 | 15 | ✅ |
| **Total** | **90** | **90** | ✅ |

---

## Test Scenarios

### Title Tag Extraction (TITLE)

| ID | Scenario | HTML Input | Expected |
|----|----------|------------|----------|
| TITLE-001 | Standard title | `<title>Page Title</title>` | `Page Title` |
| TITLE-002 | Missing title | (no title tag) | `null`, warning |
| TITLE-003 | Empty title | `<title></title>` | `''`, warning |
| TITLE-004 | Long title (65 chars) | 65 char string | Warning: too long |
| TITLE-005 | Short title (29 chars) | 29 char string | Warning: too short |
| TITLE-006 | Optimal title (50 chars) | 50 char string | No warning |
| TITLE-007 | Duplicate titles | Two `<title>` tags | Warning: duplicates |
| TITLE-008 | Title with entities | `<title>Bob &amp; Alice</title>` | `Bob & Alice` |
| TITLE-009 | Title in head vs body | Title in body | Still extracted |
| TITLE-010 | Title with whitespace | `<title>  Title  </title>` | Trimmed |
| TITLE-011 | Title with newlines | `<title>Title\nMore</title>` | Normalized |
| TITLE-012 | Title length count | `<title>Test</title>` | Length: 4 |

### Meta Description (DESC)

| ID | Scenario | HTML Input | Expected |
|----|----------|------------|----------|
| DESC-001 | Standard description | `<meta name="description" content="Desc">` | `Desc` |
| DESC-002 | Missing description | (no meta description) | `null`, warning |
| DESC-003 | Empty description | `<meta name="description" content="">` | `''`, warning |
| DESC-004 | Long description (161 chars) | 161 char string | Warning: too long |
| DESC-005 | Short description (119 chars) | 119 char string | Warning: too short |
| DESC-006 | Optimal description (140 chars) | 140 char string | No warning |
| DESC-007 | Duplicate descriptions | Two meta descriptions | Warning: duplicates |
| DESC-008 | Description with quotes | `content="He said "Hi""` | Parsed correctly |
| DESC-009 | Description with entities | `content="Bob &amp; Alice"` | `Bob & Alice` |
| DESC-010 | Og:description fallback | Og:description only | Extracted as fallback |
| DESC-011 | Case-insensitive | `<META NAME="DESCRIPTION"...>` | Still extracted |

### Canonical URL (CANON)

| ID | Scenario | HTML Input | Expected |
|----|----------|------------|----------|
| CANON-001 | Standard canonical | `<link rel="canonical" href="...">` | URL extracted |
| CANON-002 | Missing canonical | (no canonical) | `null` |
| CANON-003 | Relative canonical | `href="/page"` | Resolved to absolute |
| CANON-004 | Invalid URL | `href="not-a-url"` | `null`, warning |
| CANON-005 | Multiple canonicals | Two link canonical tags | Warning: multiple |
| CANON-006 | Self-referencing | Canonical = page URL | Valid |
| CANON-007 | Cross-domain canonical | Different domain | Valid with note |
| CANON-008 | Canonical in body | Link in body | Still extracted |

### Meta Robots (ROBOTS)

| ID | Scenario | HTML Input | Expected |
|----|----------|------------|----------|
| ROBOTS-001 | index, follow | `content="index, follow"` | Both detected |
| ROBOTS-002 | noindex | `content="noindex"` | `noindex: true` |
| ROBOTS-003 | nofollow | `content="nofollow"` | `nofollow: true` |
| ROBOTS-004 | none | `content="none"` | Noindex + nofollow |
| ROBOTS-005 | noarchive | `content="noarchive"` | `noarchive: true` |
| ROBOTS-006 | Multiple directives | `content="noindex, nofollow"` | Both detected |
| ROBOTS-007 | Case insensitive | `content="NOINDEX, FOLLOW"` | Normalized |
| ROBOTS-008 | Missing robots | (no meta robots) | Default: index, follow |
| ROBOTS-009 | X-Robots-Tag header | Header present | Parsed |
| ROBOTS-010 | Conflicting directives | Multiple meta robots | Merged |

### Open Graph Tags (OG)

| ID | Scenario | HTML Input | Expected |
|----|----------|------------|----------|
| OG-001 | og:title | `<meta property="og:title" content="Title">` | Extracted |
| OG-002 | og:description | `<meta property="og:description" ...>` | Extracted |
| OG-003 | og:image | `<meta property="og:image" ...>` | Extracted |
| OG-004 | og:url | `<meta property="og:url" ...>` | Extracted |
| OG-005 | og:type | `<meta property="og:type" ...>` | Extracted |
| OG-006 | og:site_name | `<meta property="og:site_name" ...>` | Extracted |
| OG-007 | Missing og:image | (no og:image) | Warning |
| OG-008 | og:image dimensions | og:image:width, height | Extracted |
| OG-009 | Multiple og:image | Several images | Array returned |
| OG-010 | og:locale | `<meta property="og:locale" ...>` | Extracted |
| OG-011 | Article type extras | article:published_time | Extracted |
| OG-012 | Invalid og:image URL | `content="not-a-url"` | Warning |
| OG-013 | Relative og:image | `content="/image.jpg"` | Resolved |
| OG-014 | Fallback from meta | No og:title, use title | Fallback |

### Warning Generation (WARN)

| ID | Scenario | Condition | Warning |
|----|----------|-----------|---------|
| WARN-001 | Missing title | No title tag | "Missing title tag" |
| WARN-002 | Title too short | < 30 chars | "Title is too short" |
| WARN-003 | Title too long | > 60 chars | "Title is too long" |
| WARN-004 | Missing description | No meta description | "Missing meta description" |
| WARN-005 | Description too short | < 120 chars | "Description is too short" |
| WARN-006 | Description too long | > 160 chars | "Description is too long" |
| WARN-007 | Duplicate title tags | Multiple titles | "Multiple title tags found" |
| WARN-008 | Duplicate descriptions | Multiple descriptions | "Multiple meta descriptions" |
| WARN-009 | Noindex detected | robots=noindex | "Page is blocked from indexing" |
| WARN-010 | Missing og:image | No og:image | "Missing Open Graph image" |
| WARN-011 | Canonical points elsewhere | Different URL | Note for review |
| WARN-012 | No canonical | Missing canonical | Suggestion to add |

### SERP Preview (SERP)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| SERP-001 | Standard preview | Title + URL + Desc | Full preview object |
| SERP-002 | Title truncation | 70 char title | Truncated to ~60 |
| SERP-003 | Description truncation | 200 char desc | Truncated to ~160 |
| SERP-004 | URL display | `https://example.com/path` | Clean URL display |
| SERP-005 | Date in preview | Article with date | Date prefix option |
| SERP-006 | Missing title fallback | No title | URL shown as title |
| SERP-007 | Missing description fallback | No description | Generated snippet |
| SERP-008 | Rich result indicators | Structured data | Note in preview |

### Edge Cases (EC)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| EC-001 | Empty HTML | `''` | Empty result, warnings |
| EC-002 | Malformed HTML | `<html><title>Test` | Still extracts |
| EC-003 | Non-HTML content | JSON response | Error handling |
| EC-004 | Very large page | 10MB HTML | Handles gracefully |
| EC-005 | Encoding issues | Non-UTF8 | Normalized |
| EC-006 | Script in title | `<title><script>alert(1)</script></title>` | Sanitized |
| EC-007 | HTML entities | `&lt;title&gt;` | Decoded |
| EC-008 | Comments in head | `<!-- comment -->` | Ignored |
| EC-009 | Title in comment | `<!-- <title>Fake</title> -->` | Not extracted |
| EC-010 | Iframe content | Page with iframes | Main page only |
| EC-011 | Dynamic content | JS-rendered title | Not detected (static) |
| EC-012 | Base tag | `<base href="...">` | Resolves relative URLs |
| EC-013 | Multiple heads | Two head sections | Merged |
| EC-014 | Title attribute elsewhere | Elements with title attr | Ignored |
| EC-015 | Unicode content | Emoji in title | Preserved |

---

## Test Files

| File | Location | Tests |
|------|----------|-------|
| MetaTagAnalyzerTest.php | `backend/tests/Unit/Services/Tools/` | 90 |

---

## Security Considerations

- HTML entities decoded safely
- No XSS from extracted content
- URL validation for canonical/images
- Size limits enforced

---

## Completion Date
2026-02-27
