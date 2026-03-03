# TEST-24-9: PWA Guide API Endpoint Tests

## Task Reference
- **Task:** [TASK-24-9](../TASKS/TASK-24-9.md)
- **Feature:** FEAT-024 (PWA Analysis Implementation)
- **Specification:** SPEC-024

## Overview
Feature tests for the PWA Guide API endpoint. Validates that the endpoint returns comprehensive PWA implementation guide content with proper structure, caching, and security measures.

## Test Files

| File | Tests | Assertions |
|------|-------|------------|
| `backend/tests/Feature/Api/V1/PWAGuideControllerTest.php` | 75 | 75+ |

## Test Scenarios

### Basic Endpoint Tests (PG-001 to PG-010)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PG-001 | Index endpoint returns 200 | GET /api/v1/pwa/guide | Status 200 |
| PG-002 | Index returns JSON structure | GET /api/v1/pwa/guide | success, data.title, data.description, data.table_of_contents, data.sections |
| PG-003 | Index returns success true | GET /api/v1/pwa/guide | success: true |
| PG-004 | Index is public | GET /api/v1/pwa/guide (no auth) | Status 200 |
| PG-005 | Index returns title | GET /api/v1/pwa/guide | String title > 0 chars |
| PG-006 | Index returns description | GET /api/v1/pwa/guide | String description > 0 chars |
| PG-007 | Index returns markdown content | GET /api/v1/pwa/guide | sections[] with id, title, content |
| PG-008 | Index returns all required sections | GET /api/v1/pwa/guide | manifest-basics, icon-requirements, ios-support, service-worker |
| PG-009 | Sections have valid structure | GET /api/v1/pwa/guide | id matches /^[a-z0-9-]+$/, title string, content string |
| PG-010 | Content is markdown formatted | GET /api/v1/pwa/guide | Contains #, -, *, or ``` |

### Table of Contents Tests (PG-011 to PG-015)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PG-011 | Table of contents returned | GET /api/v1/pwa/guide | data.table_of_contents present |
| PG-012 | TOC has valid structure | GET /api/v1/pwa/guide | Array with id, title for each item |
| PG-013 | TOC matches sections | GET /api/v1/pwa/guide | TOC IDs = Section IDs |
| PG-014 | TOC has required sections | GET /api/v1/pwa/guide | All 4 section IDs in TOC |
| PG-015 | TOC titles are descriptive | GET /api/v1/pwa/guide | Each title > 5 chars |

### Section Endpoint Tests (PG-020 to PG-028)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PG-020 | Section returns 200 | GET /api/v1/pwa/guide/manifest-basics | Status 200 |
| PG-021 | Section returns JSON structure | GET /api/v1/pwa/guide/manifest-basics | success, data.id, data.title, data.content |
| PG-022 | Section returns correct id | GET /api/v1/pwa/guide/manifest-basics | data.id: "manifest-basics" |
| PG-023 | Section is public | GET /api/v1/pwa/guide/manifest-basics (no auth) | Status 200 |
| PG-024 | manifest-basics section | GET /api/v1/pwa/guide/manifest-basics | Contains "manifest" |
| PG-025 | icon-requirements section | GET /api/v1/pwa/guide/icon-requirements | Contains "icon" |
| PG-026 | ios-support section | GET /api/v1/pwa/guide/ios-support | Contains "ios" or "apple" |
| PG-027 | service-worker section | GET /api/v1/pwa/guide/service-worker | Contains "service" |
| PG-028 | Section returns success true | GET /api/v1/pwa/guide/manifest-basics | success: true |

### Section Content Validation Tests (PG-030 to PG-037)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PG-030 | Manifest basics covers required fields | GET /api/v1/pwa/guide/manifest-basics | Contains name, short_name, start_url |
| PG-031 | Manifest basics explains purpose | GET /api/v1/pwa/guide/manifest-basics | Contains "json" or "file" |
| PG-032 | Icon requirements covers sizes | GET /api/v1/pwa/guide/icon-requirements | Contains "192" or "512" |
| PG-033 | Icon requirements covers formats | GET /api/v1/pwa/guide/icon-requirements | Contains "png" or "svg" |
| PG-034 | Icon requirements covers purposes | GET /api/v1/pwa/guide/icon-requirements | Contains any/maskable/monochrome/purpose |
| PG-035 | iOS support covers apple-touch-icon | GET /api/v1/pwa/guide/ios-support | Contains "apple-touch-icon" or "apple" |
| PG-036 | iOS support covers iOS considerations | GET /api/v1/pwa/guide/ios-support | Contains meta/apple-mobile/viewport/standalone |
| PG-037 | Service worker is overview | GET /api/v1/pwa/guide/service-worker | Contains cache/offline/register |

