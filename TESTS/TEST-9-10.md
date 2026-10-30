# TEST-9-10: End-to-End Integration Tests for Annotations

## Task Reference
- **Task:** [TASK-9-10](../TASKS/TASK-9-10.md)
- **Feature:** FEAT-009 Keyword Annotations
- **Specification:** SPEC-009

---

## Overview

End-to-end integration tests for the annotations feature. These tests validate the complete workflow from frontend components through backend API to database, ensuring coherence across all system layers.

---

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `backend/tests/Integration/AnnotationE2ETest.php` | Integration | API + database integration tests |
| `backend/tests/Feature/Api/V1/AnnotationApiControllerTest.php` | Feature | API endpoint testing with mock frontend |
| `frontend/tests/e2e/annotations.e2e.spec.ts` | E2E | Playwright/Cypress frontend E2E tests |
| `frontend/tests/integration/AnnotationIntegration.spec.tsx` | Integration | Component + API integration |

---

## Test Scenarios

### API Integration Tests (API)

| ID | Scenario | Method | Endpoint | Input | Expected Output |
|----|----------|--------|----------|-------|-----------------|
| API-001 | Create annotation via API | POST | `/api/v1/keywords/{id}/annotations` | Valid annotation data | 201 Created, annotation persisted |
| API-002 | Create annotation with minimal data | POST | `/api/v1/keywords/{id}/annotations` | Only required fields | 201 Created |
| API-003 | Create annotation with all fields | POST | `/api/v1/keywords/{id}/annotations` | All fields populated | 201 Created |
| API-004 | Read annotation list | GET | `/api/v1/keywords/{id}/annotations` | No filters | 200 OK, array of annotations |
| API-005 | Read single annotation | GET | `/api/v1/annotations/{id}` | Valid annotation ID | 200 OK, annotation data |
| API-006 | Update annotation | PUT | `/api/v1/annotations/{id}` | Updated title/description | 200 OK, updated data |
| API-007 | Delete annotation | DELETE | `/api/v1/annotations/{id}` | Valid annotation ID | 204 No Content |
| API-008 | Filter by date range | GET | `/api/v1/keywords/{id}/annotations?from=2026-01-01&to=2026-02-28` | Date range params | 200 OK, filtered results |
| API-009 | Filter by category | GET | `/api/v1/keywords/{id}/annotations?category=algorithm-update` | Category param | 200 OK, filtered results |
| API-010 | Pagination works | GET | `/api/v1/keywords/{id}/annotations?page=1&perPage=10` | Pagination params | 200 OK, paginated results |
| API-011 | Annotations ordered by date DESC | GET | `/api/v1/keywords/{id}/annotations` | No sort param | Results ordered by date DESC |
| API-012 | API response matches data model | GET | `/api/v1/annotations/{id}` | Valid ID | Response includes id, keyword_id, project_id, date, title, description, category, timestamps |

---

### Error Scenario Tests (ERR)

