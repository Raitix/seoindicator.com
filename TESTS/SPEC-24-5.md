# TESTS/SPEC-24-5: IconValidatorService

## Related
- Task: TASKS/TASK-24-5.md
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
- Requires ManifestFetcherService (TASK-24-3 - To Develop)
- Uses Laravel HTTP Client for concurrent requests

## Overview

IconValidatorService is responsible for:
1. Validating PWA icon URLs for accessibility and correctness
2. Checking if icons exist and are properly sized
3. Validating required icon sizes: 192x192, 512x512
4. Validating recommended icon sizes: 180x180 (iOS)
5. Resolving relative icon URLs against base URL
6. Using HEAD requests for accessibility checks (faster than GET)
7. Concurrent icon validation (up to 5 simultaneous checks)
8. Timeout per icon: 10 seconds
9. Returning structured IconValidationResult DTO

## Test Cases

### Category 1: validateIcons() Method (IV-001 to IV-012)

#### IV-001: Validate Icons Successfully
- **Type:** Unit
- **Description:** Verify all icons validated correctly with required sizes present
- **Input:**
  ```php
  $icons = [
    ['src' => '/icons/192.png', 'sizes' => '192x192', 'type' => 'image/png'],
    ['src' => '/icons/512.png', 'sizes' => '512x512', 'type' => 'image/png'],
    ['src' => '/icons/180.png', 'sizes' => '180x180', 'type' => 'image/png'],
  ];
  $baseUrl = 'https://example.com';
  ```
- **Expected Output:**
  ```php
  [
    'success' => true,
    'total' => 3,
    'valid' => 3,
    'has_192x192' => true,
    'has_512x512' => true,
    'has_180x180' => true,
    'icons' => [
      ['src' => '/icons/192.png', 'accessible' => true, 'sizes' => '192x192'],
      ['src' => '/icons/512.png', 'accessible' => true, 'sizes' => '512x512'],
      ['src' => '/icons/180.png', 'accessible' => true, 'sizes' => '180x180'],
    ],
    'errors' => [],
  ]
  ```
- **Priority:** High

#### IV-002: Missing Required Icon Sizes
- **Type:** Unit
- **Description:** Verify flags for missing required sizes
- **Input:**
  ```php
  $icons = [
    ['src' => '/icons/192.png', 'sizes' => '192x192', 'type' => 'image/png'],
  ];
  $baseUrl = 'https://example.com';
  ```
- **Expected Output:**
  ```php
  [
    'success' => true,
    'total' => 1,
    'valid' => 1,
    'has_192x192' => true,
    'has_512x512' => false, // Missing!
    'has_180x180' => false,
    'errors' => [],
  ]
  ```
- **Priority:** High

#### IV-003: Empty Icons Array
- **Type:** Unit
- **Description:** Handle empty icons array gracefully
- **Input:** `[]`, `$baseUrl = 'https://example.com'`
- **Expected Output:**
  ```php
  [
    'success' => true,
    'total' => 0,
    'valid' => 0,
    'has_192x192' => false,
    'has_512x512' => false,
    'has_180x180' => false,
    'icons' => [],
    'errors' => [],
  ]
  ```
- **Priority:** High

#### IV-004: Null Icons Input
- **Type:** Unit
- **Description:** Handle null icons input
- **Input:** `null`, `$baseUrl = 'https://example.com'`
- **Expected Output:**
  ```php
  [
    'success' => false,
    'error' => 'Icons array is required',
    'error_code' => 'INVALID_INPUT',
  ]
  ```
- **Priority:** Medium

#### IV-005: Null Base URL
- **Type:** Unit
- **Description:** Handle null base URL
- **Input:** `$icons = [...]`, `$baseUrl = null`
- **Expected Output:**
  ```php
  [
    'success' => false,
    'error' => 'Base URL is required',
    'error_code' => 'INVALID_INPUT',
  ]
  ```
- **Priority:** Medium

#### IV-006: Inaccessible Icon URL
- **Type:** Unit
- **Description:** Mark icon as inaccessible when HTTP returns error
- **Input:**
  ```php
  $icons = [
    ['src' => '/icons/missing.png', 'sizes' => '192x192', 'type' => 'image/png'],
  ];
  ```
- **HTTP Mock:** HEAD returns 404
- **Expected Output:**
  ```php
  [
    'success' => true,
    'total' => 1,
    'valid' => 0,
    'icons' => [
      ['src' => '/icons/missing.png', 'accessible' => false, 'error' => 'HTTP 404'],
    ],
    'errors' => ['Icon /icons/missing.png not accessible: HTTP 404'],
  ]
  ```
