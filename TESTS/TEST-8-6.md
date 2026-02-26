# TEST-8-6: Project Policies Tests

## Task Reference
- **Task:** [TASK-8-6](../TASKS/TASK-8-6.md)
- **Feature:** FEAT-008 Multi-Site Project Management
- **Specification:** SPEC-008

---

## Overview

Tests for the `ProjectPolicy` authorization class. This policy enforces project ownership, ensuring users can only view, edit, delete, and restore their own projects. Critical for multi-tenant data isolation and security.

---

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `backend/tests/Unit/Policies/ProjectPolicyTest.php` | Unit | Policy method logic verification |
| `backend/tests/Feature/Authorization/ProjectAuthorizationTest.php` | Feature | Gate integration and endpoint authorization |
| `backend/tests/Feature/Api/V1/ProjectControllerAuthorizationTest.php` | Feature | API endpoint authorization enforcement |

---

## Test Scenarios

### Policy Method Tests (PM)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PM-001 | ProjectPolicy class exists | `app/Policies/ProjectPolicy.php` | File exists |
| PM-002 | Policy has view method | Inspect class methods | `view(User, Project): bool` exists |
| PM-003 | Policy has update method | Inspect class methods | `update(User, Project): bool` exists |
| PM-004 | Policy has delete method | Inspect class methods | `delete(User, Project): bool` exists |
| PM-005 | Policy has restore method | Inspect class methods | `restore(User, Project): bool` exists |
| PM-006 | Policy has viewAny method | Inspect class methods | `viewAny(User): bool` exists |
| PM-007 | Policy has create method | Inspect class methods | `create(User): bool` exists |
| PM-008 | view returns true for owner | User ID matches project user_id | `true` |
| PM-009 | view returns false for non-owner | User ID differs from project user_id | `false` |
| PM-010 | update returns true for owner | User ID matches project user_id | `true` |
| PM-011 | update returns false for non-owner | User ID differs from project user_id | `false` |
| PM-012 | delete returns true for owner | User ID matches project user_id | `true` |
| PM-013 | delete returns false for non-owner | User ID differs from project user_id | `false` |
| PM-014 | restore returns true for owner | User ID matches project user_id | `true` |
| PM-015 | restore returns false for non-owner | User ID differs from project user_id | `false` |
| PM-016 | viewAny returns true for authenticated user | Any authenticated user | `true` |
| PM-017 | create returns true for authenticated user | Any authenticated user | `true` |

---

### Gate Integration Tests (GI)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GI-001 | Gate::allows('view', $project) for owner | User owns project | `true` |
| GI-002 | Gate::denies('view', $project) for non-owner | User doesn't own project | `true` |
| GI-003 | Gate::allows('update', $project) for owner | User owns project | `true` |
| GI-004 | Gate::denies('update', $project) for non-owner | User doesn't own project | `true` |
| GI-005 | Gate::allows('delete', $project) for owner | User owns project | `true` |
| GI-006 | Gate::denies('delete', $project) for non-owner | User doesn't own project | `true` |
| GI-007 | Gate::allows('restore', $project) for owner | User owns project | `true` |
| GI-008 | Gate::denies('restore', $project) for non-owner | User doesn't own project | `true` |
| GI-009 | Gate::check returns false for unauthenticated | No user context | `false` |
| GI-010 | Policy registered in AuthServiceProvider | Inspect providers | ProjectPolicy registered for Project model |

---

