# TEST-8-10: End-to-End Integration Tests for Multi-Site Project Management

## Task Reference
- **Task:** [TASK-8-10](../TASKS/TASK-8-10.md)
- **Feature:** FEAT-008 Multi-Site Project Management
- **Specification:** SPEC-008

---

## Overview

End-to-end integration tests verifying the complete flow of project CRUD operations, project context scoping, and frontend-backend interactions for the multi-site project management feature.

---

## Dependencies Status

| Dependency | Task | Status | Notes |
|------------|------|--------|-------|
| Projects Table | TASK-8-1 | To Write Tests | Required for all tests |
| CRUD Endpoints | TASK-8-7 | To Write Tests | Required for API tests |
| Frontend Components | TASK-8-8 | To Write Tests | Required for E2E tests |

> **TDD Note:** These tests are written before implementation. Tests will fail until dependencies are resolved.

---

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `backend/tests/Feature/Api/V1/ProjectControllerTest.php` | Feature | API endpoint integration tests |
| `backend/tests/Feature/Integration/ProjectContextTest.php` | Integration | Project scoping verification |
| `backend/tests/Feature/Integration/ProjectDataIsolationTest.php` | Integration | Cross-project data isolation |
| `tests/e2e/project-management.e2e.js` | E2E | Full user flow tests |

---

## Test Scenarios

### Part 1: CRUD API Operations (API)

#### Create Project Tests (CP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-001 | Create project with valid data | `POST /api/projects` with name, domain | 201 Created, project returned |
| CP-002 | Create project with minimal data | `POST /api/projects` with only name | 201 Created, defaults applied |
| CP-003 | Create project with all fields | `POST /api/projects` with name, domain, notes | 201 Created, all fields stored |
| CP-004 | Create project without name | `POST /api/projects` with empty body | 422 Unprocessable Entity |
| CP-005 | Create project with invalid domain format | Domain: "not-a-url" | 422 Validation Error |
| CP-006 | Create project with duplicate name | Same name as existing project | 201 Created (duplicate names allowed) |
| CP-007 | Create project sets user ownership | Authenticated user creates project | project.user_id = auth user |
| CP-008 | Create project with very long name | Name: 255 characters | 201 Created |
| CP-009 | Create project with name exceeding limit | Name: 256 characters | 422 Validation Error |
| CP-010 | Create project with notes containing HTML | Notes: `<script>alert(1)</script>` | 201 Created, HTML sanitized/escaped |

#### Read Project Tests (RP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RP-001 | List all user's projects | `GET /api/projects` | 200 OK, array of projects |
| RP-002 | List projects includes metadata | `GET /api/projects` | Response includes total, active, archived counts |
| RP-003 | List projects pagination | `GET /api/projects?page=1&perPage=10` | Correct pagination metadata |
| RP-004 | Get single project | `GET /api/projects/{id}` | 200 OK, project details |
| RP-005 | Get non-existent project | `GET /api/projects/99999` | 404 Not Found |
| RP-006 | Get project owned by another user | Cross-user project ID | 403 Forbidden |
| RP-007 | Get project includes keywords count | `GET /api/projects/{id}` | Response includes total_keywords |
| RP-008 | List projects sorted by name | `GET /api/projects?sort=name&order=asc` | Sorted alphabetically |
| RP-009 | List projects sorted by created_at | `GET /api/projects?sort=created_at&order=desc` | Most recent first |
| RP-010 | List only active projects | `GET /api/projects?archived=false` | Only projects with archived_at=null |
| RP-011 | List only archived projects | `GET /api/projects?archived=true` | Only projects with archived_at set |

#### Update Project Tests (UP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| UP-001 | Update project name | `PUT /api/projects/{id}` with new name | 200 OK, name updated |
| UP-002 | Update project domain | `PUT /api/projects/{id}` with new domain | 200 OK, domain updated |
| UP-003 | Update project notes | `PUT /api/projects/{id}` with new notes | 200 OK, notes updated |
| UP-004 | Update non-existent project | `PUT /api/projects/99999` | 404 Not Found |
| UP-005 | Update project owned by another user | Cross-user project ID | 403 Forbidden |
| UP-006 | Update with invalid domain | Invalid URL format | 422 Validation Error |
| UP-007 | Partial update | `PUT` with only name field | 200 OK, other fields unchanged |
| UP-008 | Update archived project | Update archived project data | 200 OK (archived projects can be edited) |
| UP-009 | Clear optional fields | Update with domain=null, notes=null | 200 OK, fields cleared |