- **Priority:** High

#### IV-007: Icon Timeout
- **Type:** Unit
- **Description:** Handle timeout per icon (10 seconds)
- **Input:** Icon URL that takes >10s to respond
- **Expected Output:**
  ```php
  [
    'success' => true,
    'total' => 1,
    'valid' => 0,
    'icons' => [
      ['src' => '/icons/slow.png', 'accessible' => false, 'error' => 'Timeout'],
    ],
    'errors' => ['Icon /icons/slow.png timeout'],
  ]
  ```
- **Priority:** High

#### IV-008: Concurrent Validation (5 simultaneous)
- **Type:** Unit
- **Description:** Verify concurrent validation works for many icons
- **Input:** 10 icons (should validate in 2 batches of 5)
- **Expected Output:** All 10 icons validated, concurrent pool size respected
- **Priority:** Medium

#### IV-009: Relative URL Resolution
- **Type:** Unit
- **Description:** Verify relative URLs are resolved against base URL
- **Input:**
  ```php
  $icons = [
    ['src' => '/icons/192.png', 'sizes' => '192x192'],
  ];
  $baseUrl = 'https://example.com/pwa/';
  ```
- **Expected Resolution:** `https://example.com/icons/192.png`
- **Priority:** High

#### IV-010: Absolute Icon URL
- **Type:** Unit
- **Description:** Verify absolute URLs are used as-is
- **Input:**
  ```php
  $icons = [
    ['src' => 'https://cdn.example.com/icons/192.png', 'sizes' => '192x192'],
  ];
  ```
- **Expected URL:** `https://cdn.example.com/icons/192.png`
- **Priority:** High

#### IV-011: Mixed Required and Recommended Sizes
- **Type:** Unit
- **Description:** Verify correct size detection for mixed icons
- **Input:**
  ```php
  $icons = [
    ['src' => '/192.png', 'sizes' => '192x192'],
    ['src' => '/180.png', 'sizes' => '180x180'], // iOS recommended
    ['src' => '/256.png', 'sizes' => '256x256'], // Other size
  ];
  ```
- **Expected Output:**
  ```php
  [
    'has_192x192' => true,
    'has_512x512' => false,
    'has_180x180' => true,
  ]
  ```
- **Priority:** High

#### IV-012: Icon With "any" Size
- **Type:** Unit
- **Description:** Handle SVG icons with "any" size
- **Input:**
  ```php
  $icons = [
    ['src' => '/icon.svg', 'sizes' => 'any', 'type' => 'image/svg+xml'],
  ];
  ```
- **Expected Output:**
  ```php
  [
    'success' => true,
    'icons' => [
      ['src' => '/icon.svg', 'accessible' => true, 'sizes' => 'any', 'is_svg' => true],
    ],
  ]
  ```
- **Priority:** Medium

---

### Category 2: validateIcon() Method (VI-001 to VI-008)

#### VI-001: Valid Single Icon
- **Type:** Unit
- **Description:** Validate a single icon correctly
- **Input:**
  ```php
  $icon = ['src' => '/icon.png', 'sizes' => '192x192', 'type' => 'image/png'];
  $baseUrl = 'https://example.com';
  ```
- **Expected Output:**
  ```php
  [
    'src' => '/icon.png',
    'resolved_url' => 'https://example.com/icon.png',
    'accessible' => true,
    'sizes' => '192x192',
    'type' => 'image/png',
    'width' => 192,
    'height' => 192,
  ]
  ```
- **Priority:** High

#### VI-002: Invalid Size String
- **Type:** Unit
- **Description:** Handle invalid size string gracefully
- **Input:** `$icon = ['src' => '/icon.png', 'sizes' => 'invalid']`
- **Expected Output:**
  ```php
  [
    'src' => '/icon.png',
    'accessible' => true,
    'sizes' => 'invalid',
    'width' => null,
    'height' => null,
    'warning' => 'Could not parse size',
  ]
  ```
- **Priority:** Medium

#### VI-003: Missing src Attribute
- **Type:** Unit
- **Description:** Handle icon without src
- **Input:** `$icon = ['sizes' => '192x192']`
- **Expected Output:**
  ```php
  [
    'error' => 'Icon missing src attribute',
    'accessible' => false,
  ]
  ```
- **Priority:** High

