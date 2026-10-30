# TEST-6-10: Sites API Endpoints

## Task Reference
- Task: [TASK-6-10](../TASKS/TASK-6-10.md)
- Specification: [SPEC-006](../SPECIFICATIONS/SPEC-006.md)
- Feature: [FEAT-006](../FEATURES/FEAT-006.md)

## Overview
Test suite for the Sites REST API endpoint. Tests cover endpoint functionality, user isolation, keyword count inclusion, pagination, rate limiting, authentication, authorization, and security scenarios.

## Test Files

| File | Location | Description |
|------|----------|-------------|
| SitesApiControllerTest.php | `backend/tests/Feature/Api/V1/` | Feature tests for Sites API endpoint |

## Test Scenarios

### Success Cases (S-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| S-01 | List all sites for user | GET /api/v1/sites with valid API key | 200, array of sites with keyword_count |
| S-02 | Empty list (no sites) | GET /api/v1/sites with user having no sites | 200, empty data array |
| S-03 | Single site with keywords | User has 1 site with 50 keywords | 200, keyword_count = 50 |
| S-04 | Multiple sites | User has 3 sites | 200, 3 sites returned |
| S-05 | Keyword count = 0 | Site with no keywords | 200, keyword_count = 0 |
| S-06 | Pagination default | 25 sites exist | 200, first 20 returned |
| S-07 | Pagination custom page | page=2, per_page=10 | 200, correct page data |
| S-08 | Pagination meta | Multiple pages | Correct total, page, per_page, total_pages |
| S-09 | Pagination max per_page | per_page=200 | Capped to 100 |
| S-10 | Site fields included | Valid request | id, url, name, keyword_count, created_at |

### Authentication Tests (A-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| A-01 | Valid API key | Bearer token with sites:read | 200 OK |
| A-02 | Missing Authorization header | No Bearer token | 401, code: UNAUTHORIZED |
| A-03 | Invalid API key | Invalid key value | 401, code: INVALID_API_KEY |
| A-04 | Revoked API key | Key with revoked_at set | 401, code: INVALID_API_KEY |
| A-05 | Expired API key | Key with expires_at in past | 401, code: INVALID_API_KEY |
| A-06 | Wrong permissions | Key without sites:read | 403, code: FORBIDDEN |

### Authorization Tests (Z-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| Z-01 | Own sites | User's own sites | 200, returns user's sites only |
| Z-02 | User isolation | Multiple users with sites | Each user sees only their sites |
| Z-03 | Cross-user access | User A tries User B's sites | User A sees 0 of User B's sites |

### Validation Tests (V-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| V-01 | Invalid page (negative) | page=-1 | 422 validation error |
| V-02 | Invalid page (zero) | page=0 | 422 validation error |
| V-03 | Invalid page (non-numeric) | page=abc | 422 validation error |
| V-04 | Invalid per_page (negative) | per_page=-5 | 422 validation error |
| V-05 | Invalid per_page (zero) | per_page=0 | 422 validation error |
| V-06 | Invalid per_page (non-numeric) | per_page=abc | 422 validation error |
| V-07 | per_page exceeds max | per_page=500 | Capped to 100, returns 200 |

### Edge Cases (E-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| E-01 | Site with many keywords | Site with 500 keywords | keyword_count = 500 |
| E-02 | Site with paused keywords | Mix of active/paused keywords | keyword_count = total (all statuses) |
| E-03 | Site with deleted keywords | Some keywords with status=deleted | keyword_count excludes deleted |
| E-04 | Large page number | page=9999 | 200, empty data array |
| E-05 | Created_at ISO format | Valid request | ISO 8601 date format |
| E-06 | Site without name | Site with name=null | Returns null for name |
| E-07 | Site with long URL | URL at max length (2083 chars) | Returns successfully |
| E-08 | Site with special chars in name | Name with unicode/emoji | Returns correctly encoded |

