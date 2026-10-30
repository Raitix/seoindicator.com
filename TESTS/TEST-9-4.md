# TEST-9-4: AnnotationController Tests

## Task Reference
- **Task:** [TASK-9-4](../TASKS/TASK-9-4.md)
- **Feature:** FEAT-009 Keyword Annotations
- **Specification:** SPEC-009

---

## Overview

Tests for the `AnnotationController` API endpoints handling CRUD operations for keyword annotations. This controller provides the REST API layer for the annotation feature, enabling users to document SEO events and correlate them with ranking changes.

---

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/keywords/{keywordId}/annotations` | List annotations with optional filters |
| POST | `/api/v1/keywords/{keywordId}/annotations` | Create a new annotation |
| GET | `/api/v1/annotations/{id}` | Show a single annotation |
| PUT | `/api/v1/annotations/{id}` | Update an annotation |
| DELETE | `/api/v1/annotations/{id}` | Delete an annotation |

---

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `backend/tests/Feature/Api/V1/AnnotationControllerTest.php` | Feature | Controller endpoint tests with authorization |
| `backend/tests/Unit/Controllers/AnnotationControllerTest.php` | Unit | Controller method unit tests |

---

## Test Scenarios

### Index (List) Tests (IX)

| ID | Scenario | Request | Expected Output |
|----|----------|---------|-----------------|
| IX-001 | List annotations for keyword | GET /keywords/{id}/annotations | 200, array of annotations |
| IX-002 | List with date range filter | GET ?from=2026-02-01&to=2026-02-28 | 200, filtered results |
| IX-003 | List with category filter | GET ?category=algorithm-update | 200, filtered results |
| IX-004 | List with pagination | GET ?page=2&perPage=10 | 200, paginated results |
| IX-005 | List empty for keyword with no annotations | GET /keywords/{id}/annotations | 200, empty array |
| IX-006 | List ordered by date desc | GET /keywords/{id}/annotations | 200, ordered correctly |
| IX-007 | List with invalid keyword ID | GET /keywords/9999/annotations | 404 Not Found |
| IX-008 | List with only `from` filter | GET ?from=2026-02-15 | 200, dates >= from |
| IX-009 | List with only `to` filter | GET ?to=2026-02-15 | 200, dates <= to |
| IX-010 | Per-page maximum enforced | GET ?perPage=200 | 200, capped at 100 |

### Store (Create) Tests (ST)

| ID | Scenario | Request | Expected Output |
|----|----------|---------|-----------------|
| ST-001 | Create annotation with valid data | POST with title, date | 201, annotation created |
| ST-002 | Create with all fields | POST with all fields | 201, all fields saved |
| ST-003 | Create with minimal fields | POST with title, date only | 201, defaults applied |
| ST-004 | Create with invalid keyword ID | POST /keywords/9999/annotations | 404 Not Found |
| ST-005 | Create without title | POST without title | 422, validation error |
| ST-006 | Create without date | POST without date | 422, validation error |
| ST-007 | Create with invalid date format | POST with bad date | 422, validation error |
| ST-008 | Create with title > 255 chars | POST with long title | 422, validation error |
| ST-009 | Create with description > 65535 chars | POST with long desc | 422, validation error |
| ST-010 | Create with category > 50 chars | POST with long category | 422, validation error |
| ST-011 | Create with invalid project_id | POST with non-existent project_id | 422, validation error |
| ST-012 | Project ID derived from keyword site | POST without project_id | 201, project_id auto-set |

### Show (Single) Tests (SH)

| ID | Scenario | Request | Expected Output |
|----|----------|---------|-----------------|
| SH-001 | Show existing annotation | GET /annotations/{id} | 200, annotation data |
| SH-002 | Show non-existent annotation | GET /annotations/9999 | 404 Not Found |
| SH-003 | Show includes all fields | GET /annotations/{id} | 200, all fields present |
| SH-004 | Show with keyword relationship | GET /annotations/{id} | Can include keyword |

### Update Tests (UP)

| ID | Scenario | Request | Expected Output |
|----|----------|---------|-----------------|
| UP-001 | Update title | PUT with new title | 200, updated title |
| UP-002 | Update description | PUT with new description | 200, updated description |
| UP-003 | Update date | PUT with new date | 200, updated date |
| UP-004 | Update category | PUT with new category | 200, updated category |
| UP-005 | Update all fields | PUT with all fields | 200, all updated |
| UP-006 | Update non-existent annotation | PUT /annotations/9999 | 404 Not Found |
| UP-007 | Update with invalid title | PUT without title | 422, validation error |
| UP-008 | Update with invalid date | PUT with bad date | 422, validation error |
| UP-009 | Partial update (only title) | PUT with title only | 200, other fields unchanged |
| UP-010 | Update preserves keyword_id | PUT any | keyword_id unchanged |

### Destroy (Delete) Tests (DE)

| ID | Scenario | Request | Expected Output |
|----|----------|---------|-----------------|
| DE-001 | Delete existing annotation | DELETE /annotations/{id} | 204 No Content |
| DE-002 | Delete non-existent annotation | DELETE /annotations/9999 | 404 Not Found |
| DE-003 | Delete removes from database | DELETE /annotations/{id} | Record not found after |
| DE-004 | Delete returns no content | DELETE /annotations/{id} | Empty response body |

---

### Authorization Tests (AZ) - Unauthenticated

| ID | Scenario | Request | Expected Output |
|----|----------|---------|-----------------|
| AZ-001 | Unauthenticated list | GET /keywords/{id}/annotations | 401 Unauthorized |
| AZ-002 | Unauthenticated create | POST /keywords/{id}/annotations | 401 Unauthorized |
| AZ-003 | Unauthenticated show | GET /annotations/{id} | 401 Unauthorized |
| AZ-004 | Unauthenticated update | PUT /annotations/{id} | 401 Unauthorized |
| AZ-005 | Unauthenticated delete | DELETE /annotations/{id} | 401 Unauthorized |

---

### Authorization Tests (AZ) - Cross-User Access

| ID | Scenario | Request | Expected Output |
|----|----------|---------|-----------------|
| AZ-010 | List other user's annotations | GET as user B for user A's keyword | 403 Forbidden |
| AZ-011 | Create on other user's keyword | POST as user B on user A's keyword | 403 Forbidden |
| AZ-012 | Show other user's annotation | GET as user B on user A's annotation | 403 Forbidden |
| AZ-013 | Update other user's annotation | PUT as user B on user A's annotation | 403 Forbidden |
| AZ-014 | Delete other user's annotation | DELETE as user B on user A's annotation | 403 Forbidden |

---

### Authorization Tests (AZ) - Owner Access

| ID | Scenario | Request | Expected Output |
|----|----------|---------|-----------------|
| AZ-020 | Owner can list | GET as owner | 200 OK |
| AZ-021 | Owner can create | POST as owner | 201 Created |
| AZ-022 | Owner can show | GET as owner | 200 OK |
| AZ-023 | Owner can update | PUT as owner | 200 OK |
| AZ-024 | Owner can delete | DELETE as owner | 204 No Content |

---

### Edge Case Tests (EC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Empty title string | title="" | 422, validation error |
| EC-002 | Whitespace-only title | title="   " | 422 or trimmed |
| EC-003 | Date in future | date="2099-12-31" | 201, allowed |
| EC-004 | Date in past | date="1990-01-01" | 201, allowed |
| EC-005 | Unicode in title | title="SEO 更新 🚀" | 201, saved correctly |
| EC-006 | HTML in description | description="<script>alert(1)</script>" | 201, stored as-is (sanitized on output) |
| EC-007 | SQL injection attempt in title | title="' OR 1=1--" | 201, stored as literal |
| EC-008 | Newlines in description | description="Line1\nLine2" | 201, preserved |
| EC-009 | Special characters in category | category="special!@#" | 201, stored as-is |
| EC-010 | Empty description | description="" | 201, null or empty string |
| EC-011 | Very long title (255 chars) | title with 255 chars | 201, saved |
| EC-012 | Title exceeds max (256 chars) | title with 256 chars | 422, validation error |

---

### Integration Tests (IT)

| ID | Scenario | Actions | Expected Output |
|----|----------|---------|-----------------|
| IT-001 | Full CRUD lifecycle | Create, read, update, delete | All operations succeed |
| IT-002 | Multiple annotations for keyword | Create 10 annotations | All listed correctly |
| IT-003 | Filter by date after create | Create 3, filter by date | Correct subset returned |
| IT-004 | Filter by category after create | Create 3 categories, filter | Correct subset returned |
| IT-005 | Delete removes from list | Create, delete, list | Not in list |
| IT-006 | Update reflected in show | Update, show | Updated values returned |

---

## Mock Data

### Sample Annotation Request (Create)

```json
{
  "date": "2026-02-27",
  "title": "Google Core Update Impact",
  "description": "Position dropped from 5 to 12 after the March 2026 core update.",
  "category": "algorithm-update"
}
```

### Sample Annotation Response

```json
{
  "id": 1,
  "keyword_id": 42,
  "project_id": 1,
  "date": "2026-02-27",
  "title": "Google Core Update Impact",
  "description": "Position dropped from 5 to 12 after the March 2026 core update.",
  "category": "algorithm-update",
  "created_at": "2026-02-27T10:00:00Z",
  "updated_at": "2026-02-27T10:00:00Z"
}
```

### Valid Categories

From `Annotation::CATEGORIES`:
- `algorithm-update`
- `content-update`
- `technical-change`
- `link-building`
- `competition`
- `seasonal`
- `manual-action`
- `recovery`
- `other`

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Index (List) | 100% | IX-001 to IX-010 (10 tests) |
| Store (Create) | 100% | ST-001 to ST-012 (12 tests) |
| Show (Single) | 100% | SH-001 to SH-004 (4 tests) |
| Update | 100% | UP-001 to UP-010 (10 tests) |
| Destroy (Delete) | 100% | DE-001 to DE-004 (4 tests) |
| Authorization (Unauthenticated) | 100% | AZ-001 to AZ-005 (5 tests) |
| Authorization (Cross-User) | 100% | AZ-010 to AZ-014 (5 tests) |
| Authorization (Owner) | 100% | AZ-020 to AZ-024 (5 tests) |
| Edge Cases | 100% | EC-001 to EC-012 (12 tests) |
| Integration | 100% | IT-001 to IT-006 (6 tests) |

**Total: 73 test scenarios**

---

## Test Execution

```bash
# Run all annotation controller tests
php artisan test --filter=AnnotationController