#### VI-004: Maskable Icon Purpose
- **Type:** Unit
- **Description:** Handle maskable icon with purpose attribute
- **Input:**
  ```php
  $icon = [
    'src' => '/maskable.png',
    'sizes' => '512x512',
    'purpose' => 'maskable any',
  ];
  ```
- **Expected Output:**
  ```php
  [
    'src' => '/maskable.png',
    'accessible' => true,
    'purpose' => 'maskable any',
    'is_maskable' => true,
  ]
  ```
- **Priority:** Medium

#### VI-005: Icon With Query Parameters
- **Type:** Unit
- **Description:** Handle icon URL with query parameters
- **Input:** `$icon = ['src' => '/icon.png?v=1.2.3', 'sizes' => '192x192']`
- **Expected URL:** `https://example.com/icon.png?v=1.2.3`
- **Priority:** Medium

#### VI-006: Protocol-Relative URL
- **Type:** Unit
- **Description:** Handle protocol-relative icon URL
- **Input:** `$icon = ['src' => '//cdn.example.com/icon.png', 'sizes' => '192x192']`
- **Expected URL:** `https://cdn.example.com/icon.png`
- **Priority:** Medium

#### VI-007: Multiple Sizes in String
- **Type:** Unit
- **Description:** Handle icon with multiple sizes
- **Input:** `$icon = ['src' => '/icon.png', 'sizes' => '72x72 96x96 128x128 192x192']`
- **Expected Output:**
  ```php
  [
    'sizes' => '72x72 96x96 128x128 192x192',
    'parsed_sizes' => [[72, 72], [96, 96], [128, 128], [192, 192]],
    'has_192x192' => true,
  ]
  ```
- **Priority:** Medium

#### VI-008: Data URI Icon
- **Type:** Unit
- **Description:** Handle data URI in icon src
- **Input:** `$icon = ['src' => 'data:image/png;base64,...', 'sizes' => '192x192']`
- **Expected Output:**
  ```php
  [
    'src' => 'data:image/png;base64,...',
    'accessible' => true, // Data URIs are inherently accessible
    'is_data_uri' => true,
  ]
  ```
- **Priority:** Low

---

### Category 3: checkIconAccessible() Method (CI-001 to CI-010)

#### CI-001: Accessible Icon (HEAD 200)
- **Type:** Unit
- **Description:** Verify accessible icon returns true
- **Input:** `https://example.com/icon.png`
- **HTTP Mock:** HEAD returns 200
- **Expected Output:** `true`
- **Priority:** High

#### CI-002: Inaccessible Icon (HEAD 404)
- **Type:** Unit
- **Description:** Verify inaccessible icon returns false
- **Input:** `https://example.com/missing.png`
- **HTTP Mock:** HEAD returns 404
- **Expected Output:** `false`
- **Priority:** High

#### CI-003: Forbidden Icon (HEAD 403)
- **Type:** Unit
- **Description:** Verify forbidden icon returns false
- **Input:** `https://example.com/protected.png`
- **HTTP Mock:** HEAD returns 403
- **Expected Output:** `false`
- **Priority:** High

#### CI-004: Server Error (HEAD 500)
- **Type:** Unit
- **Description:** Verify server error returns false
- **Input:** `https://example.com/error.png`
- **HTTP Mock:** HEAD returns 500
- **Expected Output:** `false`
- **Priority:** High

#### CI-005: Timeout Handling
- **Type:** Unit
- **Description:** Verify timeout returns false
- **Input:** URL that times out after 10s
- **Expected Output:** `false`
- **Priority:** High

#### CI-006: Redirect Follow
- **Type:** Unit
- **Description:** Verify redirect is followed
- **Input:** `https://example.com/icon` (redirects to `/icon.png`)
- **HTTP Mock:** HEAD returns 302 → 200
- **Expected Output:** `true`
- **Priority:** Medium

#### CI-007: Invalid URL
- **Type:** Unit
- **Description:** Verify invalid URL returns false
- **Input:** `not-a-valid-url`
- **Expected Output:** `false`
- **Priority:** Medium

#### CI-008: Null URL
- **Type:** Unit
- **Description:** Verify null URL returns false
- **Input:** `null`
- **Expected Output:** `false`
- **Priority:** Medium

#### CI-009: Content-Type Validation
- **Type:** Unit
- **Description:** Optionally validate Content-Type header
- **Input:** `https://example.com/icon.png`
- **HTTP Mock:** HEAD returns 200 with `Content-Type: text/html`
- **Expected Output:**
  ```php
  [
    'accessible' => true,
    'content_type' => 'text/html',
    'warning' => 'Unexpected content type for icon',
  ]
  ```
