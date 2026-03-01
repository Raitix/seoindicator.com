# TEST-16-8: Social Media Preview Tool Tests

## Task Reference
- Task: [TASK-16-8](../TASKS/TASK-16-8.md)
- Feature: [FEAT-016](../FEATURES/FEAT-016.md) - Interactive SEO Tools
- Specification: [SPEC-16](../SPECIFICATIONS/SPEC-16.md)

## Overview

Comprehensive test suite for the Social Media Preview tool that analyzes Open Graph and Twitter Card tags, generating share previews for Facebook, Twitter, and LinkedIn.

## Test Files

| File | Type | Tests | Assertions |
|------|------|-------|------------|
| `backend/tests/Unit/Services/Tools/SocialMediaPreviewTest.php` | Unit | 55+ | 100+ |
| `frontend/__tests__/components/tools/SocialMediaPreview.test.tsx` | Unit | 40+ | 80+ |

## Test Scenarios

### Backend Tests

#### Open Graph Extraction (OG)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| OG-001 | Extract all Open Graph tags | Full OG meta tags | All fields populated |
| OG-002 | Missing all Open Graph tags | No OG tags | All OG fields null |
| OG-003 | Partial Open Graph tags | Only og:title, og:image | Partial data, missing fields null |
| OG-004 | OG image with dimensions | og:image + width/height | Dimensions extracted |
| OG-005 | Multiple OG images | Two og:image tags | First image used |
| OG-006 | Relative OG URL | og:url="/path" | Normalized to absolute |
| OG-007 | Relative OG image | og:image="/img.jpg" | Normalized to absolute |
| OG-008 | OG type variations | Various type values | All types extracted |
| OG-009 | OG locale | og:locale + alternate | Locale extracted |
| OG-010 | Special characters | HTML entities, emoji | Properly decoded |

#### Twitter Card Extraction (TC)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-001 | Extract all Twitter Card tags | Full Twitter meta | All fields populated |
| TC-002 | Missing all Twitter Card tags | No Twitter tags | All TC fields null |
| TC-003 | Twitter Card type variations | summary, summary_large_image, app, player | Correct card type |
| TC-004 | Player card metadata | twitter:player + dimensions | Player data extracted |
| TC-005 | Site normalization | Site without @ | Normalized with @ |
| TC-006 | Fallback to Open Graph | Missing Twitter tags | OG values used |

#### Preview Generation (PG)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PG-001 | Facebook preview | OG tags | Complete preview object |
| PG-002 | Twitter preview | Twitter tags | Complete preview object |
| PG-003 | LinkedIn preview | OG tags | Complete preview object |
| PG-004 | Fallback to page meta | No social tags | Uses title/meta description |
| PG-005 | Missing image | No og:image | Preview without image |
| PG-006 | Twitter summary card format | twitter:card="summary" | Correct card type |

#### Missing Tags Detection (MT)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MT-001 | Missing Facebook required tags | Only og:title | Missing og:description, og:image, og:url |
| MT-002 | Missing Twitter required tags | Only twitter:card | Missing twitter:title, twitter:description |
| MT-003 | Missing LinkedIn required tags | Only og:title | Missing og:description, og:image |
| MT-004 | No missing tags | All tags present | Empty missing array |
| MT-005 | Missing image dimensions | og:image without width/height | Missing og:image:width, og:image:height |

#### Image Validation (IV)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IV-001 | HTTP image URL | og:image with http:// | HTTPS warning |
| IV-002 | HTTPS image URL | og:image with https:// | No warning |
| IV-003 | Relative image path | og:image="/img.jpg" | Normalized to absolute |
| IV-004 | Image dimensions present | og:image:width/height | Dimensions extracted |
| IV-005 | Small image warning | Width < 200px | Image too small warning |
| IV-006 | Invalid image URL | Malformed URL | Captured with possible warning |

#### Output Structure (OS)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| OS-001 | Complete output structure | Valid HTML | openGraph, twitterCard, previews, missing, warnings |
| OS-002 | Warnings array structure | HTTP image | { field, message } format |
| OS-003 | Missing tags array structure | Missing tags | Array of tag names |
| OS-004 | JSON serialization | Any valid input | Valid JSON output |

