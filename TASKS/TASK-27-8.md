# TASK-27-8: Admin Usage API Endpoints

## STATUS
`To Deploy`

## PRIORITY
`High`

## RISK
`Medium`

## Parent Specification
SPEC-027: Admin Dashboard (Ops + Usage)

## Description
Create API endpoints for admin usage metrics. Provides overview, request breakdowns, token statistics, and usage trends.

## Acceptance Criteria
- [x] Controller created at `app/Http/Controllers/Api/V1/Admin/UsageController.php`
- [x] GET `/api/v1/admin/usage/overview` returns aggregated usage stats
- [x] GET `/api/v1/admin/usage/requests` returns request counts by endpoint
- [x] GET `/api/v1/admin/usage/tokens` returns token usage by model
- [x] GET `/api/v1/admin/usage/trends` returns usage trends (time series)
- [x] All endpoints protected by AdminAuthorizationMiddleware
- [x] Rate limiting: 60 requests/minute
- [x] Optional query parameters: start_date, end_date, granularity
- [x] Feature tests cover all endpoints
- [ ] API documentation updated

## Dependencies
- TASK-27-2 (AdminAuthorizationMiddleware)
- TASK-27-7 (UsageAggregationService)

## Effort Estimate
`Medium` (2-4 hours)

## Technical Notes
- Use Laravel API resources for transformation
- Support date range filtering
- Consider adding export functionality (CSV) in future

## Comments

### 2026-03-04 - LocalTester
**Local QA Verification Complete** ✅

**Test Execution Results:**
- **Total Tests:** 71 passed (186 assertions)
- **Duration:** 10.22s
- **Status:** All tests passing

**Test Categories Verified:**
| Category | Tests | Status |
|----------|-------|--------|
| Overview Endpoint | 10 | ✅ Pass |
| Requests Endpoint | 7 | ✅ Pass |
| Tokens Endpoint | 6 | ✅ Pass |
| Trends Endpoint | 10 | ✅ Pass |
| Query Parameters | 9 | ✅ Pass |
| Rate Limiting | 1 | ✅ Pass |
| HTTP Methods | 6 | ✅ Pass |
| Security | 4 | ✅ Pass |
| Error Handling | 3 | ✅ Pass |
| Performance | 2 | ✅ Pass |
| Edge Cases | 6 | ✅ Pass |
| Data Types | 5 | ✅ Pass |
| Integration | 3 | ✅ Pass |

**Adversarial Testing Performed:**
- ✅ SQL injection attempts in query params → properly handled
- ✅ XSS attempts in query params → properly handled
- ✅ Large dataset handling → passes under 4s
- ✅ Concurrent requests → handled correctly
- ✅ Invalid date formats → validation errors returned
- ✅ Days parameter overflow (max 90) → capped correctly

**Security Verification:**
- Admin-only access enforced (401 for unauthenticated, 403 for non-admin)
- No sensitive data exposure (passwords, emails, API keys)
- Rate limiting active (60 req/min)

**Performance:**
- Overview endpoint: < 500ms
- Trends endpoint: < 500ms
- Large dataset handling: 3.23s for bulk data

**Deployment Readiness:** ✅ APPROVED
- All tests pass
- Security boundaries verified
- No regressions detected
- Ready for DevOps deployment

**Handoff to DevOps:**
- Deploy to production
- Verify endpoints after deployment
- Update API documentation (acceptance criteria item remaining)

### 2026-03-04 - Developer
**Implementation Complete**

**Files Created:**
- `app/Http/Controllers/Api/V1/Admin/UsageController.php` (7241 bytes)
- Routes added to `routes/api.php`

**Implementation Details:**
1. Created `UsageController` with 4 endpoints:
   - `overview()` - Returns aggregated usage stats (requests, tokens, analyses)
   - `requests()` - Returns request counts by endpoint
   - `tokens()` - Returns token usage statistics
   - `trends()` - Returns usage time series

2. Routes added under `api/v1/admin/usage/*`:
   - GET `/overview` → UsageController::overview
   - GET `/requests` → UsageController::requests
   - GET `/tokens` → UsageController::tokens
   - GET `/trends` → UsageController::trends

3. Middleware stack:
   - `throttle:60,1` - Rate limiting (60 req/min)
   - `admin` - AdminAuthorizationMiddleware

4. Query parameter support:
   - `start_date`, `end_date`, `granularity` for overview/requests/tokens
   - `days` for trends (capped at 90 max)

