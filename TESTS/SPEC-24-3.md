# TESTS/SPEC-24-3: ManifestFetcherService

## Related
- Task: TASKS/TASK-24-3.md
- Specification: SPECIFICATIONS/SPEC-024.md
- Feature: FEATURES/FEAT-024.md

## Test Status
- [x] Tests designed
- [ ] Unit tests written
- [ ] Integration tests written
- [ ] E2E tests written (N/A - service layer)
- [ ] All tests passing locally
- [ ] All tests passing in production

## Dependency Note
- Requires URLValidationService (TASK-16-1 - Done)
- Requires HtmlFetcherService (TASK-18-5 - Done)
- Requires PWAAnalysis model (TASK-24-1 - In Deployment)

## Overview

ManifestFetcherService is responsible for:
1. Fetching HTML from a target URL
2. Parsing HTML to find `<link rel="manifest">` element
3. Resolving relative manifest URLs to absolute
4. Fetching and parsing the manifest.json file
5. Validating manifest structure and content

## Test Cases

### Category 1: fetch() Method (MF-001 to MF-010)

#### MF-001: Fetch Manifest Successfully
- **Type:** Unit
- **Description:** Verify manifest is fetched and parsed correctly
- **Preconditions:** Valid URL with manifest link
- **Input:** `https://example.com` (HTML contains manifest link)
- **HTML Mock:**
  ```html
  <!DOCTYPE html>
  <html>
  <head>
    <link rel="manifest" href="/manifest.json">
  </head>
  <body></body>
  </html>
  ```
- **Manifest Mock:**
  ```json
  {
    "name": "Example App",
    "short_name": "Example",
    "start_url": "/",
    "display": "standalone",
    "background_color": "#ffffff",
    "theme_color": "#000000"
  }
  ```
- **Expected Output:**
  ```php
  [
    'success' => true,
    'manifest' => [
      'name' => 'Example App',
      'short_name' => 'Example',
      // ... other fields
    ],
    'manifest_url' => 'https://example.com/manifest.json',
  ]
  ```
- **Priority:** High

#### MF-002: No Manifest Link in HTML
- **Type:** Unit
- **Description:** Verify null returned when no manifest link exists
- **Preconditions:** Valid URL without manifest link
- **Input:** `https://example.com` (HTML without manifest link)
- **HTML Mock:**
  ```html
  <!DOCTYPE html>
  <html>
  <head>
    <title>No Manifest</title>
  </head>
  <body></body>
  </html>
  ```
- **Expected Output:**
  ```php
  [
    'success' => false,
    'manifest' => null,
    'manifest_url' => null,
    'error' => 'No manifest link found in HTML',
    'error_code' => 'MANIFEST_NOT_FOUND',
  ]
  ```
- **Priority:** High

#### MF-003: Invalid JSON Response
- **Type:** Unit
- **Description:** Verify graceful handling of invalid JSON
- **Preconditions:** URL with manifest link pointing to invalid JSON
- **Input:** `https://example.com`
- **Manifest Response:** `{ invalid json `
- **Expected Output:**
  ```php
  [
    'success' => false,
    'manifest' => null,
    'manifest_url' => 'https://example.com/manifest.json',
    'error' => 'Invalid JSON in manifest',
    'error_code' => 'INVALID_JSON',
  ]
  ```
- **Priority:** High

#### MF-004: Manifest Fetch Timeout
- **Type:** Unit
- **Description:** Verify timeout handling (30 second limit)
- **Preconditions:** Manifest URL takes >30s to respond
- **Input:** `https://slow-example.com`
- **Expected Output:**
  ```php
  [
    'success' => false,
    'manifest' => null,
    'error' => 'Request timed out',
    'error_code' => 'TIMEOUT',
  ]
  ```
- **Priority:** High

#### MF-005: Manifest Size Limit
- **Type:** Unit
- **Description:** Verify manifest size limit (10KB max)
- **Preconditions:** Manifest JSON > 10KB
- **Input:** `https://example.com`
- **Manifest Size:** 15KB
- **Expected Output:**
  ```php
  [
    'success' => false,
    'manifest' => null,
    'error' => 'Manifest exceeds maximum size limit (10KB)',
    'error_code' => 'MANIFEST_TOO_LARGE',
  ]
  ```
