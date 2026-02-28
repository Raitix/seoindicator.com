# TASK-24-8: PWA Analysis API Endpoints

## STATUS
`To Prioritize`

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
