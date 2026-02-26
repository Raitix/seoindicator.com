# TEST-8-4: SetProjectContext Middleware Tests

## Task Reference
- **Task:** [TASK-8-4](../TASKS/TASK-8-4.md)
- **Feature:** FEAT-008 Multi-Site Project Management
- **Specification:** SPEC-008

---

## Overview

Tests for the `SetProjectContext` middleware that injects the current project's context into the application at runtime. This middleware is the backbone of multi-tenant project scoping, ensuring all subsequent queries and operations are scoped to the active project.

---

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `backend/tests/Unit/Http/Middleware/SetProjectContextTest.php` | Unit | Middleware logic tests |
| `backend/tests/Feature/Http/Middleware/SetProjectContextIntegrationTest.php` | Integration | Full request flow tests |
| `backend/tests/Feature/Http/Middleware/ProjectContextSecurityTest.php` | Security | Security and authorization tests |

---

## Test Scenarios

### Header Extraction Tests (HE)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| HE-001 | Valid X-Project-ID header | Header: `X-Project-ID: 1` | Project bound to `app('current_project')` |
| HE-002 | Header takes precedence over session | Header: `X-Project-ID: 2`, Session: `current_project_id=1` | Project ID 2 bound (header wins) |
| HE-003 | Header with numeric string | Header: `X-Project-ID: "123"` | Project ID 123 bound |
| HE-004 | Header with whitespace | Header: `X-Project-ID: "  5  "` | Trimmed, Project ID 5 bound |
| HE-005 | Empty header value | Header: `X-Project-ID: ""` | No project bound, continues without error |
| HE-006 | Header with zero value | Header: `X-Project-ID: 0` | No project bound (invalid ID) |
| HE-007 | Header with negative value | Header: `X-Project-ID: -1` | No project bound (invalid ID) |
| HE-008 | Header with non-numeric value | Header: `X-Project-ID: "abc"` | No project bound, continues gracefully |
| HE-009 | Header with float value | Header: `X-Project-ID: 1.5` | Project ID 1 bound (truncated) or rejected |
| HE-010 | Header with very large number | Header: `X-Project-ID: 999999999999999999` | Handled gracefully (no overflow) |

---

### Session Extraction Tests (SE)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SE-001 | Valid session project ID | Session: `current_project_id=1` | Project bound to `app('current_project')` |
| SE-002 | Session without header | Session only, no X-Project-ID header | Project from session bound |
| SE-003 | Session with null value | Session: `current_project_id=null` | No project bound |
| SE-004 | Session with zero value | Session: `current_project_id=0` | No project bound (invalid ID) |
| SE-005 | Session with negative value | Session: `current_project_id=-1` | No project bound (invalid ID) |
| SE-006 | Session with non-numeric value | Session: `current_project_id="abc"` | No project bound, continues gracefully |
| SE-007 | Session with empty string | Session: `current_project_id=""` | No project bound |
| SE-008 | Session not set | No session value | No project bound, continues |
| SE-009 | Session persists across requests | Set session, make another request | Session value still used if no header |
| SE-010 | Session cleared | Session: `current_project_id=null` after being set | No project bound |

---

### Project Binding Tests (PB)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PB-001 | Valid project bound to app | Valid project ID for authenticated user | `app('current_project')` returns Project model |
| PB-002 | Project instance is correct type | Project bound | `app('current_project')` is `Project` class |
| PB-003 | Project attributes accessible | Project bound | Can access `$project->name`, `$project->id`, etc. |
| PB-004 | Project with all fields | Project with domain, notes, is_default | All attributes accessible |
| PB-005 | Project replaced on subsequent request | Request 1: Project 1, Request 2: Project 2 | New project bound on each request |
| PB-006 | No project bound when ID invalid | Invalid project ID | `app('current_project')` returns null or not set |
| PB-007 | No project bound when not found | Non-existent project ID | `app('current_project')` returns null |
| PB-008 | Project instance is fresh | Bound project | New query executed (not stale from cache) |

---