- **Priority:** Medium

#### MF-006: HTML Fetch Failure
- **Type:** Unit
- **Description:** Verify handling when HTML page fails to load
- **Preconditions:** Target URL returns 404/500
- **Input:** `https://example.com/404`
- **Expected Output:**
  ```php
  [
    'success' => false,
    'manifest' => null,
    'error' => 'Failed to fetch HTML',
    'error_code' => 'HTML_FETCH_FAILED',
  ]
  ```
- **Priority:** High

#### MF-007: Null URL Input
- **Type:** Unit
- **Description:** Verify handling of null URL
- **Input:** `null`
- **Expected Output:**
  ```php
  [
    'success' => false,
    'manifest' => null,
    'error' => 'URL is required',
    'error_code' => 'INVALID_URL',
  ]
  ```
- **Priority:** Medium

#### MF-008: Empty URL Input
- **Type:** Unit
- **Description:** Verify handling of empty string URL
- **Input:** `''`
- **Expected Output:**
  ```php
  [
    'success' => false,
    'manifest' => null,
    'error' => 'URL is required',
    'error_code' => 'INVALID_URL',
  ]
  ```
- **Priority:** Medium

#### MF-009: Manifest HTTP Error
- **Type:** Unit
- **Description:** Verify handling when manifest URL returns error
- **Preconditions:** Manifest URL returns 403/404/500
- **Input:** `https://example.com`
- **Manifest URL:** Returns 403 Forbidden
- **Expected Output:**
  ```php
  [
    'success' => false,
    'manifest' => null,
    'manifest_url' => 'https://example.com/forbidden-manifest.json',
    'error' => 'Failed to fetch manifest: HTTP 403',
    'error_code' => 'MANIFEST_FETCH_FAILED',
  ]
  ```
- **Priority:** High

#### MF-010: Complete Manifest With Icons
- **Type:** Unit
- **Description:** Verify complete manifest with icons array is parsed
- **Input:** `https://example.com`
- **Manifest Mock:**
  ```json
  {
    "name": "Example App",
    "short_name": "Example",
    "start_url": "/",
    "display": "standalone",
    "background_color": "#ffffff",
    "theme_color": "#000000",
    "icons": [
      { "src": "/icons/192.png", "sizes": "192x192", "type": "image/png" },
      { "src": "/icons/512.png", "sizes": "512x512", "type": "image/png" }
    ]
  }
  ```
- **Expected Output:** Full manifest array with icons
- **Priority:** High

---

### Category 2: extractManifestUrl() Method (EM-001 to EM-010)

#### EM-001: Standard Manifest Link
- **Type:** Unit
- **Description:** Extract manifest URL from standard link tag
- **Input HTML:**
  ```html
  <link rel="manifest" href="/manifest.json">
  ```
- **Base URL:** `https://example.com`
- **Expected Output:** `/manifest.json`
- **Priority:** High

#### EM-002: Manifest Link With Alternate Order
- **Type:** Unit
- **Description:** Extract from link with href before rel
- **Input HTML:**
  ```html
  <link href="/manifest.json" rel="manifest">
  ```
- **Base URL:** `https://example.com`
- **Expected Output:** `/manifest.json`
- **Priority:** Medium

#### EM-003: Manifest Link With Additional Attributes
- **Type:** Unit
- **Description:** Extract from link with crossorigin attribute
- **Input HTML:**
  ```html
  <link rel="manifest" href="/manifest.json" crossorigin="use-credentials">
  ```
- **Base URL:** `https://example.com`
- **Expected Output:** `/manifest.json`
- **Priority:** Medium

#### EM-004: No Manifest Link
- **Type:** Unit
- **Description:** Return null when no manifest link exists
- **Input HTML:**
  ```html
  <head>
    <title>Page</title>
    <meta charset="utf-8">
  </head>
  ```
- **Base URL:** `https://example.com`
- **Expected Output:** `null`
- **Priority:** High

#### EM-005: Multiple Link Tags - Pick Manifest
- **Type:** Unit
- **Description:** Extract manifest from multiple link tags
- **Input HTML:**
  ```html
  <link rel="stylesheet" href="/style.css">
  <link rel="manifest" href="/manifest.json">
  <link rel="icon" href="/favicon.ico">
  ```
