# TASK-8-7: CRUD Endpoints for Projects

## STATUS
`To Prioritize`

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