### Authorization Tests (AU)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AU-001 | User owns the project | Project where `user_id = auth()->id()` | Project bound successfully |
| AU-002 | User does not own project | Project where `user_id != auth()->id()` | Project NOT bound (authorization check) |
| AU-003 | Cross-user project access attempt | User 1 tries to access User 2's project | Project NOT bound |
| AU-004 | Project belongs to deleted user | Project with non-existent user_id | Not bound (FK constraint or auth check) |
| AU-005 | Anonymous user (unauthenticated) | No auth, project ID provided | No project bound, middleware passes through |
| AU-006 | Admin accessing user's project | Admin user accessing any project | Depends on policy (document behavior) |
| AU-007 | User with multiple projects | User owns projects 1, 2, 3 | Can bind to any of their own projects |
| AU-008 | Project ownership verified per request | Switch users, same project ID | Authorization re-checked |

---

### Archived Project Handling Tests (AR)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AR-001 | Active project | `archived_at = null` | Project bound successfully |
| AR-002 | Archived project | `archived_at` set to past date | Decision needed: bind or reject? |
| AR-003 | Archived project accessed via header | Archived project ID in header | Decision needed: bind or reject? |
| AR-004 | Archived project accessed via session | Archived project ID in session | Decision needed: bind or reject? |
| AR-005 | Mixed active and archived | User has both types | Active projects bind, archived handled per policy |

---

### Edge Cases Tests (EC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Both header and session empty | No X-Project-ID, no session | No project bound, request continues |
| EC-002 | Header with special characters | Header: `X-Project-ID: "1'; DROP TABLE projects; --"` | Sanitized, no SQL injection |
| EC-003 | Header with JSON | Header: `X-Project-ID: {"id":1}` | Not parsed as JSON, no project bound |
| EC-004 | Header with array-like string | Header: `X-Project-ID: [1]` | Not parsed, no project bound |
| EC-005 | Multiple X-Project-ID headers | Two headers with different values | First or last value used (document behavior) |
| EC-006 | Very long header value | Header: `X-Project-ID: 12345678901234567890...` (1000 chars) | Handled gracefully, no overflow |
| EC-007 | Unicode in header value | Header: `X-Project-ID: "１２３"` (fullwidth numbers) | Not parsed as numeric, no project bound |
| EC-008 | Header with leading zeros | Header: `X-Project-ID: "00001"` | Parsed as 1, project bound |
| EC-009 | Session with array | Session: `current_project_id = [1]` | Not parsed as array, no project bound |
| EC-010 | Concurrent requests with different projects | Request A: Project 1, Request B: Project 2 | Each request has correct project bound |
| EC-011 | Request without middleware | Route without middleware | No project context available |
| EC-012 | Middleware applied to non-project route | Route that doesn't need project context | Middleware passes through without error |
| EC-013 | Project ID from cookie | Cookie with project ID | Not read from cookie (security) |
| EC-014 | Project ID from query parameter | `?project_id=1` in URL | Not read from query (document if supported) |
| EC-015 | Request to API with project context | API request with X-Project-ID | Project bound for API responses |

---

### Integration Tests (IT)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IT-001 | Middleware registered in kernel | Check Kernel.php | Middleware listed in appropriate group |
| IT-002 | Middleware applied to routes | Check routes | Project-scoped routes use middleware |
| IT-003 | Controller can access project | `app('current_project')` in controller | Returns correct Project model |
| IT-004 | Model query scoped by project | Query with project context | Results filtered by project_id |
| IT-005 | Full request flow with project | Complete HTTP request | Project context available throughout |
| IT-006 | Project switch mid-session | Change project, make new request | New project context applied |
| IT-007 | API returns project-scoped data | GET /api/keywords with X-Project-ID | Only keywords for that project |
| IT-008 | Missing project context returns all | Request without project context | All data returned (or error if required) |
| IT-009 | Project context in service classes | Service using `app('current_project')` | Correct project available |
| IT-010 | Project context in view composers | View composer accessing project | Correct project data in view |

---

### Error Handling Tests (EH)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EH-001 | Database connection lost | DB down during project lookup | Graceful failure, no crash |
| EH-002 | Project model not found | Project ID doesn't exist | No project bound, request continues |
| EH-003 | Invalid project ID type | Non-integer project ID | No project bound, no exception |
| EH-004 | Middleware throws exception | Unexpected error in middleware | Exception caught, logged, request continues or 500 |
| EH-005 | Session read error | Session unavailable | Header used if available, no crash |
| EH-006 | Auth not available | `auth()` returns null/guest | No project bound, request continues |

---