| ID | Scenario | Method | Endpoint | Input | Expected Output |
|----|----------|--------|----------|-------|-----------------|
| ERR-001 | Create with missing title | POST | `/api/v1/keywords/{id}/annotations` | Empty title | 422 Unprocessable Entity |
| ERR-002 | Create with missing date | POST | `/api/v1/keywords/{id}/annotations` | Empty date | 422 Unprocessable Entity |
| ERR-003 | Create with invalid category | POST | `/api/v1/keywords/{id}/annotations` | Category > 50 chars | 422 Unprocessable Entity |
| ERR-004 | Create with invalid date format | POST | `/api/v1/keywords/{id}/annotations` | date="not-a-date" | 422 Unprocessable Entity |
| ERR-005 | Read non-existent annotation | GET | `/api/v1/annotations/99999` | Invalid ID | 404 Not Found |
| ERR-006 | Update non-existent annotation | PUT | `/api/v1/annotations/99999` | Valid data | 404 Not Found |
| ERR-007 | Delete non-existent annotation | DELETE | `/api/v1/annotations/99999` | Invalid ID | 404 Not Found |
| ERR-008 | Create for non-existent keyword | POST | `/api/v1/keywords/99999/annotations` | Valid data | 404 Not Found |
| ERR-009 | Unauthorized access - no token | GET | `/api/v1/annotations/{id}` | No auth header | 401 Unauthorized |
| ERR-010 | Unauthorized access - wrong user | GET | `/api/v1/annotations/{id}` | Other user's token | 403 Forbidden |
| ERR-011 | Create annotation for other user's keyword | POST | `/api/v1/keywords/{other_user_keyword}/annotations` | Valid data | 403 Forbidden |
| ERR-012 | Update other user's annotation | PUT | `/api/v1/annotations/{other_user_annotation}` | Valid data | 403 Forbidden |
| ERR-013 | Delete other user's annotation | DELETE | `/api/v1/annotations/{other_user_annotation}` | Other user's ID | 403 Forbidden |
| ERR-014 | SQL injection attempt in title | POST | `/api/v1/keywords/{id}/annotations` | title="'; DROP TABLE annotations;--" | 201 Created, title stored as-is |
| ERR-015 | XSS attempt in description | POST | `/api/v1/keywords/{id}/annotations` | description="<script>alert('xss')</script>" | 201 Created, sanitized or escaped |

---

### Frontend Integration Tests (FE)

| ID | Scenario | Component | Input | Expected Output |
|----|----------|-----------|-------|-----------------|
| FE-001 | AnnotationList renders empty state | AnnotationList | Empty annotations array | Shows "No annotations" message |
| FE-002 | AnnotationList renders annotations | AnnotationList | Array of 3 annotations | Renders 3 annotation cards |
| FE-003 | AnnotationList renders chronologically | AnnotationList | Unsorted annotations | Sorted by date DESC |
| FE-004 | AnnotationList edit button fires callback | AnnotationList | Click edit button | onEdit callback called with annotation |
| FE-005 | AnnotationList delete button fires callback | AnnotationList | Click delete button | onDelete callback called with annotationId |
| FE-006 | AnnotationForm renders in create mode | AnnotationForm | No annotation prop | Empty fields, "Create" button |
| FE-007 | AnnotationForm renders in edit mode | AnnotationForm | annotation prop | Fields populated, "Update" button |
| FE-008 | AnnotationForm validates required fields | AnnotationForm | Submit with empty title | Validation error shown |
| FE-009 | AnnotationForm date field validation | AnnotationForm | Invalid date | Validation error shown |
| FE-010 | AnnotationForm category dropdown | AnnotationForm | Click category select | Dropdown shows predefined options |
| FE-011 | AnnotationForm submit fires onSave | AnnotationForm | Click submit | onSave called with form data |
| FE-012 | AnnotationForm cancel fires onCancel | AnnotationForm | Click cancel | onCancel callback called |
| FE-013 | AnnotationForm clears on successful create | AnnotationForm | After successful submit | Fields reset to empty |
| FE-014 | AnnotationList handles loading state | AnnotationList | isLoading=true | Shows loading spinner/skeleton |
| FE-015 | AnnotationList handles error state | AnnotationList | error object | Shows error message |

---

### Full Workflow Integration Tests (WF)

