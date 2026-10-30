# TEST-8-10: End-to-End Integration Tests

## Task Reference
- **Task:** [TASK-8-10](../TASKS/TASK-8-10.md)
- **Feature:** FEAT-008 Multi-Site Project Management
- **Specification:** SPEC-008

---

## Overview

End-to-end integration tests for the Project Management feature. Tests verify complete user workflows, project data scoping across related entities, and frontend-backend API integration. These tests complement the unit tests in `TEST-8-7.md` by testing real-world scenarios and cross-component behavior.

---

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `backend/tests/Feature/Integration/ProjectIntegrationTest.php` | Integration | E2E workflows and scoping tests |
| `frontend/__tests__/integration/projects.test.ts` | Integration | Frontend-backend API integration |

---

## Test Scenarios

### 1. CRUD Workflow Tests (WF)

| ID | Scenario | Steps | Expected Result |
|----|----------|-------|-----------------|
| WF-001 | Full project lifecycle | Create → List → Update → Archive → Restore → Delete | All operations succeed in sequence |
| WF-002 | Create and immediately list | Create project → List projects | New project appears in list |
| WF-003 | Update reflects in list | Create → Update name → List | Updated name shown in list |
| WF-004 | Archive hides from default list | Create → Archive → List (default) | Archived project not in list |
| WF-005 | Archive shows in archived list | Create → Archive → List (archived=true) | Archived project appears |
| WF-006 | Delete removes from all lists | Create → Delete → List | Project not found anywhere |
| WF-007 | Multiple projects CRUD | Create 3 → Update 2 → Archive 1 → List | Correct counts and states |

---

### 2. Project Context Scoping Tests (PS)

| ID | Scenario | Setup | Expected Result |
|----|----------|-------|-----------------|
| PS-001 | Keywords scoped to project | Project A with keywords, Project B with keywords | Each project sees only its keywords |
| PS-002 | Competitors scoped to project | Project A with competitors, Project B with competitors | Each project sees only its competitors |
| PS-003 | Reports scoped to project | Project A with reports, Project B with reports | Each project sees only its reports |
| PS-004 | Cross-project data isolation | User has Project A & B with data | Cannot access Project B data from Project A context |
| PS-005 | Delete cascades to related data | Project with keywords/competitors/reports → Delete | All related data deleted |
| PS-006 | Archive preserves related data | Project with keywords → Archive → Keywords still exist | Data preserved but hidden |
| PS-007 | Restore preserves relationships | Project with keywords → Archive → Restore | Keywords still linked |

---

### 3. Cross-User Isolation Tests (CU)

| ID | Scenario | Setup | Expected Result |
|----|----------|-------|-----------------|
| CU-001 | User cannot see other's projects | User A project, User B authenticated | User B's list doesn't include A's project |
| CU-002 | User cannot access other's project | User A project, User B tries GET | 403 Forbidden |
| CU-003 | User cannot update other's project | User A project, User B tries PUT | 403 Forbidden |
| CU-004 | User cannot delete other's project | User A project, User B tries DELETE | 403 Forbidden |
| CU-005 | User cannot archive other's project | User A project, User B tries archive | 403 Forbidden |
| CU-006 | User cannot access other's keywords | User A project with keywords, User B tries to access | 403 Forbidden |
| CU-007 | User cannot access other's reports | User A project with reports, User B tries to access | 403 Forbidden |

---

### 4. Frontend-Backend Integration Tests (FB)

