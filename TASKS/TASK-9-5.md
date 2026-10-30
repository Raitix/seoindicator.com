# TASK-9-5: Annotation Policies

## STATUS
`Tests Coverage Complete`

## PRIORITY
`High`

## RISK
`Low`

## Description
Implement authorization policies to ensure that users can only access annotations associated with projects they own.

## Policy Methods
1. **view**: Ensure a user can view annotations only for their projects.
2. **create**: Allow annotation creation only if the user owns the associated project.
3. **update**: Permit editing of annotations for owned projects only.
4. **delete**: Restrict deletion to annotations within the user’s projects.

## Code Example
```php
class AnnotationPolicy
{
    public function view(User $user, Annotation $annotation): bool
    {
        return $user->id === $annotation->keyword->project->user_id;
    }

    public function create(User $user, Keyword $keyword): bool
    {
        return $user->id === $keyword->project->user_id;
    }

    public function update(User $user, Annotation $annotation): bool
    {
        return $user->id === $annotation->keyword->project->user_id;
    }

    public function delete(User $user, Annotation $annotation): bool
    {
        return $user->id === $annotation->keyword->project->user_id;
    }
}
```

## Acceptance Criteria
- [ ] Policies for `view`, `create`, `update`, and `delete` actions are implemented.
- [ ] Laravel Gate enforces policies on relevant endpoints.
- [ ] Test cases ensure strict enforcement of authorization.

## Dependencies
- TASK-9-3: Annotation model must define relationships.

## Effort Estimate
1-2 hours.

---

## Comments
### [2026-02-27 12:02] Agent: PreTester
**Test Coverage Complete:**
- Created `TESTS/TEST-9-5.md` with 56 test scenarios
- Created `backend/tests/Unit/Policies/AnnotationPolicyTest.php` - all 56 tests pass ✅
- Policy already exists at `backend/app/Policies/AnnotationPolicy.php`

**Test Categories:**
- Method Existence: 5 tests
- viewAny Authorization: 6 tests
- view Authorization: 8 tests
- create Authorization: 6 tests
- update Authorization: 7 tests
- delete Authorization: 7 tests
- Edge Cases: 8 tests
- Security: 5 tests
- Integration: 4 tests

**Key Findings:**
1. **project_id is NOT NULL** - Schema requires project_id
2. **Project membership gap** - Current policy checks `$user->projects()` (owned) NOT `$user->memberProjects()` (membership)
3. Tests document current behavior correctly

**Ownership Supported:**
- User owns project → ✅ Access
- User owns keyword → ✅ Access
- User owns site → ✅ Access (via keyword.site chain)
- Project member → ❌ Denied (implementation gap)

**Status:** Ready for Developer. Policy already implemented.

### [2026-02-26 20:08] Agent: ProjectManager
**Priority Assessment:** Confirmed `High` - Authorization policies are critical for data security. Users must only access annotations for their own projects. This is a foundation task affecting all annotation operations (view, create, update, delete).

**Risk Assessment:** Confirmed `Low` - Standard Laravel Policy implementation with straightforward user-project-annotation relationship checks. No external dependencies.

**Dependency Analysis:**
- TASK-9-3 (Annotation Model): Status `To Write Tests` - ahead in pipeline ✅
- Model must define `keyword` and `project` relationships for policy logic to work
- Policy methods rely on: `$annotation->keyword->project->user_id` chain

**Execution Order Notes:**
- This task should be developed after TASK-9-3 (Model) is implemented
- Controller (TASK-9-4) will apply these policies via Laravel Gates
- Testing should validate all 4 policy methods with various user/project scenarios

**Status changed:** `To Prioritize` → `To Write Tests`

### [2026-02-20 17:52] Agent: SpecSystemAnalyst
Authorization task created to enforce permission rules for keyword annotations.