# Run specific test file
php artisan test tests/Feature/Api/V1/AnnotationControllerTest.php

# Run with coverage
php artisan test --coverage --filter=AnnotationController
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| Annotation model | ✅ Exists | TASK-9-3 |
| AnnotationFactory | ✅ Exists | Database factories |
| Keyword model | ✅ Exists | Required for keyword_id FK |
| Project model | ✅ Exists | Required for project_id FK |
| StoreAnnotationRequest | ✅ Exists | Validation rules |
| UpdateAnnotationRequest | ✅ Exists | Validation rules |
| AnnotationPolicy | ⏳ TASK-9-5 | Authorization logic |

---

## Notes for Developer

### Controller Implementation Status

The `AnnotationController` already exists with:
- ✅ `index()` - List annotations with filters (date range, category)
- ✅ `store()` - Create annotation with authorization
- ✅ `show()` - Show single annotation
- ✅ `update()` - Update annotation
- ✅ `destroy()` - Delete annotation
- ✅ Authorization via `$this->authorize()` calls

### Request Validation

**StoreAnnotationRequest:**
- `date`: required, date
- `title`: required, string, max:255
- `description`: nullable, string, max:65535
- `category`: nullable, string, max:50
- `project_id`: nullable, integer, exists:projects,id

**UpdateAnnotationRequest:**
- Same fields with `sometimes` rule for partial updates

