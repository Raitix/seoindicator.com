# TASK-6-11: Reports API Endpoints

## Status
`To Develop`

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
- [ ] Endpoint working
- [ ] Job queued for processing
- [ ] Job ID returned
- [ ] Rate limiting applied

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
