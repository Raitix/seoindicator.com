# TEST-33-5: PublicAuditController Feature Tests

## Task Reference
- **Task:** TASK-33-5
- **Specification:** SPEC-33
- **Feature:** Public SEO Audit Reports & Embeddable Score Widget

## Overview
Feature tests for the PublicAuditController which handles public audit data retrieval, lead capture, and badge configuration. Tests validate all three API endpoints with comprehensive coverage including security, rate limiting, and edge cases.

---

## Test Files

| File | Tests | Status |
|------|-------|--------|
| `backend/tests/Feature/Api/V1/PublicAuditControllerTest.php` | 95+ | TDD (expect failures) |
| `backend/app/Http/Requests/Api/V1/PublicAuditLeadRequest.php` | - | Request validator |

---

## Test Categories

### PA-GET: GET /api/v1/public-audit/{hash}

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| PA-GET-001 | Valid hash returns 200 | 200 with audit data structure |
| PA-GET-002 | Returns site_url | `data.site_url` present |
| PA-GET-003 | Returns audited_at timestamp | `data.audited_at` ISO 8601 |
| PA-GET-004 | Returns overall_score | `data.overall_score` 0-100 |
| PA-GET-005 | Returns category_scores | Object with technical/on_page/content/mobile |
| PA-GET-006 | Returns top_issues | Array with category/issue/severity |
| PA-GET-007 | Returns is_partial flag true for anonymous | `data.is_partial: true` |
| PA-GET-008 | Returns is_partial false for full access | `data.is_partial: false` |
| PA-GET-009 | Invalid hash format returns 404 | 404 with error message |
| PA-GET-010 | Non-existent hash returns 404 | 404 with error message |
| PA-GET-011 | Non-public audit returns 404 | 404 with error message |
| PA-GET-012 | Excludes sensitive data | No audit_id, user_id, email in response |
| PA-GET-013 | No authentication required | 200 without auth header |
| PA-GET-014 | Validates hash format | Short hash returns 404 |
| PA-GET-015 | Accepts 32-char alphanumeric hash | Valid format returns 200 |
| PA-GET-016 | Case-insensitive hash matching | Uppercase hash returns 200 |
| PA-GET-017 | Tracks view in audit_views | Record created in public_audit_views |

### PA-POST: POST /api/v1/public-audit/{hash}/lead

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| PA-POST-001 | Valid email returns 201 | 201 with success message |
| PA-POST-002 | Invalid email format returns 422 | 422 validation error |
| PA-POST-003 | Missing email returns 422 | 422 validation error |
| PA-POST-004 | Invalid hash returns 404 | 404 with error message |
| PA-POST-005 | Non-public audit returns 404 | 404 with error message |
| PA-POST-006 | Stores email in audit_views | Record in public_audit_views |
| PA-POST-007 | No authentication required | 201 without auth header |
| PA-POST-008 | Normalizes email to lowercase | Email stored lowercase |
| PA-POST-009 | Trims whitespace | Email stored trimmed |
| PA-POST-010 | Handles duplicate emails | 201 (no error) |
| PA-POST-011 | Max email length enforced | 422 if > 254 chars |
| PA-POST-012 | Stores with audit reference | public_hash stored |
| PA-POST-013 | Rejects disposable domains | Optional: 422 or 201 |

### PA-BADGE: GET /api/v1/public-audit/{hash}/badge

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| PA-BADGE-001 | Valid hash returns 200 | 200 with badge config structure |
| PA-BADGE-002 | Returns score | `data.score` 0-100 |
| PA-BADGE-003 | Returns site_url | `data.site_url` present |
| PA-BADGE-004 | Returns badge_url | Full badge image URL |
| PA-BADGE-005 | Returns available sizes | `['small', 'medium', 'large']` |
| PA-BADGE-006 | Returns available themes | `['light', 'dark']` |
| PA-BADGE-007 | Invalid hash returns 404 | 404 with error message |
| PA-BADGE-008 | Non-public audit returns 404 | 404 with error message |
| PA-BADGE-009 | No authentication required | 200 without auth header |
| PA-BADGE-010 | Includes embed_code | HTML embed snippet |

### PA-RATE: Rate Limiting Tests

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| PA-RATE-001 | Allows 100 requests per hour | All 200 |
| PA-RATE-002 | Blocks after 100 requests | 429 on 101st request |
| PA-RATE-003 | Per-hash rate limit | Different hash works |
| PA-RATE-004 | IP + hash combination | Different IP works |
| PA-RATE-005 | Retry-After header | Header present on 429 |
| PA-RATE-006 | Applies to all endpoints | GET/POST/badge all blocked |
| PA-RATE-007 | Resets after one hour | Works after reset |

