# TESTS/SPEC-24-4: ManifestValidatorService

## Related
- Task: TASKS/TASK-24-4.md
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
- Requires ManifestValidationResult DTO (created with this task)
- Requires ManifestError DTO (created with this task)
- Pure validation logic - no external dependencies

## Overview

ManifestValidatorService is responsible for:
1. Validating PWA manifest structure against W3C Web App Manifest specification
2. Checking required fields: name, short_name, start_url, display, icons
3. Validating recommended fields: background_color, theme_color, description
4. Validating display enum values: standalone, fullscreen, minimal-ui, browser
5. Validating icons array structure (src, sizes, type, purpose)
6. Returning detailed validation results with errors and warnings

## Test Cases

### Category 1: validate() Method (MV-001 to MV-010)

#### MV-001: Validate Complete Valid Manifest
- **Type:** Unit
- **Description:** Verify valid manifest passes all checks
- **Input:**
  ```php
  [
    'name' => 'Example App',
    'short_name' => 'Example',
    'start_url' => '/',
    'display' => 'standalone',
    'icons' => [
      ['src' => '/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png']
    ],
    'background_color' => '#ffffff',
    'theme_color' => '#000000',
    'description' => 'A sample app'
  ]
  ```
- **Expected Output:**
  ```php
  ManifestValidationResult {
    valid: true,
    errors: [],
    warnings: [],
    fieldPresence: [
      'name' => true,
      'short_name' => true,
      'start_url' => true,
      'display' => true,
      'icons' => true,
      'background_color' => true,
      'theme_color' => true,
      'description' => true
    ]
  }
  ```
- **Priority:** High

#### MV-002: Missing Required Field - name
- **Type:** Unit
- **Description:** Verify error when name is missing
- **Input:**
  ```php
  [
    'short_name' => 'Example',
    'start_url' => '/',
    'display' => 'standalone',
    'icons' => []
  ]
  ```
- **Expected Output:**
  ```php
  ManifestValidationResult {
    valid: false,
    errors: [
      ManifestError { field: 'name', code: 'REQUIRED_FIELD_MISSING', message: 'The name field is required' }
    ],
    fieldPresence: ['name' => false, ...]
  }
  ```
- **Priority:** High

#### MV-003: Missing Required Field - short_name
- **Type:** Unit
- **Description:** Verify error when short_name is missing
- **Input:**
  ```php
  [
    'name' => 'Example App',
    'start_url' => '/',
    'display' => 'standalone',
    'icons' => []
  ]
  ```
- **Expected Output:** Error for missing short_name field
- **Priority:** High

#### MV-004: Missing Required Field - start_url
- **Type:** Unit
- **Description:** Verify error when start_url is missing
- **Input:** Manifest without start_url
- **Expected Output:** Error for missing start_url field
- **Priority:** High

#### MV-005: Missing Required Field - display
- **Type:** Unit
- **Description:** Verify error when display is missing
- **Input:** Manifest without display
- **Expected Output:** Error for missing display field
- **Priority:** High

#### MV-006: Missing Required Field - icons
- **Type:** Unit
- **Description:** Verify error when icons array is missing
- **Input:** Manifest without icons
- **Expected Output:** Error for missing icons field
- **Priority:** High

#### MV-007: Empty Icons Array
- **Type:** Unit
- **Description:** Verify error when icons array is empty
- **Input:**
  ```php
  [
    'name' => 'Example App',
    'short_name' => 'Example',
    'start_url' => '/',
    'display' => 'standalone',
    'icons' => []
  ]
  ```
- **Expected Output:** Error - icons array must contain at least one icon
- **Priority:** High

#### MV-008: Invalid Display Value
- **Type:** Unit
- **Description:** Verify error for invalid display enum value
- **Input:**
  ```php
  [
    'name' => 'Example App',
    'short_name' => 'Example',
    'start_url' => '/',
    'display' => 'invalid-mode',
    'icons' => [['src' => '/icon.png']]
  ]
  ```
- **Expected Output:** Error - display must be one of: standalone, fullscreen, minimal-ui, browser
- **Priority:** High

#### MV-009: Empty Manifest
- **Type:** Unit
- **Description:** Verify errors for all missing required fields
- **Input:** `[]`
- **Expected Output:** 5 errors (one for each required field)
- **Priority:** High

