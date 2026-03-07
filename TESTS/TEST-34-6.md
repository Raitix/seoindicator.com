# TEST-34-6: Project CRUD API Endpoints

## Task Reference
- **Task:** TASK-34-6
- **Feature:** SPEC-034 (AI Visibility Tracking - Generative Engine Optimization)
- **Priority:** High
- **Risk:** Medium

---

## Overview
Tests for REST API endpoints that power the AI Visibility project management interface. These endpoints expose the AIVisibilityService via HTTP for frontend consumption.

**Key Testing Approach:**
- Mock `AIVisibilityServiceInterface` for isolation (TASK-34-5 not yet implemented)
- Laravel HTTP testing with `$this->mock()` for service mocking
- Focus on route registration, middleware, validation, authorization, and error responses

---

## Test Files

| File | Tests | Purpose |
|------|-------|---------|
| `backend/tests/Feature/Api/V1/AIVisibility/ProjectControllerTest.php` | 140+ | Controller endpoints, auth, validation |
| `backend/tests/Unit/Http/Requests/AIVisibility/StoreProjectRequestTest.php` | 45+ | Form request validation |
| `backend/tests/Unit/Http/Requests/AIVisibility/UpdateProjectRequestTest.php` | 35+ | Form request validation |
| `backend/tests/Unit/Policies/AIVisibilityProjectPolicyTest.php` | 30+ | Authorization policy |

**Total:** 250+ test scenarios

---

## API Endpoints Under Test

| Method | Endpoint | Purpose | Auth | Rate Limit |
|--------|----------|---------|------|------------|
| GET | `/api/v1/ai-visibility/projects` | List user's projects | Sanctum | 60/min |
| POST | `/api/v1/ai-visibility/projects` | Create new project | Sanctum | 5/hour |
| GET | `/api/v1/ai-visibility/projects/{id}` | Get project details | Sanctum | 60/min |
| PUT | `/api/v1/ai-visibility/projects/{id}` | Update project | Sanctum | 60/min |
| DELETE | `/api/v1/ai-visibility/projects/{id}` | Delete project | Sanctum | 60/min |
| POST | `/api/v1/ai-visibility/projects/{id}/run` | Trigger query run | Sanctum | 3/hour |

---

## Test Scenarios

### 1. Route Registration (RR-001 to RR-006)

| ID | Scenario | Expected |
|----|----------|----------|
| RR-001 | GET /api/v1/ai-visibility/projects route exists | 200/401 |
| RR-002 | POST /api/v1/ai-visibility/projects route exists | 201/401/422 |
| RR-003 | GET /api/v1/ai-visibility/projects/{id} route exists | 200/401/404 |
| RR-004 | PUT /api/v1/ai-visibility/projects/{id} route exists | 200/401/403/404/422 |
| RR-005 | DELETE /api/v1/ai-visibility/projects/{id} route exists | 204/401/403/404 |
| RR-006 | POST /api/v1/ai-visibility/projects/{id}/run route exists | 202/401/403/404 |

### 2. Authentication Middleware (AUTH-001 to AUTH-010)

| ID | Scenario | Expected |
|----|----------|----------|
| AUTH-001 | Unauthenticated GET /projects returns 401 | 401 |
| AUTH-002 | Unauthenticated POST /projects returns 401 | 401 |
| AUTH-003 | Unauthenticated GET /projects/{id} returns 401 | 401 |
| AUTH-004 | Unauthenticated PUT /projects/{id} returns 401 | 401 |
| AUTH-005 | Unauthenticated DELETE /projects/{id} returns 401 | 401 |
| AUTH-006 | Unauthenticated POST /projects/{id}/run returns 401 | 401 |
| AUTH-007 | Authenticated user can access all endpoints | 200/201/204/202 |
| AUTH-008 | Invalid token returns 401 | 401 |
| AUTH-009 | Expired token returns 401 | 401 |
| AUTH-010 | Auth middleware applied to all routes | All routes protected |

### 3. Authorization - Ownership (AUTHZ-001 to AUTHZ-012)

