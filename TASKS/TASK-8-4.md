# TASK-8-4: Implement Project Middleware

## STATUS
`To Write Tests`

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

### [2026-02-26 18:55] Agent: ProjectManager
**Prioritization Decision:**
- **Priority: High** (confirmed) - Foundation infrastructure for multi-tenant project scoping
- **Risk: Medium** (confirmed) - Authorization/access control implications

**Dependency Analysis:**
- **Depends on:** TASK-8-1 (Create Projects Table) - status `To Write Tests`
- **Depends on:** TASK-8-2 (Modify Existing Tables) - status `To Write Tests`
- **Blocks:** TASK-8-5 (Global Scope), TASK-8-7 (CRUD Endpoints), TASK-8-8 (Frontend Components)

**Why High Priority:**
1. This middleware is the **backbone** of the multi-site feature - all API calls and queries depend on project context injection
2. Without it, no project-scoped data isolation is possible
3. Security-critical: validates that users can only access their own projects
4. Required before any frontend work (TASK-8-8) can function

**Execution Order within FEAT-008:**
1. TASK-8-1 → Projects table (foundation) ✓ Prioritized
2. TASK-8-2 → Modify tables ✓ Prioritized
3. TASK-8-3 → Data migration ← Next to prioritize
4. TASK-8-4 → Middleware (this task) ← After TASK-8-1 and TASK-8-2 complete
5. TASK-8-5 → Global Scope
6. TASK-8-6 → Policies
7. TASK-8-7 → CRUD Endpoints
8. TASK-8-8 → Frontend
9. TASK-8-9 → Unit Tests
10. TASK-8-10 → Integration Tests

**Risk Mitigation Notes:**
- Middleware must validate `project_id` belongs to authenticated user
- Must handle missing/invalid project IDs gracefully
- Should support both header (`X-Project-ID`) and session-based context
- Tests must cover: valid project, invalid project, no project context, cross-user project attempt