#### MV-010: Null Input
- **Type:** Unit
- **Description:** Verify handling of null input
- **Input:** `null`
- **Expected Output:** Error - manifest is required
- **Priority:** Medium

---

### Category 2: validateField() Method (VF-001 to VF-012)

#### VF-001: Validate name Field - Valid
- **Type:** Unit
- **Description:** Validate name field with valid string
- **Input:** `name`, `'Example App'`
- **Expected Output:** `true`
- **Priority:** High

#### VF-002: Validate name Field - Empty String
- **Type:** Unit
- **Description:** Validate name field with empty string
- **Input:** `name`, `''`
- **Expected Output:** `false` (error added)
- **Priority:** High

#### VF-003: Validate short_name Field - Valid
- **Type:** Unit
- **Description:** Validate short_name field
- **Input:** `short_name`, `'Example'`
- **Expected Output:** `true`
- **Priority:** High

#### VF-004: Validate start_url Field - Valid Relative
- **Type:** Unit
- **Description:** Validate start_url with relative path
- **Input:** `start_url`, `'/'`
- **Expected Output:** `true`
- **Priority:** High

#### VF-005: Validate start_url Field - Valid Absolute
- **Type:** Unit
- **Description:** Validate start_url with absolute URL
- **Input:** `start_url`, `'https://example.com/'`
- **Expected Output:** `true`
- **Priority:** High

#### VF-006: Validate display Field - standalone
- **Type:** Unit
- **Description:** Validate display field with valid value
- **Input:** `display`, `'standalone'`
- **Expected Output:** `true`
- **Priority:** High

#### VF-007: Validate display Field - fullscreen
- **Type:** Unit
- **Description:** Validate display field with fullscreen
- **Input:** `display`, `'fullscreen'`
- **Expected Output:** `true`
- **Priority:** High

#### VF-008: Validate display Field - minimal-ui
- **Type:** Unit
- **Description:** Validate display field with minimal-ui
- **Input:** `display`, `'minimal-ui'`
- **Expected Output:** `true`
- **Priority:** High

#### VF-009: Validate display Field - browser
- **Type:** Unit
- **Description:** Validate display field with browser
- **Input:** `display`, `'browser'`
- **Expected Output:** `true`
- **Priority:** High

#### VF-010: Validate display Field - Invalid
- **Type:** Unit
- **Description:** Validate display field with invalid value
- **Input:** `display`, `'invalid'`
- **Expected Output:** `false` (error added)
- **Priority:** High

#### VF-011: Validate icons Field - Valid Array
- **Type:** Unit
- **Description:** Validate icons field with valid array
- **Input:** `icons`, `[['src' => '/icon.png', 'sizes' => '192x192']]`
- **Expected Output:** `true`
- **Priority:** High

#### VF-012: Validate icons Field - Not Array
- **Type:** Unit
- **Description:** Validate icons field with non-array value
- **Input:** `icons`, `'not-an-array'`
- **Expected Output:** `false` (error added)
- **Priority:** High

---

### Category 3: Icon Validation (IV-001 to IV-010)

#### IV-001: Valid Icon With All Required Fields
- **Type:** Unit
- **Description:** Validate icon with src, sizes, type
- **Input:**
  ```php
  ['src' => '/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png']
  ```
- **Expected Output:** Valid
- **Priority:** High

#### IV-002: Icon Missing src Field
- **Type:** Unit
- **Description:** Validate icon without src
- **Input:** `['sizes' => '192x192', 'type' => 'image/png']`
- **Expected Output:** Error - icon src is required
- **Priority:** High

#### IV-003: Icon Missing sizes Field
- **Type:** Unit
- **Description:** Validate icon without sizes
- **Input:** `['src' => '/icon.png', 'type' => 'image/png']`
- **Expected Output:** Valid (sizes is recommended but not required)
- **Priority:** Medium

#### IV-004: Icon Missing type Field
- **Type:** Unit
- **Description:** Validate icon without type
- **Input:** `['src' => '/icon.png', 'sizes' => '192x192']`
- **Expected Output:** Valid (type is recommended but not required)
- **Priority:** Medium

#### IV-005: Icon With purpose Field - Valid
- **Type:** Unit
- **Description:** Validate icon with purpose
- **Input:** `['src' => '/icon.png', 'sizes' => '192x192', 'purpose' => 'any']`
- **Expected Output:** Valid
- **Priority:** Medium

