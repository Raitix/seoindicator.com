# TASK-6-9: Rankings API Endpoints

## Status
`To Develop`

## Priority
`High`

## Risk
`Low`

## Specification
- Spec: [SPEC-006](../SPECIFICATIONS/SPEC-006.md)
- Feature: [FEAT-006](../FEATURES/FEAT-006.md)

---

## Description
Implement REST API endpoints for reading ranking data.

## Requirements

### Endpoints
- `GET /api/v1/keywords/:id/rankings` - Get ranking history

### Query Parameters
- `from`: ISO date (start date)
- `to`: ISO date (end date)
- `limit`: number of results

### Response Format
```json
{
  "success": true,
  "data": {
    "keyword_id": 1,
    "history": [
      {
        "position": 5,
        "checked_at": "2026-02-19T17:00:00Z"
      }
    ]
  }
}
```

### Features
- Date range filtering
- Pagination for large datasets
- Efficient querying

## Acceptance Criteria
- [ ] Endpoint working
- [ ] Date range filtering
- [ ] Proper error handling
- [ ] Rate limiting applied

## Dependencies
- TASK-6-8 (Keywords API)

## Estimated Effort
Small (2-3 hours)

---

## Comments

### [2026-02-27 12:25] ProjectManager: Task Created
- Priority: High (MVP core feature)
- Risk: Low (read-only)
- Part of Phase 3: REST API

### [2026-02-27 14:35] PreTester: Test Documentation and Files Created
**Test Documentation:**
- Created `TESTS/TEST-6-9.md` with comprehensive test scenarios
- Test categories: Success, Authentication, Authorization, Validation, Edge Cases, Security, Rate Limiting
- 43+ test scenarios total

**Created Files:**
- `backend/tests/Feature/Api/V1/RankingsApiControllerTest.php` - Feature tests (43+ tests)
- `TESTS/TEST-6-9.md` - Test documentation

**Key Implementation Notes for Developer:**

1. **Route needed in `routes/api.php`:**
   ```php
   Route::prefix('v1')->middleware(['api-key.auth', 'api-key.ratelimit'])->group(function () {
       Route::get('keywords/{id}/rankings', [RankingsApiController::class, 'index'])
           ->middleware('permission:rankings:read');
   });
   ```

2. **Controller needed:** `app/Http/Controllers/Api/V1/RankingsApiController.php`
   - Single method: `index($keywordId)`
   - Uses API key authentication (not session)
   - URL is `/api/v1/keywords/{id}/rankings` (nested under keywords)
   - Must check permissions: `rankings:read`
   - Must only return rankings for keywords belonging to the API key's user

3. **Query Parameters:**
   - `from`: ISO 8601 date (optional) - filter from this date
   - `to`: ISO 8601 date (optional) - filter to this date
   - `limit`: integer 1-1000 (optional, default 100)

4. **Response Format (per SPEC-006):**
   ```json
   {
     "success": true,
     "data": {
       "keyword_id": 1,
       "history": [
         { "position": 5, "checked_at": "2026-02-19T17:00:00Z" },
         { "position": 7, "checked_at": "2026-02-18T17:00:00Z" }
       ]
     }
   }
   ```

5. **Key Implementation Points:**
   - Results ordered by `checked_at DESC` (most recent first)
   - Validate `from` <= `to` (return 422 if invalid range)
   - Default limit: 100, max limit: 1000
   - Rate limit headers on all responses
   - Authorization: User can only access rankings for their own keywords
   - Empty history should return empty array (not error)

6. **Dependencies:**
   - Requires `api-key.auth` middleware (TASK-6-4)
   - Requires `api-key.ratelimit` middleware (TASK-6-5)
   - Uses existing Keyword and Ranking models

**Status:** To Develop (43+ tests pending controller implementation)
