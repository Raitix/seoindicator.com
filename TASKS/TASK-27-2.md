# TASK-27-2: Admin Authorization Middleware

## STATUS
`To Write Tests`

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

---
*Created by SpecSystemAnalyst from SPEC-027*