#### IV-006: Icon With purpose Field - any maskable
- **Type:** Unit
- **Description:** Validate icon with multiple purposes
- **Input:** `['src' => '/icon.png', 'sizes' => '192x192', 'purpose' => 'any maskable']`
- **Expected Output:** Valid
- **Priority:** Medium

#### IV-007: Icon With Invalid purpose
- **Type:** Unit
- **Description:** Validate icon with invalid purpose
- **Input:** `['src' => '/icon.png', 'sizes' => '192x192', 'purpose' => 'invalid']`
- **Expected Output:** Warning - invalid purpose value
- **Priority:** Medium

#### IV-008: Icon With Empty src
- **Type:** Unit
- **Description:** Validate icon with empty src
- **Input:** `['src' => '', 'sizes' => '192x192']`
- **Expected Output:** Error - icon src cannot be empty
- **Priority:** High

#### IV-009: Icon sizes Format - Valid
- **Type:** Unit
- **Description:** Validate icon sizes format
- **Input:** Various: `'192x192'`, `'512x512'`, `'any'`, `'192x192 512x512'`
- **Expected Output:** All valid
- **Priority:** Medium

#### IV-010: Icon type Valid Values
- **Type:** Unit
- **Description:** Validate icon type values
- **Input:** `'image/png'`, `'image/svg+xml'`, `'image/webp'`
- **Expected Output:** All valid
- **Priority:** Medium

---

### Category 4: Recommended Fields Validation (RF-001 to RF-006)

#### RF-001: Missing background_color - Warning
- **Type:** Unit
- **Description:** Verify warning when background_color missing
- **Input:** Manifest without background_color
- **Expected Output:** Warning (not error) - background_color recommended
- **Priority:** Medium

#### RF-002: Missing theme_color - Warning
- **Type:** Unit
- **Description:** Verify warning when theme_color missing
- **Input:** Manifest without theme_color
- **Expected Output:** Warning - theme_color recommended
- **Priority:** Medium

#### RF-003: Missing description - Warning
- **Type:** Unit
- **Description:** Verify warning when description missing
- **Input:** Manifest without description
- **Expected Output:** Warning - description recommended
- **Priority:** Medium

#### RF-004: Invalid background_color Format
- **Type:** Unit
- **Description:** Validate background_color format
- **Input:** `'invalid-color'`
- **Expected Output:** Warning - invalid color format
- **Priority:** Low

#### RF-005: Valid background_color Formats
- **Type:** Unit
- **Description:** Validate valid color formats
- **Input:** `'#ffffff'`, `'#FFF'`, `'white'`, `'rgb(255, 255, 255)'`
- **Expected Output:** All valid
- **Priority:** Medium

#### RF-006: Valid theme_color Formats
- **Type:** Unit
- **Description:** Validate valid theme_color formats
- **Input:** `'#000000'`, `'#000'`, `'black'`
- **Expected Output:** All valid
- **Priority:** Medium

---

### Category 5: getErrors() Method (GE-001 to GE-003)

#### GE-001: Get Errors After Validation
- **Type:** Unit
- **Description:** Retrieve errors after validation
- **Preconditions:** Validate invalid manifest
- **Expected Output:** Array of ManifestError objects
- **Priority:** High

#### GE-002: Get Empty Errors Array
- **Type:** Unit
- **Description:** Retrieve errors when manifest is valid
- **Preconditions:** Validate valid manifest
- **Expected Output:** Empty array
- **Priority:** High

#### GE-003: Get Multiple Errors
- **Type:** Unit
- **Description:** Retrieve multiple errors from invalid manifest
- **Input:** Manifest missing all required fields
- **Expected Output:** Array with 5 ManifestError objects
- **Priority:** High

---

### Category 6: ManifestValidationResult DTO (MR-001 to MR-006)

#### MR-001: Result valid Property
- **Type:** Unit
- **Description:** Verify valid property reflects validation state
- **Expected Output:** true for valid, false for invalid
- **Priority:** High

#### MR-002: Result errors Property
- **Type:** Unit
- **Description:** Verify errors array structure
- **Expected Output:** Array of ManifestError objects
- **Priority:** High

#### MR-003: Result warnings Property
- **Type:** Unit
- **Description:** Verify warnings array structure
- **Expected Output:** Array of warning strings or objects
- **Priority:** High

