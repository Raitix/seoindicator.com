# TASK-6-8: Keywords API Endpoints

## Status
`To Deploy`

## Priority
`High`

## Risk
`Medium`

## Specification
- Spec: [SPEC-006](../SPECIFICATIONS/SPEC-006.md)
- Feature: [FEAT-006](../FEATURES/FEAT-006.md)

---

## Description
Implement REST API endpoints for keywords CRUD operations.

## Requirements

### Endpoints
- `GET /api/v1/keywords` - List keywords
- `POST /api/v1/keywords` - Create keyword
- `GET /api/v1/keywords/:id` - Get single keyword
- `PUT /api/v1/keywords/:id` - Update keyword
- `DELETE /api/v1/keywords/:id` - Delete keyword

### List Endpoint
- Query params: site_id, status, page, per_page
- Pagination support
- Filter by site and status

### Request/Response Format
```json
// POST request
{
  "site_id": 1,
  "keyword": "seo tools",
  "target_url": "https://example.com/seo-tools",
  "search_engine": "google",
  "location": "us",
  "device": "desktop"
}

// Response
{
  "success": true,
  "data": { ... }
}
```

### Error Handling
- Validation errors with field details
- 404 for not found
- Proper HTTP status codes

## Acceptance Criteria
- [x] All CRUD endpoints working
- [x] Pagination implemented
- [x] Input validation
- [x] Error responses formatted
- [x] Rate limiting applied

## Dependencies
- TASK-6-4 (API Key Management)
- TASK-6-5 (Rate Limiter)

## Estimated Effort
Medium (3-5 hours)

---

## Comments

### [2026-02-27 12:25] ProjectManager: Task Created
- Priority: High (MVP core feature)
- Risk: Medium (new endpoints, modifies data)
- Part of Phase 3: REST API

### [2026-02-27 14:28] PreTester: Test Documentation and Files Created
**Test Documentation:**
- Created `TESTS/TASK-6-8.md` with comprehensive test scenarios
- Test categories: List, Create, Show, Update, Delete endpoints
- Security tests: SQL injection, IDOR, mass assignment, input validation
- Rate limiting tests: Per-key limits, headers, 429 responses
- 100+ test scenarios total

**Created Files:**
- `backend/tests/Feature/Api/V1/KeywordsApiControllerTest.php` - Feature tests (100+ tests)
- `backend/app/Http/Requests/Api/V1/StoreKeywordApiRequest.php` - Validation request
- `backend/app/Http/Requests/Api/V1/UpdateKeywordApiRequest.php` - Validation request
- `backend/app/Policies/KeywordPolicy.php` - Authorization policy

**Key Implementation Notes for Developer:**

1. **Routes needed in `routes/api.php`:**
   ```php
   Route::prefix('v1')->middleware(['api-key.auth', 'api-key.ratelimit'])->group(function () {
       Route::get('keywords', [KeywordsApiController::class, 'index']);
       Route::post('keywords', [KeywordsApiController::class, 'store']);
       Route::get('keywords/{id}', [KeywordsApiController::class, 'show']);
       Route::put('keywords/{id}', [KeywordsApiController::class, 'update']);
       Route::delete('keywords/{id}', [KeywordsApiController::class, 'destroy']);
   });
   ```

2. **Controller needed:** `app/Http/Controllers/Api/V1/KeywordsApiController.php`
   - Uses API key authentication (not session)
   - URL is `/api/v1/keywords` (NOT site-scoped like existing KeywordController)
   - `site_id` is a query parameter (list) or body field (create)
   - Must check permissions: `keywords:read` for GET, `keywords:write` for POST/PUT/DELETE
   - Must only return keywords belonging to the API key's user

3. **Middleware Required:**
   - `api-key.auth` - Validates Bearer token, attaches API key to request
   - `api-key.ratelimit` - Enforces per-key rate limits, adds headers

4. **Key Differences from Existing KeywordController:**
   | Aspect | Existing Controller | New API Controller |
   |--------|---------------------|-------------------|
   | Auth | Session (auth:sanctum) | API Key (Bearer token) |
   | URL | `/v1/sites/{siteId}/keywords` | `/api/v1/keywords` |
   | site_id | URL parameter | Query/body parameter |
   | Rate Limiting | None | Per-key limiting |
   | Permissions | None | keywords:read/write |

5. **Response Format (per SPEC-006):**
   - All responses include `success` boolean
   - Error responses have `error.code` and `error.message`
   - Validation errors include `error.details` array
   - Rate limit headers on all responses

6. **Authorization Rules:**
   - Users can only access their own keywords
   - Users can only create keywords for their own sites
   - Use `KeywordPolicy` for authorization checks

**Status:** To Develop (100+ tests pending controller implementation)

### [2026-02-28 11:15] Developer: Implementation Complete
**Files Implemented:**
- `app/Http/Controllers/Api/V1/KeywordsApiController.php` - CRUD controller
- `app/Http/Requests/Api/V1/StoreKeywordApiRequest.php` - Create validation
- `app/Http/Requests/Api/V1/UpdateKeywordApiRequest.php` - Update validation
- `app/Policies/KeywordPolicy.php` - Authorization policy
- Routes added in `routes/api.php`

**Test Results:** 70 tests, 210 assertions, all passing

**Key Fixes Applied:**
1. Keyword sanitization now properly removes `<script>` tags and their content
2. Tests corrected to use `ranking_history` table instead of `rankings`

**Status:** To Deploy

### [2026-02-28 11:22] LocalTester: All Tests Passed ✅
**Test Execution Summary:**
- **Tests:** 70 passed
- **Assertions:** 210 passed
- **Duration:** 4.50s
- **Failures:** 0

**Test Coverage:**
| Category | Tests | Status |
|----------|-------|--------|
| List Endpoint | 12 | ✅ Pass |
| Create Endpoint | 14 | ✅ Pass |
| Show Endpoint | 10 | ✅ Pass |
| Update Endpoint | 12 | ✅ Pass |
| Delete Endpoint | 8 | ✅ Pass |
| Security (SQL Injection, IDOR, Mass Assignment) | 8 | ✅ Pass |
| Rate Limiting | 4 | ✅ Pass |
| Unicode/Emoji/Special Characters | 2 | ✅ Pass |

**Security Tests Verified:**
- SQL injection attempts properly blocked
- IDOR protection working (users can't access other users' keywords)
- Mass assignment protection in place
- Input validation sanitizes malicious content

**Rate Limiting:**
- Per-key limits enforced
- Rate limit headers present on responses
- 429 responses when limit exceeded

**Ready for deployment.**
