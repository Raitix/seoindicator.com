# TASK-11-3: Implement Team Management Service

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Develop the `TeamService` class to manage backend logic for team collaboration features, including adding/removing members, role updates, and validating invitations.

## Responsibilities
1. **Add Member**
   - Invite a user to join the team.
   - Generate and validate secure token invitations.
   - Default role: `viewer`.

2. **Update Role**
   - Owner/Admin can assign or modify member roles.

3. **Remove Member**
   - Remove user from the team.
   - Validate role restrictions (e.g., Owner cannot remove self).

## Code Example
```php
class TeamService
{
    public function addMember(array $data): TeamMember
    {
        // Validate invitation details
        // Generate token and send email invitation
    }

    public function updateRole(int $teamMemberId, string $role): bool
    {
        // Check user permissions
        // Update role in `team_members`
    }

    public function removeMember(int $teamMemberId): bool
    {
        // Validate restrictions (cannot remove Owner unless reassigned)
        // Remove from `team_members`
    }
}
```

## Acceptance Criteria
- [ ] Add, update, and remove member functionality implemented.
- [ ] Supports validation for secure token invitations.
- [ ] Enforces role-based restrictions.
- [ ] Unit and feature tests cover all methods.

## Dependencies
- TASK-11-1: Team members table must exist.

## Effort Estimate
4-6 hours.

---

## Comments
### [2026-02-20 17:31] Agent: SpecSystemAnalyst
Task for implementing core team collaboration features in backend service.

### [2026-02-26 18:13] Agent: ProjectManager
**Prioritization Complete**

- **Priority:** `High` (confirmed) — Core service layer for team collaboration MVP
- **Risk:** `Medium` (confirmed) — Business logic with role-based restrictions; non-destructive operations

**Dependency Note:**
- TASK-11-1 (team_members table) must be deployed before this task can be developed
- TASK-11-1 is currently `Prioritization In Progress` — will complete first in pipeline flow

**Handoff:** Ready for PreTester to design tests. Tests can be written independently of dependency.

### [2026-02-26 19:15] Agent: PreTester
**Test Documentation Complete**

Created `TESTS/TEST-11-3.md` with 78 test scenarios:
- **Add Member (21 scenarios):** Token generation, validation, permissions, security
- **Update Role (18 scenarios):** Permission checks, owner protection, last admin rule
- **Remove Member (16 scenarios):** Self-removal prevention, cascade behavior
- **Token Validation (9 scenarios):** Expiry, timing attacks, brute force protection
- **Permission Checks (6 scenarios):** Role-based access control

**Security Focus:**
- SQL injection tests (AM-15)
- XSS validation tests (AM-16)
- Token brute force rate limiting (TV-08)
- Timing attack prevention (TV-09)
- Permission escalation prevention throughout

**Coverage Target:** 95%

**Handoff:** Ready for Developer implementation.