- **Priority:** Low

#### CI-010: File Size Header
- **Type:** Unit
- **Description:** Check Content-Length header if present
- **Input:** `https://example.com/icon.png`
- **HTTP Mock:** HEAD returns 200 with `Content-Length: 50000000` (50MB)
- **Expected Output:**
  ```php
  [
    'accessible' => true,
    'content_length' => 50000000,
    'warning' => 'Icon file is very large (50MB)',
  ]
  ```
- **Priority:** Low

---

### Category 4: SSRF Protection Tests (SSRF-001 to SSRF-008)

#### SSRF-001: Localhost Icon URL Blocked
- **Type:** Security
- **Description:** Icon URL pointing to localhost is blocked
- **Input:**
  ```php
  $icons = [
    ['src' => 'http://localhost/icon.png', 'sizes' => '192x192'],
  ];
  ```
- **Expected Output:**
  ```php
  [
    'icons' => [
      ['src' => 'http://localhost/icon.png', 'accessible' => false, 'error' => 'BLOCKED_URL'],
    ],
  ]
  ```
- **Priority:** High

#### SSRF-002: Private IP Icon URL Blocked
- **Type:** Security
- **Description:** Icon URL pointing to private IP is blocked
- **Input:** `$icons = [['src' => 'http://192.168.1.1/icon.png', ...]]`
- **Expected Output:** `accessible: false`, `error: BLOCKED_URL`
- **Priority:** High

#### SSRF-003: Cloud Metadata Icon URL Blocked
- **Type:** Security
- **Description:** Icon URL pointing to cloud metadata is blocked
- **Input:** `$icons = [['src' => 'http://169.254.169.254/icon.png', ...]]`
- **Expected Output:** `accessible: false`, `error: BLOCKED_URL`
- **Priority:** High

#### SSRF-004: Decimal IP Icon URL Blocked
- **Type:** Security
- **Description:** Decimal IP notation in icon URL is blocked
- **Input:** `$icons = [['src' => 'http://2130706433/icon.png', ...]]` (127.0.0.1)
- **Expected Output:** `accessible: false`, `error: BLOCKED_URL`
- **Priority:** High

#### SSRF-005: Hex IP Icon URL Blocked
- **Type:** Security
- **Description:** Hex IP notation in icon URL is blocked
- **Input:** `$icons = [['src' => 'http://0x7f000001/icon.png', ...]]` (127.0.0.1)
- **Expected Output:** `accessible: false`, `error: BLOCKED_URL`
- **Priority:** High

#### SSRF-006: File Protocol Blocked
- **Type:** Security
- **Description:** File:// protocol in icon URL is blocked
- **Input:** `$icons = [['src' => 'file:///etc/passwd', ...]]`
- **Expected Output:** `accessible: false`, `error: BLOCKED_PROTOCOL`
- **Priority:** High

#### SSRF-007: Redirect to Private IP Blocked
- **Type:** Security
- **Description:** Icon URL that redirects to private IP is blocked
- **Input:** `https://public.com/icon.png` redirects to `http://192.168.1.1/`
- **Expected Output:** `accessible: false`, `error: BLOCKED_REDIRECT`
- **Priority:** High

#### SSRF-008: Cross-Origin CDN Allowed
- **Type:** Security
- **Description:** Cross-origin CDN icon URL is allowed (valid CDN)
- **Input:** `$icons = [['src' => 'https://cdn.cloudflare.com/icon.png', ...]]`
- **Expected Output:** `accessible: true` (after SSRF validation)
- **Priority:** High

---

### Category 5: Size Parsing Tests (SP-001 to SP-008)

#### SP-001: Standard Size Format
- **Type:** Unit
- **Description:** Parse standard "WxH" format
- **Input:** `"192x192"`
- **Expected Output:** `['width' => 192, 'height' => 192]`
- **Priority:** High

#### SP-002: "any" Size
- **Type:** Unit
- **Description:** Handle "any" size for SVG icons
- **Input:** `"any"`
- **Expected Output:** `['width' => null, 'height' => null, 'is_svg' => true]`
- **Priority:** Medium

