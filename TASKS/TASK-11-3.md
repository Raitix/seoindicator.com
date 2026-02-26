# TASK-11-3: Implement Team Management Service

## STATUS
`To Test Locally`

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
- [x] Add, update, and remove member functionality implemented.
- [x] Supports validation for secure token invitations.
- [x] Enforces role-based restrictions.
- [x] Unit and feature tests cover all methods.

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

### [2026-02-26 19:35] Agent: Developer
**Implementation Complete**

Created infrastructure and TeamService implementation:

**New Files:**
1. `backend/database/migrations/2026_02_26_200000_create_accounts_table.php` - Accounts table for teams
2. `backend/database/migrations/2026_02_26_200100_create_team_members_table.php` - Team members with invitation tracking
3. `backend/database/migrations/2026_02_26_200200_add_account_id_to_projects_table.php` - Link projects to accounts
4. `backend/app/Models/Account.php` - Account model with owner relationship
5. `backend/app/Models/TeamMember.php` - TeamMember model with role management
6. `backend/app/Services/TeamService.php` - Core team management service
7. `backend/database/factories/AccountFactory.php` - Test factory
8. `backend/database/factories/TeamMemberFactory.php` - Test factory with role states
9. `backend/tests/Unit/Services/TeamServiceTest.php` - 56 unit tests

**Updated Files:**
- `backend/app/Models/User.php` - Added account relationships
- `backend/app/Models/Project.php` - Added account_id relationship

**TeamService Features:**
- `addMember()` - Invite users with secure tokens (64 chars), role validation, permission checks
- `updateRole()` - Role changes with owner/admin permission enforcement
- `removeMember()` - Member removal with cascade to project_members
- `validateInvitationToken()` - Token validation with expiry checking
- `acceptInvitation()` - Accept pending invitations
- `cancelInvitation()` - Cancel pending invitations
- `canManageTeam()` - Permission helper

**Security Measures:**
- Token uniqueness and cryptographic security
- Timing-safe token comparison (hash_equals)
- Role hierarchy enforcement via PermissionValidator
- Self-modification prevention
- Last admin protection
- Cascade cleanup on member removal

**Test Coverage:** 56 tests, 101 assertions - covering all major scenarios from TEST-11-3.md

**Handoff:** Ready for LocalTester to run integration tests and verify migrations.