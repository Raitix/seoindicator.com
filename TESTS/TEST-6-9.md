# TEST-6-9: Rankings API Endpoints

## Task Reference
- Task: [TASK-6-9](../TASKS/TASK-6-9.md)
- Spec: [SPEC-006](../SPECIFICATIONS/SPEC-006.md)
- Feature: [FEAT-006](../FEATURES/FEAT-006.md)

---

## Overview
Test suite for Rankings REST API endpoint. The Rankings API provides read-only access to keyword ranking history.

### Endpoint
- `GET /api/v1/keywords/:id/rankings` - Get ranking history for a keyword

### Authentication
- Bearer token (API Key)
- Requires `rankings:read` permission

### Rate Limiting
- Per-key rate limits (default: 60 req/min)
- Rate limit headers on all responses

---

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `backend/tests/Feature/Api/V1/RankingsApiControllerTest.php` | Feature | API endpoint tests |

---

## Test Scenarios

### Success Cases (R-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| R-01 | Get ranking history for keyword | Valid keyword_id | 200 with history array |
| R-02 | Empty history (no rankings) | Keyword without rankings | 200 with empty history |
| R-03 | Filter by from date | from=2026-01-01 | Rankings from date onwards |
| R-04 | Filter by to date | to=2026-02-01 | Rankings up to date |
| R-05 | Filter by date range | from & to | Rankings in range |
| R-06 | Limit results | limit=10 | Max 10 results |
| R-07 | Ordered by checked_at DESC | Default | Most recent first |
| R-08 | Include position change | Multiple rankings | position_change calculated |
| R-09 | Large dataset | 1000+ rankings | Pagination works |
| R-10 | Single ranking | One ranking entry | Returns single item |

### Authentication Tests (RA-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RA-01 | Valid API key | Bearer token | 200 |
| RA-02 | Missing Authorization | No header | 401 UNAUTHORIZED |
| RA-03 | Invalid API key | Wrong token | 401 INVALID_API_KEY |
| RA-04 | Revoked API key | Revoked key | 401 INVALID_API_KEY |
| RA-05 | Expired API key | Expired key | 401 INVALID_API_KEY |
| RA-06 | Wrong permissions | No rankings:read | 403 FORBIDDEN |

### Authorization Tests (RZ-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RZ-01 | Own keyword | User's keyword | 200 |
| RZ-02 | Other user's keyword | Another user's keyword | 403 FORBIDDEN |
| RZ-03 | Non-existent keyword | Invalid ID | 404 NOT_FOUND |

### Validation Tests (RV-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RV-01 | Invalid keyword_id | Non-numeric ID | 404 (route mismatch) |
| RV-02 | Invalid from date | from=not-a-date | 422 VALIDATION_ERROR |
| RV-03 | Invalid to date | to=not-a-date | 422 VALIDATION_ERROR |
| RV-04 | from > to | from=2026-02, to=2026-01 | 422 VALIDATION_ERROR |
| RV-05 | Negative limit | limit=-5 | 422 VALIDATION_ERROR |
| RV-06 | Limit too large | limit=10000 | 422 VALIDATION_ERROR |
| RV-07 | Limit zero | limit=0 | 422 VALIDATION_ERROR |
| RV-08 | Non-numeric limit | limit=abc | 422 VALIDATION_ERROR |

### Edge Cases (RE-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RE-01 | Position = 0 (not ranked) | Position 0 | Included with position 0 |
| RE-02 | Position = 100+ (deep ranking) | Position 150 | Included correctly |
| RE-03 | Same position multiple times | Consecutive same positions | All included |
| RE-04 | Very old ranking | Year old data | Included if in range |
| RE-05 | Future checked_at (clock skew) | Future date | Included (no validation) |
| RE-06 | Limit exceeds total | limit=100, total=5 | Returns all 5 |
| RE-07 | ISO date with timezone | 2026-02-27T14:00:00+02:00 | Parsed correctly |
| RE-08 | ISO date with milliseconds | 2026-02-27T14:00:00.123Z | Parsed correctly |

