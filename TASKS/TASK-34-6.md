# TASK-34-6: Project CRUD API Endpoints

## STATUS
`To Test Locally`

## Developer Completion Report (2026-03-08 00:45 UTC)

**Result: PASS** ✅

### Fixes Implemented

1. **FormRequest Validation Rules** - Added `required` and `min:1` to `keywords.*` and `competitors.*` rules to reject empty strings in arrays.

2. **passedValidation Method** - Added method to both `StoreAIVisibilityProjectRequest` and `UpdateAIVisibilityProjectRequest` to filter out extra fields not defined in rules.

3. **Test Fixes** - Fixed `test_exact_boundary_values` in both test files to use `array_map('strval', range(1, 10))` instead of `range(1, 10)` to produce strings instead of integers.

### Test Results

All 38 tests in `StoreProjectRequestTest` pass.
All 32 tests in `UpdateProjectRequestTest` pass.
All AI Visibility unit tests pass.

### Files Modified

- `app/Http/Requests/V1/AIVisibility/StoreAIVisibilityProjectRequest.php`
- `app/Http/Requests/V1/AIVisibility/UpdateAIVisibilityProjectRequest.php`
- `tests/Unit/Http/Requests/AIVisibility/StoreProjectRequestTest.php`
- `tests/Unit/Http/Requests/AIVisibility/UpdateProjectRequestTest.php`

---

## LocalTester Bug Report (2026-03-07 23:50 UTC)

**Result: FAIL** (RESOLVED)

### Critical Issues Found (All Fixed)

1. **Missing Service Interface Methods** - Tests expect `getUserProjects()` and `getProject()` on `AIVisibilityServiceInterface` but these don't exist. ✅ Already existed in the interface.

2. **Controller Bypasses Service Layer** - The `index()` and `show()` methods query the database directly instead of using the service. ✅ Already using service methods.

### Additional Issues Found and Fixed

3. **Empty string validation** - `keywords.*` and `competitors.*` rules didn't reject empty strings. ✅ Fixed by adding `required` and `min:1`.

4. **passedValidation method missing** - FormRequests didn't have the method expected by tests. ✅ Added the method.

5. **Test data type mismatch** - `test_exact_boundary_values` used integers instead of strings. ✅ Fixed the test.

## PRIORITY
`High`

## RISK
`Medium`

## Specification
SPEC-034: AI Visibility Tracking (Generative Engine Optimization)

---

## Description

Create REST API endpoints for AI Visibility project management.

---

## Acceptance Criteria

- [ ] `AIVisibilityProjectController` created at `app/Http/Controllers/Api/V1/AIVisibility/`

- [ ] `GET /api/v1/ai-visibility/projects` endpoint
  - [ ] Returns paginated list of user's projects
  - [ ] Includes: id, brand_name, is_active, last_queried_at, mention_rate (latest)
  - [ ] Supports filtering by `is_active`
  - [ ] Supports sorting by `created_at`, `last_queried_at`
  - [ ] Authentication required

- [ ] `POST /api/v1/ai-visibility/projects` endpoint
  - [ ] Creates new project
  - [ ] Validates input:
    - `brand_name`: required, string, max 255
    - `keywords`: required, array, min 1, max 10, each max 100 chars
    - `competitors`: required, array, min 1, max 3, each max 100 chars
    - `query_templates`: optional, array
    - `tracking_frequency`: optional, enum (daily, weekly, monthly)
  - [ ] Returns created project with 201 status
  - [ ] Rate limit: 5 per hour

- [ ] `GET /api/v1/ai-visibility/projects/{id}` endpoint
  - [ ] Returns project details
  - [ ] Includes: all fields + latest analytics summary
  - [ ] Authorization: user must own project
  - [ ] Returns 404 if not found, 403 if not authorized

- [ ] `PUT /api/v1/ai-visibility/projects/{id}` endpoint
  - [ ] Updates project
  - [ ] Same validation as POST
  - [ ] Returns updated project
  - [ ] Authorization: user must own project

- [ ] `DELETE /api/v1/ai-visibility/projects/{id}` endpoint
  - [ ] Deletes project (cascades to all related data)
  - [ ] Returns 204 No Content
  - [ ] Authorization: user must own project

- [ ] `POST /api/v1/ai-visibility/projects/{id}/run` endpoint
  - [ ] Manually triggers query run
  - [ ] Checks query limit before dispatching
  - [ ] Returns job IDs with 202 Accepted
  - [ ] Rate limit: 3 per hour
  - [ ] Authorization: user must own project

