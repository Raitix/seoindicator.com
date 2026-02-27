# TEST-5-9: Track/Dismiss Endpoints

## Task Reference
- **Task**: [TASK-5-9](../TASKS/TASK-5-9.md)
- **Specification**: [SPEC-005](../SPECIFICATIONS/SPEC-005.md)
- **Feature**: [FEAT-005](../FEATURES/FEAT-005.md)

## Overview
Tests for the Track/Dismiss API endpoints that enable users to add keyword suggestions to tracking or dismiss unwanted suggestions.

---

## Test Files

| File | Type | Description |
|------|------|-------------|
| `backend/tests/Feature/Api/V1/SuggestionControllerTest.php` | Feature | API endpoint tests for track/dismiss operations |

---

## API Endpoints Under Test

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/sites/{siteId}/suggestions/{suggestionId}/track` | Add single suggestion to tracking |
| POST | `/api/v1/sites/{siteId}/suggestions/bulk-track` | Add multiple suggestions to tracking |
| POST | `/api/v1/sites/{siteId}/suggestions/{suggestionId}/dismiss` | Dismiss a suggestion |

---

## Test Scenarios

### 1. Track Single Suggestion Endpoint (10 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| T-001 | Track valid suggestion | Valid siteId, valid suggestionId | 201, keyword created, suggestion.is_tracking=true |
| T-002 | Track returns keyword and suggestion | Valid request | Response includes { keyword, suggestion } |
| T-003 | Track already tracking suggestion | suggestion with is_tracking=true | 200, skipped with reason |
| T-004 | Track dismissed suggestion | suggestion with is_dismissed=true | 201, keyword created, clears dismissed status |
| T-005 | Track non-existent suggestion | Invalid suggestionId | 404 Not Found |
| T-006 | Track suggestion from different site | suggestion.site_id != siteId | 404 Not Found |
| T-007 | Track creates keyword with correct attributes | Valid request | Keyword matches suggestion.keyword, site_id, status=active |
| T-008 | Track suggestion non-existent site | Invalid siteId | 404 Not Found |
| T-009 | Track suggestion already exists as keyword | Keyword already in keywords table | 200, skipped with reason "Already tracking" |
| T-010 | Track with invalid request method | GET instead of POST | 405 Method Not Allowed |

### 2. Bulk Track Endpoint (12 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| B-001 | Bulk track multiple suggestions | { suggestionIds: [1, 2, 3] } | 201, added array with all 3 |
| B-002 | Bulk track returns added and skipped | Mixed valid/invalid IDs | 201, both arrays populated |
| B-003 | Bulk track empty array | { suggestionIds: [] } | 422 Validation Error |
| B-004 | Bulk track missing suggestionIds | {} | 422 Validation Error |
| B-005 | Bulk track invalid suggestionIds type | { suggestionIds: "string" } | 422 Validation Error |
| B-006 | Bulk track partial success | [valid, valid, invalid, valid] | 201, added: 3, skipped: 1 |
| B-007 | Bulk track all invalid | All non-existent IDs | 201, added: [], skipped: all |
| B-008 | Bulk track already tracking | Suggestions already tracking | 201, added: [], skipped: all |
| B-009 | Bulk track creates keywords in transaction | Valid IDs | All or nothing on DB error |
| B-010 | Bulk track large array | 100+ suggestionIds | 201, handles gracefully |
| B-011 | Bulk track from different site | Suggestions from other site | 201, skipped: all (security) |
| B-012 | Bulk track duplicates in array | { suggestionIds: [1, 1, 2] } | 201, deduplicates |

### 3. Dismiss Suggestion Endpoint (10 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| D-001 | Dismiss valid suggestion | Valid siteId, valid suggestionId | 200, suggestion.is_dismissed=true |
| D-002 | Dismiss creates dismissed_suggestions record | Valid request | Record in dismissed_suggestions table |
| D-003 | Dismiss already dismissed | suggestion with is_dismissed=true | 200, idempotent (no duplicate record) |
| D-004 | Dismiss tracking suggestion | suggestion with is_tracking=true | 200, can dismiss even if tracking |
| D-005 | Dismiss non-existent suggestion | Invalid suggestionId | 404 Not Found |
| D-006 | Dismiss suggestion from different site | suggestion.site_id != siteId | 404 Not Found |
| D-007 | Dismiss non-existent site | Invalid siteId | 404 Not Found |
| D-008 | Dismiss with invalid request method | GET instead of POST | 405 Method Not Allowed |
| D-009 | Dismiss returns updated suggestion | Valid request | Response includes full suggestion object |
| D-010 | Dismiss prevents future rediscovery | suggestion dismissed | Excluded from future discovery queries |

### 4. Authorization Tests (6 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| A-001 | Unauthenticated track | No auth token | 401 Unauthorized |
| A-002 | Unauthenticated bulk track | No auth token | 401 Unauthorized |
| A-003 | Unauthenticated dismiss | No auth token | 401 Unauthorized |
| A-004 | Unauthorized user track | Different user's site | 403 Forbidden |
| A-005 | Unauthorized user bulk track | Different user's site | 403 Forbidden |
| A-006 | Unauthorized user dismiss | Different user's site | 403 Forbidden |

### 5. Adversarial/Security Tests (8 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| S-001 | SQL injection in suggestionId | `1; DROP TABLE suggestions` | 404 or 400, no data loss |
| S-002 | SQL injection in bulk track | `["1; DELETE FROM keywords"]` | 422, sanitized input |
| S-003 | XSS in keyword data | `<script>alert(1)</script>` | Sanitized in response |
| S-004 | Race condition track same suggestion | Concurrent requests | Only one keyword created |
| S-005 | Bulk track with negative IDs | { suggestionIds: [-1, -2] } | 422 or skipped |
| S-006 | Bulk track with huge array | 10000 IDs | 422 or handled gracefully |
| S-007 | Invalid UUID in suggestionId | `abc-123-not-uuid` | 404 or 400 |
| S-008 | Malformed JSON body | Invalid JSON | 400 Bad Request |

---

## Response Structures

### Track Single Success (201)
```json
{
  "success": true,
  "data": {
    "keyword": {
      "id": 1,
      "keyword": "best seo tools 2026",
      "site_id": 1,
      "status": "active"
    },
    "suggestion": {
      "id": 1,
      "keyword": "best seo tools 2026",
      "is_tracking": true,
      "is_dismissed": false
    }
  }
}
```

### Track Already Exists (200)
```json
{
  "success": true,
  "data": {
    "keyword": null,
    "suggestion": {
      "id": 1,
      "keyword": "best seo tools 2026",
      "is_tracking": true
    },
    "skipped": true,
    "reason": "Already tracking"
  }
}
```

### Bulk Track Success (201)
```json
{
  "success": true,
  "data": {
    "added": [
      { "suggestionId": 1, "keywordId": 1, "keyword": "best seo tools" },
      { "suggestionId": 2, "keywordId": 2, "keyword": "seo software" }
    ],
    "skipped": [
      { "suggestionId": 3, "keyword": "seo", "reason": "Already tracking" },
      { "suggestionId": 99, "keyword": null, "reason": "Not found" }
    ],
    "summary": {
      "total": 4,
      "added": 2,
      "skipped": 2
    }
  }
}
```

### Dismiss Success (200)
```json
{
  "success": true,
  "data": {
    "id": 1,
    "keyword": "best seo tools 2026",
    "is_dismissed": true,
    "dismissed_at": "2026-02-27T10:00:00Z"
  }
}
```

### Validation Error (422)
```json
{
  "message": "The suggestion ids field is required.",
  "errors": {
    "suggestionIds": ["The suggestion ids field is required."]
  }
}
```

### Not Found (404)
```json
{
  "success": false,
  "message": "Suggestion not found"
}
```

### Unauthorized (401)
```json
{
  "message": "Unauthenticated."
}
```

### Forbidden (403)
```json
{
  "message": "This action is unauthorized."
}
```

---

## Database State Verification

### Track Operation
After successful track:
- `keywords` table: New record with `keyword`, `site_id`, `status='active'`
- `keyword_suggestions` table: `is_tracking = true` for the suggestion

### Bulk Track Operation
After successful bulk track:
- `keywords` table: New records for each added suggestion
- `keyword_suggestions` table: `is_tracking = true` for all added suggestions

### Dismiss Operation
After successful dismiss:
- `keyword_suggestions` table: `is_dismissed = true`
- `dismissed_suggestions` table: New record with `site_id`, `keyword`, `dismissed_at`

---

## Test Execution

```bash
# Run all suggestion controller tests
cd backend
php artisan test tests/Feature/Api/V1/SuggestionControllerTest.php