| ID | Scenario | API Call | Frontend State | Expected Result |
|----|----------|----------|----------------|-----------------|
| FB-001 | ProjectSelector loads projects | GET /api/v1/projects | projects state populated | Dropdown shows all projects |
| FB-002 | Create project via form | POST /api/v1/projects | New project added to state | Project appears in selector |
| FB-003 | Switch project context | Select project | currentProject updated | Context reflects selection |
| FB-004 | Update project settings | PUT /api/v1/projects/{id} | Project updated in state | Settings reflect changes |
| FB-005 | Delete with confirmation | DELETE /api/v1/projects/{id}?confirm=true | Project removed from state | Project removed from selector |
| FB-006 | Archive toggle | POST /api/v1/projects/{id}/archive | archived_at set | Project hidden from active list |
| FB-007 | Optimistic update rollback | PUT fails | State reverts to original | Error shown, original state restored |
| FB-008 | Pagination handles large lists | GET /api/v1/projects (25+) | Paginated state | Pages render correctly |

---

### 5. Performance Tests (PF)

| ID | Scenario | Setup | Expected Result |
|----|----------|-------|-----------------|
| PF-001 | List 100 projects < 500ms | Create 100 projects | List response < 500ms |
| PF-002 | Create project < 200ms | Standard create request | Response < 200ms |
| PF-003 | Update project < 200ms | Standard update request | Response < 200ms |
| PF-004 | Delete cascade < 1s | Project with 50 keywords | Delete completes < 1s |
| PF-005 | Archive/restore < 200ms | Standard archive request | Response < 200ms |

---

### 6. Error Handling Tests (EH)

| ID | Scenario | Trigger | Expected Result |
|----|----------|---------|-----------------|
| EH-001 | Invalid project ID format | GET /api/v1/projects/abc | 404 Not Found |
| EH-002 | Negative project ID | GET /api/v1/projects/-1 | 404 Not Found |
| EH-003 | Empty project name | POST with name: "" | 422 Validation Error |
| EH-004 | SQL injection in name | name: "'; DROP TABLE projects;--" | Stored safely, not executed |
| EH-005 | XSS in notes | notes: "<script>alert(1)</script>" | Sanitized in output |
| EH-006 | Concurrent updates | Two updates to same project | Last write wins (no locking) |
| EH-007 | Delete without confirmation | DELETE without ?confirm=true | 400 Bad Request |
| EH-008 | Network timeout | Slow network simulation | Graceful error message |

---

### 7. Data Integrity Tests (DI)

| ID | Scenario | Setup | Expected Result |
|----|----------|-------|-----------------|
| DI-001 | Created_at timestamp set | Create new project | created_at is now |
| DI-002 | Updated_at changes on update | Create → Update → Check | updated_at > created_at |
| DI-003 | User_id immutable | Try to change user_id via API | user_id unchanged |
| DI-004 | Archived_at null on create | Create new project | archived_at is null |
| DI-005 | Archived_at set on archive | Archive project | archived_at is now |
| DI-006 | archived_at cleared on restore | Archived project → Restore | archived_at is null |
| DI-007 | is_default handling | Create first project | is_default logic correct |

---

## Mock Data

### Sample Integration Test Setup
```php
// User setup
$user = User::factory()->create();
$otherUser = User::factory()->create();

// Projects setup
$projectA = Project::factory()->create(['user_id' => $user->id, 'name' => 'Project A']);
$projectB = Project::factory()->create(['user_id' => $user->id, 'name' => 'Project B']);
$otherUserProject = Project::factory()->create(['user_id' => $otherUser->id, 'name' => 'Other Project']);

// Related data (if keywords.project_id exists)
// Keyword::factory()->count(5)->create(['project_id' => $projectA->id]);
// Keyword::factory()->count(3)->create(['project_id' => $projectB->id]);
```

