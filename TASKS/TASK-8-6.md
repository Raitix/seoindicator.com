# TASK-8-6: Implement Project Policies

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Implement authorization policies to control project access. These policies ensure that each user can only view, edit, or delete their own projects and associated data.

## Policy Implementation
```php
class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    public function update(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }
}
```

## Acceptance Criteria
- [ ] Policies implemented for `view`, `update`, `delete`, `restore` actions.
- [ ] Test cases verify policy enforcement.
- [ ] Integrated with Laravel Gate for API and UI endpoints.
- [ ] Policies extendable for future roles (e.g., admin-level permissions).

## Dependencies
- TASK-8-1: Projects table defines the data structure.

## Effort Estimate
2-3 hours.

---

## Comments
### [2026-02-20 17:44] Agent: SpecSystemAnalyst
Authorization policy task created to ensure secure access to project data. Policies enforce user ownership of projects.

### [2026-02-26 19:04] Agent: ProjectManager
**Prioritization Decision:**
- **Priority: High** (confirmed) - Security-critical task for FEAT-008 Multi-Site Project Management
- **Risk: Medium** (confirmed) - Authorization changes require careful testing

**Dependency Analysis:**
- **Depends on:** TASK-8-1 (Create Projects Table) - status `To Write Tests`
- **Blocks:** TASK-8-7 (CRUD Endpoints) - API layer needs policies in place
- **Parallel with:** TASK-8-5 (Global Scope) - Both enforce data isolation

**Why High Priority:**
1. **Security-critical:** Policies are the last line of defense for data isolation
2. **Authorization enforcement:** Prevents users from accessing other users' projects
3. **Required before API:** CRUD endpoints (TASK-8-7) need policies for authorization
4. **Extensibility:** Policies designed for future role-based access (admin, collaborator)

**Risk Assessment:**
- Risk lowered to Medium because policies are additive (don't break existing functionality)
- Laravel Gate integration is well-tested pattern
- Test coverage will verify policy enforcement

**Execution Order within FEAT-008:**
1. TASK-8-1 → Projects table (foundation) ✓ Prioritized
2. TASK-8-2 → Modify tables ✓ Prioritized
3. TASK-8-3 → Data migration ← `To Prioritize`
4. TASK-8-4 → Middleware ✓ Prioritized
5. TASK-8-5 → Global Scope ✓ Prioritized
6. TASK-8-6 → Policies (this task) ← **Prioritized now**
7. TASK-8-7 → CRUD Endpoints ← Depends on this task
8. TASK-8-8 → Frontend
9. TASK-8-9 → Unit Tests
10. TASK-8-10 → Integration Tests

**Test Coverage Requirements:**
- Verify user can view their own project
- Verify user cannot view other users' projects
- Verify update/delete permissions follow same rules
- Test policy integration with Laravel Gate

**Status changed:** `To Prioritize` → `To Write Tests`