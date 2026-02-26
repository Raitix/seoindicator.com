# TASK-8-7: CRUD Endpoints for Projects

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Implement CRUD (Create, Read, Update, Delete) API endpoints for managing projects. These endpoints allow users to create, update, archive, restore, and delete projects.

## Endpoints
1. **Create Project**
```http
POST /api/projects
Body: { "name": "Example", "domain": "example.com" }
Response: { "id": 1, "name": "Example", ... }
```

2. **Get Project**
```http
GET /api/projects/{id}
Response: { "id": 1, "name": "Example", ... }
```

3. **List Projects**
```http
GET /api/projects
Response: { "data": [...], "meta": { ... } }
```

4. **Update Project**
```http
PUT /api/projects/{id}
Body: { "name": "New Name" }
Response: { "id": 1, "name": "New Name" }
```

5. **Archive/Restore Project**
```http
POST /api/projects/{id}/archive
POST /api/projects/{id}/restore
Response: { "id": 1, "archived_at": null }
```

6. **Delete Project**
```http
DELETE /api/projects/{id}?confirm=true
Response: { "success": true, "deleted_id": 1 }
```

## Acceptance Criteria
- [ ] API endpoints implemented with request validation.
- [ ] CRUD operations tested (happy path, edge cases, and errors).
- [ ] Policies enforced for securing access to endpoints.
- [ ] Swagger/OpenAPI documentation written for endpoints.

## Dependencies
- TASK-8-1: Projects table must exist.
- TASK-8-6: Authorization policies must be implemented.

## Effort Estimate
4-6 hours.

---

## Comments
### [2026-02-20 17:46] Agent: SpecSystemAnalyst
API task created to develop key endpoints (CRUD) for project management.

### [2026-02-26 19:13] Agent: ProjectManager
**Prioritization Decision:**
- **Priority: High** (confirmed) - Core API layer for FEAT-008 Multi-Site Project Management
- **Risk: Medium** (confirmed) - New endpoints with authorization integration

**Dependency Analysis:**
- **Depends on:** TASK-8-1 (Projects Table) - status `To Write Tests` ✓
- **Depends on:** TASK-8-6 (Authorization Policies) - status `To Write Tests` ✓
- **Blocks:** TASK-8-8 (Frontend Components) - UI needs API endpoints
- **Blocks:** TASK-8-10 (Integration Tests) - E2E tests need API layer

**Why High Priority:**
1. **Core functionality:** CRUD is the backbone of project management
2. **User-facing:** These endpoints power all project operations
3. **Sequential dependency:** Frontend cannot proceed without API layer
4. **Business value:** Enables multi-site project creation/management

**Risk Assessment (Medium):**
- New endpoints with request validation (non-destructive)
- Authorization policies integrated (TASK-8-6 covers security)
- Swagger/OpenAPI documentation required
- Test coverage will validate edge cases

**Endpoints to Implement:**
| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/projects` | POST | Create project |
| `/api/projects/{id}` | GET | Get single project |
| `/api/projects` | GET | List user's projects |
| `/api/projects/{id}` | PUT | Update project |
| `/api/projects/{id}/archive` | POST | Archive project |
| `/api/projects/{id}/restore` | POST | Restore project |
| `/api/projects/{id}` | DELETE | Delete project (with confirm) |

**Test Coverage Requirements:**
- Request validation (name, domain format)
- Authorization enforcement (user owns project)
- Pagination and filtering
- Archive/restore toggle behavior
- Soft delete with confirmation

**Status changed:** `To Prioritize` → `To Write Tests`