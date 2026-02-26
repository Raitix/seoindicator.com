# TEST-8-7: CRUD Endpoints for Projects Tests

## Task Reference
- **Task:** [TASK-8-7](../TASKS/TASK-8-7.md)
- **Feature:** FEAT-008 Multi-Site Project Management
- **Specification:** SPEC-008

---

## Overview

Tests for the Project CRUD API endpoints. These endpoints enable users to create, read, update, archive, restore, and delete their projects. Tests cover request validation, authorization enforcement, response formatting, and error handling.

---

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `backend/tests/Feature/Api/V1/ProjectControllerTest.php` | Feature | API endpoint integration tests |
| `backend/tests/Unit/Controllers/ProjectControllerTest.php` | Unit | Controller method unit tests |
| `backend/tests/Unit/Requests/ProjectRequestTest.php` | Unit | Form request validation tests |

---

## Test Scenarios

### Create Project Tests (CP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-001 | Create project with valid data | `POST /api/v1/projects` with name, domain, notes | 201 Created, project returned |
| CP-002 | Create project with minimal data | `POST /api/v1/projects` with only name | 201 Created, defaults applied |
| CP-003 | Create project with all fields | `POST /api/v1/projects` with name, domain, notes | 201 Created, all fields stored |
| CP-004 | Create project without name | `POST /api/v1/projects` with empty body | 422 Unprocessable Entity |
| CP-005 | Create project with invalid domain format | Domain: "not-a-url" | 422 Validation Error |
| CP-006 | Create project with duplicate name | Same name as existing project | 201 Created (duplicate names allowed per user) |
| CP-007 | Create project sets user ownership | Authenticated user creates project | project.user_id = auth user |
| CP-008 | Create project with 255 character name | Name: 255 characters | 201 Created |
| CP-009 | Create project with name exceeding limit | Name: 256 characters | 422 Validation Error |
| CP-010 | Create project with HTML in notes | Notes: `<script>alert(1)</script>` | 201 Created, HTML sanitized/escaped |

---

### Read Project Tests (RP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RP-001 | List all user's projects | `GET /api/v1/projects` | 200 OK, array of projects |
| RP-002 | List projects includes metadata | `GET /api/v1/projects` | Response includes total, active, archived counts |
| RP-003 | List projects pagination | `GET /api/v1/projects?page=2&perPage=10` | Correct pagination metadata |
| RP-004 | Get single project | `GET /api/v1/projects/{id}` | 200 OK, project details |
| RP-005 | Get non-existent project | `GET /api/v1/projects/99999` | 404 Not Found |
| RP-006 | Get project owned by another user | Cross-user project ID | 403 Forbidden |
| RP-007 | Get project includes keywords count | `GET /api/v1/projects/{id}` | Response includes keywords_count |
| RP-010 | List only active projects | `GET /api/v1/projects?archived=false` | Only projects with archived_at=null |
| RP-011 | List only archived projects | `GET /api/v1/projects?archived=true` | Only projects with archived_at set |

---

### Update Project Tests (UP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| UP-001 | Update project name | `PUT /api/v1/projects/{id}` with new name | 200 OK, name updated |
| UP-002 | Update project domain | `PUT /api/v1/projects/{id}` with new domain | 200 OK, domain updated |
| UP-003 | Update project notes | `PUT /api/v1/projects/{id}` with new notes | 200 OK, notes updated |
| UP-004 | Update non-existent project | `PUT /api/v1/projects/99999` | 404 Not Found |
| UP-005 | Update project owned by another user | Cross-user project ID | 403 Forbidden |
| UP-006 | Update with invalid domain | Invalid URL format | 422 Validation Error |
| UP-007 | Partial update | `PUT` with only name field | 200 OK, other fields unchanged |

---

### Archive/Restore Project Tests (AR)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AR-001 | Archive active project | `POST /api/v1/projects/{id}/archive` | 200 OK, archived_at set |
| AR-002 | Archive already archived project | Archive archived project | 200 OK, archived_at unchanged |
| AR-003 | Restore archived project | `POST /api/v1/projects/{id}/restore` | 200 OK, archived_at cleared |
| AR-005 | Archive non-existent project | `POST /api/v1/projects/99999/archive` | 404 Not Found |
| AR-006 | Archive project owned by another user | Cross-user project ID | 403 Forbidden |

---

### Delete Project Tests (DP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DP-001 | Delete project with confirmation | `DELETE /api/v1/projects/{id}?confirm=true` | 200 OK, success message |
| DP-002 | Delete project without confirmation | `DELETE /api/v1/projects/{id}` | 400 Bad Request |
| DP-003 | Delete non-existent project | `DELETE /api/v1/projects/99999?confirm=true` | 404 Not Found |
| DP-004 | Delete project owned by another user | Cross-user project ID | 403 Forbidden |
| DP-005 | Delete project cascades to keywords | Delete project with keywords | All keywords deleted |
| DP-006 | Delete project cascades to competitors | Delete project with competitors | All competitors deleted |
| DP-007 | Delete project cascades to reports | Delete project with reports | All reports deleted |

---

