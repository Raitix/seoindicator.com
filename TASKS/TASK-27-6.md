# TASK-27-6: Request Tracking Middleware

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Low`

## Parent Specification
SPEC-027: Admin Dashboard (Ops + Usage)

## Description
Create middleware to track API requests for usage analytics. Records request counts by endpoint, method, and timestamp for aggregation.

## Acceptance Criteria
- [ ] Middleware class created at `app/Http/Middleware/RequestTrackingMiddleware.php`
- [ ] Records: endpoint path, HTTP method, timestamp, user_id (if authenticated)
- [ ] Uses lightweight storage (Redis or database queue)
- [ ] Does not significantly impact request latency (< 5ms overhead)
- [ ] Configurable sample rate for high-traffic endpoints
- [ ] Handles queue failures gracefully
- [ ] Unit tests verify tracking behavior
- [ ] Performance tests confirm minimal overhead

## Dependencies
- None (standalone infrastructure)

## Effort Estimate
`Medium` (2-4 hours)

## Technical Notes
- Consider using async queue for write operations
- Use Redis INCR for atomic counters
- Aggregate to database periodically (scheduled job)
- Exclude health check endpoints from tracking

## Data Storage
Option 1: Redis with TTL-based keys
Option 2: Database table with hourly aggregation

## Comments

### 2026-03-04 - PreTester
**Test Design Complete:**
- Created 155 tests (97 middleware + 58 service)
- Test files:
  - `backend/tests/Unit/Http/Middleware/RequestTrackingMiddlewareTest.php` (97 tests)
  - `backend/tests/Unit/Services/Admin/RequestTrackingServiceTest.php` (58 tests)
- Test documentation: `TESTS/TEST-27-6.md`

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| Middleware Instantiation | 5 | Service injection, sample rate config |
| Basic Tracking | 7 | GET/POST/PUT/DELETE/PATCH, pass-through, timestamp |
| Authenticated Users | 4 | User ID tracking, null handling, admin |
| Endpoint Normalization | 6 | ID/UUID normalization, query params, deep paths |
| Sample Rate | 6 | Per-endpoint rates, health exclusions |
| Error Handling | 5 | Redis/queue failures, graceful degradation |
| Performance | 4 | <5ms overhead, memory stability |
| Edge Cases | 8 | Unicode, concurrent, special chars |
| Service Integration | 5 | Mocked service calls |
| Configuration | 5 | Config values, enable/disable |
| Security | 5 | No sensitive data, SQL injection safe |
| Integration | 4 | Full flow, pipeline, exceptions |
| Acceptance Criteria | 8 | All ACs verified |

**Key Patterns for Developer:**
- Endpoint normalization: `/api/v1/users/123` → `api/v1/users/{id}`
- UUID normalization: `550e8400-e29b-41d4-a716-446655440000` → `{id}`
- Health endpoints excluded by default: `/health`, `/healthz`, `/ping`
- Sample rate: 0.0-1.0, configurable per endpoint
- Never throw exceptions: log warnings and continue

**Implementation Files Required:**
1. `app/Http/Middleware/RequestTrackingMiddleware.php`
2. `app/Services/Admin/RequestTrackingService.php`
3. `config/request-tracking.php`

**Status:** Ready for Developer implementation

### 2026-03-03 - ProjectManager
**Priority Assessment:**
- **Upgraded to HIGH** - Foundation task for SPEC-027 usage analytics pipeline
- Creates middleware that captures request data for downstream aggregation
- Enables TASK-27-7 (Usage Aggregation Service) which enables TASK-27-8 (Usage API)

**Dependency Analysis:**
- No dependencies - standalone infrastructure task
- Blocks: TASK-27-7, TASK-27-8 (both depend on request tracking data)

**Risk Assessment:**
- Risk: LOW (confirmed) - Standard Laravel middleware implementation
- No external APIs or security-critical logic
- Performance requirement (< 5ms overhead) is standard for middleware

**Execution Order:**
- This task should complete BEFORE TASK-27-7 and TASK-27-8
- Can proceed immediately to PreTester - no blockers

**TDD Ready:** PreTester can design tests with mocked request/response objects

---
*Created by SpecSystemAnalyst from SPEC-027*