#### MR-004: Result fieldPresence Property
- **Type:** Unit
- **Description:** Verify fieldPresence mapping
- **Expected Output:** Array with field => bool mapping
- **Priority:** High

#### MR-005: Result hasErrors Method
- **Type:** Unit
- **Description:** Check if result has errors
- **Expected Output:** Boolean
- **Priority:** Medium

#### MR-006: Result hasWarnings Method
- **Type:** Unit
- **Description:** Check if result has warnings
- **Expected Output:** Boolean
- **Priority:** Medium

---

### Category 7: ManifestError DTO (ME-001 to ME-004)

#### ME-001: Error field Property
- **Type:** Unit
- **Description:** Verify error field name
- **Expected Output:** String field name
- **Priority:** High

#### ME-002: Error code Property
- **Type:** Unit
- **Description:** Verify error code
- **Expected Output:** String error code (e.g., 'REQUIRED_FIELD_MISSING')
- **Priority:** High

#### ME-003: Error message Property
- **Type:** Unit
- **Description:** Verify human-readable message
- **Expected Output:** String message
- **Priority:** High

#### ME-004: Error severity Property
- **Type:** Unit
- **Description:** Verify error severity (error/warning)
- **Expected Output:** 'error' or 'warning'
- **Priority:** Medium

---

### Category 8: Edge Cases (EC-001 to EC-010)

#### EC-001: Extra Fields in Manifest
- **Type:** Unit
- **Description:** Handle extra unknown fields gracefully
- **Input:** Manifest with extra fields (custom_property, etc.)
- **Expected Output:** Valid (extra fields ignored)
- **Priority:** Low

#### EC-002: Unicode in name Field
- **Type:** Unit
- **Description:** Handle Unicode characters in name
- **Input:** `['name' => '示例应用', ...]`
- **Expected Output:** Valid
- **Priority:** Medium

#### EC-003: Very Long name Field
- **Type:** Unit
- **Description:** Handle very long name
- **Input:** name with 500+ characters
- **Expected Output:** Warning - name exceeds recommended length
- **Priority:** Low

#### EC-004: Icons With Same Sizes
- **Type:** Unit
- **Description:** Multiple icons with same sizes
- **Input:** Two icons both 192x192
- **Expected Output:** Valid (allowed)
- **Priority:** Low

#### EC-005: Icon src With Query Parameters
- **Type:** Unit
- **Description:** Icon src with query string
- **Input:** `['src' => '/icon.png?v=1.2.3', ...]`
- **Expected Output:** Valid
- **Priority:** Medium

#### EC-006: start_url With Query Parameters
- **Type:** Unit
- **Description:** start_url with query string
- **Input:** `'start_url' => '/?source=pwa'`
- **Expected Output:** Valid
- **Priority:** Medium

#### EC-007: Display Value Case Sensitivity
- **Type:** Unit
- **Description:** Display values should be case-sensitive
- **Input:** `'display' => 'STANDALONE'`
- **Expected Output:** Error (uppercase not valid)
- **Priority:** High

#### EC-008: Empty Object in Icons Array
- **Type:** Unit
- **Description:** Handle empty icon object
- **Input:** `['icons' => [{}]]`
- **Expected Output:** Error - icon src is required
- **Priority:** Medium

#### EC-009: Nested Objects in Manifest
- **Type:** Unit
- **Description:** Handle nested objects (shortcuts, screenshots)
- **Input:** Manifest with shortcuts array
- **Expected Output:** Valid (not validated, ignored)
- **Priority:** Low

#### EC-010: Array Instead of Object
- **Type:** Unit
- **Description:** Handle array input instead of object
- **Input:** `['item1', 'item2']`
- **Expected Output:** Errors for missing required fields
- **Priority:** Medium

---

### Category 9: Security Tests (SEC-001 to SEC-003)

