# TASK-11-4: Develop Role Assignment Validation

## STATUS
`To Test Locally`

## PRIORITY
`Medium`

## RISK
`Medium`

## Description
Create the `PermissionValidator` to ensure role-based access permissions are upheld. Validate operational boundaries (e.g., Admins cannot modify Owners).

## Responsibilities
1. **Role Validation**
   - Validate role transition logic (e.g., Viewer → Admin).
   - Restrict certain actions (e.g., no self-demotion of Owner).

2. **Scope Permissions**
   - Ensure users perform actions within their assigned scope (global vs project).

## Code Example
```php
class PermissionValidator
{
    public function canUpdateRole(User $actor, User $target, string $newRole): bool
    {
        // Prevent Owners from changing their own role
        // Restrict Admin actions on equal/higher roles
    }

    public function canRemoveMember(User $actor, User $target): bool
    {
        // Block self-removal or changes to Owner role
    }
}
```

## Acceptance Criteria
- [x] Validation methods ensure role modifications follow strict logic.
- [x] Unit and integration tests verify enforcement.
- [x] Separate rules for global teams and project-specific scopes.

## Dependencies
- TASK-11-2: Project members table must exist.

## Effort Estimate
3-4 hours.

## Implementation
**Files Created:**
- `backend/app/Services/PermissionValidator.php` - Main validation service
- `backend/tests/Unit/Services/PermissionValidatorTest.php` - 55 unit tests

**Key Methods:**
- `canUpdateRole()` - Validates role change permissions
- `canRemoveMember()` - Validates member removal permissions
- `hasPermission()` - Checks action permissions
- `canInviteWithRole()` - Validates invitation permissions
- `getMaxAssignableRole()` - Returns max role a user can assign

**Role Hierarchy:** owner (100) > admin (75) > editor (50) > viewer (25)

---

## Comments
### [2026-02-21 20:45] Agent: Developer - Implementation Complete
Created PermissionValidator service with comprehensive validation logic:
- Role hierarchy enforcement (owner > admin > editor > viewer)
- Self-modification restrictions (owners cannot change own role)
- Scope validation (team vs project roles)
- Member removal permissions
- Invitation permissions
- 55 unit tests all passing

Note: Pre-existing test failures in other test files are unrelated to this implementation (SQLite transaction issues).

---

## Comments
### [2026-02-20 18:32] ProjectManager: Prioritization Complete
- Priority: Medium (correct - validation layer after foundation tasks)
- Dependencies: TASK-11-2 must complete first
- Execution order: After TASK-11-1, 11-2, and 11-3
- Status: To Develop

### [2026-02-20 17:31] Agent: SpecSystemAnalyst
Created task for validating role change and access permissions in team management.