### Security Tests (SEC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SEC-001 | Unauthenticated access to projects | `GET /api/v1/projects` without token | 401 Unauthorized |
| SEC-002 | Unauthenticated create project | `POST /api/v1/projects` without token | 401 Unauthorized |
| SEC-003 | SQL injection in project name | Name: `'; DROP TABLE projects;--` | Stored as string, not executed |
| SEC-004 | Mass assignment protection | Try to set user_id via API | Ignored, project owned by auth user |
| SEC-005 | XSS in project notes | Notes: `<script>alert('xss')</script>` | Sanitized/escaped in output |
| SEC-006 | Access other user's project | GET with other user's project ID | 403 Forbidden |
| SEC-007 | Update other user's project | PUT with other user's project ID | 403 Forbidden |
| SEC-008 | Delete other user's project | DELETE with other user's project ID | 403 Forbidden |
| SEC-009 | Archive other user's project | POST archive with other's project ID | 403 Forbidden |
| SEC-010 | Restore other user's project | POST restore with other's project ID | 403 Forbidden |

---

### Edge Cases & Validation (EC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Empty project name | name: "" | 422 Validation Error |
| EC-002 | Whitespace-only name | name: "   " | 422 Validation Error or trimmed |
| EC-003 | Name exactly at limit | name: 255 characters | 201 Created |
| EC-004 | Name exceeds limit | name: 256 characters | 422 Validation Error |
| EC-005 | Domain with path | domain: "https://example.com/path" | Accepted or normalized |
| EC-006 | Domain with query string | domain: "https://example.com?query=1" | Accepted |
| EC-007 | HTTP vs HTTPS domain | domain: "http://example.com" | Accepted |
| EC-008 | Domain without protocol | domain: "example.com" | Accepted or normalized |
| EC-009 | Very long notes | notes: 64KB text | Accepted or appropriate error |
| EC-010 | Unicode in project name | name: "Проект 日本語 🚀" | Stored and retrieved correctly |
| EC-011 | Special characters in name | name: "<>&\"'test" | Stored safely |
| EC-012 | Null domain | domain: null | Accepted |
| EC-013 | Null notes | notes: null | Accepted |
| EC-014 | Empty notes | notes: "" | Accepted |
| EC-015 | Boolean is_default handling | is_default: true | Handled correctly |

---

### Response Format Tests (RF)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RF-001 | Create response format | POST success | `{ success: true, data: { id, name, ... } }` |
| RF-002 | List response format | GET success | `{ success: true, data: [...], meta: {...} }` |
| RF-003 | Get response format | GET single success | `{ success: true, data: { id, name, ... } }` |
| RF-004 | Update response format | PUT success | `{ success: true, data: { id, name, ... } }` |
| RF-005 | Delete response format | DELETE success | `{ success: true, message: "..." }` |
| RF-006 | Error response format | Any error | `{ success: false, message: "..." }` |
| RF-007 | Validation error format | 422 response | `{ message: "...", errors: { field: [...] } }` |
| RF-008 | Timestamps in response | Any success | `created_at`, `updated_at` in ISO format |

---

## Mock Data

### Sample Project Creation Request
```json
{
  "name": "Example Project",
  "domain": "https://example.com",
  "notes": "Project for tracking example.com rankings"
}
```

### Sample Project Response
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Example Project",
    "domain": "https://example.com",
    "notes": "Project for tracking example.com rankings",
    "is_default": false,
    "archived_at": null,
    "created_at": "2026-02-26T18:00:00Z",
    "updated_at": "2026-02-26T18:00:00Z"
  }
}
```

### Sample List Response
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Example Project",
      "domain": "https://example.com",
      "is_default": false,
      "archived_at": null
    }
  ],
  "meta": {
    "total": 1,
    "active": 1,
    "archived": 0,
    "page": 1,
    "perPage": 15
  }
}
```

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Create Project (CP) | 100% | CP-001 to CP-010 (10 tests) |
| Read Project (RP) | 100% | RP-001 to RP-011 (9 tests) |
| Update Project (UP) | 100% | UP-001 to UP-007 (7 tests) |
| Archive/Restore (AR) | 100% | AR-001 to AR-006 (5 tests) |
| Delete Project (DP) | 100% | DP-001 to DP-007 (7 tests) |
| Security (SEC) | 100% | SEC-001 to SEC-010 (10 tests) |
| Edge Cases (EC) | 100% | EC-001 to EC-015 (15 tests) |
| Response Format (RF) | 100% | RF-001 to RF-008 (8 tests) |

**Total: 71 test scenarios**

---

## Test Execution

```bash
# Run all project controller tests
php artisan test --filter=ProjectController

# Run specific test file
php artisan test tests/Feature/Api/V1/ProjectControllerTest.php

# Run with coverage
php artisan test --coverage --filter=ProjectController

# Run only security tests
php artisan test --filter="SEC"
```

---

## Dependencies

| Dependency | Task | Status | Notes |
|------------|------|--------|-------|
| Projects Table | TASK-8-1 | To Test Locally | Required for Project model |
| Project Model | TASK-8-1 | ✅ Complete | Model exists with relationships |
| Authorization Policies | TASK-8-6 | To Develop | Required for 403 responses |
| User Authentication | Pre-existing | ✅ Complete | Required for auth middleware |