#### SP-003: Multiple Sizes
- **Type:** Unit
- **Description:** Parse multiple sizes in string
- **Input:** `"72x72 96x96 128x128"`
- **Expected Output:**
  ```php
  [
    ['width' => 72, 'height' => 72],
    ['width' => 96, 'height' => 96],
    ['width' => 128, 'height' => 128],
  ]
  ```
- **Priority:** Medium

#### SP-004: Invalid Size String
- **Type:** Unit
- **Description:** Handle invalid size gracefully
- **Input:** `"invalid"`
- **Expected Output:** `['width' => null, 'height' => null]`
- **Priority:** Medium

#### SP-005: Empty Size String
- **Type:** Unit
- **Description:** Handle empty size string
- **Input:** `""`
- **Expected Output:** `['width' => null, 'height' => null]`
- **Priority:** Medium

#### SP-006: Non-Square Size
- **Type:** Unit
- **Description:** Parse non-square size
- **Input:** `"512x256"`
- **Expected Output:** `['width' => 512, 'height' => 256]`
- **Priority:** Low

#### SP-007: Case Sensitivity
- **Type:** Unit
- **Description:** Handle uppercase X in size
- **Input:** `"192X192"`
- **Expected Output:** `['width' => 192, 'height' => 192]`
- **Priority:** Low

#### SP-008: Whitespace Handling
- **Type:** Unit
- **Description:** Handle extra whitespace in size
- **Input:** `" 192 x 192 "`
- **Expected Output:** `['width' => 192, 'height' => 192]`
- **Priority:** Low

---

### Category 6: URL Resolution Tests (UR-001 to UR-008)

#### UR-001: Relative Path Resolution
- **Type:** Unit
- **Description:** Resolve relative icon path
- **Input:** `$src = '/icons/192.png'`, `$baseUrl = 'https://example.com'`
- **Expected Output:** `https://example.com/icons/192.png`
- **Priority:** High

#### UR-002: Relative Path With Subdirectory
- **Type:** Unit
- **Description:** Resolve relative path with subdirectory in base
- **Input:** `$src = 'icon.png'`, `$baseUrl = 'https://example.com/pwa/'`
- **Expected Output:** `https://example.com/pwa/icon.png`
- **Priority:** High

#### UR-003: Absolute URL Unchanged
- **Type:** Unit
- **Description:** Absolute URL is not modified
- **Input:** `$src = 'https://cdn.example.com/icon.png'`
- **Expected Output:** `https://cdn.example.com/icon.png`
- **Priority:** High

#### UR-004: Protocol-Relative URL
- **Type:** Unit
- **Description:** Resolve protocol-relative URL
- **Input:** `$src = '//cdn.example.com/icon.png'`, `$baseUrl = 'https://example.com'`
- **Expected Output:** `https://cdn.example.com/icon.png`
- **Priority:** Medium

#### UR-005: Query Parameters Preserved
- **Type:** Unit
- **Description:** Query parameters are preserved
- **Input:** `$src = '/icon.png?v=1.2.3'`, `$baseUrl = 'https://example.com'`
- **Expected Output:** `https://example.com/icon.png?v=1.2.3`
- **Priority:** Medium

#### UR-006: Fragment Preserved
- **Type:** Unit
- **Description:** Fragment is preserved
- **Input:** `$src = '/icon.svg#icon-id'`, `$baseUrl = 'https://example.com'`
- **Expected Output:** `https://example.com/icon.svg#icon-id`
- **Priority:** Low

#### UR-007: Port in Base URL
- **Type:** Unit
- **Description:** Port is preserved from base URL
- **Input:** `$src = '/icon.png'`, `$baseUrl = 'https://example.com:8443'`
- **Expected Output:** `https://example.com:8443/icon.png`
- **Priority:** Medium

#### UR-008: Data URI Pass-Through
- **Type:** Unit
- **Description:** Data URI is not modified
- **Input:** `$src = 'data:image/png;base64,ABC123=='`
- **Expected Output:** `data:image/png;base64,ABC123==`
- **Priority:** Low

---

### Category 7: Result DTO Tests (RD-001 to RD-006)

#### RD-001: Complete Result Structure
- **Type:** Unit
- **Description:** Verify complete result structure
- **Expected Output:**
  ```php
  [
    'success' => bool,
    'total' => int,
    'valid' => int,
    'has_192x192' => bool,
    'has_512x512' => bool,
    'has_180x180' => bool,
    'icons' => array,
    'errors' => array,
    'warnings' => array, // optional
  ]
  ```
- **Priority:** High