### Authorization Flow

1. **index()**: `authorize('viewAny', [Annotation::class, $keyword])`
2. **store()**: `authorize('create', [Annotation::class, $keyword])`
3. **show()**: `authorize('view', $annotation)`
4. **update()**: `authorize('update', $annotation)`
5. **destroy()**: `authorize('delete', $annotation)`

### Policy Dependency

Tests for authorization (AZ-010 to AZ-024) require `AnnotationPolicy` (TASK-9-5) to be implemented. Until then:
- Tests will fail with policy-related errors
- This is correct TDD behavior - policies should be implemented first

---

## Comments

### [2026-02-27 11:50] Agent: PreTester
**Test Documentation Created:**
- 73 test scenarios covering all CRUD operations, authorization, and edge cases
- Focus on request validation and authorization enforcement
- Cross-user access tests ensure data isolation

**Key Testing Priorities:**
1. AZ-010 to AZ-014: Cross-user authorization (critical for security)
2. ST-005, ST-006: Required field validation
3. EC-006, EC-007: XSS/SQL injection handling
4. IX-002, IX-003: Filter functionality

**Dependency Note:**
TASK-9-5 (AnnotationPolicy) is required for authorization tests. Tests will initially fail until policy is implemented.

**Status:** `Tests Coverage In Progress` → Ready for test implementation
