# TASK-6-8: Keywords API Endpoints

## Status
`To Write Tests`

## Priority
`High`

## Risk
`Medium`

## Specification
- Spec: [SPEC-006](../SPECIFICATIONS/SPEC-006.md)
- Feature: [FEAT-006](../FEATURES/FEAT-006.md)

---

## Description
Implement REST API endpoints for keywords CRUD operations.

## Requirements

### Endpoints
- `GET /api/v1/keywords` - List keywords
- `POST /api/v1/keywords` - Create keyword
- `GET /api/v1/keywords/:id` - Get single keyword
- `PUT /api/v1/keywords/:id` - Update keyword
- `DELETE /api/v1/keywords/:id` - Delete keyword

### List Endpoint
- Query params: site_id, status, page, per_page
- Pagination support
- Filter by site and status

### Request/Response Format
```json
// POST request
{
  "site_id": 1,
  "keyword": "seo tools",
  "target_url": "https://example.com/seo-tools",
  "search_engine": "google",
  "location": "us",
  "device": "desktop"
}

// Response
{
  "success": true,
  "data": { ... }
}
```

### Error Handling
- Validation errors with field details
- 404 for not found
- Proper HTTP status codes

## Acceptance Criteria
- [ ] All CRUD endpoints working
- [ ] Pagination implemented
- [ ] Input validation
- [ ] Error responses formatted
- [ ] Rate limiting applied

## Dependencies
- TASK-6-4 (API Key Management)
- TASK-6-5 (Rate Limiter)

## Estimated Effort
Medium (3-5 hours)

---

## Comments

### [2026-02-27 12:25] ProjectManager: Task Created
- Priority: High (MVP core feature)
- Risk: Medium (new endpoints, modifies data)
- Part of Phase 3: REST API