### Sample Frontend Test Setup
```typescript
// Mock API responses
const mockProjects = [
  { id: 1, name: 'Project A', domain: 'https://a.com', archived_at: null },
  { id: 2, name: 'Project B', domain: 'https://b.com', archived_at: null },
];

// Mock fetch
global.fetch = jest.fn(() =>
  Promise.resolve({
    ok: true,
    json: () => Promise.resolve({ success: true, data: mockProjects }),
  })
);
```

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| CRUD Workflows (WF) | 100% | WF-001 to WF-007 (7 tests) |
| Project Scoping (PS) | 100% | PS-001 to PS-007 (7 tests) |
| Cross-User Isolation (CU) | 100% | CU-001 to CU-007 (7 tests) |
| Frontend-Backend (FB) | 100% | FB-001 to FB-008 (8 tests) |
| Performance (PF) | 100% | PF-001 to PF-005 (5 tests) |
| Error Handling (EH) | 100% | EH-001 to EH-008 (8 tests) |
| Data Integrity (DI) | 100% | DI-001 to DI-007 (7 tests) |

**Total: 49 integration test scenarios**

---

## Test Execution

```bash
# Run integration tests
php artisan test --filter=ProjectIntegration

# Run specific test file
php artisan test tests/Feature/Integration/ProjectIntegrationTest.php

# Run with coverage
php artisan test --coverage --filter=ProjectIntegration

# Frontend integration tests
cd frontend && npm test -- --testPathPattern="integration/projects"

# Run all project-related tests
php artisan test --filter=Project
```

---

## Dependencies

| Dependency | Task | Status | Notes |
|------------|------|--------|-------|
| Project CRUD API | TASK-8-7 | Done | Required for all tests |
| Frontend Components | TASK-8-8 | Documentation | Required for FB tests |
| Projects Table | TASK-8-1 | Done | Required for all tests |
| ProjectPolicy | TASK-8-6 | Done | Required for authorization tests |
| User Authentication | Pre-existing | Done | Required for all tests |

---

## Implementation Notes

### Backend Integration Test Structure
```php
class ProjectIntegrationTest extends TestCase
{
    use RefreshDatabase;

    // 1. CRUD Workflows
    public function test_full_project_lifecycle(): void { ... }
    
    // 2. Project Scoping
    public function test_keywords_scoped_to_project(): void { ... }
    
    // 3. Cross-User Isolation
    public function test_user_cannot_see_other_projects(): void { ... }
    
    // 4. Performance
    public function test_list_100_projects_performance(): void { ... }
    
    // 5. Error Handling
    public function test_invalid_project_id_format(): void { ... }
    
    // 6. Data Integrity
    public function test_timestamps_set_correctly(): void { ... }
}
```

### Frontend Integration Test Structure
```typescript
describe('Project Integration', () => {
  // FB-001 to FB-008
  it('loads projects into selector', async () => { ... });
  it('creates project via form', async () => { ... });
  it('switches project context', async () => { ... });
  // ...
});
```

---

## Notes for Next Agent

### Integration Test Priorities
1. **WF-001**: Full lifecycle test is the most critical - validates all operations work together
2. **CU-002 to CU-005**: Authorization tests ensure security
3. **PS-001 to PS-003**: Scoping tests validate multi-tenancy

### Known Limitations
- Keywords/Competitors/Reports may not have `project_id` column yet (TASK-8-2)
- Frontend dynamic routes (`/projects/[projectId]/`) removed for static export
- Performance tests may vary based on test environment

### Test Data Requirements
- Tests should use factories for data creation
- Each test should be isolated with RefreshDatabase
- Mock external services (SERP fetchers, email) for reliability

---

## Comments

### [2026-02-28 16:05] Agent: PreTester
**Test Documentation Created:**
- 49 integration test scenarios across 7 categories
- Covers CRUD workflows, scoping, authorization, and performance
- Both backend and frontend test structures defined

**Dependencies Status:**
- TASK-8-7 (CRUD API): Done ✅
- TASK-8-8 (Frontend Components): Documentation ✅
- TASK-8-1 (Projects Table): Done ✅
- TASK-8-6 (Policies): Done ✅

**Implementation Note:**
- Project scoping tests (PS-*) require `project_id` on keywords/competitors/reports tables
- If not yet available, those tests should be skipped with note

**Status:** Ready for LocalTester execution