| ID | Scenario | Expected |
|----|----------|----------|
| AUTHZ-001 | User cannot access another user's project (GET) | 403 |
| AUTHZ-002 | User cannot update another user's project (PUT) | 403 |
| AUTHZ-003 | User cannot delete another user's project (DELETE) | 403 |
| AUTHZ-004 | User cannot trigger run on another user's project | 403 |
| AUTHZ-005 | Owner can access their own project | 200 |
| AUTHZ-006 | Owner can update their own project | 200 |
| AUTHZ-007 | Owner can delete their own project | 204 |
| AUTHZ-008 | Owner can trigger run on their own project | 202 |
| AUTHZ-009 | Non-existent project returns 404 (not 403) | 404 |
| AUTHZ-010 | Deleted project returns 404 | 404 |
| AUTHZ-011 | Policy registered for AIVisibilityProject model | Policy exists |
| AUTHZ-012 | Policy methods: view, update, delete, run | Methods exist |

### 4. GET /projects - List Projects (LIST-001 to LIST-020)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| LIST-001 | Returns empty array when no projects | No projects | `[]` |
| LIST-002 | Returns user's projects only | Multiple users | Only own projects |
| LIST-003 | Includes required fields | Projects exist | id, brand_name, is_active, last_queried_at, mention_rate |
| LIST-004 | Supports pagination | 25 projects | 15 per page, meta with total |
| LIST-005 | Default pagination is 15 | 20 projects | 15 items returned |
| LIST-006 | Custom per_page parameter | per_page=10 | 10 items returned |
| LIST-007 | Max per_page is 100 | per_page=150 | 100 items returned |
| LIST-008 | Filter by is_active=true | Mixed projects | Only active projects |
| LIST-009 | Filter by is_active=false | Mixed projects | Only inactive projects |
| LIST-010 | Sort by created_at ascending | sort=created_at&order=asc | Oldest first |
| LIST-011 | Sort by created_at descending (default) | No params | Newest first |
| LIST-012 | Sort by last_queried_at ascending | sort=last_queried_at&order=asc | Oldest queried first |
| LIST-013 | Sort by last_queried_at descending | sort=last_queried_at&order=desc | Most recent queried first |
| LIST-014 | Invalid sort field ignored | sort=invalid | Default sort applied |
| LIST-015 | Invalid order defaults to desc | order=invalid | Descending order |
| LIST-016 | Mention rate is latest available | Multiple runs | Latest run's rate |
| LIST-017 | Mention rate null if never queried | Never queried | mention_rate: null |
| LIST-018 | Response includes success flag | Any | `{ success: true, data: [...] }` |
| LIST-019 | HTTP method restriction | POST request | 405 |
| LIST-020 | API resource format | Projects exist | CamelCase keys in data |

### 5. POST /projects - Create Project (CREATE-001 to CREATE-030)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| CREATE-001 | Create with valid data | All required fields | 201, project returned |
| CREATE-002 | brand_name required | Missing brand_name | 422, error message |
| CREATE-003 | brand_name max 255 chars | 256 char brand_name | 422, error message |
| CREATE-004 | brand_name alphanumeric + spaces | Special chars | 422, error message |
| CREATE-005 | keywords required | Missing keywords | 422, error message |
| CREATE-006 | keywords must be array | keywords: "string" | 422, error message |
| CREATE-007 | keywords min 1 item | keywords: [] | 422, error message |
| CREATE-008 | keywords max 10 items | 11 keywords | 422, error message |
| CREATE-009 | Each keyword max 100 chars | 101 char keyword | 422, error message |
| CREATE-010 | competitors required | Missing competitors | 422, error message |
| CREATE-011 | competitors must be array | competitors: "string" | 422, error message |
| CREATE-012 | competitors min 1 item | competitors: [] | 422, error message |
| CREATE-013 | competitors max 3 items | 4 competitors | 422, error message |
| CREATE-014 | Each competitor max 100 chars | 101 char competitor | 422, error message |
| CREATE-015 | query_templates optional | No query_templates | 201, defaults applied |
| CREATE-016 | query_templates must be array | query_templates: "string" | 422, error message |
| CREATE-017 | tracking_frequency optional | No tracking_frequency | 201, 'daily' default |
| CREATE-018 | tracking_frequency valid enum | tracking_frequency: 'hourly' | 422, invalid value |
| CREATE-019 | Valid tracking_frequency values | daily, weekly, monthly | 201 each |
| CREATE-020 | Project assigned to authenticated user | Valid data | user_id matches auth |
| CREATE-021 | is_active defaults to true | No is_active | is_active: true |
| CREATE-022 | Response includes success flag | Valid data | `{ success: true, data: {...} }` |
| CREATE-023 | HTTP method restriction | GET request | 405 |
| CREATE-024 | Multiple validation errors | Missing multiple fields | 422, all errors listed |
| CREATE-025 | Unicode in brand_name | 日本語 brand_name | 201, stored correctly |
| CREATE-026 | Unicode in keywords | 日本語 keyword | 201, stored correctly |
| CREATE-027 | Duplicate brand_name allowed | Same brand_name twice | 201 each (user can have duplicates) |
| CREATE-028 | Very long brand_name (255 chars) | 255 char brand_name | 201, stored correctly |
| CREATE-029 | Rate limit 5/hour enforced | 6 requests within hour | 429 on 6th request |
| CREATE-030 | Rate limit resets after hour | Wait 1 hour | New requests allowed |