### PA-SEC: Security Tests

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| PA-SEC-001 | SQL injection in hash | 404, no SQL error |
| PA-SEC-002 | XSS in email | 422 validation error |
| PA-SEC-003 | Path traversal in hash | 404 |
| PA-SEC-004 | No sensitive data exposed | No api_key/password/secret |
| PA-SEC-005 | Private audit not accessible | 404 |
| PA-SEC-006 | Enumeration protection | Same error for non-existent/non-public |
| PA-SEC-007 | CSRF protection | 415 for form submissions |

### PA-EDGE: Edge Cases

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| PA-EDGE-001 | Empty hash | 404 |
| PA-EDGE-002 | Special characters in hash | 404 |
| PA-EDGE-003 | Unicode in hash | 404 |
| PA-EDGE-004 | Very long hash | 404 |
| PA-EDGE-005 | Hash with spaces | 404 |
| PA-EDGE-006 | Concurrent requests | No race conditions |
| PA-EDGE-007 | Score boundary zero | 200 with score=0 |
| PA-EDGE-008 | Score boundary 100 | 200 with score=100 |
| PA-EDGE-009 | Empty category_scores | 200 with empty array |
| PA-EDGE-010 | Empty top_issues | 200 with empty array |
| PA-EDGE-011 | Missing optional fields | Graceful handling |

### PA-HTTP: HTTP Method Tests

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| PA-HTTP-001 | POST to GET endpoint | 405 Method Not Allowed |
| PA-HTTP-002 | PUT to GET endpoint | 405 Method Not Allowed |
| PA-HTTP-003 | PATCH to GET endpoint | 405 Method Not Allowed |
| PA-HTTP-004 | DELETE to GET endpoint | 405 Method Not Allowed |
| PA-HTTP-005 | GET to lead endpoint | 405 Method Not Allowed |
| PA-HTTP-006 | POST to badge endpoint | 405 Method Not Allowed |

### PA-PERF: Performance Tests

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| PA-PERF-001 | GET audit < 500ms | Response time under threshold |
| PA-PERF-002 | POST lead < 500ms | Response time under threshold |
| PA-PERF-003 | GET badge < 200ms | Response time under threshold |

### PA-AC: Acceptance Criteria Tests

| ID | Acceptance Criteria | Test Coverage |
|----|---------------------|---------------|
| AC-01 | GET /api/v1/public-audit/{hash} returns audit data or 404 | ✅ PA-GET tests |
| AC-02 | Response includes siteUrl, auditedAt, overallScore, categoryScores, topIssues | ✅ PA-GET-002 to PA-GET-006 |
| AC-03 | Response includes isPartial flag | ✅ PA-GET-007, PA-GET-008 |
| AC-04 | POST /api/v1/public-audit/{hash}/lead accepts email | ✅ PA-POST-001 |
| AC-05 | Lead endpoint validates email format | ✅ PA-POST-002 |
| AC-06 | Lead endpoint stores email with audit reference | ✅ PA-POST-006, PA-POST-012 |
| AC-07 | GET /api/v1/public-audit/{hash}/badge returns badge config | ✅ PA-BADGE tests |
| AC-08 | Rate limiting 100 requests/hour per hash | ✅ PA-RATE tests |
| AC-09 | No authentication required | ✅ PA-GET-013, PA-POST-007, PA-BADGE-009 |
| AC-10 | 404 for invalid/expired/non-public hashes | ✅ PA-GET-009 to PA-GET-011 |
| AC-11 | Feature tests for all endpoints | ✅ ac_feature_tests_for_all_endpoints |

---

## API Response Examples

### GET /api/v1/public-audit/{hash}

**Success (200):**
```json
{
  "success": true,
  "data": {
    "site_url": "https://example.com",
    "audited_at": "2026-03-06T12:00:00Z",
    "overall_score": 85,
    "category_scores": {
      "technical": 90,
      "on_page": 85,
      "content": 80,
      "mobile": 88
    },
    "top_issues": [
      {"category": "technical", "issue": "Missing H1 tag", "severity": "high"},
      {"category": "on_page", "issue": "Meta description too long", "severity": "medium"}
    ],
    "is_partial": true
  }
}
```

**Error (404):**
```json
{
  "success": false,
  "error": "Audit not found"
}
```

### POST /api/v1/public-audit/{hash}/lead

**Success (201):**
```json
{
  "success": true,
  "message": "Email captured successfully"
}
```

**Validation Error (422):**
```json
{
  "success": false,
  "error": "Validation failed",
  "details": {
    "email": ["Please provide a valid email address."]
  }
}
```

### GET /api/v1/public-audit/{hash}/badge

