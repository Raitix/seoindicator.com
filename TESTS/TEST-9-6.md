# TEST-9-6: Annotation FormRequest Validation Tests

## Task Reference
- **Task:** [TASK-9-6](../TASKS/TASK-9-6.md)
- **Feature:** FEAT-9 - Keyword Annotations
- **Specification:** SPEC-9

## Overview
Comprehensive unit tests for `StoreAnnotationRequest` and `UpdateAnnotationRequest` FormRequest classes. These validation classes protect backend endpoints from malformed or unauthorized annotation data.

## Test Files

| Test File | Tests | Assertions | Description |
|-----------|-------|------------|-------------|
| `backend/tests/Unit/Http/Requests/StoreAnnotationRequestTest.php` | 35+ | 35+ | Validation rule tests for annotation creation |
| `backend/tests/Unit/Http/Requests/UpdateAnnotationRequestTest.php` | 10+ | 10+ | Inheritance and override tests |

---

## Test Scenarios

### 1. StoreAnnotationRequest - Field Presence Tests (SA-001 to SA-006)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| SA-001 | All required fields present | `{date, title, category}` | Passes validation |
| SA-002 | Missing `date` field | `{title, category}` | Fails: date required |
| SA-003 | Missing `title` field | `{date, category}` | Fails: title required |
| SA-004 | Missing `category` field | `{date, title}` | Fails: category required |
| SA-005 | All fields including optional | `{date, title, category, description}` | Passes validation |
| SA-006 | Empty request body | `{}` | Fails: 3 required fields |

### 2. Date Field Validation (DT-001 to DT-010)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| DT-001 | Valid date format (Y-m-d) | `2026-02-27` | Passes |
| DT-002 | Valid date format (Y-m-d H:i:s) | `2026-02-27 14:30:00` | Passes (date part used) |
| DT-003 | Invalid date format | `27-02-2026` | Fails: invalid date |
| DT-004 | Non-date string | `not-a-date` | Fails: invalid date |
| DT-005 | Empty string | `""` | Fails: required |
| DT-006 | Null value | `null` | Fails: required |
| DT-007 | Integer input | `20260227` | Fails: invalid date |
| DT-008 | Array input | `[]` | Fails: invalid date |
| DT-009 | Date in distant past | `1900-01-01` | Passes (valid date) |
| DT-010 | Date in distant future | `2100-12-31` | Passes (valid date) |

### 3. Title Field Validation (TL-001 to TL-012)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| TL-001 | Valid title (normal) | `"Google Algorithm Update"` | Passes |
| TL-002 | Title exactly 255 chars | `"A" * 255` | Passes |
| TL-003 | Title exceeds 255 chars | `"A" * 256` | Fails: max 255 |
| TL-004 | Title with special characters | `"Updated <script>alert('xss')</script>"` | Passes (sanitized by app) |
| TL-005 | Title with Unicode | `"Content update 中文 🚀"` | Passes |
| TL-006 | Title with HTML entities | `"Update &amp; Fix"` | Passes |
| TL-007 | Empty string | `""` | Fails: required |
| TL-008 | Null value | `null` | Fails: required |
| TL-009 | Whitespace only | `"   "` | Fails: required (after trim) |
| TL-010 | Integer input | `12345` | Fails: must be string |
| TL-011 | Array input | `[]` | Fails: must be string |
| TL-012 | Single character | `"A"` | Passes |

### 4. Description Field Validation (DC-001 to DC-012)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| DC-001 | Valid description | `"Position dropped after algorithm update"` | Passes |
| DC-002 | Long description (TEXT field) | `"A" * 65000` | Passes |
| DC-003 | Description with newlines | `"Line 1\nLine 2\nLine 3"` | Passes |
| DC-004 | Description with HTML | `"<p>Content</p>"` | Passes (sanitized by app) |
| DC-005 | Description with Unicode | `"内容更新 📈"` | Passes |
| DC-006 | Null value (optional) | `null` | Passes |
| DC-007 | Empty string | `""` | Passes (nullable) |
| DC-008 | Whitespace only | `"   "` | Passes (nullable) |
| DC-009 | Integer input | `12345` | Fails: must be string |
| DC-010 | Array input | `[]` | Fails: must be string |
| DC-011 | Markdown content | `"**Bold** and *italic*"` | Passes |
| DC-012 | JSON string | `{"key": "value"}` | Passes |

