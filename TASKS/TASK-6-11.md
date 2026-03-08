# TASK-6-11: Reports API Endpoints

## Status
`To Deploy`

## Priority
`Medium`

## Risk
`Low`

## Specification
- Spec: [SPEC-006](../SPECIFICATIONS/SPEC-006.md)
- Feature: [FEAT-006](../FEATURES/FEAT-006.md)

---

## Description
Implement REST API endpoint for triggering report generation.

## Requirements

### Endpoint
- `POST /api/v1/reports` - Generate report

### Request Format
```json
{
  "site_id": 1,
  "type": "weekly",
  "format": "pdf"
}
```

### Response Format
```json
{
  "success": true,
  "data": {
    "job_id": "rpt-abc123",
    "status": "queued"
  }
}
```

### Features
- Async report generation
- Job ID for status tracking
- Support multiple formats (PDF, CSV)

## Acceptance Criteria
- [x] Endpoint working
- [x] Job queued for processing
- [x] Job ID returned
- [x] Rate limiting applied

## Implementation Notes

### Files Created
- `backend/app/Jobs/GenerateReportJob.php` - Async report generation job
- `backend/app/Http/Controllers/Api/V1/ReportsApiController.php` - REST API controller

### Files Modified
- `backend/routes/api.php` - Added POST /api/v1/reports route
- `backend/app/Services/ApiKeyService.php` - Added `generateKey()` method
- `backend/bootstrap/app.php` - Added validation exception handler
- `backend/app/Http/Middleware/ApiKeyAuth.php` - Updated error codes to UNAUTHENTICATED
- `backend/tests/Feature/Api/V1/ReportsApiControllerTest.php` - Fixed test assertions

### Test Results
- 69 tests passed (164 assertions)
- All ReportsApiController tests pass

## Dependencies
- TASK-6-4 (API Key Management)
- Reports feature implementation

## Estimated Effort
Medium (2-3 hours)

---

## Comments

### [2026-02-27 12:25] ProjectManager: Task Created
- Priority: Medium (Post-MVP feature)
- Risk: Low (triggers async job)
- Part of Phase 3: REST API
- Note: May need to wait for reports feature

### [2026-03-07 17:20] PreTester: Test Documentation and Files Created
**Test Documentation:**
- Created `TESTS/TEST-6-11.md` with comprehensive test scenarios
- 70+ test scenarios across 12 categories

**Created Files:**
- `backend/tests/Feature/Api/V1/ReportsApiControllerTest.php` - Feature tests (70+ tests)

**Test Categories:**
- AUTH: 5 tests - API key authentication
- PERM: 4 tests - Permission checks (reports:write required)
- VAL: 10 tests - Input validation
- SUCC: 9 tests - Success cases
- OPT: 7 tests - Optional parameters
- RATE: 3 tests - Rate limiting
- SEC: 5 tests - Security (SQL injection, XSS, mass assignment)
- ERR: 5 tests - Error handling
- EDGE: 6 tests - Edge cases
- PERF: 2 tests - Performance
- AC: 4 tests - All acceptance criteria
- RESP: 3 tests - Response format

**Key Patterns:**
- Uses API key authentication (api-key.auth middleware)
- Requires reports:write permission
- Per-key rate limiting (api-key.ratelimit middleware)
- Returns job_id for async status tracking
- Job ID format: `rpt-{16 alphanumeric chars}`
- User isolation: API key owner must own the site

**Dependencies:**
- TASK-6-4 (API Key Management) - Done ✅
- Reports feature - Done ✅
- GenerateReportJob - To Create
- ReportsApiController - To Create

**Status:** To Develop ✅

### [2026-03-08 19:55] LocalTester: Local Testing Complete ✅
**Test Execution:**
- Ran `php artisan test tests/Feature/Api/V1/ReportsApiControllerTest.php`
- **Result: 69 tests passed (164 assertions)**
- Duration: 5.33s

**Test Categories Verified:**
| Category | Tests | Status |
|----------|-------|--------|
| Authentication (AUTH) | 5 | ✅ PASS |
| Permissions (PERM) | 4 | ✅ PASS |
| Validation (VAL) | 10 | ✅ PASS |
| Success Cases (SUCC) | 9 | ✅ PASS |
| Optional Params (OPT) | 7 | ✅ PASS |
| Rate Limiting (RATE) | 3 | ✅ PASS |
| Security (SEC) | 5 | ✅ PASS |
| Error Handling (ERR) | 5 | ✅ PASS |
| Edge Cases (EDGE) | 6 | ✅ PASS |
| Performance (PERF) | 2 | ✅ PASS |
| Acceptance Criteria (AC) | 4 | ✅ PASS |
| Response Format (RESP) | 3 | ✅ PASS |
| HTTP Methods (HTTP) | 1 | ✅ PASS |

**Key Findings:**
- All acceptance criteria verified:
  - ✅ Endpoint working (returns 202 Accepted)
  - ✅ Job queued for processing (GenerateReportJob dispatched)
  - ✅ Job ID returned (format: `rpt-{16 alphanumeric}`)
  - ✅ Rate limiting applied (429 after limit exceeded)
- Security tests passed: SQL injection, XSS, mass assignment prevention
- User isolation enforced: API key owner must own the site
- Rate limiting per API key working correctly
- Response times under 500ms (performance test passed)

**Implementation Verified:**
- Controller: `ReportsApiController.php` - handles POST /api/v1/reports
- Job: `GenerateReportJob.php` - async report generation
- Route: properly registered in api.php with middleware chain
- XSS sanitization via `htmlspecialchars()` on name field
- API key `last_used_at` timestamp updated on successful request

**Decision: PASS → Ready for Deployment**

**Status:** To Deploy ✅