- **Base URL:** `https://example.com`
- **Expected Output:** `/manifest.json`
- **Priority:** High

#### EM-006: Absolute Manifest URL
- **Type:** Unit
- **Description:** Extract absolute URL from manifest link
- **Input HTML:**
  ```html
  <link rel="manifest" href="https://cdn.example.com/manifest.json">
  ```
- **Base URL:** `https://example.com`
- **Expected Output:** `https://cdn.example.com/manifest.json`
- **Priority:** High

#### EM-007: Protocol-Relative URL
- **Type:** Unit
- **Description:** Extract protocol-relative manifest URL
- **Input HTML:**
  ```html
  <link rel="manifest" href="//cdn.example.com/manifest.json">
  ```
- **Base URL:** `https://example.com`
- **Expected Output:** `//cdn.example.com/manifest.json`
- **Priority:** Medium

#### EM-008: Query Parameters in URL
- **Type:** Unit
- **Description:** Extract manifest URL with query parameters
- **Input HTML:**
  ```html
  <link rel="manifest" href="/manifest.json?v=1.2.3">
  ```
- **Base URL:** `https://example.com`
- **Expected Output:** `/manifest.json?v=1.2.3`
- **Priority:** Medium

#### EM-009: Malformed HTML
- **Type:** Unit
- **Description:** Handle malformed HTML gracefully
- **Input HTML:** `<html><head><link rel="manifest" href="/manifest.json"`
- **Base URL:** `https://example.com`
- **Expected Output:** `/manifest.json` (or null if parser fails)
- **Priority:** Low

#### EM-010: Empty href Attribute
- **Type:** Unit
- **Description:** Handle empty href in manifest link
- **Input HTML:**
  ```html
  <link rel="manifest" href="">
  ```
- **Base URL:** `https://example.com`
- **Expected Output:** `null`
- **Priority:** Medium

---

### Category 3: resolveManifestUrl() Method (RM-001 to RM-008)

#### RM-001: Relative Path
- **Type:** Unit
- **Description:** Resolve relative manifest URL
- **Manifest URL:** `/manifest.json`
- **Base URL:** `https://example.com`
- **Expected Output:** `https://example.com/manifest.json`
- **Priority:** High

#### RM-002: Relative Path With Subdirectory
- **Type:** Unit
- **Description:** Resolve relative URL with path
- **Manifest URL:** `manifest.json`
- **Base URL:** `https://example.com/app/`
- **Expected Output:** `https://example.com/app/manifest.json`
- **Priority:** High

#### RM-003: Absolute URL
- **Type:** Unit
- **Description:** Return absolute URL unchanged
- **Manifest URL:** `https://cdn.example.com/pwa/manifest.json`
- **Base URL:** `https://example.com`
- **Expected Output:** `https://cdn.example.com/pwa/manifest.json`
- **Priority:** High

#### RM-004: Protocol-Relative URL
- **Type:** Unit
- **Description:** Resolve protocol-relative URL
- **Manifest URL:** `//cdn.example.com/manifest.json`
- **Base URL:** `https://example.com`
- **Expected Output:** `https://cdn.example.com/manifest.json`
- **Priority:** Medium

#### RM-005: Port in Base URL
- **Type:** Unit
- **Description:** Handle custom port in base URL
- **Manifest URL:** `/manifest.json`
- **Base URL:** `https://example.com:8443`
- **Expected Output:** `https://example.com:8443/manifest.json`
- **Priority:** Medium

#### RM-006: Query String in Base URL
- **Type:** Unit
- **Description:** Ignore query string in base URL
- **Manifest URL:** `/manifest.json`
- **Base URL:** `https://example.com/page?query=value`
- **Expected Output:** `https://example.com/manifest.json`
- **Priority:** Low

#### RM-007: Fragment in Base URL
- **Type:** Unit
- **Description:** Ignore fragment in base URL
- **Manifest URL:** `/manifest.json`
- **Base URL:** `https://example.com/page#section`
- **Expected Output:** `https://example.com/manifest.json`
- **Priority:** Low

