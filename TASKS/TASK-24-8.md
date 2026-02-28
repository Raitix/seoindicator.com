# TASK-24-8: PWA Analysis API Endpoints

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create API controller and routes for PWA analysis endpoints. Enables frontend to trigger and retrieve PWA analysis data.

## Acceptance Criteria
- [ ] PWAController created with methods:
  - show(int $siteId): JsonResponse - GET /api/v1/sites/{siteId}/pwa
  - analyze(int $siteId): JsonResponse - POST /api/v1/sites/{siteId}/pwa/analyze
  - refresh(int $siteId): JsonResponse - POST /api/v1/sites/{siteId}/pwa/refresh
- [ ] GET endpoint returns:
  - Full PWA analysis data (hasManifest, manifestUrl, manifestValid, pwaScore, readinessLevel)
  - Fields breakdown with presence and values
  - Icons validation results
  - Recommendations array
  - analyzedAt timestamp
- [ ] POST endpoint:
  - Dispatches analysis job (queued)
  - Returns 202 Accepted with jobId
  - Rate limited: max 10 analyses per site per hour
- [ ] Authorization: user must own the site
- [ ] Error responses:
  - 404 if site not found
  - 403 if not authorized
  - 404 if no analysis yet (suggest trigger)
- [ ] API routes registered in routes/api.php
- [ ] Feature tests for all endpoints

## Dependencies
- TASK-24-2 (PWAAnalysis model/repository)
- TASK-24-3 (ManifestFetcherService)
- TASK-24-4 (ManifestValidatorService)
- TASK-24-5 (IconValidatorService)
- TASK-24-6 (PWAScorerService)
- TASK-24-7 (PWASuggestionService)

## Effort Estimate
`Medium` (4-6 hours)

## Technical Notes
- Use Laravel's queue system for analysis jobs
- Consider caching GET responses
- Follow existing API response format
- Use Form Requests for validation

---

## Comments
_Agent communication log_

### [2026-02-28] Agent: SpecSystemAnalyst
Created task for API endpoints. Integrates all services into consumable API. Medium risk due to rate limiting and queue job handling. Must follow existing API patterns in project.

### [2026-02-28 23:39] Agent: ProjectManager
**Prioritization complete.** Priority confirmed as `High` - this is a core API endpoint that integrates all PWA services and is required for frontend dashboard functionality.

**Risk confirmed as `Medium`** - involves queue jobs, rate limiting, and authorization checks.

**Dependencies status:**
- TASK-24-2 through TASK-24-6: `To Write Tests` (not yet complete)
- TASK-24-7: `To Prioritize` (needs prioritization)

**Order of execution:** Developer must complete dependencies (24-2 through 24-7) before this task. This task integrates all services.

Status updated: `To Prioritize` → `To Write Tests`