### 6. GET /projects/{id} - Get Project Details (SHOW-001 to SHOW-015)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| SHOW-001 | Returns project details | Valid ID | 200, all fields |
| SHOW-002 | Includes latest analytics summary | Project with runs | analytics_summary object |
| SHOW-003 | Analytics summary null if never queried | No runs | analytics_summary: null |
| SHOW-004 | Includes all project fields | Valid ID | id, brand_name, keywords, competitors, etc. |
| SHOW-005 | Non-existent project | Invalid ID | 404 |
| SHOW-006 | Another user's project | Other user's ID | 403 (not 404) |
| SHOW-007 | Response includes success flag | Valid ID | `{ success: true, data: {...} }` |
| SHOW-008 | HTTP method restriction | POST request | 405 |
| SHOW-009 | String ID handling | String instead of int | 404 (route binding) |
| SHOW-010 | Negative ID | ID: -1 | 404 |
| SHOW-011 | Zero ID | ID: 0 | 404 |
| SHOW-012 | Large ID | ID: 999999999 | 404 |
| SHOW-013 | Includes created_at and updated_at | Valid ID | Timestamps in ISO format |
| SHOW-014 | Keywords returned as array | Project with keywords | keywords: [...] |
| SHOW-015 | Competitors returned as array | Project with competitors | competitors: [...] |

### 7. PUT /projects/{id} - Update Project (UPDATE-001 to UPDATE-025)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| UPDATE-001 | Update with valid data | All updatable fields | 200, updated project |
| UPDATE-002 | Partial update (brand_name only) | brand_name only | 200, other fields preserved |
| UPDATE-003 | Partial update (keywords only) | keywords only | 200, other fields preserved |
| UPDATE-004 | Update is_active | is_active: false | 200, project inactive |
| UPDATE-005 | Same validation as CREATE | Invalid data | 422, error messages |
| UPDATE-006 | Non-existent project | Invalid ID | 404 |
| UPDATE-007 | Another user's project | Other user's ID | 403 |
| UPDATE-008 | Response includes updated data | Valid update | `{ success: true, data: {...} }` |
| UPDATE-009 | HTTP method restriction | GET request | 405 |
| UPDATE-010 | updated_at timestamp changes | Update request | updated_at > created_at |
| UPDATE-011 | Cannot change user_id | user_id in request | Ignored or 422 |
| UPDATE-012 | Cannot change id | id in request | Ignored |
| UPDATE-013 | Empty brand_name fails | brand_name: "" | 422 |
| UPDATE-014 | Empty keywords array fails | keywords: [] | 422 |
| UPDATE-015 | Empty competitors array fails | competitors: [] | 422 |
| UPDATE-016 | Can add keywords | More keywords | 200, updated list |
| UPDATE-017 | Can remove keywords | Fewer keywords | 200, updated list |
| UPDATE-018 | Can change tracking_frequency | New frequency | 200, updated |
| UPDATE-019 | Unicode handling | 日本語 brand_name | 200, stored correctly |
| UPDATE-020 | Very long brand_name (255 chars) | 255 char brand_name | 200, stored correctly |
| UPDATE-021 | Max keywords validation | 11 keywords | 422 |
| UPDATE-022 | Max competitors validation | 4 competitors | 422 |
| UPDATE-023 | query_templates update | New templates | 200, updated |
| UPDATE-024 | Response format matches GET | Update response | Same structure as show |
| UPDATE-025 | Concurrent update handling | Two updates | Last write wins (or optimistic lock) |