| ID | Scenario | Steps | Expected Output |
|----|----------|-------|-----------------|
| WF-001 | Create annotation workflow | 1. User opens AnnotationForm<br>2. Fills title, date, category<br>3. Clicks save<br>4. API creates annotation<br>5. List refreshes | New annotation appears in list |
| WF-002 | Edit annotation workflow | 1. User clicks edit on annotation<br>2. Form opens with pre-filled data<br>3. User modifies description<br>4. Clicks update<br>5. API updates annotation<br>6. List refreshes | Updated annotation shown in list |
| WF-003 | Delete annotation workflow | 1. User clicks delete on annotation<br>2. Confirmation dialog appears<br>3. User confirms<br>4. API deletes annotation<br>5. List refreshes | Annotation removed from list |
| WF-004 | Filter annotations by date | 1. User selects date range<br>2. API called with date params<br>3. Response filtered | Only annotations in range shown |
| WF-005 | Filter annotations by category | 1. User selects category filter<br>2. API called with category param<br>3. Response filtered | Only matching category shown |
| WF-006 | Pagination workflow | 1. User navigates to page 2<br>2. API called with page param<br>3. Response paginated | Page 2 results shown |
| WF-007 | Multiple annotations same keyword same date | 1. Create annotation for keyword on date<br>2. Create second annotation for same keyword on same date | Both annotations visible, ordered by created_at |
| WF-008 | Concurrent edit handling | 1. User A opens annotation for edit<br>2. User B updates same annotation<br>3. User A saves | Conflict handling (last write wins or error) |

---

### Authorization Integration Tests (AUTH)

| ID | Scenario | User Role | Action | Expected Output |
|----|----------|-----------|--------|-----------------|
| AUTH-001 | Owner can view annotations | Owner | GET annotations | 200 OK |
| AUTH-002 | Owner can create annotations | Owner | POST annotation | 201 Created |
| AUTH-003 | Owner can update annotations | Owner | PUT annotation | 200 OK |
| AUTH-004 | Owner can delete annotations | Owner | DELETE annotation | 204 No Content |
| AUTH-005 | Non-owner cannot view other's annotations | Non-owner | GET other's annotation | 403 Forbidden |
| AUTH-006 | Non-owner cannot create for other's keyword | Non-owner | POST to other's keyword | 403 Forbidden |
| AUTH-007 | Non-owner cannot update other's annotation | Non-owner | PUT other's annotation | 403 Forbidden |
| AUTH-008 | Non-owner cannot delete other's annotation | Non-owner | DELETE other's annotation | 403 Forbidden |
| AUTH-009 | Guest cannot access annotations | Guest | GET annotations | 401 Unauthorized |
| AUTH-010 | Guest cannot create annotations | Guest | POST annotation | 401 Unauthorized |

---

### Data Consistency Tests (DC)

| ID | Scenario | Action | Expected Output |
|----|----------|--------|-----------------|
| DC-001 | Created annotation persists in database | Create via API | SELECT returns matching record |
| DC-002 | Updated annotation reflects changes | Update via API | Database shows updated values |
| DC-003 | Deleted annotation removed from database | Delete via API | SELECT returns no results |
| DC-004 | Annotation keyword_id matches | Create with keyword_id | annotation.keyword_id = provided ID |
| DC-005 | Annotation project_id auto-populated | Create annotation | project_id = keyword.project_id |
| DC-006 | Deleted keyword cascades to annotations | Delete keyword | All keyword's annotations deleted |
| DC-007 | Deleted project cascades to annotations | Delete project | All project's annotations deleted |
| DC-008 | Timestamps auto-managed | Create annotation | created_at, updated_at set |
| DC-009 | Updated_at changes on update | Update annotation | updated_at > created_at |
| DC-010 | Category defaults to 'other' | Create without category | category = 'other' |

---

### Performance Tests (PERF)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PERF-001 | List 100 annotations < 200ms | 100 annotations | Response time < 200ms |
| PERF-002 | Create annotation < 100ms | Valid POST | Response time < 100ms |
| PERF-003 | Update annotation < 100ms | Valid PUT | Response time < 100ms |
| PERF-004 | Delete annotation < 100ms | Valid DELETE | Response time < 100ms |
| PERF-005 | Pagination with 1000 annotations | 1000 annotations, page 5 | Fast response, correct page |
| PERF-006 | Date range query uses index | Large dataset, date filter | EXPLAIN shows index usage |
| PERF-007 | Category filter uses index | Large dataset, category filter | EXPLAIN shows index usage |

---

