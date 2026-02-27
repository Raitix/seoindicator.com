# TEST-5-8: Generate Suggestions Endpoint Tests

## Task Reference
- **Task**: [TASK-5-8](../TASKS/TASK-5-8.md)
- **Specification**: [SPEC-005](../SPECIFICATIONS/SPEC-005.md)
- **Feature**: [FEAT-005](../FEATURES/FEAT-005.md)

## Overview
Feature tests for the POST /api/v1/sites/:siteId/suggestions/generate endpoint. This endpoint triggers the suggestion aggregation service asynchronously via Laravel queue.

---

## Test Files

| File | Type | Description |
|------|------|-------------|
| `backend/tests/Feature/Api/V1/SuggestionControllerTest.php` | Feature | API endpoint tests (existing) |
| `backend/tests/Unit/Http/Requests/GenerateSuggestionsRequestTest.php` | Unit | Form request validation tests |
| `backend/tests/Feature/Jobs/GenerateSuggestionsJobTest.php` | Feature | Queue job tests |

---

## Test Scenarios

### 1. Happy Path Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GEN-001 | Generate with valid data | seedKeyword: "seo", sources: ["question"] | 202, {jobId, status: "queued"} |
| GEN-002 | Generate with all sources | sources: ["question", "suggest"] | 202, job dispatched |
| GEN-003 | Generate with maxResults | maxResults: 50 | 202, maxResults passed to job |
| GEN-004 | Generate with min maxResults | maxResults: 1 | 202, valid |
| GEN-005 | Generate with max maxResults | maxResults: 100 | 202, valid |

### 2. Validation Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| VAL-001 | Missing seedKeyword | {} | 422, validation error |
| VAL-002 | Empty seedKeyword | seedKeyword: "" | 422, validation error |
| VAL-003 | seedKeyword too long | seedKeyword: 501 chars | 422, validation error |
| VAL-004 | Missing sources | seedKeyword: "seo" | 422, validation error |
| VAL-005 | Empty sources array | sources: [] | 422, validation error |
| VAL-006 | Invalid source type | sources: ["invalid"] | 422, validation error |
| VAL-007 | maxResults below min | maxResults: 0 | 422, validation error |
| VAL-008 | maxResults above max | maxResults: 101 | 422, validation error |
| VAL-009 | maxResults negative | maxResults: -1 | 422, validation error |
| VAL-010 | maxResults non-integer | maxResults: "abc" | 422, validation error |

### 3. Authorization Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AUTH-001 | Unauthenticated user | No auth token | 401 Unauthorized |
| AUTH-002 | Wrong user (not site owner) | Other user's site | 403 Forbidden |
| AUTH-003 | Non-existent site | siteId: 99999 | 404 Not Found |
| AUTH-004 | Site owner | Correct user | 202 Accepted |

### 4. Rate Limiting Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RATE-001 | Within rate limit | 5 requests within hour | All 202 |
| RATE-002 | At rate limit | 10 requests within hour | All 202 |
| RATE-003 | Exceeds rate limit | 11th request within hour | 429 Too Many Requests |
| RATE-004 | Rate limit reset | After 1 hour | Counter reset, 202 |
| RATE-005 | Rate limit per site | Different sites same user | Independent limits |

### 5. Async Job Dispatch Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JOB-001 | Job dispatched to queue | Valid request | Job queued with correct payload |
| JOB-002 | Job payload contains seedKeyword | Valid request | Job has seedKeyword |
| JOB-003 | Job payload contains sources | Valid request | Job has sources array |
| JOB-004 | Job payload contains siteId | Valid request | Job has siteId |
| JOB-005 | Job payload contains maxResults | Valid request | Job has maxResults (or default) |
| JOB-006 | Job has unique ID | Valid request | jobId is unique UUID |