### 8. DELETE /projects/{id} - Delete Project (DELETE-001 to DELETE-015)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| DELETE-001 | Delete existing project | Valid ID | 204 No Content |
| DELETE-002 | Non-existent project | Invalid ID | 404 |
| DELETE-003 | Another user's project | Other user's ID | 403 |
| DELETE-004 | Cascades to related data | Project with runs | All runs deleted |
| DELETE-005 | Cascades to queries | Project with queries | All queries deleted |
| DELETE-006 | Cascades to mentions | Project with mentions | All mentions deleted |
| DELETE-007 | Cascades to alerts | Project with alerts | All alerts deleted |
| DELETE-008 | HTTP method restriction | GET request | 405 |
| DELETE-009 | Delete twice returns 404 | Delete same ID twice | Second: 404 |
| DELETE-010 | Response body empty | Valid delete | Empty body |
| DELETE-011 | Soft delete vs hard delete | Delete request | Verify cascade behavior |
| DELETE-012 | Delete sets deleted_at | Soft delete | deleted_at set |
| DELETE-013 | Deleted project not in list | After delete | Not in GET /projects |
| DELETE-014 | Cannot GET deleted project | After delete | 404 |
| DELETE-015 | Cannot update deleted project | After delete | 404 |

### 9. POST /projects/{id}/run - Trigger Query Run (RUN-001 to RUN-020)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| RUN-001 | Trigger run successfully | Valid ID, within limit | 202 Accepted, job_ids |
| RUN-002 | Non-existent project | Invalid ID | 404 |
| RUN-003 | Another user's project | Other user's ID | 403 |
| RUN-004 | Query limit exceeded | Over monthly limit | 403, limit info |
| RUN-005 | Rate limit 3/hour enforced | 4 requests within hour | 429 on 4th request |
| RUN-006 | Response includes job IDs | Valid run | `{ success: true, data: { job_ids: [...] } }` |
| RUN-007 | Job IDs are valid UUIDs | Valid run | UUID format |
| RUN-008 | Multiple jobs for multiple keywords | 5 keywords | 5 job IDs |
| RUN-009 | HTTP method restriction | GET request | 405 |
| RUN-010 | Checks query limit before dispatch | Near limit | Warning or blocked |
| RUN-011 | Inactive project can still run | is_active: false | 202 |
| RUN-012 | Run increments usage count | Valid run | Usage incremented |
| RUN-013 | Monthly usage reset | New month | Usage reset |
| RUN-014 | Tier limits enforced | Free: 5/month | Blocked after 5 |
| RUN-015 | Tier limits enforced | Pro: 50/month | Blocked after 50 |
| RUN-016 | Tier limits enforced | Business: 200/month | Blocked after 200 |
| RUN-017 | Error response format | Over limit | `{ success: false, error: "...", limit_info: {...} }` |
| RUN-018 | Job dispatch to queue | Valid run | Job dispatched to queue |
| RUN-019 | Job includes project context | Valid run | Job has project_id |
| RUN-020 | Async status tracking | After run | Job status available |

### 10. HTTP Method Restrictions (HTTP-001 to HTTP-030)

| ID | Endpoint | Allowed | Blocked |
|----|----------|---------|---------|
| HTTP-001 | GET /projects | GET | POST, PUT, DELETE, PATCH |
| HTTP-002 | POST /projects | POST | GET, PUT, DELETE, PATCH |
| HTTP-003 | GET /projects/{id} | GET | POST, PUT, DELETE, PATCH |
| HTTP-004 | PUT /projects/{id} | PUT | GET, POST, DELETE, PATCH |
| HTTP-005 | DELETE /projects/{id} | DELETE | GET, POST, PUT, PATCH |
| HTTP-006 | POST /projects/{id}/run | POST | GET, PUT, DELETE, PATCH |
| HTTP-007 | PATCH on /projects | PATCH | 405 |
| HTTP-008 | OPTIONS returns allowed methods | OPTIONS | 200 with Allow header |
| HTTP-009 | HEAD on GET endpoints | HEAD | 200, no body |
| HTTP-010 | Invalid method returns 405 | PROPFIND | 405 |

### 11. Error Response Format (ERR-001 to ERR-010)

| ID | Scenario | Status | Response Format |
|----|----------|--------|-----------------|
| ERR-001 | Validation error | 422 | `{ success: false, errors: {...} }` |
| ERR-002 | Authentication error | 401 | `{ message: "Unauthenticated" }` |
| ERR-003 | Authorization error | 403 | `{ success: false, error: "Forbidden" }` |
| ERR-004 | Not found | 404 | `{ success: false, error: "Not found" }` |
| ERR-005 | Rate limit exceeded | 429 | `{ success: false, error: "Too many requests", retry_after: 3600 }` |
| ERR-006 | Server error | 500 | `{ success: false, error: "Server error" }` |
| ERR-007 | Query limit exceeded | 403 | `{ success: false, error: "...", limit_info: {...} }` |
| ERR-008 | Error messages are user-friendly | Any error | Clear, actionable message |
| ERR-009 | No stack traces in production | 500 | Generic message only |
| ERR-010 | Validation errors grouped by field | 422 | `{ field: [errors] }` |

