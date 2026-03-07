# TASK-34-6: Project CRUD API Endpoints

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Specification
SPEC-034: AI Visibility Tracking (Generative Engine Optimization)

---

## Description

Create REST API endpoints for AI Visibility project management.

---

## Acceptance Criteria

- [ ] `AIVisibilityProjectController` created at `app/Http/Controllers/Api/V1/AIVisibility/`

- [ ] `GET /api/v1/ai-visibility/projects` endpoint
  - [ ] Returns paginated list of user's projects
  - [ ] Includes: id, brand_name, is_active, last_queried_at, mention_rate (latest)
  - [ ] Supports filtering by `is_active`
  - [ ] Supports sorting by `created_at`, `last_queried_at`
  - [ ] Authentication required

- [ ] `POST /api/v1/ai-visibility/projects` endpoint
  - [ ] Creates new project
  - [ ] Validates input:
    - `brand_name`: required, string, max 255
    - `keywords`: required, array, min 1, max 10, each max 100 chars
    - `competitors`: required, array, min 1, max 3, each max 100 chars
    - `query_templates`: optional, array
    - `tracking_frequency`: optional, enum (daily, weekly, monthly)
  - [ ] Returns created project with 201 status
  - [ ] Rate limit: 5 per hour

- [ ] `GET /api/v1/ai-visibility/projects/{id}` endpoint
  - [ ] Returns project details
  - [ ] Includes: all fields + latest analytics summary
  - [ ] Authorization: user must own project
  - [ ] Returns 404 if not found, 403 if not authorized

- [ ] `PUT /api/v1/ai-visibility/projects/{id}` endpoint
  - [ ] Updates project
  - [ ] Same validation as POST
  - [ ] Returns updated project
  - [ ] Authorization: user must own project

- [ ] `DELETE /api/v1/ai-visibility/projects/{id}` endpoint
  - [ ] Deletes project (cascades to all related data)
  - [ ] Returns 204 No Content
  - [ ] Authorization: user must own project

- [ ] `POST /api/v1/ai-visibility/projects/{id}/run` endpoint
  - [ ] Manually triggers query run
  - [ ] Checks query limit before dispatching
  - [ ] Returns job IDs with 202 Accepted
  - [ ] Rate limit: 3 per hour
  - [ ] Authorization: user must own project

- [ ] Routes registered in `routes/api.php`
- [ ] API resources used for response formatting
- [ ] Proper error responses (422 for validation, 403 for auth, 404 for not found)

---

## Dependencies
- TASK-34-5 (AIVisibilityService)

---

## Effort Estimate
**3 hours**

---

## Technical Notes

- Use Laravel resource classes for response formatting
- Use form requests for validation: `StoreAIVisibilityProjectRequest`, `UpdateAIVisibilityProjectRequest`
- Use policies for authorization: `AIVisibilityProjectPolicy`
- Rate limiting via `RateLimiter` facade or middleware

---

*Created by SpecSystemAnalyst on 2026-03-07*