### Error Handling Tests (PG-040 to PG-045)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PG-040 | Invalid section returns 404 | GET /api/v1/pwa/guide/non-existent | Status 404 |
| PG-041 | Invalid section error structure | GET /api/v1/pwa/guide/invalid | success: false, error.code, error.message |
| PG-042 | Invalid section error code | GET /api/v1/pwa/guide/invalid | error.code: "SECTION_NOT_FOUND" |
| PG-043 | Invalid section descriptive message | GET /api/v1/pwa/guide/invalid | message > 10 chars |
| PG-044 | Case-sensitive section ID | GET /api/v1/pwa/guide/MANIFEST-BASICS | Status 404 |
| PG-045 | Special chars in section ID | GET /api/v1/pwa/guide/manifest-basics<script> | Status 404 |

### HTTP Methods Tests (PG-050 to PG-054)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PG-050 | POST not allowed on index | POST /api/v1/pwa/guide | Status 405 |
| PG-051 | PUT not allowed on index | PUT /api/v1/pwa/guide | Status 405 |
| PG-052 | DELETE not allowed on index | DELETE /api/v1/pwa/guide | Status 405 |
| PG-053 | POST not allowed on section | POST /api/v1/pwa/guide/manifest-basics | Status 405 |
| PG-054 | PATCH not allowed | PATCH /api/v1/pwa/guide | Status 405 |

### Security Tests (PG-060 to PG-065)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PG-060 | SQL injection protection | GET /api/v1/pwa/guide/1' OR '1'='1 | Status 404 |
| PG-061 | XSS protection in section ID | GET /api/v1/pwa/guide/<script>alert(1)</script> | Status 404 |
| PG-062 | No sensitive data exposed | GET /api/v1/pwa/guide | No password/secret/api_key |
| PG-063 | Path traversal protection | GET /api/v1/pwa/guide/../../../etc/passwd | Status 404 |
| PG-064 | Null byte injection protection | GET /api/v1/pwa/guide/manifest-basics%00 | Status 200 or 404 |
| PG-065 | Response headers security | GET /api/v1/pwa/guide | Content-Type: application/json |

### Caching Tests (PG-070 to PG-075)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PG-070 | Response is cacheable | GET /api/v1/pwa/guide | Cache-Control header present |
| PG-071 | Cache header allows caching | GET /api/v1/pwa/guide | Contains "public" or "max-age" |
| PG-072 | Section response is cacheable | GET /api/v1/pwa/guide/manifest-basics | Cache-Control header present |
| PG-073 | Consistent responses | 2x GET /api/v1/pwa/guide | Same title |
| PG-074 | Section content consistent | 2x GET /api/v1/pwa/guide/manifest-basics | Same content |
| PG-075 | ETag header present | GET /api/v1/pwa/guide | Optional |

### Edge Cases Tests (PG-080 to PG-085)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PG-080 | Unicode in content | GET /api/v1/pwa/guide | Valid JSON (no UTF-8 errors) |
| PG-081 | Empty request body ignored | GET /api/v1/pwa/guide (empty body) | Status 200 |
| PG-082 | Query parameters ignored | GET /api/v1/pwa/guide?foo=bar | Status 200 |
| PG-083 | Trailing slash on index | GET /api/v1/pwa/guide/ | Status 200/301/302 |
| PG-084 | Very long section ID | GET /api/v1/pwa/guide/{1000 chars} | Status 404 |
| PG-085 | Section ID with spaces | GET /api/v1/pwa/guide/manifest basics | Status 404 |

### Performance Tests (PG-090 to PG-095)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PG-090 | Index response time | GET /api/v1/pwa/guide | < 500ms |
| PG-091 | Section response time | GET /api/v1/pwa/guide/manifest-basics | < 500ms |
| PG-092 | Multiple concurrent requests | 5x GET /api/v1/pwa/guide | All status 200 |
| PG-093 | Large content handling | GET /api/v1/pwa/guide | Each section < 100KB |
| PG-094 | Memory efficient | GET /api/v1/pwa/guide | Memory < 5MB |
| PG-095 | No memory leaks | 10x GET /api/v1/pwa/guide | Memory growth < 1MB |