#### Edge Cases (EC)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Empty page | No meta tags | Empty previews with fallback |
| EC-002 | Malformed HTML | Unclosed tags | Graceful parsing |
| EC-003 | Very long title | 500+ char title | Title captured (may truncate in display) |
| EC-004 | Unicode in tags | Emoji, CJK characters | Properly preserved |
| EC-005 | HTML entities | &amp;, &quot;, etc. | Properly decoded |
| EC-006 | Duplicate meta tags | Two og:title | First occurrence used |

#### Security (SC)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SC-001 | XSS in og:title | `<script>` in title | Script tags stripped |
| SC-002 | XSS in twitter:description | `<img onerror>` | Tags stripped |
| SC-003 | JavaScript URL in image | javascript: URL | Blocked or sanitized |
| SC-004 | Data URL in image | data:image/svg | Evaluated/handled |
| SC-005 | SQL injection in URL | `' OR '1'='1` | URL validated |
| SC-006 | SSRF protection | localhost, private IPs | URL_BLOCKED error |

#### Error Handling (EH)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EH-001 | Invalid URL format | "not-a-url" | INVALID_URL error |
| EH-002 | Connection timeout | Slow server | TIMEOUT error |
| EH-003 | HTTP 404 error | Non-existent page | FETCH_FAILED error |
| EH-004 | HTTP 500 error | Server error | FETCH_FAILED error |
| EH-005 | Non-HTML content | JSON response | FETCH_FAILED error |

### Frontend Tests

#### Form Component (FM)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FM-001 | Form renders | Component mount | Form, input, button visible |
| FM-002 | Empty URL validation | Submit empty | Error message shown |
| FM-003 | Invalid URL format | "not-a-url" | Error message shown |
| FM-004 | Valid URL submission | "https://example.com" | onAnalyze called |
| FM-005 | Loading state | isLoading=true | Form disabled |
| FM-006 | Initial URL | initialUrl prop | Input pre-populated |
| FM-007 | Protocol validation | ftp:// URL | Error message shown |
| FM-008 | Error clear on change | Type after error | Error message removed |

#### Platform Tabs (PT)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PT-001 | All tabs render | Component mount | Facebook, Twitter, LinkedIn tabs |
| PT-002 | Active tab highlighted | activeTab="twitter" | Twitter tab aria-selected=true |
| PT-003 | Tab click callback | Click LinkedIn | onTabChange("linkedin") |
| PT-004 | Keyboard navigation | Arrow keys | Focus moves between tabs |
| PT-005 | ARIA attributes | Any state | role="tablist", role="tab" |

#### Preview Card (PC)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PC-001 | Full preview data | title, desc, image, domain | All elements rendered |
| PC-002 | Missing image | image=null | No image element |
| PC-003 | Missing title | title=null | "No title" fallback |
| PC-004 | Missing description | description=null | "No description" fallback |
| PC-005 | Platform-specific styling | platform prop | Correct test ID |
| PC-006 | Long title | 200 char title | Title rendered (may truncate visually) |
| PC-007 | Image alt text | Any image | Accessible alt text |

#### Missing Tags List (MT)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MT-001 | Tags displayed | Missing array | List items rendered |
| MT-002 | Empty list | Empty array | Component not rendered |
| MT-003 | Accessible heading | Any missing | Screen reader heading |

#### Warnings List (WL)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| WL-001 | Warnings displayed | Warnings array | List items with field + message |
| WL-002 | Empty warnings | Empty array | Component not rendered |
| WL-003 | Field name shown | Warning object | Field name in output |

#### Results Component (RC)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RC-001 | Results with data | Full data object | All components rendered |
| RC-002 | Error state | error prop | Error message shown |
| RC-003 | Null data | data=null | Nothing rendered |
| RC-004 | Tab switching | Click tab | Preview changes |
| RC-005 | Missing tags populated | missing array | Missing tags list shown |
| RC-006 | Warnings populated | warnings array | Warnings list shown |
| RC-007 | Default tab | Initial render | Facebook tab active |
| RC-008 | Tab panel ARIA | Any state | role="tabpanel" with attributes |

#### Accessibility (AC)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AC-001 | Form accessibility | Form render | Label associated, error alert |
| AC-002 | Tabs ARIA pattern | Tabs render | Correct roles and attributes |
| AC-003 | Image alt text | Preview with image | Alt attribute present |
| AC-004 | Error announcement | Error state | role="alert" on error |
| AC-005 | Focus management | Tab through | All interactive elements focusable |