### Security Tests (SEC-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SEC-01 | SQL injection in keyword_id | id=1; DROP TABLE | 404 (no SQL executed) |
| SEC-02 | SQL injection in from | from='; DROP TABLE | 422 (sanitized) |
| SEC-03 | IDOR via keyword_id | Other user's keyword | 403 FORBIDDEN |
| SEC-04 | Path traversal in from | from=../../../etc | 422 VALIDATION_ERROR |

### Rate Limiting Tests (RL-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RL-01 | Within rate limit | Normal request | 200 + rate limit headers |
| RL-02 | Rate limit exceeded | 61st request | 429 RATE_LIMIT_EXCEEDED |
| RL-03 | Rate limit per API key | Different keys | Independent limits |
| RL-04 | Rate limit headers present | Any request | X-RateLimit-* headers |

---

## Mock Data

### Sample Keyword
```json
{
  "id": 1,
  "keyword": "seo tools",
  "site_id": 1,
  "user_id": 1,
  "status": "active"
}
```

### Sample Rankings
```json
[
  { "keyword_id": 1, "position": 5, "checked_at": "2026-02-27T14:00:00Z" },
  { "keyword_id": 1, "position": 7, "checked_at": "2026-02-26T14:00:00Z" },
  { "keyword_id": 1, "position": 6, "checked_at": "2026-02-25T14:00:00Z" },
  { "keyword_id": 1, "position": 8, "checked_at": "2026-02-24T14:00:00Z" }
]
```

### Sample Response
```json
{
  "success": true,
  "data": {
    "keyword_id": 1,
    "history": [
      {
        "position": 5,
        "checked_at": "2026-02-27T14:00:00Z"
      },
      {
        "position": 7,
        "checked_at": "2026-02-26T14:00:00Z"
      }
    ]
  }
}
```

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Success Cases | 100% | 10 |
| Authentication | 100% | 6 |
| Authorization | 100% | 3 |
| Validation | 100% | 8 |
| Edge Cases | 100% | 8 |
| Security | 100% | 4 |
| Rate Limiting | 100% | 4 |
| **Total** | **100%** | **43+** |

---

## Test Execution

```bash
# Run all Rankings API tests
php artisan test --filter=RankingsApiControllerTest

# Run specific test
php artisan test --filter=test_show_ranking_history

# Run with coverage
php artisan test --filter=RankingsApiControllerTest --coverage
```

---

## Dependencies

- TASK-6-4: API Key Management (authentication)
- TASK-6-5: Rate Limiter Middleware
- TASK-6-8: Keywords API (keyword model)

---

## Notes for Developer

### Routes Required (routes/api.php)
```php
Route::prefix('v1')->middleware(['api-key.auth', 'api-key.ratelimit'])->group(function () {
    Route::get('keywords/{id}/rankings', [RankingsApiController::class, 'index'])
        ->middleware('permission:rankings:read');
});
```

### Controller Required
`app/Http/Controllers/Api/V1/RankingsApiController.php`

### Key Implementation Details

1. **URL Structure**: `/api/v1/keywords/{id}/rankings` (nested under keywords)
2. **Authentication**: API Key via Bearer token (not session)
3. **Authorization**: 
   - Check `rankings:read` permission
   - Verify keyword belongs to API key's user
4. **Query Parameters**:
   - `from`: ISO 8601 date (optional)
   - `to`: ISO 8601 date (optional)
   - `limit`: integer 1-1000 (optional, default 100)
5. **Response Format**: See SPEC-006
6. **Ordering**: `checked_at DESC` (most recent first)
7. **Rate Limit Headers**: Must include on all responses

### Differences from Keywords API
- Read-only endpoint (no POST/PUT/DELETE)
- Nested under keywords resource
- Different permission (`rankings:read` instead of `keywords:read`)

---

## Comments

### [2026-02-27 14:35] PreTester: Test Documentation Created
- Created TEST-6-9.md with 43+ test scenarios
- Coverage categories: Success, Auth, Authorization, Validation, Edge Cases, Security, Rate Limiting
- Test file: RankingsApiControllerTest.php (to be created)
- Status: Tests Coverage In Progress