#### Archive/Restore Project Tests (AR)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AR-001 | Archive active project | `POST /api/projects/{id}/archive` | 200 OK, archived_at set |
| AR-002 | Archive already archived project | Archive archived project | 200 OK, archived_at unchanged |
| AR-003 | Restore archived project | `POST /api/projects/{id}/restore` | 200 OK, archived_at cleared |
| AR-004 | Restore active project | Restore non-archived project | 200 OK, archived_at remains null |
| AR-005 | Archive non-existent project | `POST /api/projects/99999/archive` | 404 Not Found |
| AR-006 | Archive project owned by another user | Cross-user project ID | 403 Forbidden |
| AR-007 | Archived project hidden from default list | `GET /api/projects` (default) | Archived projects not included |
| AR-008 | Archived project visible with filter | `GET /api/projects?archived=true` | Archived projects included |
| AR-009 | Archived project retains all data | Archive then restore | All data intact |

#### Delete Project Tests (DP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DP-001 | Delete project with confirmation | `DELETE /api/projects/{id}?confirm=true` | 200 OK, success message |
| DP-002 | Delete project without confirmation | `DELETE /api/projects/{id}` | 400 Bad Request (confirmation required) |
| DP-003 | Delete non-existent project | `DELETE /api/projects/99999?confirm=true` | 404 Not Found |
| DP-004 | Delete project owned by another user | Cross-user project ID | 403 Forbidden |
| DP-005 | Delete project cascades to keywords | Delete project with keywords | All keywords deleted |
| DP-006 | Delete project cascades to competitors | Delete project with competitors | All competitors deleted |
| DP-007 | Delete project cascades to reports | Delete project with reports | All reports deleted |
| DP-008 | Delete default project | Delete project with is_default=true | 200 OK, another project becomes default? |

---

### Part 2: Project Context Testing (CTX)

#### Keywords Scoped to Project (CTX-KW)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CTX-KW-001 | List keywords for project | `GET /api/keywords` with X-Project-ID header | Only keywords for that project |
| CTX-KW-002 | Create keyword with project context | `POST /api/keywords` with X-Project-ID header | Keyword linked to project |
| CTX-KW-003 | Create keyword without project context | `POST /api/keywords` without header | Error or default project used |
| CTX-KW-004 | Access keyword from different project | Try to get keyword from another project | 404 Not Found (scoped) |
| CTX-KW-005 | Update keyword maintains project | `PUT /api/keywords/{id}` with project context | Project unchanged |
| CTX-KW-006 | Delete keyword with project context | `DELETE /api/keywords/{id}` with X-Project-ID | Only if keyword belongs to project |
| CTX-KW-007 | Switch project context mid-session | Change X-Project-ID, query keywords | Results from new project only |
| CTX-KW-008 | Invalid project ID in header | X-Project-ID: 99999 | 404 Not Found or empty results |
| CTX-KW-009 | Keywords count per project | Query keywords count for each project | Accurate counts |
| CTX-KW-010 | Bulk keyword operations | Import 100 keywords to project | All linked to correct project |

#### Competitors Scoped to Project (CTX-COMP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CTX-COMP-001 | List competitors for project | `GET /api/competitors` with X-Project-ID header | Only competitors for that project |
| CTX-COMP-002 | Create competitor with project context | `POST /api/competitors` with X-Project-ID header | Competitor linked to project |
| CTX-COMP-003 | Access competitor from different project | Try to get competitor from another project | 404 Not Found |
| CTX-COMP-004 | Competitor rankings scoped to project | Query rankings with project context | Only rankings for project's competitors |
| CTX-COMP-005 | Delete competitor with project context | `DELETE /api/competitors/{id}` with X-Project-ID | Only if competitor belongs to project |

#### Reports Scoped to Project (CTX-RPT)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CTX-RPT-001 | List reports for project | `GET /api/reports` with X-Project-ID header | Only reports for that project |
| CTX-RPT-002 | Generate report with project context | `POST /api/reports/generate` with X-Project-ID | Report linked to project |
| CTX-RPT-003 | Download report with project context | `GET /api/reports/{id}/download` with X-Project-ID | Only if report belongs to project |
| CTX-RPT-004 | Scheduled reports scoped to project | Query scheduled reports | Only for current project |
| CTX-RPT-005 | Report contains only project data | Generate report, check content | Only keywords from project |

---

### Part 3: Frontend-Backend Integration (FB)

#### Project Selector Integration (FB-PS)

