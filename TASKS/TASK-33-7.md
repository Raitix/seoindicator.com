# TASK-33-7: Create Public Audit API Routes

## STATUS
`To Test Locally`

## PRIORITY
`High`

## RISK
`Low`

## Specification
**SPEC-33**: Public SEO Audit Reports & Embeddable Score Widget

## Description
Define all API routes for public audit functionality in the Laravel routes file. This includes public endpoints (no auth) and authenticated audit management endpoints.

## Acceptance Criteria
- [x] Public routes (no authentication):
  - GET `/api/v1/public-audit/{hash}`
  - POST `/api/v1/public-audit/{hash}/lead`
  - GET `/api/v1/public-audit/{hash}/badge`
  - POST `/api/v1/badge/impression`
  - POST `/api/v1/badge/click`
- [x] Authenticated routes (require auth:sanctum):
  - POST `/api/v1/audits/{id}/publish`
  - DELETE `/api/v1/audits/{id}/unpublish`
  - GET `/api/v1/audits/{id}/embed-code`
- [x] Rate limiting middleware applied
- [x] Routes documented with route:list command
- [x] Integration test verifies route accessibility

## Dependencies
- TASK-33-5 (PublicAuditController)
- TASK-33-17, TASK-33-18 (badge tracking endpoints)

## Effort Estimate
**1 hour** - Route definitions

## Technical Notes
- Use route groups for public vs authenticated
- Apply `throttle:public-audit` rate limiter to public routes
- Use route model binding with Audit model
- Publish/unpublish endpoints check user ownership

## Files to Modify
- `backend/routes/api.php` ✅
- `backend/app/Providers/RouteServiceProvider.php` (rate limiter) → `backend/app/Providers/AppServiceProvider.php` ✅

## Comments

### [2026-03-06 18:25 GMT+2] Developer - Implementation Complete

**Routes Implemented:**

1. **Public Audit Routes** (no auth):
   - GET `/api/v1/public-audit/{hash}` → `api.v1.public-audit.show`
   - POST `/api/v1/public-audit/{hash}/lead` → `api.v1.public-audit.lead`
   - GET `/api/v1/public-audit/{hash}/badge` → `api.v1.public-audit.badge`
   - All with `throttle:public-audit` middleware (100/hour per IP)

2. **Badge Tracking Routes** (no auth):
   - POST `/api/v1/badge/impression` → `api.v1.badge.impression`
   - POST `/api/v1/badge/click` → `api.v1.badge.click`
   - All with `throttle:badge-tracking` middleware (60/minute per IP)

3. **Authenticated Audit Routes** (auth:sanctum):
   - POST `/api/v1/audits/{id}/publish` → `api.v1.audits.publish`
   - DELETE `/api/v1/audits/{id}/unpublish` → `api.v1.audits.unpublish`
   - GET `/api/v1/audits/{id}/embed-code` → `api.v1.audits.embed-code`
   - All with `throttle:60,1` middleware (60/minute per user)

**Rate Limiters Added** (`AppServiceProvider.php`):
- `public-audit`: 100 requests/hour per IP
- `badge-tracking`: 60 requests/minute per IP

**Controller Stubs Created:**
- `BadgeTrackingController.php` - impression/click methods (returns success for now)
- `AuditVisibilityController.php` - publish/unpublish/embedCode methods (returns 404 for now)

**Test Suite:**
- Created `PublicAuditRoutesTest.php` with 73 tests covering:
  - Route registration (8 tests)
  - Route paths (8 tests)
  - HTTP methods (8 tests)
  - Middleware - public routes (5 tests)
  - Middleware - authenticated routes (3 tests)
  - Rate limiting (3 tests)
  - HTTP requests - public (6 tests)
  - HTTP requests - authenticated (6 tests)
  - HTTP method restrictions (8 tests)
  - Route model binding (3 tests)
  - API prefix (1 test)
  - Security (5 tests)
  - Edge cases (4 tests)
  - Acceptance criteria (5 tests)

**Test Results:** ✅ 73 passed (181 assertions)

**Handoff to LocalTester:**
- All routes registered and accessible
- Rate limiting configured
- Controller stubs return appropriate responses
- Ready for integration testing with full controller implementations (TASK-33-5, TASK-33-17, TASK-33-18)

## Comments

### [2026-03-06 15:58 GMT+2] ProjectManager - Prioritization Assessment

**Decision:** HIGH priority confirmed ✅

**Priority Assessment:**
- **Foundation Task:** Routes are the API surface that enables all frontend integration
- **Multiple Endpoint Groups:**
  - 5 public routes (no auth): public-audit and badge tracking
  - 3 authenticated routes: audit publish/unpublish/embed
- **Business Critical:** Routes connect user actions to backend functionality
- **Effort:** 1 hour (low effort, high impact configuration)

