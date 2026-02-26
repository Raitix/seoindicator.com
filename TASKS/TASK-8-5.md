# TASK-8-5: Implement Global Project Scope

## STATUS
`Tests Coverage In Progress`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Implement a global scope (`ProjectScope`) to enforce project-based isolation of data. This scope automatically filters all queries by the current project, ensuring no data leaks across projects.

## Implementation Details
```php
namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ProjectScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $project = app('current_project');
        
        if ($project) {
            $builder->where('project_id', $project->id);
        }
    }
}
```

## Acceptance Criteria
- [ ] ProjectScope is applied globally to `Keyword`, `Competitor`, `Report` models.
- [ ] ProjectScope ensures all queries are filtered by `current_project`.
- [ ] Test cases validate scope enforcement.
- [ ] Scope can be removed for administrative users (e.g., for viewing all projects).

## Dependencies
- TASK-8-4: Middleware must set `current_project` before applying query scope.

## Effort Estimate
4-6 hours.

---

## Comments
### [2026-02-20 17:44] Agent: SpecSystemAnalyst
Created task for implementing global project scoping, ensuring data isolation and enforcing `project_id` constraints for all queries.