# Run specific test method
php artisan test --filter "test_track_suggestion"

# Run with coverage
php artisan test tests/Feature/Api/V1/SuggestionControllerTest.php --coverage
```

---

## Dependencies

- TASK-5-1 (keyword_suggestions and dismissed_suggestions tables)
- TASK-5-2 (KeywordSuggestion model and repository)
- FEAT-001 (keywords table for tracking)
- Site model and factory (existing)
- User model and factory (existing for auth tests)

---

## Notes for Developer

1. **Controller Location**: `backend/app/Http/Controllers/Api/V1/SuggestionController.php`

2. **Routes to Register**:
   ```php
   Route::post('sites/{site}/suggestions/{suggestion}/track', [SuggestionController::class, 'track']);
   Route::post('sites/{site}/suggestions/bulk-track', [SuggestionController::class, 'bulkTrack']);
   Route::post('sites/{site}/suggestions/{suggestion}/dismiss', [SuggestionController::class, 'dismiss']);
   ```

3. **Authorization**: Use Laravel Policies or middleware to ensure user owns the site.

4. **Transaction**: Bulk operations should use database transactions for atomicity.

5. **Service Layer**: Consider creating `SuggestionService` for business logic:
   - `trackSuggestion(siteId, suggestionId)`
   - `bulkTrackSuggestions(siteId, suggestionIds)`
   - `dismissSuggestion(siteId, suggestionId)`

6. **Keyword Creation**: When tracking, copy `keyword` and `site_id` from suggestion to keywords table.

7. **Idempotency**: Both track and dismiss should be idempotent (return 200 if already done).

8. **Validation**: Validate `suggestionIds` array is not empty and contains valid integers.

---

## Comments

### [2026-02-27 11:03] PreTester: Test Design In Progress
- Analyzing TASK-5-9 requirements
- Creating test documentation and test files
- Status: To Write Tests → Tests Coverage In Progress