**Risk Assessment: LOW (confirmed)**
- Route definitions are configuration-level code
- No external API dependencies
- Standard Laravel routing patterns
- Rate limiting middleware is well-documented
- Route model binding is standard Laravel feature

**Dependency Status (ALL BLOCKED):**
- TASK-33-5 (PublicAuditController): `To Write Tests` - NOT IMPLEMENTED ❌
- TASK-33-17 (Badge Impression Controller): `To Prioritize` - NOT IMPLEMENTED ❌
- TASK-33-18 (Badge Click Controller): `To Prioritize` - NOT IMPLEMENTED ❌

**TDD Pattern Applied:**
PreTester CAN design tests before controllers exist:
1. **Route Registration Tests:** Verify routes are registered via `route:list`
2. **Middleware Tests:** Verify rate limiting and auth middleware applied correctly
3. **Route Model Binding Tests:** Verify hash resolution works
4. **Mock Controller Tests:** Use mock controllers to test routing logic
5. **HTTP Method Tests:** Verify correct verbs (GET, POST, DELETE)

**Test Categories for PreTester:**
| Category | Tests | Description |
|----------|-------|-------------|
| Route Registration | 8 | All 8 routes registered |
| Middleware Assignment | 8 | Rate limiting, auth applied |
| HTTP Methods | 8 | Correct verbs for each route |
| Route Names | 8 | Named routes for URL generation |
| Route Model Binding | 3 | Hash resolution for public-audit routes |
| Group Configuration | 2 | Public vs authenticated groups |

**Downstream Impact:**
- TASK-33-8 (PublicAuditPage Component) - Needs GET /public-audit/{hash}
- TASK-33-20 (Publish Audit UI) - Needs POST /audits/{id}/publish
- TASK-33-21 (Embed Code Generator) - Needs GET /audits/{id}/embed-code
- All Phase 2 Frontend tasks - INDIRECTLY BLOCKED until routes exist

**Key Technical Points:**
- Use route groups for clean separation:
  - `Route::prefix('public-audit')->withoutMiddleware('auth')` for public
  - `Route::middleware('auth:sanctum')` for authenticated
- Rate limiter `throttle:public-audit` needs configuration in RouteServiceProvider
- Route model binding: `Route::get('public-audit/{audit:public_hash}', ...)`
- Publish/unpublish must check `$audit->user_id === auth()->id()`

**Execution Order:**
1. PreTester designs tests (NOW) - parallel with controller development
2. Controllers implemented (TASK-33-5, 33-17, 33-18)
3. Developer implements routes (waits for controllers)
4. Integration tests run against full stack

**Handoff to PreTester:** Ready for test design. Use TDD with mocked controllers.

### [2026-03-06 16:52 GMT+2] PreTester - Test Design Complete

**Test Suite Created:** `backend/tests/Feature/Api/V1/PublicAuditRoutesTest.php`
**Test Documentation:** `PROJECT-SEOINDICATOR/TESTS/TEST-33-7.md`

**Tests Created:** 65 tests across 14 categories
| Category | Tests | Status |
|----------|-------|--------|
| Route Registration (RR) | 8 | Ready |
| Route Paths (RP) | 8 | Ready |
| HTTP Methods (HM) | 8 | Ready |
| Middleware - Public (MW-PUB) | 5 | Ready |
| Middleware - Auth (MW-AUTH) | 3 | Ready |
| Rate Limiting (RL) | 3 | Ready |
| HTTP Requests - Public (HTTP-PUB) | 6 | Ready |
| HTTP Requests - Auth (HTTP-AUTH) | 6 | Ready |
| HTTP Method Restrictions (HMR) | 8 | Ready |
| Route Model Binding (RMB) | 3 | Ready |
| API Prefix (API) | 1 | Ready |
| Security (SEC) | 5 | Ready |
| Edge Cases (EC) | 4 | Ready |
| Acceptance Criteria (AC) | 5 | Ready |

**TDD Pattern Applied:**
- Tests verify route registration and middleware BEFORE controllers exist
- Tests use 404 as expected status when controllers not implemented
- After route implementation, tests will validate full stack

**Security Tests Included:**
- SQL injection prevention in hash/id parameters
- XSS sanitization in email input
- Invalid format handling

**Handoff to Developer:**
1. Define routes in `backend/routes/api.php`
2. Configure rate limiters in `RouteServiceProvider`
3. Create controller stubs (can return empty responses initially)
4. Run tests: `php artisan test --filter PublicAuditRoutesTest`
5. Tests will FAIL until routes are registered

**Expected Test Results (Before Implementation):**
- Route registration tests: FAIL (routes not defined)
- Middleware tests: FAIL (routes not defined)
- HTTP tests: Return 404 (no routes)

**Expected Test Results (After Implementation):**
- All tests PASS
- Routes return appropriate responses based on controller logic
