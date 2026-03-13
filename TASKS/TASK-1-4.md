# SPEC-001-4: Keywords CRUD API Endpoints

## STATUS
`To Test Prod`
## PRIORITY
`High`

## RISK
`Medium`

## Description
Implement REST API endpoints for keyword management (Create, Read, Update, Delete). Core API for the rank tracking feature.

## Acceptance Criteria
- [x] GET /api/v1/sites/:siteId/keywords - List keywords with pagination, sorting, filtering
- [x] POST /api/v1/sites/:siteId/keywords - Create keyword with validation
- [x] GET /api/v1/keywords/:id - Get single keyword with current position
- [x] PUT /api/v1/keywords/:id - Update keyword (targetUrl, status)
- [x] DELETE /api/v1/keywords/:id - Delete keyword (soft delete, optional history retention)
- [x] Query params: status, page, perPage, sortBy, sortOrder
- [x] Validation: keyword required, targetUrl valid URL, unique constraint
- [x] Proper HTTP status codes (200, 201, 204, 400, 404, 409)
- [x] Error responses follow standard format with code and message
- [x] API returns current position from latest ranking

## Dependencies
- SPEC-001-2 (keyword repository)

## Effort Estimate
`Medium` (4-6 hours)

## Technical Notes
- Use Laravel resource controllers or Fastify routes
- Include request validation classes
- Add keyword limit check per subscription tier (stub for now)
- Return position change from previous check

---

## Comments
_Agent communication log_

### [2026-02-20 09:44] Agent: SpecSystemAnalyst
Created task for keywords CRUD API. Core API functionality for rank tracking.

### [2026-02-22 18:10] Agent: Developer
Implemented KeywordController + Store/Update request validation, list/sort/paginate, create/update/delete (soft delete + optional history removal), and single keyword responses with position change. Routes wired under v1.

### [2026-02-22 23:50] Agent: Developer
Verified controller/routes/requests and keyword CRUD behavior already implemented in backend. Marked acceptance criteria complete and moved task to To Test Locally.

### [2026-02-22 23:59] Agent: LocalTester
Set status to Testing Locally and ran: `php artisan test --filter KeywordControllerTest`.
FAIL: All tests failed due to SQLite error during migrations: `SQLSTATE[HY000]: General error: 1 no such function: source_url` when creating index `idx_source_target` on `backlinks` (uses prefix length like `source_url(191)`). This syntax is MySQL-specific and breaks SQLite in-memory tests.
Action: Returning to Developer. Please adjust migration/index creation for SQLite (conditional index, or use full-column index in sqlite), or configure tests to use MySQL.
Status set back to `To Develop`.

### [2026-02-23 14:55] Agent: Developer
Re-ran tests: `php artisan test --filter KeywordControllerTest` - all 25 tests pass with 90 assertions.
The migration file `2026_02_21_220000_create_backlinks_table.php` already contains conditional logic:
- For MySQL: Uses `DB::raw('source_url(191)')` for prefix-length index
- For SQLite: Uses full-column index `['source_url', 'target_url']`
The previous test failure was likely due to an outdated migration state. Tests now pass cleanly.
Status set to `To Test Locally`.

### [2026-02-23 15:11] Agent: LocalTester
Ran full test suite for KeywordControllerTest: **25 tests PASS (90 assertions)**.

**Test Coverage:**
- CRUD operations: list, get, create, update, delete âœ“
- Pagination (page, perPage) âœ“
- Filtering by status âœ“
- Sorting (keyword, position, lastChecked) âœ“
- Validation (required keyword, valid URL, unique constraint) âœ“
- HTTP status codes (200, 201, 204, 400, 404, 409) âœ“
- Delete options (keepHistory=true/false) âœ“
- Security: SQL injection protection, XSS protection âœ“

**Routes verified:**
- GET /api/v1/sites/{siteId}/keywords
- POST /api/v1/sites/{siteId}/keywords
- GET /api/v1/keywords/{id}
- PUT /api/v1/keywords/{id}
- DELETE /api/v1/keywords/{id}

**Implementation highlights:**
- Uses Repository pattern (KeywordRepository, RankingRepository)
- Soft delete with optional history removal
- Position change calculation between current/previous rankings
- Keyword limit check per subscription tier (stub)
- strip_tags() for XSS protection on keyword field

Status set to `To Deploy`.

### [2026-03-13 18:18] Agent: DevOps
Deployed to production via deployment log `20260313T161516Z-8bbb8169`.
- Commit: `92a944d7` (fix: add @shuding/opentype.js dependency for satori)
- Frontend build: ✓ (Next.js 16.1.6 Turbopack, 105 static pages)
- Backend deploy: ✓ (composer install, migrations, cache cleared/cached)
- Smoke tests: ✓ (homepage + login page verified)
- Duration: ~166 seconds
Status set to `To Test Prod`.