#### RM-008: Null Manifest URL
- **Type:** Unit
- **Description:** Return null for null input
- **Manifest URL:** `null`
- **Base URL:** `https://example.com`
- **Expected Output:** `null`
- **Priority:** Medium

---

### Category 4: Cross-Origin Manifest URLs (CO-001 to CO-005)

#### CO-001: Cross-Origin CDN Manifest
- **Type:** Unit
- **Description:** Validate cross-origin manifest URL is accessible
- **Manifest URL:** `https://cdn.cloudflare.com/example/manifest.json`
- **Base URL:** `https://example.com`
- **Expected Behavior:** URL validated for SSRF, then fetched
- **Priority:** High

#### CO-002: Cross-Origin Blocked by SSRF
- **Type:** Unit
- **Description:** Cross-origin to internal IP blocked
- **Manifest URL:** `https://192.168.1.1/manifest.json`
- **Base URL:** `https://example.com`
- **Expected Output:**
  ```php
  [
    'success' => false,
    'error' => 'Access to this URL is not allowed for security reasons',
    'error_code' => 'BLOCKED_URL',
  ]
  ```
- **Priority:** High

#### CO-003: Data URI Manifest
- **Type:** Unit
- **Description:** Data URI in manifest href is rejected
- **Input HTML:**
  ```html
  <link rel="manifest" href="data:application/json,{&quot;name&quot;:&quot;App&quot;}">
  ```
- **Expected Output:**
  ```php
  [
    'success' => false,
    'error' => 'Data URIs not supported for manifest',
    'error_code' => 'INVALID_URL',
  ]
  ```
- **Priority:** Medium

#### CO-004: JavaScript URI Manifest
- **Type:** Unit
- **Description:** JavaScript URI in manifest href is rejected
- **Input HTML:**
  ```html
  <link rel="manifest" href="javascript:alert(1)">
  ```
- **Expected Output:** `null` or error
- **Priority:** Medium

#### CO-005: Redirect Chain Manifest
- **Type:** Unit
- **Description:** Manifest URL redirects to another location
- **Manifest URL:** `https://example.com/manifest` (redirects to `/manifest.json`)
- **Expected Behavior:** Follow redirect with SSRF validation
- **Priority:** Medium

---

### Category 5: SSRF Protection Tests (SSRF-001 to SSRF-007)

#### SSRF-001: Localhost Blocked
- **Type:** Security
- **Description:** Manifest URL pointing to localhost is blocked
- **Input URL:** `https://example.com`
- **Manifest URL:** `http://localhost/manifest.json`
- **Expected Output:** BLOCKED_URL error
- **Priority:** High

#### SSRF-002: Private IP Blocked
- **Type:** Security
- **Description:** Manifest URL pointing to private IP is blocked
- **Input URL:** `https://example.com`
- **Manifest URL:** `http://192.168.1.1/manifest.json`
- **Expected Output:** BLOCKED_URL error
- **Priority:** High

#### SSRF-003: Cloud Metadata IP Blocked
- **Type:** Security
- **Description:** Manifest URL pointing to cloud metadata is blocked
- **Input URL:** `https://example.com`
- **Manifest URL:** `http://169.254.169.254/latest/meta-data/`
- **Expected Output:** BLOCKED_URL error
- **Priority:** High

#### SSRF-004: Decimal IP Notation Blocked
- **Type:** Security
- **Description:** Decimal IP notation (2130706433 = 127.0.0.1) blocked
- **Manifest URL:** `http://2130706433/manifest.json`
- **Expected Output:** BLOCKED_URL error
- **Priority:** High

#### SSRF-005: Hex IP Notation Blocked
- **Type:** Security
- **Description:** Hex IP notation (0x7f000001 = 127.0.0.1) blocked
- **Manifest URL:** `http://0x7f000001/manifest.json`
- **Expected Output:** BLOCKED_URL error
- **Priority:** High

#### SSRF-006: DNS Reindinging Protection
- **Type:** Security
- **Description:** Manifest URL that resolves to private IP after redirect
- **Manifest URL:** `https://public-domain-that-redirects-to-private.com/manifest.json`
- **Expected Output:** BLOCKED_REDIRECT error
- **Priority:** High