#### RD-002: Total vs Valid Count
- **Type:** Unit
- **Description:** Verify correct counting of total and valid icons
- **Input:** 5 icons, 3 accessible, 2 inaccessible
- **Expected Output:** `total: 5`, `valid: 3`
- **Priority:** High

#### RD-003: Errors Array Population
- **Type:** Unit
- **Description:** Verify errors array contains relevant error messages
- **Input:** 2 inaccessible icons
- **Expected Output:** 2 error messages in errors array
- **Priority:** High

#### RD-004: Required Size Flags Accuracy
- **Type:** Unit
- **Description:** Verify size presence flags are accurate
- **Input:** Icons with 192x192 and 180x180 only
- **Expected Output:** `has_192x192: true`, `has_512x512: false`, `has_180x180: true`
- **Priority:** High

#### RD-005: Warnings for Non-Critical Issues
- **Type:** Unit
- **Description:** Non-critical issues go to warnings, not errors
- **Input:** Icon with wrong content-type but accessible
- **Expected Output:** Warning about content-type, not error
- **Priority:** Medium

#### RD-006: Success Flag Logic
- **Type:** Unit
- **Description:** Success is true even if some icons fail (partial success)
- **Input:** 3 icons, 1 fails
- **Expected Output:** `success: true`, `valid: 2`, `total: 3`
- **Priority:** High

---

### Category 8: Edge Cases (EC-001 to EC-010)

#### EC-001: Empty src Attribute
- **Type:** Unit
- **Description:** Handle empty src attribute
- **Input:** `$icon = ['src' => '', 'sizes' => '192x192']`
- **Expected Output:** Error about missing src
- **Priority:** Medium

#### EC-002: Very Long Icon URL
- **Type:** Unit
- **Description:** Handle very long icon URL
- **Input:** URL > 2000 characters
- **Expected Output:** Error or truncation handling
- **Priority:** Low

#### EC-003: Unicode in Icon URL
- **Type:** Unit
- **Description:** Handle Unicode characters in icon path
- **Input:** `https://example.com/アイコン.png`
- **Expected Output:** Properly URL-encoded request
- **Priority:** Medium

#### EC-004: Special Characters in URL
- **Type:** Unit
- **Description:** Handle special characters in icon path
- **Input:** `https://example.com/icon (1).png`
- **Expected Output:** Properly URL-encoded request
- **Priority:** Medium

#### EC-005: Missing sizes Attribute
- **Type:** Unit
- **Description:** Handle icon without sizes attribute
- **Input:** `$icon = ['src' => '/icon.png', 'type' => 'image/png']`
- **Expected Output:** Warning about missing sizes
- **Priority:** Medium

#### EC-006: Missing type Attribute
- **Type:** Unit
- **Description:** Handle icon without type attribute
- **Input:** `$icon = ['src' => '/icon.png', 'sizes' => '192x192']`
- **Expected Output:** Still validate, note missing type
- **Priority:** Medium

#### EC-007: Icon Index in Result
- **Type:** Unit
- **Description:** Verify icon index is preserved in result
- **Input:** 5 icons with various issues
- **Expected Output:** Icons array preserves original order
- **Priority:** Low

#### EC-008: Concurrent Request Limits
- **Type:** Unit
- **Description:** Verify max 5 concurrent requests
- **Input:** 20 icons
- **Expected Behavior:** Requests made in batches of 5
- **Priority:** Medium

#### EC-009: Head Request Not Supported
- **Type:** Unit
- **Description:** Handle server that doesn't support HEAD
- **Input:** HEAD returns 405
- **Expected Behavior:** Fallback to GET or mark as error
- **Priority:** Medium

#### EC-010: Self-Referencing URL
- **Type:** Unit
- **Description:** Handle icon URL same as base URL
- **Input:** `$src = '/'`, `$baseUrl = 'https://example.com'`
- **Expected Output:** `https://example.com/`
- **Priority:** Low

---

## Test File Locations

| Test Type | Location |
|-----------|----------|
| Unit Tests | `backend/tests/Unit/Services/IconValidatorServiceTest.php` |
| Integration Tests | `backend/tests/Feature/Services/IconValidatorServiceTest.php` |

## Test Scenarios Summary

