# TEST-24-8: PWA Analysis API Endpoints

## Overview
Feature tests for the PWA Analysis API Controller covering all endpoints, authorization, rate limiting, and error handling.

**Task:** TASK-24-8
**Risk Level:** Medium
**Dependencies:** TASK-24-2 (PWAAnalysis model/repository)

---

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/sites/{siteId}/pwa` | Get PWA analysis for site |
| POST | `/api/v1/sites/{siteId}/pwa/analyze` | Trigger new PWA analysis |
| POST | `/api/v1/sites/{siteId}/pwa/refresh` | Refresh existing PWA analysis |

---

## Test Categories

### 1. SHOW Endpoint Tests (GET /api/v1/sites/{siteId}/pwa)

| ID | Test | Description |
|----|------|-------------|
| SH-001 | Returns analysis data | Returns full PWA analysis for site with existing analysis |
| SH-002 | Returns 404 if no analysis | Returns 404 with suggestion to trigger analysis |
| SH-003 | Returns 404 if site not found | Site doesn't exist |
| SH-004 | Returns 403 if not authorized | User doesn't own the site |
| SH-005 | Returns 401 if unauthenticated | No auth token provided |
| SH-006 | Returns all required fields | hasManifest, manifestUrl, manifestValid, pwaScore, readinessLevel |
| SH-007 | Returns fields breakdown | Manifest fields with presence and values |
| SH-008 | Returns icons validation | Icons validation results |
| SH-009 | Returns recommendations array | PWA suggestions |
| SH-010 | Returns analyzedAt timestamp | ISO 8601 format |
| SH-011 | Cache hit performance | Cached response returns quickly |
| SH-012 | Cache refresh | Cache invalidated on new analysis |

### 2. ANALYZE Endpoint Tests (POST /api/v1/sites/{siteId}/pwa/analyze)

| ID | Test | Description |
|----|------|-------------|
| AN-001 | Dispatches analysis job | Returns 202 Accepted with jobId |
| AN-002 | Returns 404 if site not found | Site doesn't exist |
| AN-003 | Returns 403 if not authorized | User doesn't own the site |
| AN-004 | Returns 401 if unauthenticated | No auth token provided |
| AN-005 | Rate limited within limit | 10 analyses per site per hour allowed |
| AN-006 | Rate limited over limit | Returns 429 when limit exceeded |
| AN-007 | Rate limit per site | Different sites have independent limits |
| AN-008 | Rate limit reset | Limit resets after 1 hour |
| AN-009 | Job ID format | UUID format for traceability |
| AN-010 | Queue dispatch | Job dispatched to correct queue |

### 3. REFRESH Endpoint Tests (POST /api/v1/sites/{siteId}/pwa/refresh)

| ID | Test | Description |
|----|------|-------------|
| RF-001 | Refreshes existing analysis | Re-analyzes site with existing analysis |
| RF-002 | Returns 202 Accepted | Same as analyze endpoint |
| RF-003 | Returns 404 if site not found | Site doesn't exist |
| RF-004 | Returns 403 if not authorized | User doesn't own the site |
| RF-005 | Returns 401 if unauthenticated | No auth token provided |
| RF-006 | Rate limited | Same 10/hour limit as analyze |
| RF-007 | Cache invalidated | Old cached data cleared |
| RF-008 | Job ID returned | For tracking refresh progress |

### 4. Authorization Tests

| ID | Test | Description |
|----|------|-------------|
| AZ-001 | Owner can access | Site owner has full access |
| AZ-002 | Non-owner blocked | Cross-user access returns 403 |
| AZ-003 | Unauthenticated blocked | Returns 401 for all endpoints |
| AZ-004 | Site user_id matches | Authorization based on site.user_id |

### 5. Security Tests

| ID | Test | Description |
|----|------|-------------|
| SEC-001 | SQL injection in siteId | Route constraint blocks non-numeric |
| SEC-002 | XSS in manifest data | Data properly escaped |
| SEC-003 | IDOR prevention | Cannot access other users' analyses |
| SEC-004 | CSRF protection | POST endpoints require CSRF token |
| SEC-005 | Input validation | siteId must be numeric |

### 6. Edge Cases

| ID | Test | Description |
|----|------|-------------|
| EC-001 | Site with no manifest | Returns analysis showing no manifest |
| EC-002 | Invalid manifest | Returns analysis with validation errors |
| EC-003 | Partial manifest | Missing required fields flagged |
| EC-004 | Large manifest JSON | Handles 10KB+ manifests |
| EC-005 | Unicode in manifest | Handles non-ASCII characters |
| EC-006 | Null fields | Handles null manifest_json gracefully |
| EC-007 | Empty icons array | Handles sites with no icons |
| EC-008 | Concurrent requests | Race condition handling |
| EC-009 | Very long manifest_url | URL length validation |
| EC-010 | Negative score capping | Score never negative |

### 7. Integration Tests

| ID | Test | Description |
|----|------|-------------|
| INT-001 | Full analyze workflow | Analyze → poll → get results |
| INT-002 | Cache integration | Cache hit/miss behavior |
| INT-003 | Queue integration | Job dispatch and processing |
| INT-004 | Repository integration | Data stored correctly |
| INT-005 | Service integration | All services work together |

### 8. Performance Tests

| ID | Test | Description |
|----|------|-------------|
| PF-001 | GET response time | < 500ms for cached response |
| PF-002 | POST response time | < 200ms for job dispatch |
| PF-003 | Large manifest handling | < 1s for 10KB manifest |

---

## Response Structures

### GET Response (Success - 200)

```json
{
  "success": true,
  "data": {
    "hasManifest": true,
    "manifestUrl": "/manifest.json",
    "manifestValid": true,
    "pwaScore": 85,
    "readinessLevel": "enhanced",
    "fields": {
      "name": { "present": true, "value": "My App" },
      "short_name": { "present": true, "value": "MyApp" },
      "start_url": { "present": true, "value": "/" },
      "display": { "present": true, "value": "standalone" },
      "background_color": { "present": true, "value": "#ffffff" },
      "theme_color": { "present": true, "value": "#2563eb" },
      "description": { "present": false, "value": null }
    },
    "icons": {
      "total": 3,
      "valid": 2,
      "required": {
        "192x192": { "present": true },
        "512x512": { "present": true },
        "180x180": { "present": false }
      },
      "errors": [
        { "size": "180x180", "error": "Icon not found", "severity": "warning" }
      ]
    },
    "recommendations": [
      {
        "type": "missing_field",
        "field": "description",
        "message": "Add a description to your manifest",
        "priority": "medium"
      }
    ],
    "analyzedAt": "2026-03-01T10:00:00Z"
  }
}
```

### GET Response (No Analysis - 404)

```json
{
  "success": false,
  "error": {
    "code": "ANALYSIS_NOT_FOUND",
    "message": "No PWA analysis found for this site.",
    "suggestion": "POST /api/v1/sites/{siteId}/pwa/analyze to trigger an analysis."
  }
}
```

### POST Response (Accepted - 202)

```json
{
  "success": true,
  "data": {
    "jobId": "pwa-analyze-550e8400-e29b-41d4-a716-446655440000",
    "status": "queued",
    "message": "PWA analysis queued for processing."
  }
}
```

### Rate Limit Response (429)

```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Too many analysis requests. Please try again later.",
    "retryAfter": 1800
  }
}
```

---

## Implementation Requirements

### Controller Location
`backend/app/Http/Controllers/Api/V1/PWAController.php`

### Routes (routes/api.php)
```php
Route::prefix('v1')->middleware(['auth'])->group(function () {
    Route::get('sites/{siteId}/pwa', [PWAController::class, 'show']);
    Route::post('sites/{siteId}/pwa/analyze', [PWAController::class, 'analyze']);
    Route::post('sites/{siteId}/pwa/refresh', [PWAController::class, 'refresh']);
});
```

### Dependencies
- `PWAAnalysisRepository` - Data access
- `PWAScorerService` - Score calculation (TASK-24-6)
- `PWASuggestionService` - Recommendations (TASK-24-7)
- `ManifestFetcherService` - Manifest retrieval (TASK-24-3)
- `ManifestValidatorService` - Validation (TASK-24-4)
- `IconValidatorService` - Icon validation (TASK-24-5)

### Rate Limiting
- Key: `pwa-analyze:{siteId}`
- Limit: 10 requests per hour per site
- Implementation: Laravel Cache with TTL

### Caching
- Key: `pwa-analysis:{siteId}`
- TTL: 24 hours
- Invalidate on: analyze, refresh

---

## Test File Location
`backend/tests/Feature/Api/V1/PWAControllerTest.php`

---

Last updated: 2026-03-01