#### SSRF-007: File Protocol Blocked
- **Type:** Security
- **Description:** File:// protocol in manifest URL is blocked
- **Manifest URL:** `file:///etc/passwd`
- **Expected Output:** BLOCKED_PROTOCOL or INVALID_URL error
- **Priority:** High

---

### Category 6: Manifest Content Validation (MCV-001 to MCV-006)

#### MCV-001: Empty Manifest Object
- **Type:** Unit
- **Description:** Handle empty manifest object
- **Manifest JSON:** `{}`
- **Expected Output:**
  ```php
  [
    'success' => true,
    'manifest' => [],
    'warnings' => ['Manifest has no required fields'],
  ]
  ```
- **Priority:** Medium

#### MCV-002: Manifest With Only Name
- **Type:** Unit
- **Description:** Minimal valid manifest
- **Manifest JSON:** `{"name": "App"}`
- **Expected Output:**
  ```php
  [
    'success' => true,
    'manifest' => ['name' => 'App'],
  ]
  ```
- **Priority:** Medium

#### MCV-003: Unicode in Manifest
- **Type:** Unit
- **Description:** Handle Unicode characters in manifest
- **Manifest JSON:**
  ```json
  {
    "name": "示例应用",
    "description": "日本語の説明"
  }
  ```
- **Expected Output:** Unicode preserved correctly
- **Priority:** Medium

#### MCV-004: Large Icons Array
- **Type:** Unit
- **Description:** Handle manifest with many icons
- **Manifest JSON:** 50+ icons in array
- **Expected Output:** All icons parsed correctly
- **Priority:** Low

#### MCV-005: Invalid Icon Object
- **Type:** Unit
- **Description:** Handle malformed icon object
- **Manifest JSON:**
  ```json
  {
    "icons": [
      { "src": "/icon.png" },
      "invalid-icon-entry"
    ]
  }
  ```
- **Expected Output:** Valid icons parsed, invalid skipped with warning
- **Priority:** Medium

#### MCV-006: Nested Objects
- **Type:** Unit
- **Description:** Handle nested objects (screenshots, shortcuts)
- **Manifest JSON:**
  ```json
  {
    "screenshots": [
      { "src": "/screenshot.png", "sizes": "1280x720" }
    ],
    "shortcuts": [
      { "name": "Home", "url": "/" }
    ]
  }
  ```
- **Expected Output:** Nested objects preserved
- **Priority:** Medium

---

### Category 7: Edge Cases (EC-001 to EC-008)

#### EC-001: Self-Closing Link Tag
- **Type:** Unit
- **Description:** Handle self-closing manifest link
- **Input HTML:** `<link rel="manifest" href="/manifest.json" />`
- **Expected Output:** `/manifest.json`
- **Priority:** Low

#### EC-002: Multiple Manifest Links
- **Type:** Unit
- **Description:** Handle multiple manifest links (use first)
- **Input HTML:**
  ```html
  <link rel="manifest" href="/manifest-default.json">
  <link rel="manifest" href="/manifest-alt.json">
  ```
- **Expected Output:** `/manifest-default.json`
- **Priority:** Medium

#### EC-003: Whitespace in href
- **Type:** Unit
- **Description:** Handle whitespace around href value
- **Input HTML:** `<link rel="manifest" href="  /manifest.json  ">`
- **Expected Output:** `/manifest.json` (trimmed)
- **Priority:** Low

#### EC-004: HTML Entities in href
- **Type:** Unit
- **Description:** Handle HTML entities in manifest URL
- **Input HTML:** `<link rel="manifest" href="/manifest.json?app=test&amp;v=1">`
- **Expected Output:** `/manifest.json?app=test&v=1`
- **Priority:** Low

#### EC-005: BOM in Manifest JSON
- **Type:** Unit
- **Description:** Handle BOM (Byte Order Mark) in manifest
- **Manifest Response:** `\uFEFF{"name": "App"}`
- **Expected Output:** BOM stripped, manifest parsed
- **Priority:** Medium

#### EC-006: JSONP Response
- **Type:** Unit
- **Description:** Handle JSONP-style response (invalid)
- **Manifest Response:** `callback({"name": "App"});`
- **Expected Output:** INVALID_JSON error
- **Priority:** Medium

