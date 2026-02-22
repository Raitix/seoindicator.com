# SPEC-001-4: Keywords CRUD API Endpoints

## STATUS
`To Test Locally`
## PRIORITY
`High`

## RISK
`Medium`

## Description
Implement REST API endpoints for keyword management (Create, Read, Update, Delete). Core API for the rank tracking feature.

## Acceptance Criteria
- [x] GET /api/v1/sites/:siteId/keywords - List keywords with pagination, sorting, filtering
- [x] POST /api/v1/sites/:siteId/keywords - Create keyword with validation
- [x] GET /api/v1/keywords/:id - Get single keyword with current position
- [x] PUT /api/v1/keywords/:id - Update keyword (targetUrl, status)
- [x] DELETE /api/v1/keywords/:id - Delete keyword (soft delete, optional history retention)
- [x] Query params: status, page, perPage, sortBy, sortOrder
- [x] Validation: keyword required, targetUrl valid URL, unique constraint
- [x] Proper HTTP status codes (200, 201, 204, 400, 404, 409)
- [x] Error responses follow standard format with code and message
- [x] API returns current position from latest ranking

## Dependencies
- SPEC-001-2 (keyword repository)

## Effort Estimate
`Medium` (4-6 hours)

## Technical Notes
- Use Laravel resource controllers or Fastify routes
- Include request validation classes
- Add keyword limit check per subscription tier (stub for now)
- Return position change from previous check

---

## Comments
_Agent communication log_

### [2026-02-20 09:44] Agent: SpecSystemAnalyst
Created task for keywords CRUD API. Core API functionality for rank tracking.

### [2026-02-22 18:10] Agent: Developer
Implemented KeywordController + Store/Update request validation, list/sort/paginate, create/update/delete (soft delete + optional history removal), and single keyword responses with position change. Routes wired under v1.

### [2026-02-22 23:50] Agent: Developer
Verified controller/routes/requests and keyword CRUD behavior already implemented in backend. Marked acceptance criteria complete and moved task to To Test Locally.
