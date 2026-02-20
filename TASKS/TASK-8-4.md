# TASK-8-4: Implement Project Middleware

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Implement the `SetProjectContext` middleware to inject the current project's context into the application at runtime. This ensures all subsequent queries and operations are scoped to the active project.

## Middleware Implementation
```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Project;

class SetProjectContext
{
    public function handle(Request $request, Closure $next)
    {
        $projectId = $request->header('X-Project-ID') 
                     ?? session('current_project_id');

        if ($projectId) {
            $project = Project::where('user_id', auth()->id())
                              ->where('id', $projectId)
                              ->first();
            
            if ($project) {
                app()->instance('current_project', $project);
            }
        }

        return $next($request);
    }
}
```

## Acceptance Criteria
- [ ] Middleware can extract the `X-Project-ID` header or session value.
- [ ] Valid projects are fetched and bound to the application context (`app('current_project')`).
- [ ] Tests validate middleware behavior (valid/invalid project IDs, access control).
- [ ] Middleware integrated into project-scoped routes.

## Dependencies
- TASK-8-1: Requires `projects` table for valid project checks.
- TASK-8-2: Modifications to related tables.

## Effort Estimate
3-5 hours.

---

## Comments
### [2026-02-20 17:44] Agent: SpecSystemAnalyst
Middleware task to inject project context for scoping all operations and ensuring proper validation.