#### EC-007: Comment in JSON
- **Type:** Unit
- **Description:** Handle JSON with comments (non-standard)
- **Manifest Response:**
  ```json
  {
    // This is a comment
    "name": "App"
  }
  ```
- **Expected Output:** INVALID_JSON error (standard JSON doesn't support comments)
- **Priority:** Low

#### EC-008: Very Long Manifest URL
- **Type:** Unit
- **Description:** Handle very long manifest URL path
- **Manifest URL:** `/very/long/path/.../manifest.json` (>500 chars)
- **Expected Output:** Handle gracefully (may truncate or reject)
- **Priority:** Low

---

## Test File Locations

| Test Type | Location |
|-----------|----------|
| Unit Tests | `backend/tests/Unit/Services/ManifestFetcherServiceTest.php` |
| Integration Tests | `backend/tests/Feature/Services/ManifestFetcherServiceTest.php` |

## Test Scenarios Summary

| ID | Category | Description | Priority |
|----|----------|-------------|----------|
| MF-001 | fetch() | Successful manifest fetch | High |
| MF-002 | fetch() | No manifest link | High |
| MF-003 | fetch() | Invalid JSON | High |
| MF-004 | fetch() | Timeout handling | High |
| MF-005 | fetch() | Size limit | Medium |
| MF-006 | fetch() | HTML fetch failure | High |
| MF-007 | fetch() | Null URL | Medium |
| MF-008 | fetch() | Empty URL | Medium |
| MF-009 | fetch() | Manifest HTTP error | High |
| MF-010 | fetch() | Complete manifest with icons | High |
| EM-001 | extractManifestUrl() | Standard link | High |
| EM-002 | extractManifestUrl() | Alternate attribute order | Medium |
| EM-003 | extractManifestUrl() | Additional attributes | Medium |
| EM-004 | extractManifestUrl() | No manifest link | High |
| EM-005 | extractManifestUrl() | Multiple link tags | High |
| EM-006 | extractManifestUrl() | Absolute URL | High |
| EM-007 | extractManifestUrl() | Protocol-relative URL | Medium |
| EM-008 | extractManifestUrl() | Query parameters | Medium |
| EM-009 | extractManifestUrl() | Malformed HTML | Low |
| EM-010 | extractManifestUrl() | Empty href | Medium |
| RM-001 | resolveManifestUrl() | Relative path | High |
| RM-002 | resolveManifestUrl() | Relative with subdirectory | High |
| RM-003 | resolveManifestUrl() | Absolute URL | High |
| RM-004 | resolveManifestUrl() | Protocol-relative | Medium |
| RM-005 | resolveManifestUrl() | Port in base URL | Medium |
| RM-006 | resolveManifestUrl() | Query string in base | Low |
| RM-007 | resolveManifestUrl() | Fragment in base | Low |
| RM-008 | resolveManifestUrl() | Null input | Medium |
| CO-001 | Cross-Origin | CDN manifest | High |
| CO-002 | Cross-Origin | SSRF blocked | High |
| CO-003 | Cross-Origin | Data URI rejected | Medium |
| CO-004 | Cross-Origin | JavaScript URI rejected | Medium |
| CO-005 | Cross-Origin | Redirect chain | Medium |
| SSRF-001 | Security | Localhost blocked | High |
| SSRF-002 | Security | Private IP blocked | High |
| SSRF-003 | Security | Cloud metadata blocked | High |
| SSRF-004 | Security | Decimal IP blocked | High |
| SSRF-005 | Security | Hex IP blocked | High |
| SSRF-006 | Security | DNS rebinding protection | High |
| SSRF-007 | Security | File protocol blocked | High |
| MCV-001 | Validation | Empty manifest | Medium |
| MCV-002 | Validation | Minimal manifest | Medium |
| MCV-003 | Validation | Unicode handling | Medium |
| MCV-004 | Validation | Large icons array | Low |
| MCV-005 | Validation | Invalid icon object | Medium |
| MCV-006 | Validation | Nested objects | Medium |
| EC-001 | Edge Case | Self-closing tag | Low |
| EC-002 | Edge Case | Multiple manifest links | Medium |
| EC-003 | Edge Case | Whitespace in href | Low |
| EC-004 | Edge Case | HTML entities | Low |
| EC-005 | Edge Case | BOM in JSON | Medium |
| EC-006 | Edge Case | JSONP response | Medium |
| EC-007 | Edge Case | JSON comments | Low |
| EC-008 | Edge Case | Very long URL | Low |

**Total Test Cases:** 51

## Coverage Targets

| Category | Target | Status |
|----------|--------|--------|
| fetch() method | 100% | Pending |
| extractManifestUrl() method | 100% | Pending |
| resolveManifestUrl() method | 100% | Pending |
| Cross-origin handling | 100% | Pending |
| SSRF protection | 100% | Pending |
| Manifest validation | 100% | Pending |
| Edge cases | 100% | Pending |

## Test Execution Commands

```bash
# Run all ManifestFetcherService tests
php artisan test --filter ManifestFetcherServiceTest

# Run unit tests only
php artisan test --filter ManifestFetcherServiceTest tests/Unit

# Run integration tests only
php artisan test --filter ManifestFetcherServiceTest tests/Feature

# Run with coverage
php artisan test --coverage --filter ManifestFetcherServiceTest
```

## Dependencies
- URLValidationService (TASK-16-1) - Done ✅
- HtmlFetcherService (TASK-18-5) - Done ✅
- HtmlParserService (TASK-16-3) - Done ✅
- PWAAnalysis model (TASK-24-1) - In Deployment

## Notes for Developer

### Service Location
`backend/app/Services/ManifestFetcherService.php`

### Expected Service Methods

```php
<?php

namespace App\Services;

class ManifestFetcherService
{
    /**
     * Fetch and parse web app manifest from a URL.
     *
     * @param string|null $siteUrl
     * @return array{
     *     success: bool,
     *     manifest: array|null,
     *     manifest_url: string|null,
     *     error: string|null,
     *     error_code: string|null
     * }
     */
    public function fetch(?string $siteUrl): array;

    /**
     * Extract manifest URL from HTML.
     *
     * @param string $html
     * @param string $baseUrl
     * @return string|null
     */
    public function extractManifestUrl(string $html, string $baseUrl): ?string;

    /**
     * Resolve relative manifest URL to absolute.
     *
     * @param string|null $manifestUrl
     * @param string $baseUrl
     * @return string|null
     */
    public function resolveManifestUrl(?string $manifestUrl, string $baseUrl): ?string;
}
```

### Key Implementation Notes

1. **SSRF Protection:** MUST use URLValidationService for ALL URL validations
2. **Timeout:** 30 seconds total for HTML + manifest fetch
3. **Size Limit:** 10KB max for manifest JSON
4. **HTML Parsing:** Use Symfony DomCrawler (already in project)
5. **HTTP Client:** Use Laravel HTTP Client
6. **Error Handling:** Log warnings (not errors) for missing manifests
7. **BOM Handling:** Strip UTF-8 BOM from manifest JSON before parsing

### Mock Data for Testing

Test manifests should include:
- Minimal manifest (name only)
- Complete manifest with all fields
- Manifest with icons array
- Invalid JSON
- Empty object
- Unicode content

---

## Comments
_Agent communication log_

### [2026-03-01 00:38 GMT+2] Agent: PreTester
Created test documentation for TASK-24-3 (ManifestFetcherService).

**Test Categories:**
1. **fetch() Method (10 tests):** Main entry point for fetching manifests
2. **extractManifestUrl() Method (10 tests):** HTML parsing for manifest link
3. **resolveManifestUrl() Method (8 tests):** URL resolution logic
4. **Cross-Origin Handling (5 tests):** CDN and cross-origin manifests
5. **SSRF Protection (7 tests):** Security validation using URLValidationService
6. **Manifest Content Validation (6 tests):** JSON parsing and structure
7. **Edge Cases (8 tests):** Malformed input, BOM, JSONP, etc.

**Total Test Cases:** 51

**Key Dependencies:**
- URLValidationService for SSRF protection (TASK-16-1)
- HtmlFetcherService for HTTP requests (TASK-18-5)
- HtmlParserService/Symfony DomCrawler for HTML parsing

**Security Focus:** All SSRF tests are High priority. The service MUST reuse URLValidationService for all URL validations to prevent SSRF attacks through manifest URLs.