| ID | Scenario | Test | Expected Output |
|----|----------|------|-----------------|
| FB-PS-001 | ProjectSelector loads projects | Render component | Dropdown shows all user projects |
| FB-PS-002 | ProjectSelector shows current project | Select project | Current project highlighted |
| FB-PS-003 | Switching project updates context | Select different project | Context updates, API calls use new project ID |
| FB-PS-004 | ProjectSelector shows "All Projects" option | Render with showAllOption=true | "All Projects" option available |
| FB-PS-005 | Create new project from selector | Click "Create New" | Modal opens, form displayed |
| FB-PS-006 | Archive project from selector | Archive current project | Project removed from list, context switches |
| FB-PS-007 | ProjectSelector handles loading state | Initial load | Loading indicator shown |
| FB-PS-008 | ProjectSelector handles error state | API error | Error message displayed |

#### Project Dashboard Integration (FB-PD)

| ID | Scenario | Test | Expected Output |
|----|----------|------|-----------------|
| FB-PD-001 | Dashboard loads project metrics | Render with projectId | Metrics displayed for project |
| FB-PD-002 | Dashboard shows correct keyword count | Query project with 50 keywords | "50 Keywords" displayed |
| FB-PD-003 | Dashboard shows average position | Query project with rankings | Correct average position |
| FB-PD-004 | Dashboard shows position changes | Query with 7-day changes | Improved/declined counts shown |
| FB-PD-005 | Dashboard handles empty project | New project with no data | Empty state message |
| FB-PD-006 | Dashboard updates on project switch | Switch project | Metrics refresh for new project |
| FB-PD-007 | Dashboard real-time updates | After keyword rank check | Metrics update automatically |
| FB-PD-008 | Dashboard SERP features count | Project with SERP features | Correct count displayed |

#### All Projects View Integration (FB-APV)

| ID | Scenario | Test | Expected Output |
|----|----------|------|-----------------|
| FB-APV-001 | View loads aggregate metrics | Render component | Total keywords, avg position across all projects |
| FB-APV-002 | View shows project breakdown | Render with multiple projects | Table with per-project metrics |
| FB-APV-003 | View sorts projects | Click sort column header | Projects sorted correctly |
| FB-APV-004 | View filters projects | Search/filter input | Filtered project list |
| FB-APV-005 | View handles no projects | User with no projects | Empty state with "Create Project" CTA |
| FB-APV-006 | View navigation to project | Click project row | Navigates to project dashboard |
| FB-APV-007 | View refresh data | Trigger refresh | Updated aggregate metrics |

#### API Integration (FB-API)

| ID | Scenario | Test | Expected Output |
|----|----------|------|-----------------|
| FB-API-001 | Context provides switchProject method | Call switchProject(id) | Project context updates, API calls use new ID |
| FB-API-002 | Context provides createProject method | Call createProject(data) | Project created, context updated |
| FB-API-003 | Context provides updateProject method | Call updateProject(id, data) | Project updated, context refreshed |
| FB-API-004 | Context provides deleteProject method | Call deleteProject(id) | Project deleted, context updated |
| FB-API-005 | API calls include X-Project-ID header | Make any API call | Header present with current project ID |
| FB-API-006 | API calls handle 401 unauthorized | Token expired | Redirect to login |
| FB-API-007 | API calls handle 403 forbidden | Access other user's project | Error displayed, no data shown |
| FB-API-008 | API calls handle network errors | Offline scenario | Retry option or cached data |

---

### Part 4: Security & Authorization Tests (SEC)

| ID | Scenario | Test | Expected Output |
|----|----------|------|-----------------|
| SEC-001 | Unauthenticated access to projects | GET /api/projects without token | 401 Unauthorized |
| SEC-002 | Unauthenticated create project | POST /api/projects without token | 401 Unauthorized |
| SEC-003 | Access other user's project | GET /api/projects/{other_user_project_id} | 403 Forbidden |
| SEC-004 | Update other user's project | PUT /api/projects/{other_user_project_id} | 403 Forbidden |
| SEC-005 | Delete other user's project | DELETE /api/projects/{other_user_project_id} | 403 Forbidden |
| SEC-006 | Access keywords from other user's project | X-Project-ID header with other's project | 403 Forbidden or empty results |
| SEC-007 | SQL injection in project name | Name: `'; DROP TABLE projects;--` | Stored as string, not executed |
| SEC-008 | XSS in project notes | Notes: `<script>alert('xss')</script>` | Sanitized/escaped in output |
| SEC-009 | CSRF protection | POST without CSRF token | 419 Page Expired (Laravel default) |
| SEC-010 | Mass assignment protection | Try to set user_id via API | Ignored or error |

---

### Part 5: Edge Cases & Error Handling (EC)

