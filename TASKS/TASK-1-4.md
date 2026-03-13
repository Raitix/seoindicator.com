# TASK-1-4: Keywords CRUD API Endpoints

## STATUS
`Needs Fix`

## Priority
High

## Risk
Medium

## Description
Implement REST API endpoints for keyword management including create, read, update, and delete operations.

## Acceptance Criteria
- [x] GET /api/v1/sites/:siteId/keywords - List keywords with pagination, sorting, filtering
- [x] POST /api/v1/sites/:siteId/keywords - Create keyword with validation
- [ ] GET /api/v1/keywords/:id - Get single keyword with current position (401 - middleware issue)
- [ ] PUT /api/v1/keywords/:id - Update keyword (targetUrl, status) (401 - middleware issue)
- [ ] DELETE /api/v1/keywords/:id - Delete keyword (soft delete, optional history retention) (401 - middleware issue)
- [x] Query params: status, page, perPage, sortBy, sortOrder
- [x] Validation: keyword required, targetUrl valid URL, unique constraint
- [x] Proper HTTP status codes (200, 201, 204, 400, 404, 409)
- [x] Error responses follow standard format with code and message
- [x] API returns current position from latest ranking
- [x] SQL injection protection
- [x] XSS protection
- [x] SERP feature filtering

## Production Validation Results (2026-03-13 18:39 GMT+2)

### Local Test Results
- **Passed:** 24 tests (list, create, validation, security, SERP filtering)
- **Failed:** 12 tests (get/update/delete single keyword - all 401)
- **Assertions:** 125

### Root Cause Analysis
The routes under `/api/v1/keywords/{id}` (GET, PUT, DELETE) are protected by `api-key` middleware instead of `auth:sanctum` middleware, causing 401 responses for Sanctum-authenticated users.

### Routes Working Correctly
| Endpoint | Status | Notes |
|----------|--------|-------|
| GET /api/v1/sites/:siteId/keywords | ✅ PASS | List with pagination, filtering, sorting |
| POST /api/v1/sites/:siteId/keywords | ✅ PASS | Create with validation |
| Validation (400) | ✅ PASS | Missing keyword, invalid URL |
| Duplicate detection (409) | ✅ PASS | Unique constraint |
| SQL injection protection | ✅ PASS | Parameterized queries |
| XSS protection | ✅ PASS | Input sanitized |
| SERP feature filtering | ✅ PASS | Filter by features, present parameter |

### Routes Failing Authentication
| Endpoint | Expected | Actual | Issue |
|----------|----------|--------|-------|
| GET /api/v1/keywords/:id | 200/404 | 401 | Requires API key, not Sanctum |
| PUT /api/v1/keywords/:id | 200/400/404 | 401 | Requires API key, not Sanctum |
| DELETE /api/v1/keywords/:id | 204/404 | 401 | Requires API key, not Sanctum |

### Production API Tests
| Test | Result | Details |
|------|--------|---------|
| Unauthenticated list | 200 (empty) | Returns empty data for guests |
| Validation - missing keyword | ✅ 400 | Proper error format |
| Validation - invalid URL | ✅ 400 | Proper error format |
| SQL injection attempt | 500 | Site ownership check exception |
| XSS attempt | 500 | Site ownership check exception |

### Recommended Fix
Update routes file to use consistent authentication middleware:
```php
// Current (problematic):
Route::middleware(['api-key'])->group(function () {
    Route::get('/keywords/{id}', [KeywordController::class, 'show']);
    Route::put('/keywords/{id}', [KeywordController::class, 'update']);
    Route::delete('/keywords/{id}', [KeywordController::class, 'destroy']);
});

// Should be:
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/keywords/{id}', [KeywordController::class, 'show']);
    Route::put('/keywords/{id}', [KeywordController::class, 'update']);
    Route::delete('/keywords/{id}', [KeywordController::class, 'destroy']);
});
```

## Dependencies
- TASK-1-1: Core API Infrastructure (Done ✅)
- TASK-1-2: Site Management API (Done ✅)

## Related Tasks
- TASK-1-5: Keywords Search Volume API
- TASK-1-6: Keywords Bulk Import

---
Last updated: 2026-03-13 18:55 GMT+2 (ProdTester)