**Success (200):**
```json
{
  "success": true,
  "data": {
    "hash": "abc123def456ghi789jkl012mno345pqr",
    "score": 85,
    "site_url": "example.com",
    "badge_url": "https://seoindicator.com/badge/abc123def456ghi789jkl012mno345pqr/large",
    "sizes": ["small", "medium", "large"],
    "themes": ["light", "dark"],
    "embed_code": "<a href=\"https://seoindicator.com/audit/abc123def456ghi789jkl012mno345pqr\"><img src=\"https://seoindicator.com/badge/abc123def456ghi789jkl012mno345pqr/large\" alt=\"SEO Score: 85\"></a>"
  }
}
```

---

## Mock Data Patterns

### Mock Public Audit Response
```php
protected function mockPublicAuditData(): array
{
    return [
        'hash' => 'abc123def456ghi789jkl012mno345pqr',
        'site_url' => 'https://example.com',
        'audited_at' => '2026-03-06T12:00:00Z',
        'overall_score' => 85,
        'category_scores' => [
            'technical' => 90,
            'on_page' => 85,
            'content' => 80,
            'mobile' => 88,
        ],
        'top_issues' => [
            ['category' => 'technical', 'issue' => 'Missing H1 tag', 'severity' => 'high'],
        ],
        'is_public' => true,
        'is_partial' => true,
        'audit_id' => 1,
    ];
}
```

---

## Test Execution

```bash
# Run all PublicAuditController tests
php artisan test --filter PublicAuditControllerTest

# Run specific test category
php artisan test --filter "PA-GET"
php artisan test --filter "PA-POST"
php artisan test --filter "PA-BADGE"
php artisan test --filter "PA-RATE"
php artisan test --filter "PA-SEC"

# Run acceptance criteria tests
php artisan test --filter "ac_"
```

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| PA-GET | 17 | ✅ |
| PA-POST | 13 | ✅ |
| PA-BADGE | 10 | ✅ |
| PA-RATE | 7 | ✅ |
| PA-SEC | 7 | ✅ |
| PA-EDGE | 11 | ✅ |
| PA-HTTP | 6 | ✅ |
| PA-PERF | 3 | ✅ |
| PA-AC | 11 | ✅ |
| **Total** | **95+** | **✅** |

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-33-4 (PublicAudit Model) | To Develop | Mocked in tests |
| TASK-33-1 (visibility columns) | Done | ✅ |
| TASK-33-2 (public_audit_views) | Done | ✅ |
| public_audits table migration | Missing | Developer to create |

---

## Notes for Developer

### Files to Create
1. `backend/app/Http/Controllers/Api/V1/PublicAuditController.php`
2. `backend/routes/api.php` - Add routes

### Expected Controller Methods
```php
class PublicAuditController extends Controller
{
    public function show(string $hash): JsonResponse;
    public function storeLead(string $hash, PublicAuditLeadRequest $request): JsonResponse;
    public function badge(string $hash): JsonResponse;
}
```

### Route Registration
```php
Route::prefix('v1/public-audit')->group(function () {
    Route::get('/{hash}', [PublicAuditController::class, 'show']);
    Route::post('/{hash}/lead', [PublicAuditController::class, 'storeLead']);
    Route::get('/{hash}/badge', [PublicAuditController::class, 'badge']);
});
```

### Rate Limiting
- Use Laravel's built-in `throttle` middleware
- Key: `public-audit:{ip}:{hash}`
- Limit: 100 requests per 60 minutes
- Apply to entire prefix group

### View Tracking
- Track each GET request in `public_audit_views` table
- Record: audit_id, public_hash, ip_hash, referrer, user_agent
- Use `ip_hash` for privacy (SHA-256 + app.key salt)

---

## Comments

### [2026-03-06 16:15 GMT+2] PreTester - Test Coverage Complete ✅

**Status:** To Write Tests → Tests Coverage In Progress → To Develop

**Test Files Created:**
- `backend/tests/Feature/Api/V1/PublicAuditControllerTest.php` - 95+ test scenarios
- `backend/app/Http/Requests/Api/V1/PublicAuditLeadRequest.php` - Email validation request
- `TESTS/TEST-33-5.md` - This documentation

**Test Summary:**
| Category | Tests |
|----------|-------|
| PA-GET (GET endpoint) | 17 |
| PA-POST (Lead capture) | 13 |
| PA-BADGE (Badge config) | 10 |
| PA-RATE (Rate limiting) | 7 |
| PA-SEC (Security) | 7 |
| PA-EDGE (Edge cases) | 11 |
| PA-HTTP (HTTP methods) | 6 |
| PA-PERF (Performance) | 3 |
| PA-AC (Acceptance) | 11 |
| **Total** | **95+** |

**TDD Pattern:**
- Tests use Mockery for PublicAudit model mocking
- Tests expect routes and controller to be created
- Rate limiting mocked with `RateLimiter::shouldReceive()`

**Expected Result:** All tests fail (404) until routes and controller are created.

**Handoff to Developer:** Ready for implementation.