### 5. Category Field Validation (CT-001 to CT-015)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| CT-001 | Valid category: `content_update` | `"content_update"` | Passes |
| CT-002 | Valid category: `link_building` | `"link_building"` | Passes |
| CT-003 | Valid category: `technical_fix` | `"technical_fix"` | Passes |
| CT-004 | Valid category: `algorithm_update` | `"algorithm_update"` | Passes |
| CT-005 | Valid category: `competitor_action` | `"competitor_action"` | Passes |
| CT-006 | Valid category: `seasonal` | `"seasonal"` | Passes |
| CT-007 | Valid category: `manual_action` | `"manual_action"` | Passes |
| CT-008 | Valid category: `other` | `"other"` | Passes |
| CT-009 | Invalid category value | `"invalid_category"` | Fails: invalid category |
| CT-010 | Category with wrong case | `"Content_Update"` | Fails: case-sensitive |
| CT-011 | Empty string | `""` | Fails: required |
| CT-012 | Null value | `null` | Fails: required |
| CT-013 | Integer input | `1` | Fails: must be valid category |
| CT-014 | Category with spaces | `"content update"` | Fails: invalid category |
| CT-015 | Non-existent enum case | `"deleted"` | Fails: invalid category |

### 6. Combined Field Tests (CB-001 to CB-008)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| CB-001 | All valid fields | Valid date, title, category, description | Passes |
| CB-002 | Multiple validation errors | Missing date, title > 255, invalid category | Returns all errors |
| CB-003 | Valid minimal (required only) | date, title, category | Passes |
| CB-004 | All fields empty strings | All `""` | Fails: 3 required fields |
| CB-005 | Extra unexpected fields | Valid + `extra_field` | Passes (ignored) |
| CB-006 | Nested object instead of flat | `{annotation: {...}}` | Fails: fields not at root |
| CB-007 | Very large payload | All fields max size | Passes (within limits) |
| CB-008 | Unicode throughout | All fields with Chinese/emoji | Passes |

### 7. Security Tests (SC-001 to SC-008)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| SC-001 | SQL injection in title | `"' OR '1'='1"` | Passes validation (escaped later) |
| SC-002 | SQL injection in description | `"; DROP TABLE annotations;--` | Passes validation (escaped later) |
| SC-003 | XSS attempt in title | `"<script>alert('xss')</script>"` | Passes validation (sanitized later) |
| SC-004 | XSS attempt in description | `"<img src=x onerror=alert('xss')>"` | Passes validation (sanitized later) |
| SC-005 | Null byte injection | `"title\x00description"` | Passes validation (handled by app) |
| SC-006 | CRLF injection | `"title\r\nSet-Cookie: hacked=true"` | Passes validation (sanitized later) |
| SC-007 | Path traversal | `"../../../etc/passwd"` | Passes validation (not a path field) |
| SC-008 | Unicode homoglyph | `"ｓｅｌｅｃｔ"` | Passes validation (normal text) |

### 8. UpdateAnnotationRequest Tests (UA-001 to UA-010)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| UA-001 | Inherits all rules from StoreAnnotationRequest | Same as StoreAnnotationRequest | Same validation |
| UA-002 | Partial update with date | `{date}` | Fails: title required |
| UA-003 | Partial update with title | `{title}` | Fails: date required |
| UA-004 | Valid full update | All fields | Passes |
| UA-005 | Update with same validation errors | Same as StoreAnnotationRequest | Same error messages |
| UA-006 | Update with valid description only | `{date, title, category, description}` | Passes |
| UA-007 | Class extends StoreAnnotationRequest | Check inheritance | `extends StoreAnnotationRequest` |
| UA-008 | Override rules possible | Custom rules method | Extends parent rules |
| UA-009 | Different error messages | Custom messages | Can customize |
| UA-010 | Authorization method exists | `authorize()` method | Returns true (controller handles auth) |

### 9. Edge Cases (EC-001 to EC-012)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| EC-001 | Leap year date | `2024-02-29` | Passes |
| EC-002 | Non-leap year Feb 29 | `2023-02-29` | Fails: invalid date |
| EC-003 | Title with only numbers | `"123456"` | Passes |
| EC-004 | Title with newlines | `"Line 1\nLine 2"` | Passes (may be sanitized) |
| EC-005 | Description exactly 65535 chars | `"A" * 65535` | Passes |
| EC-006 | Description over TEXT limit | `"A" * 65536` | May fail or truncate |
| EC-007 | Category enum case match | Exact case match required | Case-sensitive |
| EC-008 | Mixed encoding in title | UTF-8 + Latin-1 mix | Passes (normalized) |
| EC-009 | Zero-width characters | Title with `\u200B` | Passes (may be trimmed) |
| EC-010 | Maximum boundary title | 255 chars exactly | Passes |
| EC-011 | Boundary + 1 title | 256 chars | Fails |
| EC-012 | All categories tested | All 8 enum values | All pass |

---

## Mock Data

