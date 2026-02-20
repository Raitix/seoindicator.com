# TASK-11-4: Develop Role Assignment Validation

## STATUS
`To Develop`

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
- [ ] Validation methods ensure role modifications follow strict logic.
- [ ] Unit and integration tests verify enforcement.
- [ ] Separate rules for global teams and project-specific scopes.

## Dependencies
- TASK-11-2: Project members table must exist.

## Effort Estimate
3-4 hours.

---

## Comments
### [2026-02-20 18:32] ProjectManager: Prioritization Complete
- Priority: Medium (correct - validation layer after foundation tasks)
- Dependencies: TASK-11-2 must complete first
- Execution order: After TASK-11-1, 11-2, and 11-3
- Status: To Develop

### [2026-02-20 17:31] Agent: SpecSystemAnalyst
Created task for validating role change and access permissions in team management.