### 12. Security Testing (SEC-001 to SEC-015)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| SEC-001 | SQL injection in brand_name | `' OR 1=1 --` | Stored as literal string |
| SEC-002 | SQL injection in ID | `1 OR 1=1` | 404, not exploited |
| SEC-003 | XSS in brand_name | `<script>alert(1)</script>` | Sanitized or escaped |
| SEC-004 | XSS in keywords | `"><script>` | Sanitized or escaped |
| SEC-005 | Path traversal in ID | `../../../etc/passwd` | 404, no file access |
| SEC-006 | Mass assignment protection | user_id: 999 | Ignored or rejected |
| SEC-007 | Mass assignment protection | is_admin: true | Ignored |
| SEC-008 | CSRF protection | Without token | Handled by Sanctum |
| SEC-009 | No sensitive data in response | Any request | No passwords, tokens |
| SEC-010 | Rate limiting prevents abuse | Rapid requests | 429 after limit |
| SEC-011 | Enumeration protection | Other user's project | 403, not revealing |
| SEC-012 | Input length limits enforced | Very long input | 422 |
| SEC-013 | JSON parsing errors handled | Invalid JSON body | 400 |
| SEC-014 | Content-Type validation | XML body | 400 or 415 |
| SEC-015 | No timing attacks | Non-existent vs other's | Similar response times |

### 13. Edge Cases (EDGE-001 to EDGE-020)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| EDGE-001 | Empty request body | {} | 422, required fields |
| EDGE-002 | Extra fields in request | brand_name + extra | Extra ignored |
| EDGE-003 | Null values in optional fields | query_templates: null | Handled gracefully |
| EDGE-004 | Unicode in all text fields | 日本語, العربية | Stored correctly |
| EDGE-005 | Emoji in brand_name | 🚀 Brand | Stored correctly |
| EDGE-006 | Very large keyword array | 10 keywords, 100 chars each | Accepted |
| EDGE-007 | Duplicate keywords in array | Same keyword twice | Deduplicated or accepted |
| EDGE-008 | Whitespace-only brand_name | "   " | 422 or trimmed |
| EDGE-009 | Mixed case tracking_frequency | "DAILY" | 422 (case sensitive) |
| EDGE-010 | Negative ID in URL | -1 | 404 |
| EDGE-011 | String ID in URL | "abc" | 404 |
| EDGE-012 | Float ID in URL | 1.5 | 404 |
| EDGE-013 | Very large page number | page=999999 | Empty data array |
| EDGE-014 | Page=0 | page=0 | Defaults to page 1 |
| EDGE-015 | Negative page number | page=-1 | Defaults to page 1 |
| EDGE-016 | Empty keywords array with string | keywords: [""] | 422 (empty keyword) |
| EDGE-017 | Brand name with newlines | "Brand\nName" | Stored or sanitized |
| EDGE-018 | Keywords with special chars | "keyword?&=" | Stored correctly |
| EDGE-019 | Competitor with URL | "https://competitor.com" | Accepted |
| EDGE-020 | Project with no runs | New project | Runs empty, not error |

### 14. Performance Testing (PERF-001 to PERF-005)

| ID | Scenario | Threshold | Expected |
|----|----------|-----------|----------|
| PERF-001 | GET /projects response time | < 200ms | Pass |
| PERF-002 | POST /projects response time | < 300ms | Pass |
| PERF-003 | GET /projects/{id} response time | < 150ms | Pass |
| PERF-004 | DELETE /projects/{id} response time | < 200ms | Pass |
| PERF-005 | List with 100 projects | < 500ms | Pass |

---

## Mock Data Structures

### AIVisibilityProject Model
```php
[
    'id' => 1,
    'user_id' => 1,
    'brand_name' => 'Acme Corp',
    'keywords' => ['acme corp', 'acme products', 'acme services'],
    'competitors' => ['Competitor A', 'Competitor B'],
    'query_templates' => ['What is {brand}?', 'How does {brand} compare?'],
    'tracking_frequency' => 'daily',
    'is_active' => true,
    'last_queried_at' => '2026-03-07T10:00:00Z',
    'created_at' => '2026-03-01T10:00:00Z',
    'updated_at' => '2026-03-07T10:00:00Z',
]
```

