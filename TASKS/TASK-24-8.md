# TASK-24-8: PWA Analysis API Endpoints

## STATUS
`To Develop`

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

### [2026-03-01 10:45 GMT+2] Agent: PreTester
**Test Coverage Complete**

Created comprehensive test suite for PWA Analysis API endpoints:

**Test Documentation:** `TESTS/TEST-24-8.md`
**Test File:** `backend/tests/Feature/Api/V1/PWAControllerTest.php`

**Test Categories (51 tests):**

1. **SHOW Endpoint Tests (SH-001 to SH-011) - 11 tests:**
   - Returns analysis data for existing analysis
   - Returns 404 if no analysis (with suggestion to trigger)
   - Returns 404 if site not found
   - Returns 403 if not authorized (IDOR protection)
   - Returns 401 if unauthenticated
   - Returns all required fields (hasManifest, manifestUrl, etc.)
   - Returns fields breakdown with presence and values
   - Returns icons validation results
   - Returns recommendations array
   - Returns analyzedAt timestamp in ISO format
   - Cache hit performance

2. **ANALYZE Endpoint Tests (AN-001 to AN-009) - 9 tests:**
   - Dispatches analysis job, returns 202 with jobId
   - Returns 404 if site not found
   - Returns 403 if not authorized
   - Returns 401 if unauthenticated
   - Rate limit allows 10 per hour per site
   - Rate limit blocks over 10 per hour (429 response)
   - Rate limit is per-site (independent limits)
   - Rate limit resets after 1 hour
   - Job ID format validation (pwa-analyze-{uuid})

3. **REFRESH Endpoint Tests (RF-001 to RF-008) - 8 tests:**
   - Dispatches refresh job, returns 202
   - Returns 404 if site not found
   - Returns 403 if not authorized
   - Returns 401 if unauthenticated
   - Rate limited same as analyze
   - Cache invalidated on refresh
   - Job ID returned for tracking

4. **Authorization Tests (AZ-001 to AZ-003) - 3 tests:**
   - Owner can access all endpoints
   - Non-owner blocked from all endpoints (403)
   - Unauthenticated blocked from all endpoints (401)

5. **Security Tests (SEC-001 to SEC-004) - 4 tests:**
   - SQL injection blocked by route constraint
   - XSS in manifest data handled safely
   - IDOR prevention
   - Input validation for siteId

6. **Edge Cases (EC-001 to EC-009) - 9 tests:**
   - Site with no manifest
   - Invalid manifest with errors
   - Partial manifest (missing required fields)
   - Large manifest JSON (10KB+)
   - Unicode in manifest
   - Null manifest_json handled gracefully
   - Empty icons array
   - Very long manifest_url
   - Icon errors displayed correctly

7. **Integration Tests (INT-001 to INT-002) - 2 tests:**
   - Full analyze workflow (analyze → poll → get results)
   - Repository integration

8. **Performance Tests (PF-001 to PF-003) - 3 tests:**
   - GET response time (< 500ms)
   - POST response time (< 200ms)
   - Large manifest handling (< 1s)

9. **Data Consistency Tests (DC-001 to DC-003) - 3 tests:**
   - Score matches readiness level (0-39: none, 40-69: basic, 70-89: enhanced, 90-100: full)
   - Installable check (model method)
   - Latest analysis returned when multiple exist

**Controller Interface Expected:**
```php
class PWAController extends Controller
{
    public function show(int $siteId): JsonResponse;      // GET /api/v1/sites/{siteId}/pwa
    public function analyze(int $siteId): JsonResponse;   // POST /api/v1/sites/{siteId}/pwa/analyze
    public function refresh(int $siteId): JsonResponse;   // POST /api/v1/sites/{siteId}/pwa/refresh
}
```

**Response Structures:**
- GET 200: `{ success: true, data: { hasManifest, manifestUrl, manifestValid, pwaScore, readinessLevel, fields, icons, recommendations, analyzedAt } }`
- GET 404: `{ success: false, error: { code: 'ANALYSIS_NOT_FOUND', message, suggestion } }`
- POST 202: `{ success: true, data: { jobId, status, message } }`
- POST 429: `{ success: false, error: { code: 'RATE_LIMIT_EXCEEDED', retryAfter } }`

**Rate Limiting:**
- Key: `pwa-analyze:{siteId}`
- Limit: 10 requests per hour per site
- Implementation: Laravel Cache with TTL

**Caching:**
- Key: `pwa-analysis:{siteId}`
- TTL: 24 hours
- Invalidate on: analyze, refresh

**Notes for Developer:**
- Controller should be created at `backend/app/Http/Controllers/Api/V1/PWAController.php`
- Routes should be added to authenticated V1 API group in `routes/api.php`
- Use `PWAAnalysisRepository` for data access
- Implement rate limiting with Cache
- Cache GET responses for 24 hours
- Follow existing API response format (success/error structure)
- Tests currently return 404 (expected TDD behavior - routes not registered)

Status updated to `To Develop`. Ready for Developer to implement controller and routes.