#### SEC-001: XSS in name Field
- **Type:** Security
- **Description:** Handle potential XSS in name
- **Input:** `['name' => '<script>alert(1)</script>', ...]`
- **Expected Output:** Valid (validation doesn't sanitize, but should not execute)
- **Priority:** High

#### SEC-002: SQL Injection in start_url
- **Type:** Security
- **Description:** Handle SQL injection attempt
- **Input:** `'start_url' => "/?id=' OR '1'='1"`
- **Expected Output:** Valid (validation accepts string)
- **Priority:** High

#### SEC-003: JavaScript URL in Icon src
- **Type:** Security
- **Description:** Handle javascript: URL in icon src
- **Input:** `['src' => 'javascript:alert(1)', ...]`
- **Expected Output:** Warning or error for invalid URL scheme
- **Priority:** Medium

---

## Test File Locations

| Test Type | Location |
|-----------|----------|
| Unit Tests | `backend/tests/Unit/Services/ManifestValidatorServiceTest.php` |
| DTO Tests | `backend/tests/Unit/DTO/ManifestValidationResultTest.php` |
| DTO Tests | `backend/tests/Unit/DTO/ManifestErrorTest.php` |

## Test Scenarios Summary

| ID | Category | Description | Priority |
|----|----------|-------------|----------|
| MV-001 | validate() | Complete valid manifest | High |
| MV-002 | validate() | Missing name | High |
| MV-003 | validate() | Missing short_name | High |
| MV-004 | validate() | Missing start_url | High |
| MV-005 | validate() | Missing display | High |
| MV-006 | validate() | Missing icons | High |
| MV-007 | validate() | Empty icons array | High |
| MV-008 | validate() | Invalid display value | High |
| MV-009 | validate() | Empty manifest | High |
| MV-010 | validate() | Null input | Medium |
| VF-001 | validateField() | Valid name | High |
| VF-002 | validateField() | Empty name | High |
| VF-003 | validateField() | Valid short_name | High |
| VF-004 | validateField() | Valid relative start_url | High |
| VF-005 | validateField() | Valid absolute start_url | High |
| VF-006 | validateField() | display=standalone | High |
| VF-007 | validateField() | display=fullscreen | High |
| VF-008 | validateField() | display=minimal-ui | High |
| VF-009 | validateField() | display=browser | High |
| VF-010 | validateField() | Invalid display | High |
| VF-011 | validateField() | Valid icons array | High |
| VF-012 | validateField() | Non-array icons | High |
| IV-001 | Icon Validation | Valid icon all fields | High |
| IV-002 | Icon Validation | Missing src | High |
| IV-003 | Icon Validation | Missing sizes | Medium |
| IV-004 | Icon Validation | Missing type | Medium |
| IV-005 | Icon Validation | Valid purpose | Medium |
| IV-006 | Icon Validation | Multiple purposes | Medium |
| IV-007 | Icon Validation | Invalid purpose | Medium |
| IV-008 | Icon Validation | Empty src | High |
| IV-009 | Icon Validation | Valid sizes format | Medium |
| IV-010 | Icon Validation | Valid type values | Medium |
| RF-001 | Recommended | Missing background_color | Medium |
| RF-002 | Recommended | Missing theme_color | Medium |
| RF-003 | Recommended | Missing description | Medium |
| RF-004 | Recommended | Invalid color format | Low |
| RF-005 | Recommended | Valid background_color | Medium |
| RF-006 | Recommended | Valid theme_color | Medium |
| GE-001 | getErrors() | Get errors | High |
| GE-002 | getErrors() | Empty errors | High |
| GE-003 | getErrors() | Multiple errors | High |
| MR-001 | Result DTO | valid property | High |
| MR-002 | Result DTO | errors property | High |
| MR-003 | Result DTO | warnings property | High |
| MR-004 | Result DTO | fieldPresence property | High |
| MR-005 | Result DTO | hasErrors method | Medium |
| MR-006 | Result DTO | hasWarnings method | Medium |
| ME-001 | Error DTO | field property | High |
| ME-002 | Error DTO | code property | High |
| ME-003 | Error DTO | message property | High |
| ME-004 | Error DTO | severity property | Medium |
| EC-001 | Edge Case | Extra fields | Low |
| EC-002 | Edge Case | Unicode in name | Medium |
| EC-003 | Edge Case | Very long name | Low |
| EC-004 | Edge Case | Duplicate icon sizes | Low |
| EC-005 | Edge Case | Icon src with query | Medium |
| EC-006 | Edge Case | start_url with query | Medium |
| EC-007 | Edge Case | Display case sensitivity | High |
| EC-008 | Edge Case | Empty icon object | Medium |
| EC-009 | Edge Case | Nested objects | Low |
| EC-010 | Edge Case | Array input | Medium |
| SEC-001 | Security | XSS in name | High |
| SEC-002 | Security | SQL injection in start_url | High |
| SEC-003 | Security | JavaScript URL in icon | Medium |

**Total Test Cases:** 53

## Coverage Targets

| Category | Target | Status |
|----------|--------|--------|
| validate() method | 100% | Pending |
| validateField() method | 100% | Pending |
| Icon validation | 100% | Pending |
| Recommended fields | 100% | Pending |
| getErrors() method | 100% | Pending |
| ManifestValidationResult DTO | 100% | Pending |
| ManifestError DTO | 100% | Pending |
| Edge cases | 100% | Pending |
| Security | 100% | Pending |

## Test Execution Commands

```bash
# Run all ManifestValidatorService tests
php artisan test --filter ManifestValidatorServiceTest

# Run unit tests only
php artisan test --filter ManifestValidatorServiceTest tests/Unit

# Run with coverage
php artisan test --coverage --filter ManifestValidatorServiceTest
```

## Dependencies
- None (pure validation logic)

## Notes for Developer

### Service Location
`backend/app/Services/ManifestValidatorService.php`

### DTO Locations
- `backend/app/DTO/ManifestValidationResult.php`
- `backend/app/DTO/ManifestError.php`

### Expected Service Methods

```php
<?php

namespace App\Services;

use App\DTO\ManifestValidationResult;
use App\DTO\ManifestError;

class ManifestValidatorService
{
    /**
     * Validate a PWA manifest array.
     *
     * @param array|null $manifest
     * @return ManifestValidationResult
     */
    public function validate(?array $manifest): ManifestValidationResult;

    /**
     * Validate a single field.
     *
     * @param string $field
     * @param mixed $value
     * @return bool
     */
    public function validateField(string $field, mixed $value): bool;

    /**
     * Get all validation errors.
     *
     * @return array<ManifestError>
     */
    public function getErrors(): array;
}
```

### Expected DTO Classes

```php
<?php

namespace App\DTO;

class ManifestValidationResult
{
    public bool $valid;
    public array $errors;
    public array $warnings;
    public array $fieldPresence;

    public function hasErrors(): bool;
    public function hasWarnings(): bool;
}

class ManifestError
{
    public string $field;
    public string $code;
    public string $message;
    public string $severity; // 'error' or 'warning'
}
```

### Key Implementation Notes

1. **Required Fields:** name, short_name, start_url, display, icons
2. **Recommended Fields:** background_color, theme_color, description (generate warnings if missing)
3. **Display Enum Values:** standalone, fullscreen, minimal-ui, browser (case-sensitive)
4. **Icon Required:** src field is required for each icon
5. **Icon Recommended:** sizes, type, purpose (generate warnings if missing)
6. **Purpose Values:** any, maskable, any maskable
7. **Color Formats:** hex (#FFF, #ffffff), named colors (white), rgb()
8. **Extra Fields:** Ignore any fields not in spec (don't error)
9. **Null Handling:** Return error for null manifest input

### W3C Web App Manifest Spec Reference
- https://www.w3.org/TR/appmanifest/

---

## Comments
_Agent communication log_

### [2026-03-01 00:50 GMT+2] Agent: PreTester
Created test documentation for TASK-24-4 (ManifestValidatorService).

**Test Categories:**
1. **validate() Method (10 tests):** Main validation entry point
2. **validateField() Method (12 tests):** Individual field validation
3. **Icon Validation (10 tests):** Icon array structure and content
4. **Recommended Fields (6 tests):** background_color, theme_color, description
5. **getErrors() Method (3 tests):** Error retrieval
6. **ManifestValidationResult DTO (6 tests):** Result object structure
7. **ManifestError DTO (4 tests):** Error object structure
8. **Edge Cases (10 tests):** Unicode, extra fields, etc.
9. **Security Tests (3 tests):** XSS, SQL injection, JS URLs

**Total Test Cases:** 53

**Key Design Decisions:**
- Required fields generate errors (block validation)
- Recommended fields generate warnings (don't block)
- Display values are case-sensitive
- Extra fields are ignored (not validated)
- Icon src is required, sizes/type/purpose are recommended
- Security tests verify validation doesn't break on malicious input

Status will be updated to `To Develop` after test implementation.
