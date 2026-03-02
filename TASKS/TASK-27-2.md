# TASK-27-2: Admin Authorization Middleware

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`High`

## Parent Specification
SPEC-027: Admin Dashboard (Ops + Usage)

## Description
Implement AdminAuthorizationMiddleware to protect all admin routes. The middleware checks if the authenticated user has admin role before allowing access to admin endpoints.

## Acceptance Criteria
- [ ] Middleware class created at `app/Http/Middleware/AdminAuthorizationMiddleware.php`
- [ ] Middleware checks `isAdmin()` method on authenticated user
- [ ] Returns 403 Forbidden JSON response for non-admin users
- [ ] Returns 401 Unauthorized JSON response for unauthenticated requests
- [ ] Logs unauthorized access attempts to admin_activity_logs
- [ ] Middleware registered in Kernel.php as 'admin'
- [ ] Route group uses middleware in api.php
- [ ] Feature tests cover:
  - Admin user access granted
  - Regular user access denied (403)
  - Unauthenticated request denied (401)
  - Access attempt logged

## Dependencies
- TASK-27-1 (admin_activity_logs table for logging)

## Effort Estimate
`Small` (1-2 hours)

## Technical Notes
- Use Laravel middleware pattern
- Consider role hierarchy: super_admin > admin > moderator
- Log IP address and user agent for audit trail
- Rate limiting should be applied at route level

## Security Considerations
- Critical security component - thorough testing required
- All admin routes must use this middleware
- Consider adding 2FA requirement for admin actions in future

## Comments

### 2026-03-02 - ProjectManager
**Prioritization Complete**

- **Priority: High** (confirmed) - Security-critical component, blocks all admin routes
- **Risk: High** (confirmed) - Authentication/authorization layer, requires thorough security testing
- **Dependencies:** TASK-27-1 (admin_activity_logs table) - Currently `Testing Locally`
- **Execution Order:** Phase 1, Task 2 of 19 (after TASK-27-1 foundation)
- **Security Review Required:** Yes - SecurityAuditor must review before deployment

**Technical Assessment:**
- Middleware checks `isAdmin()` on authenticated user
- Returns 403 Forbidden for non-admin, 401 Unauthorized for unauthenticated
- Logs unauthorized attempts to admin_activity_logs (requires TASK-27-1)
- Role hierarchy consideration: super_admin > admin > moderator
- Rate limiting at route level recommended

**Risk Mitigation:**
- SecurityAuditor review mandatory before `To Deploy`
- Feature tests must cover all access scenarios
- Audit logging must be verified in tests
- Consider IP-based rate limiting for admin endpoints

**Blockers:** None - TASK-27-1 is in progress (Testing Locally), should complete soon

**Handoff:** Ready for PreTester to design security-focused tests. Focus on:
- Admin access granted
- Non-admin denied (403)
- Unauthenticated denied (401)
- Logging verification
- Edge cases (session expiry, role changes)

### 2026-03-02 - PreTester
**Test Design Complete**

**Test Files Created:**
- `TESTS/TASK-27-2.md` - Test documentation with 51+ scenarios
- `backend/tests/Feature/Http/Middleware/AdminAuthorizationMiddlewareTest.php` - 55 feature tests

**Test Coverage:**
- **AA-001 to AA-002:** Admin/Super Admin access granted (5 tests)
- **AA-003:** Regular user denied 403 Forbidden (5 tests)
- **AA-004:** Unauthenticated denied 401 Unauthorized (5 tests)
- **AA-005:** Access attempt logging to file (5 tests)
- **AA-006:** admin_activity_logs table integration (5 tests - skip if table missing)
- **AA-007:** Response format validation (3 tests)
- **AA-008:** HTTP method handling (4 tests)
- **AA-009:** Role hierarchy (3 tests)
- **SC-001:** Security tests - SQL injection, XSS, IDOR (5 tests)
- **EC-001:** Edge cases - unicode roles, null roles (6 tests)
- **EC-002:** Concurrent sessions (2 tests)

**Key Test Patterns:**
- Uses existing UserFactory `admin()` and `superAdmin()` states
- Tests skip DB logging if `admin_activity_logs` table doesn't exist
- Validates response format: `{success, error:{code, message}}`
- Security tests cover SQL injection, XSS, IDOR prevention
- Role hierarchy: super_admin > admin > user
- Tests both Sanctum token and session authentication

**Response Format Expected:**
- 401: `{"success":false,"error":{"code":"UNAUTHENTICATED","message":"..."}}`
- 403: `{"success":false,"error":{"code":"FORBIDDEN","message":"..."}}`

**Handoff:** Ready for Developer to implement middleware. Run tests with:
```bash
cd backend
php artisan test --filter=AdminAuthorizationMiddlewareTest
```

---
*Created by SpecSystemAnalyst from SPEC-027*