### Valid Annotation Request Data
```php
$validData = [
    'date' => '2026-02-27',
    'title' => 'Google Core Update Impact',
    'description' => 'Position dropped from #5 to #12 after core update rollout',
    'category' => 'algorithm_update',
];

$minimalData = [
    'date' => '2026-02-27',
    'title' => 'Quick Note',
    'category' => 'other',
];
```

### Invalid Request Data
```php
$missingRequired = [
    'description' => 'Missing required fields',
];

$invalidCategory = [
    'date' => '2026-02-27',
    'title' => 'Test',
    'category' => 'invalid_category',
];

$tooLongTitle = [
    'date' => '2026-02-27',
    'title' => str_repeat('A', 256),
    'category' => 'other',
];
```

### AnnotationCategory Enum Values
```php
enum AnnotationCategory: string
{
    case CONTENT_UPDATE = 'content_update';
    case LINK_BUILDING = 'link_building';
    case TECHNICAL_FIX = 'technical_fix';
    case ALGORITHM_UPDATE = 'algorithm_update';
    case COMPETITOR_ACTION = 'competitor_action';
    case SEASONAL = 'seasonal';
    case MANUAL_ACTION = 'manual_action';
    case OTHER = 'other';
}
```

---

## Coverage Targets

| Category | Target | Tests | Status |
|----------|--------|-------|--------|
| Field Presence | 100% | 6 | ⏳ |
| Date Validation | 100% | 10 | ⏳ |
| Title Validation | 100% | 12 | ⏳ |
| Description Validation | 100% | 12 | ⏳ |
| Category Validation | 100% | 15 | ⏳ |
| Combined Fields | 100% | 8 | ⏳ |
| Security | 100% | 8 | ⏳ |
| UpdateAnnotationRequest | 100% | 10 | ⏳ |
| Edge Cases | 100% | 12 | ⏳ |
| **Total** | **100%** | **93** | ⏳ |

---

## Test Execution

```bash
# Run all FormRequest tests
cd backend
php artisan test --filter=AnnotationRequest

# Run specific test file
php artisan test tests/Unit/Http/Requests/StoreAnnotationRequestTest.php

# Run with coverage
php artisan test --filter=AnnotationRequest --coverage

# Run specific test method
php artisan test --filter=test_date_field_valid
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| AnnotationCategory Enum | ⏳ TASK-9-2 | Must exist for category validation |
| Laravel Validation | ✅ Built-in | Uses Rule::in() for enum validation |
| FormRequest Base Class | ✅ Built-in | Extends Illuminate\Foundation\Http\FormRequest |

---

## Notes for Developer

### Implementation Checklist
- [ ] Create `backend/app/Http/Requests/StoreAnnotationRequest.php`
- [ ] Create `backend/app/Http/Requests/UpdateAnnotationRequest.php`
- [ ] Ensure `AnnotationCategory` enum exists (TASK-9-2)
- [ ] Apply requests to controller methods

### FormRequest Implementation Template
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\AnnotationCategory;

class StoreAnnotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by controller/policy
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => [
                'required',
                Rule::in(AnnotationCategory::cases()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'The date field is required.',
            'date.date' => 'Please provide a valid date.',
            'title.required' => 'The title field is required.',
            'title.max' => 'The title may not be greater than 255 characters.',
            'category.required' => 'Please select a category.',
            'category.in' => 'The selected category is invalid.',
        ];
    }
}
```

```php
<?php

namespace App\Http\Requests;

class UpdateAnnotationRequest extends StoreAnnotationRequest
{
    // Inherits all validation rules from StoreAnnotationRequest
    // Override rules() or messages() if different behavior needed
}
```

### Controller Usage
```php
public function store(StoreAnnotationRequest $request)
{
    $validated = $request->validated();
    // $validated contains: date, title, description (nullable), category
}

public function update(UpdateAnnotationRequest $request, Annotation $annotation)
{
    $validated = $request->validated();
    // Same validation as store
}
```

---

## Comments

### [2026-02-27 12:08] Agent: PreTester
**Test Documentation Created:**
- 93 test scenarios covering all validation aspects
- Focus on required field validation (date, title, category)
- Category validation depends on AnnotationCategory enum (TASK-9-2)
- Security tests verify validation doesn't block valid but "suspicious" content
- Edge cases cover boundary conditions and special inputs

**Key Testing Priorities:**
1. DT-003/DT-004: Date format validation (most common user error)
2. TL-003: Title max length (255 chars)
3. CT-009-CT-015: Category validation against enum values
4. UA-007: UpdateAnnotationRequest inheritance verification

**Dependency Note:**
- TASK-9-2 (AnnotationCategory Enum) is a hard dependency
- Category validation tests will fail until enum is implemented
- Consider mocking AnnotationCategory for isolated tests

**Status:** `Tests Coverage In Progress` → Creating PHP test file