### Analytics Summary
```php
[
    'mention_rate' => 75.5,
    'total_mentions' => 150,
    'total_queries' => 200,
    'last_7_days' => [
        'mentions' => 35,
        'queries' => 50,
        'rate_change' => 5.2,
    ],
]
```

### Query Limit Info
```php
[
    'tier' => 'pro',
    'monthly_limit' => 50,
    'used_this_month' => 45,
    'remaining' => 5,
    'resets_at' => '2026-04-01T00:00:00Z',
]
```

---

## AIVisibilityServiceInterface Mock Methods

```php
interface AIVisibilityServiceInterface
{
    public function createProject(int $userId, array $data): AIVisibilityProject;
    public function updateProject(int $projectId, int $userId, array $data): AIVisibilityProject;
    public function deleteProject(int $projectId, int $userId): bool;
    public function getProject(int $projectId, int $userId): ?AIVisibilityProject;
    public function getUserProjects(int $userId, array $filters = []): LengthAwarePaginator;
    public function runQueries(int $projectId, int $userId): array;
    public function getAnalyticsSummary(int $projectId): ?array;
    public function checkQueryLimit(int $projectId): array;
}
```

---

## Acceptance Criteria Mapping

| AC | Test IDs | Status |
|----|----------|--------|
| AIVisibilityProjectController created | RR-001 to RR-006 | Tested |
| GET /projects endpoint | LIST-001 to LIST-020 | Tested |
| POST /projects endpoint | CREATE-001 to CREATE-030 | Tested |
| GET /projects/{id} endpoint | SHOW-001 to SHOW-015 | Tested |
| PUT /projects/{id} endpoint | UPDATE-001 to UPDATE-025 | Tested |
| DELETE /projects/{id} endpoint | DELETE-001 to DELETE-015 | Tested |
| POST /projects/{id}/run endpoint | RUN-001 to RUN-020 | Tested |
| Routes registered in api.php | RR-001 to RR-006 | Tested |
| API resources for response formatting | LIST-018, CREATE-022, SHOW-007, UPDATE-008 | Tested |
| Proper error responses | ERR-001 to ERR-010 | Tested |

---

## Test Execution Commands

```bash
# Run all tests for this task
php artisan test --filter=ProjectController

# Run specific test file
php artisan test tests/Feature/Api/V1/AIVisibility/ProjectControllerTest.php

# Run with coverage
php artisan test --coverage --filter=ProjectController

# Run specific test category
php artisan test --filter="AUTH-"
```

---

## Dependencies
- TASK-34-5 (AIVisibilityService) - Mocked in tests
- Laravel Sanctum (auth)
- AIVisibilityProject model - Mocked or factory
- Form Requests: StoreProjectRequest, UpdateProjectRequest
- Policy: AIVisibilityProjectPolicy

---

## Notes for Developer

### Controller Implementation Checklist
1. Create `AIVisibilityProjectController` at `app/Http/Controllers/Api/V1/AIVisibility/`
2. Implement all 6 endpoint methods
3. Create `StoreAIVisibilityProjectRequest` form request
4. Create `UpdateAIVisibilityProjectRequest` form request
5. Create `AIVisibilityProjectPolicy` policy
6. Register routes in `routes/api.php`
7. Create `AIVisibilityProjectResource` for response formatting

### Rate Limiter Configuration
```php
// AppServiceProvider.php
RateLimiter::for('ai-visibility-create', function (Request $request) {
    return Limit::perHour(5)->by($request->user()->id);
});

RateLimiter::for('ai-visibility-run', function (Request $request) {
    return Limit::perHour(3)->by($request->user()->id);
});
```

### Route Registration
```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('ai-visibility/projects', AIVisibilityProjectController::class);
    Route::post('ai-visibility/projects/{project}/run', [AIVisibilityProjectController::class, 'run'])
        ->middleware('throttle:ai-visibility-run');
});
```

---

## Comments

### PreTester Notes (2026-03-07)
- Tests designed with mocked AIVisibilityServiceInterface (TASK-34-5 not implemented)
- Focus on controller layer: routes, middleware, validation, authorization
- Integration with actual service happens when TASK-34-5 reaches To Test Locally
- All 6 endpoints covered with comprehensive scenarios
- Security testing includes SQL injection, XSS, mass assignment, CSRF
- Rate limiting tests verify POST (5/hour) and run (3/hour) limits