### Edge Case Integration Tests (EC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Empty annotations list response | GET with no data | Empty array `[]` |
| EC-002 | Special characters in title | title="Test & Co. <special>" | Stored and retrieved correctly |
| EC-003 | Unicode in description | description="日本語 עברית العربية" | Stored and retrieved correctly |
| EC-004 | Emoji in title | title="Rocket 🚀 Launch" | Stored and retrieved correctly |
| EC-005 | Very long title (255 chars) | title=255 chars | Accepted |
| EC-006 | Title exceeds 255 chars | title=256 chars | 422 Validation error |
| EC-007 | Very long description | description=65000 chars | Accepted (TEXT limit) |
| EC-008 | Date in far future | date="2099-12-31" | Accepted |
| EC-009 | Date in far past | date="2000-01-01" | Accepted |
| EC-010 | Multiple rapid creates | 10 POSTs simultaneously | All created, no race conditions |
| EC-011 | Concurrent updates to same annotation | 2 PUTs simultaneously | No corruption, last write wins |
| EC-012 | Category with spaces | category="content update" | Accepted (no enum restriction) |
| EC-013 | Category exactly 50 chars | category=50 chars | Accepted |
| EC-014 | Category exceeds 50 chars | category=51 chars | 422 Validation error |

---

## Mock Data

### Sample API Request Bodies

**Create Annotation:**
```json
{
  "date": "2026-02-27",
  "title": "Google Core Update Impact",
  "description": "Position dropped from #5 to #12 after core update",
  "category": "algorithm-update"
}
```

**Update Annotation:**
```json
{
  "title": "Google Core Update Impact - Recovered",
  "description": "Position recovered to #7 after content optimization",
  "category": "recovery"
}
```

### Sample API Response

**Single Annotation:**
```json
{
  "data": {
    "id": 1,
    "keyword_id": 42,
    "project_id": 1,
    "date": "2026-02-27",
    "title": "Google Core Update Impact",
    "description": "Position dropped from #5 to #12 after core update",
    "category": "algorithm-update",
    "created_at": "2026-02-27T10:00:00Z",
    "updated_at": "2026-02-27T10:00:00Z"
  }
}
```

**Annotation List:**
```json
{
  "data": [
    {
      "id": 1,
      "keyword_id": 42,
      "project_id": 1,
      "date": "2026-02-27",
      "title": "Google Core Update Impact",
      "description": "Position dropped from #5 to #12 after core update",
      "category": "algorithm-update",
      "created_at": "2026-02-27T10:00:00Z",
      "updated_at": "2026-02-27T10:00:00Z"
    }
  ],
  "meta": {
    "total": 1,
    "page": 1,
    "perPage": 15
  }
}
```

### Category Options for Dropdown
```typescript
const ANNOTATION_CATEGORIES = [
  { value: 'algorithm-update', label: 'Algorithm Update', icon: '🔄' },
  { value: 'content-update', label: 'Content Update', icon: '📝' },
  { value: 'technical-change', label: 'Technical Change', icon: '🔧' },
  { value: 'link-building', label: 'Link Building', icon: '🔗' },
  { value: 'competition', label: 'Competitor Activity', icon: '👀' },
  { value: 'seasonal', label: 'Seasonal Trend', icon: '📅' },
  { value: 'manual-action', label: 'Manual Action', icon: '⚠️' },
  { value: 'recovery', label: 'Recovery', icon: '✅' },
  { value: 'other', label: 'Other', icon: '📌' },
];
```

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| API Integration | 100% | API-001 to API-012 (12 tests) |
| Error Scenarios | 100% | ERR-001 to ERR-015 (15 tests) |
| Frontend Integration | 100% | FE-001 to FE-015 (15 tests) |
| Full Workflows | 100% | WF-001 to WF-008 (8 tests) |
| Authorization | 100% | AUTH-001 to AUTH-010 (10 tests) |
| Data Consistency | 100% | DC-001 to DC-010 (10 tests) |
| Performance | 100% | PERF-001 to PERF-007 (7 tests) |
| Edge Cases | 100% | EC-001 to EC-014 (14 tests) |

**Total: 91 test scenarios**

---

## Test Execution