### 6. Edge Case Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EDGE-001 | Unicode seedKeyword | seedKeyword: "seo 日本語" | 202, accepted |
| EDGE-002 | Special characters | seedKeyword: "seo-tools_2026" | 202, accepted |
| EDGE-003 | Very long seedKeyword | seedKeyword: 500 chars | 202, accepted (if under limit) |
| EDGE-004 | SQL injection attempt | seedKeyword: "'; DROP TABLE --" | 202, sanitized |
| EDGE-005 | XSS attempt | seedKeyword: "<script>alert(1)</script>" | 202, sanitized |
| EDGE-006 | Concurrent requests | Multiple simultaneous requests | Each gets unique jobId |
| EDGE-007 | Sources deduplication | sources: ["question", "question"] | Deduplicated or handled |

---

## Mock Data

### Valid Request Body
```json
{
  "seedKeyword": "best seo tools",
  "sources": ["question", "suggest"],
  "maxResults": 50
}
```

### Minimal Request Body
```json
{
  "seedKeyword": "seo",
  "sources": ["question"]
}
```

### Expected Response (202)
```json
{
  "success": true,
  "data": {
    "jobId": "suggest-abc123-def456",
    "status": "queued"
  }
}
```

### Validation Error Response (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "seedKeyword": ["The seed keyword field is required."]
  }
}
```

### Rate Limit Error Response (429)
```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Too many generation requests. Please try again later.",
    "retryAfter": 3600
  }
}
```

---

## Coverage Targets

| Component | Target | Status |
|-----------|--------|--------|
| Endpoint returns 202 | 100% | ✅ Existing |
| Validation - seedKeyword | 100% | ✅ Existing |
| Validation - sources | 100% | ✅ Existing |
| Validation - maxResults | 100% | ⏳ To Add |
| Authorization | 100% | ✅ Existing (partial) |
| Rate limiting | 100% | ⏳ To Add |
| Job dispatch | 100% | ⏳ To Add |
| Edge cases | 100% | ⏳ To Add |

---

## Test Execution

```bash
# Run all suggestion controller tests
cd backend
php artisan test tests/Feature/Api/V1/SuggestionControllerTest.php

# Run specific test method
php artisan test --filter test_generate_suggestions

# Run with coverage
php artisan test tests/Feature/Api/V1/SuggestionControllerTest.php --coverage

# Run rate limiting tests
php artisan test --filter "rate"
```

---

## Dependencies

- PHPUnit
- Laravel Queue component
- App\Http\Requests\GenerateSuggestionsRequest
- App\Jobs\GenerateSuggestionsJob
- App\Models\Site
- App\Models\User

---

## Notes for Developer

### Implementation Requirements

1. **Form Request Validation**: Create `GenerateSuggestionsRequest` form request with:
   - `seedKeyword`: required, string, max:500
   - `sources`: required, array, min:1
   - `sources.*`: in:question,suggest,competitor,related
   - `maxResults`: nullable, integer, min:1, max:100

2. **Controller Method**:
   ```php
   public function generate(GenerateSuggestionsRequest $request, Site $site)
   {
       // Authorization handled by policy
       $this->authorize('update', $site);
       
       // Rate limiting via middleware or explicit check
       
       // Dispatch job
       $jobId = (string) Str::uuid();
       GenerateSuggestionsJob::dispatch(
           $site->id,
           $request->seedKeyword,
           $request->sources,
           $request->maxResults ?? 50,
           $jobId
       );
       
       return response()->json([
           'success' => true,
           'data' => [
               'jobId' => $jobId,
               'status' => 'queued',
           ],
       ], 202);
   }
   ```

3. **Rate Limiting**: Use Laravel's built-in rate limiting:
   ```php
   // In RouteServiceProvider
   RateLimiter::for('suggestions-generate', function (Request $request) {
       return Limit::perHour(10)->by($request->route('site')->id);
   });
   ```

4. **Job Implementation**: Create `GenerateSuggestionsJob` that:
   - Implements `ShouldQueue`
   - Has unique jobId tracking
   - Calls `SuggestionService::generateSuggestions()`
   - Handles failures gracefully

5. **Queue Configuration**: Ensure queue driver is configured for async processing.

---

## Comments

### [2026-02-27 10:58] PreTester: Test Design Started
- Created test documentation TEST-5-8.md
- Identified existing tests in SuggestionControllerTest.php
- Missing tests: maxResults validation, rate limiting, job dispatch verification
- Status: Tests Coverage In Progress