| ID | Category | Description | Priority |
|----|----------|-------------|----------|
| IV-001 | validateIcons() | All icons valid with required sizes | High |
| IV-002 | validateIcons() | Missing required icon sizes | High |
| IV-003 | validateIcons() | Empty icons array | High |
| IV-004 | validateIcons() | Null icons input | Medium |
| IV-005 | validateIcons() | Null base URL | Medium |
| IV-006 | validateIcons() | Inaccessible icon | High |
| IV-007 | validateIcons() | Icon timeout | High |
| IV-008 | validateIcons() | Concurrent validation | Medium |
| IV-009 | validateIcons() | Relative URL resolution | High |
| IV-010 | validateIcons() | Absolute URL | High |
| IV-011 | validateIcons() | Mixed sizes | High |
| IV-012 | validateIcons() | "any" size (SVG) | Medium |
| VI-001 | validateIcon() | Valid single icon | High |
| VI-002 | validateIcon() | Invalid size string | Medium |
| VI-003 | validateIcon() | Missing src | High |
| VI-004 | validateIcon() | Maskable icon | Medium |
| VI-005 | validateIcon() | Query parameters | Medium |
| VI-006 | validateIcon() | Protocol-relative URL | Medium |
| VI-007 | validateIcon() | Multiple sizes | Medium |
| VI-008 | validateIcon() | Data URI | Low |
| CI-001 | checkIconAccessible() | Accessible (200) | High |
| CI-002 | checkIconAccessible() | Not found (404) | High |
| CI-003 | checkIconAccessible() | Forbidden (403) | High |
| CI-004 | checkIconAccessible() | Server error (500) | High |
| CI-005 | checkIconAccessible() | Timeout | High |
| CI-006 | checkIconAccessible() | Redirect follow | Medium |
| CI-007 | checkIconAccessible() | Invalid URL | Medium |
| CI-008 | checkIconAccessible() | Null URL | Medium |
| CI-009 | checkIconAccessible() | Content-Type validation | Low |
| CI-010 | checkIconAccessible() | File size header | Low |
| SSRF-001 | Security | Localhost blocked | High |
| SSRF-002 | Security | Private IP blocked | High |
| SSRF-003 | Security | Cloud metadata blocked | High |
| SSRF-004 | Security | Decimal IP blocked | High |
| SSRF-005 | Security | Hex IP blocked | High |
| SSRF-006 | Security | File protocol blocked | High |
| SSRF-007 | Security | Redirect to private blocked | High |
| SSRF-008 | Security | Cross-origin CDN allowed | High |
| SP-001 | Size Parsing | Standard format | High |
| SP-002 | Size Parsing | "any" size | Medium |
| SP-003 | Size Parsing | Multiple sizes | Medium |
| SP-004 | Size Parsing | Invalid size | Medium |
| SP-005 | Size Parsing | Empty size | Medium |
| SP-006 | Size Parsing | Non-square | Low |
| SP-007 | Size Parsing | Case sensitivity | Low |
| SP-008 | Size Parsing | Whitespace | Low |
| UR-001 | URL Resolution | Relative path | High |
| UR-002 | URL Resolution | Subdirectory | High |
| UR-003 | URL Resolution | Absolute URL | High |
| UR-004 | URL Resolution | Protocol-relative | Medium |
| UR-005 | URL Resolution | Query params | Medium |
| UR-006 | URL Resolution | Fragment | Low |
| UR-007 | URL Resolution | Port | Medium |
| UR-008 | URL Resolution | Data URI | Low |
| RD-001 | Result DTO | Complete structure | High |
| RD-002 | Result DTO | Count accuracy | High |
| RD-003 | Result DTO | Errors array | High |
| RD-004 | Result DTO | Size flags | High |
| RD-005 | Result DTO | Warnings | Medium |
| RD-006 | Result DTO | Success flag | High |
| EC-001 | Edge Case | Empty src | Medium |
| EC-002 | Edge Case | Long URL | Low |
| EC-003 | Edge Case | Unicode URL | Medium |
| EC-004 | Edge Case | Special chars | Medium |
| EC-005 | Edge Case | Missing sizes | Medium |
| EC-006 | Edge Case | Missing type | Medium |
| EC-007 | Edge Case | Icon index | Low |
| EC-008 | Edge Case | Concurrent limits | Medium |
| EC-009 | Edge Case | HEAD not supported | Medium |
| EC-010 | Edge Case | Self-referencing | Low |

**Total Test Cases:** 70

## Coverage Targets

| Category | Target | Status |
|----------|--------|--------|
| validateIcons() method | 100% | Pending |
| validateIcon() method | 100% | Pending |
| checkIconAccessible() method | 100% | Pending |
| SSRF protection | 100% | Pending |
| Size parsing | 100% | Pending |
| URL resolution | 100% | Pending |
| Result DTO | 100% | Pending |
| Edge cases | 100% | Pending |