### API Endpoint Authorization Tests (EA)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EA-001 | Unauthenticated GET /projects returns 401 | No auth token | 401 Unauthorized |
| EA-002 | Unauthenticated POST /projects returns 401 | No auth token | 401 Unauthorized |
| EA-003 | Unauthenticated GET /projects/{id} returns 401 | No auth token | 401 Unauthorized |
| EA-004 | Unauthenticated PUT /projects/{id} returns 401 | No auth token | 401 Unauthorized |
| EA-005 | Unauthenticated DELETE /projects/{id} returns 401 | No auth token | 401 Unauthorized |
| EA-006 | Owner can GET their project | Auth + owner | 200 OK with project data |
| EA-007 | Owner can PUT their project | Auth + owner | 200 OK |
| EA-008 | Owner can DELETE their project | Auth + owner | 204 No Content |
| EA-009 | Non-owner GET other user's project | Auth + non-owner | 403 Forbidden |
| EA-010 | Non-owner PUT other user's project | Auth + non-owner | 403 Forbidden |
| EA-011 | Non-owner DELETE other user's project | Auth + non-owner | 403 Forbidden |
| EA-012 | Non-existing project GET returns 404 | Auth + non-existent ID | 404 Not Found |
| EA-013 | Non-existing project PUT returns 404 | Auth + non-existent ID | 404 Not Found |
| EA-014 | Non-existing project DELETE returns 404 | Auth + non-existent ID | 404 Not Found |

---

### Archived Project Tests (AP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AP-001 | Owner can view archived project | Auth + owner + archived project | 200 OK |
| AP-002 | Owner can update archived project | Auth + owner + archived project | 200 OK |
| AP-003 | Owner can delete archived project | Auth + owner + archived project | 204 No Content |
| AP-004 | Owner can restore archived project | POST /projects/{id}/restore | 200 OK |
| AP-005 | Non-owner cannot view archived project | Auth + non-owner + archived | 403 Forbidden |
| AP-006 | Non-owner cannot restore archived project | Auth + non-owner + archived | 403 Forbidden |
| AP-007 | Archived projects excluded from index by default | GET /projects | archived_at IS NULL filter applied |
| AP-008 | Archived projects included with filter | GET /projects?archived=true | Archived projects returned |

---

### Security Tests (ST)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ST-001 | IDOR: Cannot access project by guessing ID | Try sequential IDs | Only own projects accessible |
| ST-002 | IDOR: Cannot modify project by guessing ID | PUT with sequential IDs | 403 for other users' projects |
| ST-003 | IDOR: Cannot delete project by guessing ID | DELETE with sequential IDs | 403 for other users' projects |
| ST-004 | Mass assignment does not bypass policy | Set user_id in request body | Ignored, policy still enforces |
| ST-005 | Authorization header manipulation | Modified JWT/token | Token validation fails, 401 |
| ST-006 | Session fixation attack prevention | Old session + new login | New session issued |
| ST-007 | CSRF token validation for stateful routes | Invalid CSRF token | 419 CSRF token mismatch |
| ST-008 | Rate limiting on auth endpoints | Multiple failed auth attempts | Rate limited after threshold |
| ST-009 | SQL injection in project ID | `1 OR 1=1` as project ID | Treated as string, 404 returned |
| ST-010 | SQL injection in query params | `'; DROP TABLE projects;--` | Sanitized, no injection |
| ST-011 | Race condition on project delete | Concurrent delete requests | One succeeds, others fail gracefully |
| ST-012 | Authorization after user role change | User role changed mid-session | Re-evaluated on next request |
| ST-013 | Project ownership transfer scenario | Project user_id changed | New owner has access, old doesn't |

---

### Controller Integration Tests (CI)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CI-001 | ProjectController uses ProjectPolicy | Inspect controller | `authorizeResource(Project::class)` or manual `authorize()` calls |
| CI-002 | Index returns only user's projects | GET /projects | Only projects where user_id = auth user |
| CI-003 | Store creates project with auth user | POST /projects | project.user_id = auth user ID |
| CI-004 | Show authorizes before returning | GET /projects/{id} | Policy checked, 403 if fails |
| CI-005 | Update authorizes before modifying | PUT /projects/{id} | Policy checked, 403 if fails |
| CI-006 | Destroy authorizes before deleting | DELETE /projects/{id} | Policy checked, 403 if fails |
| CI-007 | Restore endpoint authorizes | POST /projects/{id}/restore | Policy checked, 403 if fails |
| CI-008 | Policy exception returns proper response | Authorization fails | JSON error with message |
| CI-009 | Policy passes through for super admin (future) | Admin role | Policy allows all (extensibility) |