### Security Tests (SEC-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SEC-01 | SQL injection in page | page=1; DROP TABLE sites; | 422 validation error |
| SEC-02 | SQL injection in per_page | per_page=1; SELECT * | 422 validation error |
| SEC-03 | IDOR prevention | User A's key querying sites | Only User A's sites returned |
| SEC-04 | XSS in site name | name with `<script>` | Sanitized or escaped in response |

### Rate Limiting Tests (RL-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RL-01 | Within rate limit | Request within limit | 200, rate limit headers present |
| RL-02 | Rate limit exceeded | 61st request (limit=60) | 429, code: RATE_LIMIT_EXCEEDED |
| RL-03 | Rate limit per API key | Two different keys | Each has independent limit |
| RL-04 | Rate limit headers | Any request | X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset |

### Integration Tests (INT-*)

| ID | Scenario | Description |
|----|----------|-------------|
| INT-01 | Full sites list flow | Create sites, add keywords, list sites, verify counts |
| INT-02 | User isolation flow | Create sites for multiple users, verify each sees only their own |

## Mock Data

### Sample Site Response
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "url": "https://example.com",
      "name": "Example Site",
      "keyword_count": 50,
      "created_at": "2026-02-01T00:00:00Z"
    }
  ],
  "meta": {
    "total": 1,
    "page": 1,
    "per_page": 20,
    "total_pages": 1
  }
}
```

### Sample Error Response
```json
{
  "success": false,
  "error": {
    "code": "UNAUTHORIZED",
    "message": "Missing or invalid API key."
  }
}
```

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Success Cases | 100% | 10 |
| Authentication | 100% | 6 |
| Authorization | 100% | 3 |
| Validation | 100% | 7 |
| Edge Cases | 100% | 8 |
| Security | 100% | 4 |
| Rate Limiting | 100% | 4 |
| Integration | 100% | 2 |
| **Total** | **100%** | **44** |

## Test Execution

```bash
# Run all Sites API tests
cd backend
php artisan test --filter=SitesApiControllerTest

# Run with coverage
php artisan test --filter=SitesApiControllerTest --coverage

# Run specific test
php artisan test --filter=test_list_all_sites_for_user
```

## Dependencies

- TASK-6-4: API Key Management (API keys table, ApiKey model)
- TASK-1-1: Sites table and Site model
- TASK-1-1: Keywords table and Keyword model

## Notes for Developer

### Route Configuration
```php
Route::get('sites', [SitesApiController::class, 'index'])
    ->middleware('api-key.auth:sites:read');
```

### Controller Implementation Notes
1. Query sites where `user_id` matches the API key's user
2. Include `keyword_count` via subquery or eager loading with COUNT
3. Exclude keywords with `status = 'deleted'` from count
4. Support pagination with default 20 per page, max 100
5. Return ISO 8601 formatted `created_at`

### Response Format
- Success: `{ "success": true, "data": [...], "meta": {...} }`
- Error: `{ "success": false, "error": { "code": "...", "message": "..." } }`

### Authorization
- Middleware: `api-key.auth:sites:read`
- Permission required: `sites:read`
- User isolation: Filter by `user_id` from API key

---

## Comments

### [2026-02-28 10:35] PreTester: Tests Coverage In Progress
- Creating test documentation and test scripts
- Endpoint: GET /api/v1/sites
- Features to test: User isolation, keyword count, pagination, rate limiting
- Following test patterns from KeywordsApiControllerTest and RankingsApiControllerTest

### [2026-02-28 10:40] PreTester: Tests Complete
- Created 43 test cases covering:
  - 10 success cases (S-*)
  - 6 authentication tests (A-*)
  - 3 authorization tests (Z-*)
  - 7 validation tests (V-*)
  - 8 edge cases (E-*)
  - 4 security tests (SEC-*)
  - 4 rate limiting tests (RL-*)
  - 2 integration tests (INT-*)
- Test file: `backend/tests/Feature/Api/V1/SitesApiControllerTest.php`
- Status: Tests are ready for Developer implementation
- All tests expect 404 (route not found) until controller is implemented
- Task status updated to `To Develop`