#### Edge Cases (EC)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Very long URL | 2000+ char URL | Accepted in input |
| EC-002 | Unicode URL | 例え.jp/パス | Accepted in input |
| EC-003 | XSS in preview data | <script> in title | Rendered as text, not executed |
| EC-004 | Empty preview data | All null | Fallback text shown |
| EC-005 | Many missing tags | 20 missing tags | All displayed |
| EC-006 | Many warnings | 15 warnings | All displayed |

## Mock Data

### Valid Open Graph HTML
```html
<!DOCTYPE html>
<html>
<head>
    <meta property="og:title" content="Example Page Title" />
    <meta property="og:description" content="This is a meta description." />
    <meta property="og:image" content="https://example.com/og-image.jpg" />
    <meta property="og:url" content="https://example.com/page" />
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="Example Site" />
</head>
<body></body>
</html>
```

### Valid Twitter Card HTML
```html
<!DOCTYPE html>
<html>
<head>
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:site" content="@example" />
    <meta name="twitter:creator" content="@author" />
    <meta name="twitter:title" content="Twitter Title" />
    <meta name="twitter:description" content="Twitter description." />
    <meta name="twitter:image" content="https://example.com/twitter.jpg" />
</head>
<body></body>
</html>
```

### Expected Output Structure
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
    "creator": "@author",
    "title": "Twitter Title",
    "description": "Twitter description.",
    "image": "https://example.com/twitter.jpg"
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
      "title": "Twitter Title",
      "description": "Twitter description.",
      "image": "https://example.com/twitter.jpg"
    },
    "linkedin": {
      "title": "Example Page Title",
      "description": "This is a meta description.",
      "image": "https://example.com/og-image.jpg"
    }
  },
  "missing": [],
  "warnings": []
}
```

## Coverage Targets

| Category | Target | Actual |
|----------|--------|--------|
| Open Graph Extraction | 100% | - |
| Twitter Card Extraction | 100% | - |
| Preview Generation | 100% | - |
| Missing Tags Detection | 100% | - |
| Image Validation | 100% | - |
| Security Tests | 100% | - |
| Error Handling | 100% | - |
| Accessibility | 100% | - |
| **Overall** | **80%+** | - |

## Test Execution

```bash
# Run backend tests
cd backend
php artisan test --filter SocialMediaPreviewTest

# Run frontend tests
cd frontend
npm test -- --testPathPattern="SocialMediaPreview"

# Run with coverage
npm test -- --coverage --testPathPattern="SocialMediaPreview"
```

## Dependencies
- TASK-16-1 (Tools Infrastructure) - Base SEOTool class, ToolResult DTO
- TASK-16-2 (Meta Tag Analyzer) - Similar pattern for tag extraction
- Symfony DomCrawler (HTML parsing)
- Laravel HTTP Client (URL fetching)
- React Testing Library (frontend tests)
- Jest (frontend test runner)

## Notes for Developer

### Implementation Notes
1. **Tag Extraction Priority**: Open Graph tags take precedence for LinkedIn previews
2. **URL Normalization**: All relative URLs must be converted to absolute
3. **Twitter Fallback**: Twitter preview falls back to OG tags when missing
4. **Security**: All user input must be sanitized (XSS prevention)
5. **SSRF Protection**: Block localhost, private IPs, cloud metadata endpoints

### Required Platform Tags
| Platform | Required Tags |
|----------|---------------|
| Facebook | og:title, og:description, og:image, og:url |
| Twitter | twitter:card, twitter:title, twitter:description |
| LinkedIn | og:title, og:description, og:image |

### Image Recommendations
| Platform | Recommended Size | Aspect Ratio |
|----------|------------------|--------------|
| Facebook | 1200 x 630 | 1.91:1 |
| Twitter (large) | 1200 x 600 | 2:1 |
| Twitter (summary) | 240 x 240 | 1:1 |
| LinkedIn | 1200 x 627 | 1.91:1 |

---

## Comments
_Agent communication log_

### [2026-03-01] Agent: PreTester
Created comprehensive test suite for Social Media Preview tool. Tests cover:
- Open Graph and Twitter Card tag extraction
- Preview generation for all three platforms
- Missing tag detection and warnings
- Image validation (HTTPS, dimensions)
- Security (XSS, SSRF, injection)
- Error handling and edge cases
- Frontend component tests with accessibility coverage

Tests follow existing project patterns from TASK-16-2 (Meta Tag Analyzer).
