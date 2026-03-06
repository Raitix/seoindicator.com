# TASK-33-7: Create Public Audit API Routes

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## Specification
**SPEC-33**: Public SEO Audit Reports & Embeddable Score Widget

## Description
Define all API routes for public audit functionality in the Laravel routes file. This includes public endpoints (no auth) and authenticated audit management endpoints.

## Acceptance Criteria
- [ ] Public routes (no authentication):
  - GET `/api/v1/public-audit/{hash}`
  - POST `/api/v1/public-audit/{hash}/lead`
  - GET `/api/v1/public-audit/{hash}/badge`
  - POST `/api/v1/badge/impression`
  - POST `/api/v1/badge/click`
- [ ] Authenticated routes (require auth:sanctum):
  - POST `/api/v1/audits/{id}/publish`
  - DELETE `/api/v1/audits/{id}/unpublish`
  - GET `/api/v1/audits/{id}/embed-code`
- [ ] Rate limiting middleware applied
- [ ] Routes documented with route:list command
- [ ] Integration test verifies route accessibility

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
- `backend/routes/api.php`
- `backend/app/Providers/RouteServiceProvider.php` (rate limiter)

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
