# TASK-19-4: Job CRUD API Endpoints

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create REST API endpoints for job management: create, list, get, cancel.

## Acceptance Criteria
- [ ] `POST /api/v1/sites/{siteId}/analysis/jobs` - Create new analysis job
  - Request: { indicators: string[] }
  - Response: 201 with job data
  - Validation: indicators array required, at least 1 item
- [ ] `GET /api/v1/sites/{siteId}/analysis/jobs` - List jobs for site
  - Query params: status, page, perPage
  - Response: paginated job list
- [ ] `GET /api/v1/analysis/jobs/{jobId}` - Get job progress
  - Response: job with results array
  - Authorization: user owns job
- [ ] `DELETE /api/v1/analysis/jobs/{jobId}` - Cancel job
  - Response: 200 with updated job status
  - Only pending/running jobs can be cancelled
- [ ] Authentication required for all endpoints
- [ ] Authorization: users can only access their own jobs
- [ ] Request validation with appropriate error responses
- [ ] Feature tests for all endpoints

## Dependencies
- TASK-19-3 (AnalysisJobService)

## Effort Estimate
`Medium` (4-6 hours)

## Technical Notes
- Use Laravel API resources for consistent response format
- Follow existing API patterns in project
- Consider rate limiting (10 jobs/user/hour)

---

## Comments
_Agent communication log_

### [2026-02-28] Agent: SpecSystemAnalyst
Created task for API layer. Must follow existing API patterns. Set priority High as it enables frontend integration.
