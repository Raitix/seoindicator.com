# TASK-27-2: Admin Authorization Middleware

## STATUS
`To Prioritize`

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

---
*Created by SpecSystemAnalyst from SPEC-027*