### Performance Tests (PF)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PF-001 | Middleware execution time | Normal request | <5ms overhead |
| PF-002 | Project lookup cached | Multiple requests same project | Query cached or efficient |
| PF-003 | No N+1 queries | Project with relationships | Single query for project |
| PF-004 | Memory usage | Project binding | Minimal memory footprint |
| PF-005 | Concurrent requests | 100 simultaneous requests | No race conditions, each has correct project |

---

### Adversarial Security Tests (AS)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AS-001 | SQL injection via header | `X-Project-ID: 1 OR 1=1` | No injection, safe handling |
| AS-002 | SQL injection via session | Session with malicious value | No injection, safe handling |
| AS-003 | IDOR attack | User A's project ID in User B's session | Project NOT bound (authorization) |
| AS-004 | Session hijacking attempt | Stolen session with different project ID | Authorization check prevents access |
| AS-005 | Header injection | Multiple headers, malformed values | Sanitized, no injection |
| AS-006 | CSRF with project switch | POST request with different project ID | CSRF token validation (if applicable) |
| AS-007 | Race condition exploitation | Concurrent project switch attempts | Atomic operations, no data leak |
| AS-008 | Memory dump with project data | App crash with bound project | Project data not exposed in logs |
| AS-009 | Timing attack on project lookup | Measure response times | No timing information leak |
| AS-010 | Mass assignment via session | Session with extra fields | Only project_id used, no mass assignment |

---

## Mock Data

### Sample Users
```php
User::create([
    'id' => 1,
    'name' => 'User One',
    'email' => 'user1@example.com',
    'password' => bcrypt('password'),
]);

User::create([
    'id' => 2,
    'name' => 'User Two',
    'email' => 'user2@example.com',
    'password' => bcrypt('password'),
]);
```

### Sample Projects
```php
// User 1's active project
Project::create([
    'id' => 1,
    'user_id' => 1,
    'name' => 'User 1 Project',
    'domain' => 'https://user1.example.com',
    'is_default' => true,
    'archived_at' => null,
]);

// User 1's second project
Project::create([
    'id' => 2,
    'user_id' => 1,
    'name' => 'User 1 Second Project',
    'domain' => 'https://user1-second.example.com',
    'is_default' => false,
    'archived_at' => null,
]);

// User 2's project
Project::create([
    'id' => 3,
    'user_id' => 2,
    'name' => 'User 2 Project',
    'domain' => 'https://user2.example.com',
    'is_default' => true,
    'archived_at' => null,
]);

// User 1's archived project
Project::create([
    'id' => 4,
    'user_id' => 1,
    'name' => 'Archived Project',
    'domain' => 'https://archived.example.com',
    'is_default' => false,
    'archived_at' => '2026-01-15 10:00:00',
]);
```

### Test Request Setup
```php
// Request with header
$request = Request::create('/api/keywords', 'GET');
$request->headers->set('X-Project-ID', '1');

// Request with session
$request = Request::create('/api/keywords', 'GET');
$request->session()->put('current_project_id', 1);

// Authenticated request
$user = User::find(1);
$this->actingAs($user);
```

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Header Extraction | 100% | HE-001 to HE-010 (10 tests) |
| Session Extraction | 100% | SE-001 to SE-010 (10 tests) |
| Project Binding | 100% | PB-001 to PB-008 (8 tests) |
| Authorization | 100% | AU-001 to AU-008 (8 tests) |
| Archived Projects | 100% | AR-001 to AR-005 (5 tests) |
| Edge Cases | 100% | EC-001 to EC-015 (15 tests) |
| Integration | 100% | IT-001 to IT-010 (10 tests) |
| Error Handling | 100% | EH-001 to EH-006 (6 tests) |
| Performance | 100% | PF-001 to PF-005 (5 tests) |
| Adversarial Security | 100% | AS-001 to AS-010 (10 tests) |

**Total: 87 test scenarios**

---

## Test Execution