---

### Edge Case Tests (EC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Project with null user_id | Query project with user_id = NULL | Policy returns false (no access) |
| EC-002 | User with no projects | User has 0 projects | Empty array returned |
| EC-003 | User deleted, projects cascade deleted | User deleted | Projects gone, no orphan data |
| EC-004 | Project ID is zero or negative | GET /projects/0 or /projects/-1 | 404 Not Found |
| EC-005 | Project ID is non-numeric | GET /projects/abc | 404 Not Found |
| EC-006 | Very large project ID | GET /projects/999999999999 | 404 Not Found |
| EC-007 | Concurrent project creation | Two requests create same project name | Both created (name not unique) |
| EC-008 | Authorization with soft-deleted user | User soft-deleted but token valid | Depends on auth implementation |
| EC-009 | Policy called with wrong model type | Pass non-Project to policy | TypeError or false |
| EC-010 | Empty request body on update | PUT /projects/{id} with {} | Policy passes, validation may fail |

---

### Role Extensibility Tests (RE)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RE-001 | Policy can be extended for roles | Inspect policy structure | Extendable via before() hook |
| RE-002 | Admin role can bypass ownership check | Admin user + any project | Policy returns true |
| RE-003 | before() hook in policy | Policy has before() method | Returns true for admin, null otherwise |
| RE-004 | Collaborator role for project access | Collaborator user + shared project | Policy returns true (future feature) |
| RE-005 | Role-based access control integration | Check role in policy | Role column checked if exists |
| RE-006 | Multiple roles per user | User has multiple roles | Highest privilege wins |
| RE-007 | Role change reflected immediately | User role updated | Policy reflects new role |

---

### Middleware Integration Tests (MI)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MI-001 | Project middleware checks ownership | Access project via middleware | Middleware enforces ownership |
| MI-002 | Middleware returns 403 for non-owner | Non-owner accesses project | 403 Forbidden |
| MI-003 | Middleware integrates with policy | Middleware uses policy | Policy called for authorization |
| MI-004 | Global scope filters by project ownership | Query projects | Only user's projects returned |
| MI-005 | X-Project-ID header authorization | Request with X-Project-ID | Project ownership verified |
| MI-006 | Project context set after authorization | Authorized project access | Project context available |
| MI-007 | Middleware clears project context | Response sent | Context cleared to prevent leak |

---

## Mock Data

### Sample Users for Testing
```php
// Owner user
User::factory()->create([
    'id' => 1,
    'name' => 'Project Owner',
    'email' => 'owner@example.com',
]);

// Non-owner user
User::factory()->create([
    'id' => 2,
    'name' => 'Other User',
    'email' => 'other@example.com',
]);

// Admin user (future role)
User::factory()->create([
    'id' => 3,
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'role' => 'admin',
]);
```

### Sample Projects
```php
// Active project owned by user 1
Project::create([
    'id' => 1,
    'user_id' => 1,
    'name' => 'Main Website',
    'domain' => 'https://example.com',
    'is_default' => true,
]);

// Archived project owned by user 1
Project::create([
    'id' => 2,
    'user_id' => 1,
    'name' => 'Old Blog',
    'domain' => 'https://old.example.com',
    'archived_at' => '2026-01-15 10:00:00',
]);

// Project owned by user 2
Project::create([
    'id' => 3,
    'user_id' => 2,
    'name' => "Other User's Project",
    'domain' => 'https://other.example.com',
]);

// Project with null user_id (edge case)
Project::create([
    'id' => 4,
    'user_id' => null,
    'name' => 'Orphan Project',
]);
```

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Policy Methods | 100% | PM-001 to PM-017 (17 tests) |
| Gate Integration | 100% | GI-001 to GI-010 (10 tests) |
| API Endpoint Authorization | 100% | EA-001 to EA-014 (14 tests) |
| Archived Project Handling | 100% | AP-001 to AP-008 (8 tests) |
| Security (IDOR/Injection) | 100% | ST-001 to ST-013 (13 tests) |
| Controller Integration | 100% | CI-001 to CI-009 (9 tests) |
| Edge Cases | 100% | EC-001 to EC-010 (10 tests) |
| Role Extensibility | 100% | RE-001 to RE-007 (7 tests) |
| Middleware Integration | 100% | MI-001 to MI-007 (7 tests) |

