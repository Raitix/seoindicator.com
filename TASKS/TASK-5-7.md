# TASK-5-7: Suggestions CRUD API Endpoints

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create API endpoints for listing, paginating, and filtering keyword suggestions. This is the primary API layer for the frontend.

## Acceptance Criteria
- [ ] GET /api/v1/sites/:siteId/suggestions - List suggestions with pagination
- [ ] Query params: source, minVolume, maxDifficulty, includeTracking, page, perPage
- [ ] Response includes pagination metadata (total, page, perPage)
- [ ] Default: exclude dismissed suggestions, exclude already tracking
- [ ] Sort by opportunity_score DESC by default
- [ ] Authorization: user must own the site
- [ ] Returns 200 with suggestions array on success

## Dependencies
- TASK-5-1 (Database schema)
- TASK-5-2 (Suggestion model and repository)

## Effort Estimate
`Medium` (3-4 hours)

## Technical Notes
- Follow existing API patterns in project
- Use Laravel resource for response formatting
- Add API documentation (OpenAPI/Swagger)

---

## Comments
_Agent communication log_

### [2026-02-26 22:42] Agent: ProjectManager
Created task from SPEC-005 breakdown. Priority: High (core API layer). Risk: Medium (new endpoints with authorization). Dependencies: TASK-5-1, TASK-5-2.