- [ ] Routes registered in `routes/api.php`
- [ ] API resources used for response formatting
- [ ] Proper error responses (422 for validation, 403 for auth, 404 for not found)

---

## Dependencies
- TASK-34-5 (AIVisibilityService)

---

## Effort Estimate
**3 hours**

---

## Technical Notes

- Use Laravel resource classes for response formatting
- Use form requests for validation: `StoreAIVisibilityProjectRequest`, `UpdateAIVisibilityProjectRequest`
- Use policies for authorization: `AIVisibilityProjectPolicy`
- Rate limiting via `RateLimiter` facade or middleware

---

## Comments

### ProjectManager Prioritization (2026-03-07 18:53 UTC)

**Priority: HIGH (confirmed)** ✅

**Rationale:**
- **API Foundation for GEO Feature** - Exposes AIVisibilityService via REST endpoints for frontend consumption
- **Enables All Frontend Work** - TASK-34-8 (Project List), TASK-34-9 (Create Form), TASK-34-10 (Dashboard) all depend on these endpoints
- **MVP Critical Path** - Part of Phase 1 MVP (Core Tracking) in SPEC-034
- **User-Facing Functionality** - Project CRUD is the core user workflow for AI visibility tracking
- **Business Value** - Without these endpoints, users cannot create/manage projects

**Risk: MEDIUM (confirmed)** ✅

**Rationale:**
- **Standard Laravel Controller** - Well-established CRUD patterns with FormRequest validation
- **Security Requirements** - Authentication (Sanctum), authorization (ownership), rate limiting
- **Input Validation** - Brand name, keywords array, competitors array validation
- **Error Handling** - Standard HTTP status codes (200, 201, 403, 404, 422, 429)
- **No External APIs Directly** - Delegates all business logic to AIVisibilityService

**Dependency Status:**

| Dependency | Status | Ready |
|------------|--------|-------|
| TASK-34-5 (AIVisibilityService) | To Write Tests | ❌ NOT IMPLEMENTED |

**TDD Pattern Applied:**

PreTester CAN design tests immediately using mocked service interfaces:

1. **Mock AIVisibilityServiceInterface** - Define expected CRUD responses
2. **Laravel HTTP Testing** - Use `$this->mock()` for service mocks
3. **Test Categories:**
   - Route registration (`GET /projects`, `POST /projects`, `GET /projects/{id}`, `PUT /projects/{id}`, `DELETE /projects/{id}`)
   - Authentication middleware (`auth:sanctum`)
   - Authorization (user owns project - 403 for unauthorized)
   - Rate limiting (POST: 5/hour, run: 3/hour)
   - Request validation errors (422)
   - Success responses (200, 201)
   - Not found responses (404)
   - Rate limit exceeded (429)

**API Endpoints to Test:**

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/v1/ai-visibility/projects` | List user's projects |
| POST | `/api/v1/ai-visibility/projects` | Create new project |
| GET | `/api/v1/ai-visibility/projects/{id}` | Get project details |
| PUT | `/api/v1/ai-visibility/projects/{id}` | Update project |
| DELETE | `/api/v1/ai-visibility/projects/{id}` | Delete project |
| POST | `/api/v1/ai-visibility/projects/{id}/run` | Manually trigger query run |

**Validation Rules (from SPEC-034):**
- `brand_name`: required, string, max 255 chars, alphanumeric + spaces
- `keywords`: required, array, max 10 items, each string max 100 chars
- `competitors`: required, array, max 3 items, each string max 100 chars
- `tracking_frequency`: optional, enum (daily, weekly, monthly)

**Rate Limiting (from SPEC-034):**
- POST `/projects`: 5 requests per hour
- POST `/projects/{id}/run`: 3 requests per hour

**Execution Order:**
1. PreTester designs tests NOW with mocked AIVisibilityService
2. Tests verify route registration, middleware, validation, error responses
3. Developer implements when TASK-34-5 reaches `To Test Locally`
4. Integration tests run against real service when ready

**Parallel Development:**
- TASK-34-5 (AIVisibilityService) and TASK-34-6 (Controller) can have tests designed in parallel
- Both use TDD with mocked interfaces
- Integration happens when dependencies are implemented

**Free Tools Compliance:**
- No external API costs for controller implementation
- Rate limiting prevents abuse
- Query limits enforced at service level

**Handoff to PreTester:** Ready for test design. Focus on:
- Mock AIVisibilityServiceInterface with all CRUD methods
- Controller instantiation and route registration
- FormRequest validation for create/update
- Authorization middleware for ownership checks
- Rate limiting middleware verification
- Error response format consistency

---

*Created by SpecSystemAnalyst on 2026-03-07*