## Test Execution Commands

```bash
# Run all IconValidatorService tests
php artisan test --filter IconValidatorServiceTest

# Run unit tests only
php artisan test --filter IconValidatorServiceTest tests/Unit

# Run integration tests only
php artisan test --filter IconValidatorServiceTest tests/Feature

# Run with coverage
php artisan test --coverage --filter IconValidatorServiceTest
```

## Dependencies
- URLValidationService (TASK-16-1) - Done ✅
- ManifestFetcherService (TASK-24-3) - To Develop
- Laravel HTTP Client for concurrent requests

## Notes for Developer

### Service Location
`backend/app/Services/IconValidatorService.php`

### Expected Service Methods

```php
<?php

namespace App\Services;

class IconValidatorService
{
    /**
     * Validate multiple PWA icons.
     *
     * @param array|null $icons Array of icon objects from manifest
     * @param string|null $baseUrl Base URL for resolving relative URLs
     * @return array{
     *     success: bool,
     *     total: int,
     *     valid: int,
     *     has_192x192: bool,
     *     has_512x512: bool,
     *     has_180x180: bool,
     *     icons: array,
     *     errors: array,
     *     warnings?: array
     * }
     */
    public function validateIcons(?array $icons, ?string $baseUrl): array;

    /**
     * Validate a single icon.
     *
     * @param array $icon Icon object from manifest
     * @param string $baseUrl Base URL for resolving relative URLs
     * @return array{
     *     src: string,
     *     resolved_url: string,
     *     accessible: bool,
     *     sizes: string,
     *     type?: string,
     *     width?: int|null,
     *     height?: int|null,
     *     error?: string
     * }
     */
    public function validateIcon(array $icon, string $baseUrl): array;

    /**
     * Check if an icon URL is accessible via HEAD request.
     *
     * @param string|null $url Icon URL to check
     * @return bool True if accessible
     */
    public function checkIconAccessible(?string $url): bool;
}
```

### Key Implementation Notes

1. **SSRF Protection:** MUST use URLValidationService for ALL URL validations
2. **HTTP Method:** Use HEAD requests for faster accessibility checks
3. **Timeout:** 10 seconds per icon
4. **Concurrency:** Maximum 5 simultaneous requests
5. **Required Sizes:** 192x192, 512x512 (PWA requirements)
6. **Recommended Sizes:** 180x180 (iOS)
7. **Size Parsing:** Handle "any" for SVG icons
8. **Error Handling:** Log inaccessible icons as warnings (not errors)
9. **Partial Success:** Return success even if some icons fail
10. **Data URIs:** Treat as inherently accessible

### Icon Size Requirements (PWA)

| Size | Requirement | Purpose |
|------|-------------|---------|
| 192x192 | Required | Android Chrome |
| 512x512 | Required | Android Chrome (splash) |
| 180x180 | Recommended | iOS (apple-touch-icon) |

### Mock Data for Testing

Test icons should include:
- Standard PNG icons (192x192, 512x512)
- SVG icons with "any" size
- Maskable icons
- Icons with multiple sizes
- Invalid/inaccessible URLs
- Data URIs

---

## Comments
_Agent communication log_

### [2026-03-01 00:50 GMT+2] Agent: PreTester
Created test documentation for TASK-24-5 (IconValidatorService).

**Test Categories:**
1. **validateIcons() Method (12 tests):** Main entry point for validating icon arrays
2. **validateIcon() Method (8 tests):** Single icon validation
3. **checkIconAccessible() Method (10 tests):** HEAD request accessibility checks
4. **SSRF Protection (8 tests):** Security validation using URLValidationService
5. **Size Parsing (8 tests):** Parse "WxH" and "any" formats
6. **URL Resolution (8 tests):** Relative to absolute URL conversion
7. **Result DTO (6 tests):** Output structure validation
8. **Edge Cases (10 tests):** Malformed input, Unicode, etc.

**Total Test Cases:** 70

**Key Dependencies:**
- URLValidationService for SSRF protection (TASK-16-1) - Done ✅
- ManifestFetcherService (TASK-24-3) - To Develop

**Security Focus:** All SSRF tests are High priority. The service MUST reuse URLValidationService for all URL validations to prevent SSRF attacks through icon URLs.

**Concurrency:** Use Laravel HTTP Client's pool() method for concurrent requests (max 5 at a time).
