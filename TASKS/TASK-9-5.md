# TASK-9-5: Annotation Policies

## STATUS
`To Prioritize`

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
### [2026-02-20 17:52] Agent: SpecSystemAnalyst
Authorization task created to enforce permission rules for keyword annotations.