5. Error handling:
   - Validation errors return 422 with proper error format
   - Service exceptions return 500 with proper error format

**Test Results:**
- **71 tests passed** (186 assertions)
- Duration: 7.57s

**Fixes Applied:**
- `trends_limits_max_days`: Changed validation to cap days at 90 instead of rejecting
- `endpoints_do_not_expose_sensitive_user_data`: Updated test to check for actual sensitive data (password, email, api_key, bearer, secret) instead of substring "token" which matched legitimate "tokens" field

**Handoff to LocalTester:**
- All 71 feature tests pass
- Controller and routes implemented
- Ready for local QA validation

### 2026-03-04 - PreTester
**Tests Coverage Complete**

- **Tests Created:** 71 feature tests
- **Test File:** `backend/tests/Feature/Api/V1/Admin/UsageControllerTest.php`
- **Documentation:** `TESTS/TEST-27-8.md`

**Test Categories:**
| Category | Tests |
|----------|-------|
| Overview Endpoint | 10 tests |
| Requests Endpoint | 8 tests |
| Tokens Endpoint | 7 tests |
| Trends Endpoint | 11 tests |
| Query Parameters | 9 tests |
| Rate Limiting | 1 test |
| HTTP Methods | 8 tests |
| Security | 5 tests |
| Error Handling | 3 tests |
| Performance | 2 tests |
| Edge Cases | 6 tests |
| Data Types | 5 tests |
| Integration | 3 tests |

**Expected Status:** Tests return 404 (routes don't exist) - this is correct TDD behavior.

**Implementation Notes for Developer:**
1. Create controller at `app/Http/Controllers/Api/V1/Admin/UsageController.php`
2. Add routes in `routes/api.php` under `api/v1/admin/usage/*`
3. Inject `UsageAggregationService` into controller
4. Add middleware: `auth:sanctum`, `admin`, `throttle:60,1`
5. Support query params: `start_date`, `end_date`, `granularity`, `days`

**Service Methods Available:**
- `UsageAggregationService::getStats()` → overview data
- `UsageAggregationService::getRequestsByEndpoint()` → endpoint counts
- `UsageAggregationService::getTokenTrends(int $days)` → time series

**Response Format:**
```json
{
  "success": true,
  "data": { ... }
}
```

**Error Format:**
```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human readable message"
  }
}
```

### 2026-03-03 - ProjectManager
**Prioritization Complete**

- **Priority: High** (upgraded from Medium) - Admin Dashboard MVP foundation API
- **Risk: Medium** (confirmed) - Standard Laravel API controller, delegates to services

**Dependency Analysis:**
| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-27-2 (AdminAuthorizationMiddleware) | Done ✅ | Middleware ready for admin route protection |
| TASK-27-7 (UsageAggregationService) | To Write Tests | Service IMPLEMENTED (8187 bytes), tests need schema fixes |

**Service Methods Available (UsageAggregationService):**
- `getStats()` → returns `{requests, tokens, analyses}`
- `getRequestsByEndpoint()` → returns endpoint counts
- `getTokenTrends(int $days = 7)` → returns time series

**TDD Approach:**
- PreTester can design tests NOW using mocked UsageAggregationService interface
- Tests should cover all 4 endpoints with date range parameters
- Mock AdminAuthorizationMiddleware for non-admin denial tests

**Endpoints to Create:**
1. `GET /api/v1/admin/usage/overview` → UsageAggregationService::getStats()
2. `GET /api/v1/admin/usage/requests` → UsageAggregationService::getRequestsByEndpoint()
3. `GET /api/v1/admin/usage/tokens` → UsageAggregationService::getTokenStats()
4. `GET /api/v1/admin/usage/trends` → UsageAggregationService::getTokenTrends()

**Priority Upgrade Reasoning:**
1. Admin Dashboard MVP - high visibility feature
2. Dependencies essentially satisfied (service exists, middleware done)
3. Foundation for frontend dashboard components (TASK-27-15, 27-16)
4. Low-medium risk implementation (read-only endpoints, delegates to services)

**Security Considerations:**
- All endpoints protected by AdminAuthorizationMiddleware (TASK-27-2 Done)
- Rate limiting: 60 requests/minute recommended
- Query parameter validation: start_date, end_date, granularity

**Handoff to PreTester:**
- Design tests with mocked UsageAggregationService
- Cover all 4 endpoints + query parameters
- Test admin-only access enforcement
- Test rate limiting behavior

---
*Created by SpecSystemAnalyst from SPEC-027*