```bash
# Run all SetProjectContext middleware tests
php artisan test --filter=SetProjectContext

# Run unit tests only
php artisan test tests/Unit/Http/Middleware/SetProjectContextTest.php

# Run integration tests
php artisan test tests/Feature/Http/Middleware/SetProjectContextIntegrationTest.php

# Run security tests
php artisan test tests/Feature/Http/Middleware/ProjectContextSecurityTest.php

# Run with coverage
php artisan test --coverage --filter=SetProjectContext
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| Projects table (TASK-8-1) | 🔄 Tests Written | Required for project lookup |
| User authentication | ✅ Exists | Required for user context |
| Session handling | ✅ Exists | Required for session-based project ID |
| Laravel middleware system | ✅ Exists | Required for middleware implementation |

---

## Notes for Developer

### Implementation Checklist
- [ ] Create middleware at `backend/app/Http/Middleware/SetProjectContext.php`
- [ ] Register middleware in `app/Http/Kernel.php`
- [ ] Add middleware to route groups or specific routes
- [ ] Update controllers to use `app('current_project')`
- [ ] Add helper function or facade for easy access (optional)

### Middleware Implementation Template
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Project;
use Symfony\Component\HttpFoundation\Response;

class SetProjectContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only process if user is authenticated
        if (!Auth::check()) {
            return $next($request);
        }

        // Get project ID from header (priority) or session
        $projectId = $this->getProjectId($request);

        if ($projectId !== null && $projectId > 0) {
            $this->bindProject($projectId);
        }

        return $next($request);
    }

    /**
     * Extract project ID from request (header takes precedence).
     */
    private function getProjectId(Request $request): ?int
    {
        // Check header first
        $headerValue = $request->header('X-Project-ID');
        if ($headerValue !== null && $headerValue !== '') {
            $id = $this->sanitizeProjectId($headerValue);
            if ($id !== null) {
                return $id;
            }
        }

        // Fall back to session
        $sessionValue = $request->session()->get('current_project_id');
        if ($sessionValue !== null) {
            return $this->sanitizeProjectId($sessionValue);
        }

        return null;
    }

    /**
     * Sanitize and validate project ID.
     */
    private function sanitizeProjectId(mixed $value): ?int
    {
        // Handle string with whitespace
        if (is_string($value)) {
            $value = trim($value);
        }

        // Convert to integer
        $id = filter_var($value, FILTER_VALIDATE_INT);

        // Must be positive integer
        return ($id !== false && $id > 0) ? (int) $id : null;
    }

    /**
     * Bind project to application container if user owns it.
     */
    private function bindProject(int $projectId): void
    {
        $project = Project::where('id', $projectId)
            ->where('user_id', Auth::id())
            ->first();

        if ($project) {
            app()->instance('current_project', $project);
        }
    }
}
```

### Kernel Registration
```php
// In app/Http/Kernel.php

protected $middlewareGroups = [
    'web' => [
        // ...
        \App\Http\Middleware\SetProjectContext::class,
    ],

    'api' => [
        // ...
        \App\Http\Middleware\SetProjectContext::class,
    ],
];
```

### Helper Function (Optional)
```php
// In a helper file or service provider

if (!function_exists('current_project')) {
    function current_project(): ?Project
    {
        return app('current_project');
    }
}
```

### Security Considerations

1. **Always verify ownership:** The middleware MUST verify `user_id === auth()->id()` before binding
2. **Sanitize input:** Project ID must be validated as positive integer
3. **No error on invalid:** Invalid IDs should silently skip binding (fail open for UX)
4. **Header precedence:** Header should override session (explicit request wins)

### Business Rule Decisions Needed

1. **Archived projects:** Should archived projects be accessible? (Recommend: YES, with warning)
2. **Missing project context:** What happens when no project is selected? (Recommend: Show all or show default)
3. **API vs Web:** Different behavior for API vs web requests? (Recommend: Consistent behavior)

---

## Comments

### [2026-02-26 20:05] Agent: PreTester
**Test Documentation Created:**
- 87 test scenarios covering middleware logic, authorization, security, and integration
- Focus on authorization (AU-001 to AU-008) - critical for multi-tenant security
- Adversarial security tests (AS-001 to AS-010) prevent IDOR, SQL injection, race conditions
- Edge cases cover malformed input, concurrent requests, performance

**Critical Tests:**
- AU-002/AU-003: Cross-user project access prevention (security critical)
- AS-001/AS-002: SQL injection via header/session (security critical)
- HE-002: Header precedence over session (behavioral requirement)
- IT-004: Model query scoping (integration critical)

**Dependency Note:**
- Requires TASK-8-1 (Projects table) to be completed first
- Middleware tests will mock Project model until table exists

**Status:** `Tests Coverage In Progress` → Ready for Developer implementation
