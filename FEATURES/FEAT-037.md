# FEAT-037: Production Critical Fixes - Auth & Navigation

## STATUS
`To Break Into Tasks`

## PRIORITY
`Critical`

## RISK
`High`

## Description
Emergency production fixes for authentication flow and navigation issues discovered during CEO production audit on 2026-03-13. Multiple critical paths are broken including login, registration, and dashboard access.

## Business Impact
- Users CANNOT register new accounts
- Users CANNOT login to existing accounts
- Dashboard is inaccessible even for authenticated users
- Core user acquisition funnel is completely broken
- Revenue impact: HIGH - no new users can onboard

## Issues Identified

### CRITICAL (Blocking all users)
1. **/login/ page broken** - Shows "Failed to load dashboard data" instead of login form
2. **/register/ page broken** - Shows "Failed to load dashboard data" instead of registration form
3. **/dashboard/ inaccessible** - Shows "Failed to load dashboard data" even for authenticated users

### HIGH (Blocking navigation)
4. **"Sign In" button not clickable** - Button element has no action/href
5. **"Indicators" button not clickable** - Button element has no action/href

### MEDIUM (UX issues)
6. **Navigation hover states broken** - White background makes white text unreadable
7. **Missing pointer cursor** - Nav buttons don't show pointer on hover
8. **Protected routes visible to guests** - Dashboard, Competitors, Comparison shown to non-authenticated users

### LOW (Expected behavior)
9. **401 on /api/v1/auth/user** - Expected when no valid token present

## Root Cause Hypothesis
The "Failed to load dashboard data" error on login/register pages suggests:
1. Auth context is trying to fetch dashboard data before authentication
2. Protected route middleware redirecting authenticated pages incorrectly
3. Login/Register pages may be inside a protected route wrapper

## Acceptance Criteria
- [ ] /login/ shows login form with email/password and Google OAuth
- [ ] /register/ shows registration form with email/password and Google OAuth
- [ ] "Sign In" button navigates to /login/ or shows login modal
- [ ] "Indicators" button navigates to /indicators/ or /dashboard/#indicators
- [ ] Dashboard loads successfully for authenticated users
- [ ] Nav buttons show pointer cursor on hover
- [ ] Nav buttons have visible hover state (not white-on-white)
- [ ] Protected routes hidden from non-authenticated users OR redirect properly
- [ ] Back button works after redirect from protected route
- [ ] All authentication flows tested with automated tests
- [ ] Production validation passes before deployment

## Technical Investigation Needed
1. Check if /login and /register are wrapped in protected route component
2. Verify AuthContext doesn't fetch dashboard data on public pages
3. Check button components for missing onClick/href handlers
4. Review CSS for hover states on navigation buttons
5. Implement conditional rendering for protected nav items

## Related Files
- `frontend/app/(auth)/login/page.tsx`
- `frontend/app/(auth)/register/page.tsx`
- `frontend/app/(dashboard)/dashboard/page.tsx`
- `frontend/components/layout/Header.tsx` (navigation)
- `frontend/lib/contexts/AuthContext.tsx`
- `frontend/middleware.ts`

## Dependencies
- None (emergency fix)

## Effort Estimate
**Medium** (4-8 hours) - Multiple component fixes required

## Notes
- SEO Guides (18 guides) are working correctly
- API authentication works when valid token present
- Local development environment needs separate investigation

## Comments

### Architect (2026-03-13 16:54 UTC)
Specification completed. Technical architecture defined for:
- **Group A: Authentication Fixes (5 tasks)** - Critical priority, High risk
- **Group B: Dashboard Data Loading (3 tasks)** - Critical priority, Medium risk
- **Group C: Navigation Fixes (3 tasks)** - High priority, Low risk
- **Group D: Functional Fixes (2 tasks)** - Medium priority, Medium risk
- **Group E: Verification & Documentation (4 tasks)** - Medium priority, Low risk

**Risk Assessment:** High (authentication system changes)
**SecurityAuditor:** Review required before deployment
**Handoff:** Ready for SpecSystemAnalyst to break into task files