### Data Types Tests (PG-100 to PG-105)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PG-100 | Title is string | GET /api/v1/pwa/guide | is_string(title) |
| PG-101 | Description is string | GET /api/v1/pwa/guide | is_string(description) |
| PG-102 | TOC is array | GET /api/v1/pwa/guide | is_array(table_of_contents) |
| PG-103 | Sections is array | GET /api/v1/pwa/guide | is_array(sections) |
| PG-104 | Success is boolean | GET /api/v1/pwa/guide | is_bool(success) |
| PG-105 | Section ID matches route | GET /api/v1/pwa/guide/icon-requirements | data.id = "icon-requirements" |

## Mock Data

### Expected Index Response Structure
```json
{
  "success": true,
  "data": {
    "title": "PWA Implementation Guide",
    "description": "Learn how to implement Progressive Web App features...",
    "table_of_contents": [
      { "id": "manifest-basics", "title": "Manifest Basics" },
      { "id": "icon-requirements", "title": "Icon Requirements" },
      { "id": "ios-support", "title": "iOS Support" },
      { "id": "service-worker", "title": "Service Worker Overview" }
    ],
    "sections": [
      {
        "id": "manifest-basics",
        "title": "Manifest Basics",
        "content": "# What is a Web App Manifest?\n\nThe web app manifest..."
      },
      {
        "id": "icon-requirements",
        "title": "Icon Requirements",
        "content": "# Icon Sizes and Formats\n\nPWAs require icons..."
      },
      {
        "id": "ios-support",
        "title": "iOS Support",
        "content": "# iOS-Specific Considerations\n\niOS has unique..."
      },
      {
        "id": "service-worker",
        "title": "Service Worker Overview",
        "content": "# What is a Service Worker?\n\nA service worker..."
      }
    ]
  }
}
```

### Expected Section Response Structure
```json
{
  "success": true,
  "data": {
    "id": "manifest-basics",
    "title": "Manifest Basics",
    "content": "# What is a Web App Manifest?\n\nThe web app manifest is a JSON file..."
  }
}
```

### Expected Error Response Structure
```json
{
  "success": false,
  "error": {
    "code": "SECTION_NOT_FOUND",
    "message": "The requested section 'invalid-section' was not found."
  }
}
```

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Basic Endpoint | 100% | 10 |
| Table of Contents | 100% | 5 |
| Section Endpoint | 100% | 9 |
| Content Validation | 100% | 8 |
| Error Handling | 100% | 6 |
| HTTP Methods | 100% | 5 |
| Security | 100% | 6 |
| Caching | 100% | 6 |
| Edge Cases | 100% | 6 |
| Performance | 100% | 6 |
| Data Types | 100% | 6 |
| **Total** | **100%** | **75** |

## Test Execution

```bash
# Run all PWA Guide tests
cd backend
php artisan test --filter=PWAGuideControllerTest

# Run specific test category
php artisan test --filter="PG-001|PG-002|PG-003"

# Run with coverage
php artisan test --filter=PWAGuideControllerTest --coverage
```

## Dependencies

### Required Routes
- `GET /api/v1/pwa/guide` - Index endpoint
- `GET /api/v1/pwa/guide/{id}` - Section endpoint

### Required Controller
- `App\Http\Controllers\Api\V1\PWAGuideController`
  - `index(): JsonResponse`
  - `section(string $id): JsonResponse`

### Content Storage Options
- Database table `pwa_guide_sections`
- OR Markdown files in `resources/guides/pwa/`
- OR Config file with content

## Notes for Developer

### Implementation Checklist
1. Create `PWAGuideController` with `index()` and `section()` methods
2. Define routes in `routes/api.php`
3. Create content storage mechanism (markdown files recommended)
4. Implement caching for guide content
5. Return proper error responses for invalid section IDs

### Content Requirements
- **manifest-basics**: Cover manifest.json structure, required fields (name, short_name, start_url, display, icons), optional fields
- **icon-requirements**: Cover sizes (192x192, 512x512 minimum), formats (PNG recommended), purposes (any, maskable, monochrome)
- **ios-support**: Cover apple-touch-icon, apple-mobile-web-app-capable, viewport settings, splash screens
- **service-worker**: Brief overview of registration, caching strategies, offline support (not full implementation)

### Caching Strategy
```php
// Recommended caching approach
Cache::remember('pwa_guide_index', 3600, function () {
    return $this->loadGuideContent();
});
```

---

## Comments

### [2026-03-03] PreTester
Created comprehensive test suite with 75 test scenarios covering:
- Basic endpoint functionality
- Table of contents validation
- Individual section endpoints
- Content validation for all 4 required sections
- Error handling (404 for invalid sections)
- HTTP method restrictions (GET only)
- Security (SQL injection, XSS, path traversal)
- Caching behavior
- Edge cases and performance
- Data type validation

Tests follow existing project patterns from TASK-26-17 and TASK-26-18.
