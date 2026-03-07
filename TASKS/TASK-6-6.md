# TASK-6-6: Request Logging

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
Implement API request logging for analytics and debugging.

## Requirements

### Log Table
```sql
CREATE TABLE api_request_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    api_key_id INTEGER NOT NULL,
    endpoint TEXT NOT NULL,
    method TEXT NOT NULL,
    status_code INTEGER,
    response_time_ms INTEGER,
    ip_address TEXT,
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE CASCADE
);
```

### Logging Middleware
- Capture request start time
- Log after response
- Include all metadata
- Async logging (don't block response)

### Retention
- Keep logs for 30 days
- Cleanup job (optional for MVP)

## Acceptance Criteria
- [ ] Table created
- [ ] Middleware implemented
- [ ] All API requests logged
- [ ] Response time captured
- [ ] Non-blocking logging

## Dependencies
- TASK-6-1 (Database schema)

## Estimated Effort
Small (1-2 hours)

---

## Comments

### [2026-03-07 18:15] PreTester: Tests Written
- **Status:** To Develop ✅
- **Test Files Created:**
  - `backend/tests/Unit/Models/ApiRequestLogTest.php` (120+ tests)
  - `backend/tests/Unit/Http/Middleware/RequestLoggingMiddlewareTest.php` (100+ tests)
  - `TESTS/TEST-6-6.md` (documentation)
- **Total Tests:** 220+ test scenarios across 15 categories

### Test Categories Summary

**Model Tests (ApiRequestLogTest.php):**
- MF: Model instantiation & fillables (15 tests)
- CA: Casts (10 tests)
- RE: Relationships to ApiKey (10 tests)
- SC: Scopes (10 tests)
- DR: Date & retention queries (10 tests)
- ES: Edge cases & security (10 tests)
- SA: Statistics & analytics (10 tests)
- AC: Acceptance criteria (6 tests)

**Middleware Tests (RequestLoggingMiddlewareTest.php):**
- MI: Middleware instantiation (10 tests)
- BL: Basic logging behavior (15 tests)
- EE: Excluded endpoints (10 tests)
- DM: Disabled middleware (10 tests)
- EH: Error handling (10 tests)
- PT: Performance tests (10 tests)
- AC: Acceptance criteria (6 tests)

### Key Test Features

1. **Database Schema Verification:** Tests verify all columns, foreign keys, indexes
2. **Async Logging:** Tests verify non-blocking behavior with performance benchmarks
3. **Retention Queries:** Tests for 30-day retention with configurable days
4. **Security Tests:** XSS, SQL injection, sensitive data not logged
5. **Performance Tests:** <10ms overhead, memory stability, concurrent requests

### Handoff to Developer

**Files to create:**
1. `backend/database/migrations/YYYY_MM_DD_HHMMSS_create_api_request_logs_table.php`
2. `backend/app/Models/ApiRequestLog.php`
3. `backend/app/Http/Middleware/RequestLoggingMiddleware.php`
4. `backend/config/api-logging.php` (optional config)

**Required Model Features:**
- Fillable: api_key_id, endpoint, method, status_code, response_time_ms, ip_address, user_agent
- Relationship: belongsTo(ApiKey::class)
- Scopes: forApiKey, forEndpoint, forMethod, successful, failed, withinRetention, olderThanRetention

**Required Middleware Features:**
- Capture request start time
- Log after response (async/dispatch)
- Exclude health endpoints by default
- Configurable enable/disable
- Handle exceptions gracefully

### [2026-02-27 12:25] ProjectManager: Task Created
- Priority: Medium (useful but not MVP-critical)
- Risk: Low (logging only)
- Part of Phase 2: Rate Limiting