| ID | Scenario | Test | Expected Output |
|----|----------|------|-----------------|
| EC-001 | Create project with empty name | POST with name="" | 422 Validation Error |
| EC-002 | Create project with whitespace-only name | POST with name="   " | 422 Validation Error or trimmed |
| EC-003 | Update project with same data | PUT with unchanged data | 200 OK, no error |
| EC-004 | Concurrent project updates | Two requests update same project | Last write wins or optimistic locking |
| EC-005 | Delete project while being viewed | Delete while dashboard open | Dashboard shows error or redirects |
| EC-006 | Very large notes field | Notes: 64KB text | Accepted or appropriate error |
| EC-007 | Unicode in project name | Name: "Проект 日本語 🚀" | Stored and retrieved correctly |
| EC-008 | Special characters in domain | Domain: "https://example.com/path?query=1&two=2" | Accepted or validation error |
| EC-009 | Project with 10,000 keywords | Query project metrics | Performance acceptable (<2s) |
| EC-010 | Switch project during API call | Change project while request pending | Correct project data returned |

---

## Coverage Summary

| Category | Tests | Priority |
|----------|-------|----------|
| Create Project (CP) | 10 | High |
| Read Project (RP) | 11 | High |
| Update Project (UP) | 9 | High |
| Archive/Restore (AR) | 9 | High |
| Delete Project (DP) | 8 | High |
| Keywords Context (CTX-KW) | 10 | High |
| Competitors Context (CTX-COMP) | 5 | High |
| Reports Context (CTX-RPT) | 5 | High |
| Project Selector (FB-PS) | 8 | Medium |
| Project Dashboard (FB-PD) | 8 | Medium |
| All Projects View (FB-APV) | 7 | Medium |
| API Integration (FB-API) | 8 | Medium |
| Security (SEC) | 10 | High |
| Edge Cases (EC) | 10 | Medium |

**Total: 118 test scenarios**

---

## Test Execution

```bash
# Run all project integration tests
php artisan test --filter=Project

# Run API tests only
php artisan test tests/Feature/Api/V1/ProjectControllerTest.php

# Run context tests
php artisan test tests/Feature/Integration/ProjectContextTest.php

# Run E2E tests
npx playwright test tests/e2e/project-management.e2e.js

# Run with coverage
php artisan test --coverage --filter=Project
```

---

## Mock Data Setup

### Test Users
```php
// User 1 - owns projects A, B
$user1 = User::factory()->create(['id' => 1, 'email' => 'user1@test.com']);

// User 2 - owns project C
$user2 = User::factory()->create(['id' => 2, 'email' => 'user2@test.com']);
```

### Test Projects
```php
// Project A - User 1's active project with data
$projectA = Project::factory()->create([
    'user_id' => 1,
    'name' => 'Project A',
    'domain' => 'https://project-a.com',
    'is_default' => true,
]);

// Project B - User 1's archived project
$projectB = Project::factory()->create([
    'user_id' => 1,
    'name' => 'Project B',
    'archived_at' => now(),
]);

// Project C - User 2's project
$projectC = Project::factory()->create([
    'user_id' => 2,
    'name' => 'Project C',
]);
```

### Test Keywords
```php
// 10 keywords for Project A
Keyword::factory()->count(10)->create(['project_id' => $projectA->id]);

// 5 keywords for Project B
Keyword::factory()->count(5)->create(['project_id' => $projectB->id]);

// 3 keywords for Project C
Keyword::factory()->count(3)->create(['project_id' => $projectC->id]);
```

---

## Notes for Developer

### Implementation Order
1. Ensure TASK-8-1 (Projects Table) is complete
2. Implement TASK-8-7 (CRUD Endpoints)
3. Implement TASK-8-8 (Frontend Components)
4. Run these integration tests

### Test Data Isolation
Each test should use `RefreshDatabase` trait to ensure clean state.

### API Headers
All scoped API calls should include `X-Project-ID` header:
```php
$response = $this->withHeaders(['X-Project-ID' => $projectId])
    ->getJson('/api/keywords');
```

### Frontend Testing
Use React Testing Library for component tests and Playwright for E2E.

---

## Comments

### [2026-02-26 19:55] Agent: PreTester
**Test Documentation Created:**
- 118 test scenarios covering full E2E integration
- CRUD operations (48 tests)
- Project context scoping (20 tests)
- Frontend-backend integration (23 tests)
- Security & edge cases (27 tests)

**Dependencies Not Yet Implemented:**
- TASK-8-7 (CRUD Endpoints) - status: To Write Tests
- TASK-8-8 (Frontend Components) - status: To Write Tests
- Tests will FAIL until implementation is complete

**Key Testing Priorities:**
1. CTX-* tests: Project context scoping is critical for multi-tenant data isolation
2. SEC-* tests: Authorization ensures users can't access other users' data
3. DP-005/006/007: Cascade delete verification for data integrity

**Status:** `Tests Coverage In Progress` → Tests designed, scripts to be created