**Total: 95 test scenarios**

---

## Test Execution

```bash
# Run all project policy tests
php artisan test --filter=ProjectPolicy

# Run policy unit tests
php artisan test tests/Unit/Policies/ProjectPolicyTest.php

# Run authorization feature tests
php artisan test tests/Feature/Authorization/ProjectAuthorizationTest.php

# Run API authorization tests
php artisan test tests/Feature/Api/V1/ProjectControllerAuthorizationTest.php

# Run with coverage
php artisan test --coverage --filter=Project
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| Users table | ✅ Exists | Pre-existing authentication system |
| User model | ✅ Exists | Pre-existing |
| Projects table | 🔄 TASK-8-1 | Required for Project model |
| Project model | 🔄 TASK-8-1 | Required for policy |
| AuthServiceProvider | ✅ Exists | Must register ProjectPolicy |

---

## Notes for Developer

### Implementation Checklist
- [ ] Create `backend/app/Policies/ProjectPolicy.php`
- [ ] Add `view`, `update`, `delete`, `restore` methods
- [ ] Add `before()` hook for admin role support
- [ ] Register ProjectPolicy in `AuthServiceProvider.php`
- [ ] Add `authorizeResource(Project::class)` to ProjectController
- [ ] Or manually call `$this->authorize('view', $project)` in each method

### Policy Implementation Template
```php
<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Perform pre-authorization checks.
     * Allows admins to bypass all checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role === 'admin') {
            return true;
        }
        return null;
    }

    /**
     * Determine if the user can view any projects.
     */
    public function viewAny(User $user): bool
    {
        return true; // Any authenticated user
    }

    /**
     * Determine if the user can create projects.
     */
    public function create(User $user): bool
    {
        return true; // Any authenticated user
    }

    /**
     * Determine if the user can view the project.
     */
    public function view(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    /**
     * Determine if the user can update the project.
     */
    public function update(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    /**
     * Determine if the user can delete the project.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    /**
     * Determine if the user can restore the project.
     */
    public function restore(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }
}
```

### AuthServiceProvider Registration
```php
protected $policies = [
    Project::class => ProjectPolicy::class,
];
```

### Controller Usage
```php
// Option 1: Resource authorization (recommended)
class ProjectController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Project::class, 'project');
    }
}

// Option 2: Manual authorization
public function show(Project $project)
{
    $this->authorize('view', $project);
    return $project;
}
```

### Critical Security Considerations
1. **IDOR Prevention:** Every endpoint must check project ownership
2. **Default Deny:** Policy should return `false` by default, `true` only for owners
3. **No Bypass:** Never skip policy check "for performance" or "internal use"
4. **Audit Trail:** Log all authorization failures for security monitoring
5. **Rate Limiting:** Protect against enumeration attacks on project IDs

---

## Comments

### [2026-02-26 20:22] Agent: PreTester
**Test Documentation Created:**
- 95 test scenarios covering policy methods, gate integration, API authorization, security, and extensibility
- Strong focus on IDOR prevention (ST-001 to ST-013) - critical for multi-tenant security
- Role extensibility tests (RE-001 to RE-007) ensure policy supports future admin/collaborator features
- Middleware integration tests verify project context enforcement

**Key Testing Priorities:**
1. ST-001 to ST-003: IDOR prevention (most critical security requirement)
2. GI-001 to GI-010: Gate integration (Laravel authorization core)
3. RE-002 to RE-003: Admin role bypass (extensibility for future roles)
4. AP-004: Restore authorization (often overlooked)

**Status:** `Tests Coverage In Progress` → Ready for Developer implementation