### Backend Tests
```bash
# Run all annotation E2E tests
php artisan test --filter=AnnotationE2E

# Run specific test file
php artisan test tests/Integration/AnnotationE2ETest.php

# Run API controller tests
php artisan test tests/Feature/Api/V1/AnnotationApiControllerTest.php

# Run with coverage
php artisan test --coverage --filter=Annotation
```

### Frontend Tests
```bash
# Run Playwright E2E tests
npx playwright test tests/e2e/annotations.e2e.spec.ts

# Run component integration tests
npm test -- AnnotationIntegration.spec.tsx

# Run all frontend tests
npm run test:e2e
```

---

## Dependencies

| Dependency | Task | Status | Notes |
|------------|------|--------|-------|
| Annotations table | TASK-9-1 | To Develop | Database schema |
| Annotation model | TASK-9-2 | To Develop | Eloquent model |
| Annotation relationships | TASK-9-3 | To Develop | Model relationships |
| AnnotationController | TASK-9-4 | To Write Tests | API endpoints |
| AnnotationPolicy | TASK-9-5 | To Write Tests | Authorization |
| Validation requests | TASK-9-6 | To Write Tests | Input validation |
| AnnotationList component | TASK-9-7 | To Prioritize | Frontend list |
| AnnotationForm component | TASK-9-8 | To Prioritize | Frontend form |
| Unit tests | TASK-9-9 | To Write Tests | Backend unit tests |

---

## Notes for Developer

### API Endpoint Structure
```
GET    /api/v1/keywords/{keywordId}/annotations         - List annotations for keyword
POST   /api/v1/keywords/{keywordId}/annotations         - Create annotation
GET    /api/v1/annotations/{id}                         - Get single annotation
PUT    /api/v1/annotations/{id}                         - Update annotation
DELETE /api/v1/annotations/{id}                         - Delete annotation
```

### Frontend Component Props

**AnnotationList:**
```typescript
interface AnnotationListProps {
  keywordId: number;
  annotations: Annotation[];
  onEdit: (annotation: Annotation) => void;
  onDelete: (annotationId: number) => void;
  isLoading?: boolean;
  error?: Error | null;
}
```

**AnnotationForm:**
```typescript
interface AnnotationFormProps {
  keywordId: number;
  annotation?: Annotation;  // For edit mode
  onSave: (data: CreateAnnotationDTO) => void;
  onCancel: () => void;
}
```

### Key Integration Points
1. **API → Database**: AnnotationController creates records via Annotation model
2. **Frontend → API**: Components call API via fetch/axios hooks
3. **Authorization**: Policy checks on every controller action
4. **Validation**: FormRequest classes validate input before model creation
5. **State Management**: React Query/SWR for API state caching

### Testing Priority
1. AUTH-005 to AUTH-008: Authorization is critical for security
2. ERR-009 to ERR-013: Unauthorized access must be blocked
3. WF-001 to WF-003: Core user workflows must work
4. DC-006, DC-007: Cascade deletes ensure data integrity

---

## Comments

### [2026-02-27 11:25] Agent: PreTester
**Test Documentation Created:**
- 91 test scenarios covering API, errors, frontend, workflows, authorization, data consistency, performance, and edge cases
- Comprehensive coverage of all CRUD operations with frontend-backend alignment
- Security tests include SQL injection (ERR-014) and XSS (ERR-015) attempts
- Authorization tests cover owner vs non-owner access (AUTH-001 to AUTH-010)
- Cascade delete tests ensure data integrity (DC-006, DC-007)

**Key Integration Priorities:**
1. API-001 to API-012: All CRUD endpoints must work with correct responses
2. ERR-009 to ERR-013: Authorization enforcement is critical
3. WF-001 to WF-003: User workflows must be seamless
4. FE-001 to FE-015: Frontend components must integrate with API

**Test Dependencies:**
- Backend tests require: Migration, Model, Controller, Policy
- Frontend tests require: Components, API hooks
- E2E tests require: Full stack implementation

**Status:** Tests Coverage In Progress - Ready for Developer implementation