---

## API Endpoints Reference

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/projects` | List user's projects |
| POST | `/api/v1/projects` | Create new project |
| GET | `/api/v1/projects/{id}` | Get single project |
| PUT | `/api/v1/projects/{id}` | Update project |
| DELETE | `/api/v1/projects/{id}` | Delete project (requires confirm=true) |
| POST | `/api/v1/projects/{id}/archive` | Archive project |
| POST | `/api/v1/projects/{id}/restore` | Restore archived project |

---

## Notes for Developer

### Controller Implementation Checklist
- [ ] Create `backend/app/Http/Controllers/Api/V1/ProjectController.php`
- [ ] Implement `index()` - List projects with pagination and filtering
- [ ] Implement `store()` - Create new project with validation
- [ ] Implement `show()` - Get single project
- [ ] Implement `update()` - Update project fields
- [ ] Implement `destroy()` - Delete with confirmation
- [ ] Implement `archive()` - Soft archive (set archived_at)
- [ ] Implement `restore()` - Unarchive (clear archived_at)

### Request Validation
Create `backend/app/Http/Requests/ProjectRequest.php`:
```php
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255'],
        'domain' => ['nullable', 'url', 'max:500'],
        'notes' => ['nullable', 'string'],
    ];
}
```

### Controller Template
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Http\Requests\ProjectRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Project::class, 'project');
    }

    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->projects();
        
        if ($request->has('archived')) {
            $archived = filter_var($request->archived, FILTER_VALIDATE_BOOLEAN);
            $query->{$archived ? 'archived' : 'active'}();
        }
        
        $projects = $query->paginate($request->perPage ?? 15);
        
        return response()->json([
            'success' => true,
            'data' => $projects->items(),
            'meta' => [
                'total' => $projects->total(),
                'page' => $projects->currentPage(),
                'perPage' => $projects->perPage(),
            ],
        ]);
    }

    public function store(ProjectRequest $request): JsonResponse
    {
        $project = $request->user()->projects()->create($request->validated());
        
        return response()->json([
            'success' => true,
            'data' => $project,
        ], 201);
    }

    public function show(Project $project): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $project,
        ]);
    }

    public function update(ProjectRequest $request, Project $project): JsonResponse
    {
        $project->update($request->validated());
        
        return response()->json([
            'success' => true,
            'data' => $project->fresh(),
        ]);
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        if ($request->confirm !== true && $request->confirm !== 'true') {
            return response()->json([
                'success' => false,
                'message' => 'Confirmation required. Add ?confirm=true to delete.',
            ], 400);
        }
        
        $projectId = $project->id;
        $project->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully.',
            'deleted_id' => $projectId,
        ]);
    }

    public function archive(Project $project): JsonResponse
    {
        if (!$project->isArchived()) {
            $project->archive();
        }
        
        return response()->json([
            'success' => true,
            'data' => $project->fresh(),
        ]);
    }

    public function restore(Project $project): JsonResponse
    {
        if ($project->isArchived()) {
            $project->unarchive();
        }
        
        return response()->json([
            'success' => true,
            'data' => $project->fresh(),
        ]);
    }
}
```

### Route Registration
Add to `routes/api.php`:
```php
Route::prefix('v1')->middleware(['auth'])->group(function () {
    Route::post('projects/{project}/archive', [ProjectController::class, 'archive']);
    Route::post('projects/{project}/restore', [ProjectController::class, 'restore']);
    Route::apiResource('projects', ProjectController::class);
});
```

### Critical Implementation Notes
1. **Authorization:** Every endpoint must check project ownership via ProjectPolicy
2. **Confirmation:** Delete endpoint requires `confirm=true` query param
3. **Archiving:** Use `archived_at` timestamp (not soft delete)
4. **User Scope:** Index should only return projects for authenticated user
5. **Validation:** Domain must be valid URL format, name is required

---

## Comments

### [2026-02-26 20:23] Agent: PreTester
**Test Documentation Created:**
- 71 test scenarios covering CRUD operations, security, validation, and response formats
- Existing test file has 56 tests already defined in ProjectControllerTest.php
- Tests will FAIL until ProjectController is implemented

**Dependencies Status:**
- TASK-8-1 (Projects Table): `To Test Locally` - Model exists
- TASK-8-6 (Authorization Policies): `To Develop` - Required for 403 responses

**Key Testing Priorities:**
1. SEC-001/002: Authentication enforcement (401 for unauthenticated)
2. RP-006, UP-005, DP-004, AR-006: Authorization enforcement (403 for non-owners)
3. DP-002: Delete confirmation requirement
4. EC-010/011: Unicode and special character handling

**Implementation Notes:**
- Controller should use `authorizeResource(Project::class)` for automatic authorization
- Archive/restore are custom endpoints (not part of apiResource)
- Delete requires confirmation to prevent accidental deletion

**Status:** `Tests Coverage In Progress` → Ready for Developer implementation
