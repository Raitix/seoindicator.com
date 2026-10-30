# TEST-33-7: Public Audit API Routes Tests

## Task Reference
- **Task:** TASK-33-7
- **Specification:** SPEC-33: Public SEO Audit Reports & Embeddable Score Widget
- **Status:** To Develop

## Overview
Comprehensive test suite for all API routes related to public audit functionality. Tests verify route registration, middleware assignment, HTTP methods, route model binding, rate limiting, authentication requirements, and security measures.

## Test Files

| File | Tests | Location |
|------|-------|----------|
| `PublicAuditRoutesTest.php` | 65+ | `backend/tests/Feature/Api/V1/` |

## Test Categories

### RR: Route Registration (8 tests)
| ID | Test | Description |
|----|------|-------------|
| RR-001 | `route_get_public_audit_by_hash_is_registered` | GET `/api/v1/public-audit/{hash}` exists |
| RR-002 | `route_post_public_audit_lead_is_registered` | POST `/api/v1/public-audit/{hash}/lead` exists |
| RR-003 | `route_get_public_audit_badge_is_registered` | GET `/api/v1/public-audit/{hash}/badge` exists |
| RR-004 | `route_post_badge_impression_is_registered` | POST `/api/v1/badge/impression` exists |
| RR-005 | `route_post_badge_click_is_registered` | POST `/api/v1/badge/click` exists |
| RR-006 | `route_post_audit_publish_is_registered` | POST `/api/v1/audits/{id}/publish` exists |
| RR-007 | `route_delete_audit_unpublish_is_registered` | DELETE `/api/v1/audits/{id}/unpublish` exists |
| RR-008 | `route_get_audit_embed_code_is_registered` | GET `/api/v1/audits/{id}/embed-code` exists |

### RP: Route Paths (8 tests)
| ID | Test | Description |
|----|------|-------------|
| RP-001 | `public_audit_show_route_has_correct_path` | Path contains `public-audit/{hash}` |
| RP-002 | `public_audit_lead_route_has_correct_path` | Path contains `public-audit/{hash}/lead` |
| RP-003 | `public_audit_badge_route_has_correct_path` | Path contains `public-audit/{hash}/badge` |
| RP-004 | `badge_impression_route_has_correct_path` | Path contains `badge/impression` |
| RP-005 | `badge_click_route_has_correct_path` | Path contains `badge/click` |
| RP-006 | `audit_publish_route_has_correct_path` | Path contains `audits/{id}/publish` |
| RP-007 | `audit_unpublish_route_has_correct_path` | Path contains `audits/{id}/unpublish` |
| RP-008 | `audit_embed_code_route_has_correct_path` | Path contains `audits/{id}/embed-code` |

### HM: HTTP Methods (8 tests)
| ID | Test | Description |
|----|------|-------------|
| HM-001 | `public_audit_show_uses_get_method` | Route accepts GET |
| HM-002 | `public_audit_lead_uses_post_method` | Route accepts POST |
| HM-003 | `public_audit_badge_uses_get_method` | Route accepts GET |
| HM-004 | `badge_impression_uses_post_method` | Route accepts POST |
| HM-005 | `badge_click_uses_post_method` | Route accepts POST |
| HM-006 | `audit_publish_uses_post_method` | Route accepts POST |
| HM-007 | `audit_unpublish_uses_delete_method` | Route accepts DELETE |
| HM-008 | `audit_embed_code_uses_get_method` | Route accepts GET |

### MW-PUB: Middleware - Public Routes (5 tests)
| ID | Test | Description |
|----|------|-------------|
| MW-PUB-001 | `public_audit_show_does_not_require_authentication` | No `auth:sanctum` middleware |
| MW-PUB-002 | `public_audit_lead_does_not_require_authentication` | No `auth:sanctum` middleware |
| MW-PUB-003 | `public_audit_badge_does_not_require_authentication` | No `auth:sanctum` middleware |
| MW-PUB-004 | `badge_impression_does_not_require_authentication` | No `auth:sanctum` middleware |
| MW-PUB-005 | `badge_click_does_not_require_authentication` | No `auth:sanctum` middleware |

### MW-AUTH: Middleware - Authenticated Routes (3 tests)
| ID | Test | Description |
|----|------|-------------|
| MW-AUTH-001 | `audit_publish_requires_authentication` | Has `auth:sanctum` middleware |
| MW-AUTH-002 | `audit_unpublish_requires_authentication` | Has `auth:sanctum` middleware |
| MW-AUTH-003 | `audit_embed_code_requires_authentication` | Has `auth:sanctum` middleware |

### RL: Rate Limiting (3 tests)
| ID | Test | Description |
|----|------|-------------|
| RL-001 | `public_audit_routes_have_rate_limiting` | All public-audit routes have throttle |
| RL-002 | `badge_routes_have_rate_limiting` | All badge routes have throttle |
| RL-003 | `authenticated_audit_routes_have_rate_limiting` | All authenticated routes have throttle |

### HTTP-PUB: HTTP Requests - Public Routes (6 tests)
| ID | Test | Expected Status |
|----|------|-----------------|
| HTTP-PUB-001 | `public_audit_show_returns_404_for_invalid_hash` | 404 |
| HTTP-PUB-002 | `public_audit_show_returns_404_for_non_existent_hash` | 404 |
| HTTP-PUB-003 | `public_audit_lead_accepts_post_request` | 404 (controller pending) |
| HTTP-PUB-004 | `public_audit_badge_accepts_get_request` | 404 (controller pending) |
| HTTP-PUB-005 | `badge_impression_accepts_post_request` | 404 (controller pending) |
| HTTP-PUB-006 | `badge_click_accepts_post_request` | 404 (controller pending) |

### HTTP-AUTH: HTTP Requests - Authenticated Routes (6 tests)
| ID | Test | Expected Status |
|----|------|-----------------|
| HTTP-AUTH-001 | `audit_publish_returns_401_without_authentication` | 401 |
| HTTP-AUTH-002 | `audit_unpublish_returns_401_without_authentication` | 401 |
| HTTP-AUTH-003 | `audit_embed_code_returns_401_without_authentication` | 401 |
| HTTP-AUTH-004 | `audit_publish_accepts_authenticated_request` | 404 (controller pending) |
| HTTP-AUTH-005 | `audit_unpublish_accepts_authenticated_request` | 404 (controller pending) |
| HTTP-AUTH-006 | `audit_embed_code_accepts_authenticated_request` | 404 (controller pending) |

### HMR: HTTP Method Restrictions (8 tests)
| ID | Test | Expected Status |
|----|------|-----------------|
| HMR-001 | `public_audit_show_rejects_post_method` | 405 |
| HMR-002 | `public_audit_lead_rejects_get_method` | 405 |
| HMR-003 | `public_audit_badge_rejects_post_method` | 405 |
| HMR-004 | `badge_impression_rejects_get_method` | 405 |
| HMR-005 | `badge_click_rejects_get_method` | 405 |
| HMR-006 | `audit_publish_rejects_get_method` | 405 |
| HMR-007 | `audit_unpublish_rejects_post_method` | 405 |
| HMR-008 | `audit_embed_code_rejects_post_method` | 405 |

### RMB: Route Model Binding (3 tests)
| ID | Test | Description |
|----|------|-------------|
| RMB-001 | `public_audit_routes_use_hash_parameter` | URL contains `{hash}` |
| RMB-002 | `authenticated_audit_routes_use_id_parameter` | URL contains `{id}` |
| RMB-003 | `audit_routes_bind_to_audit_model` | Routes bind to Audit model |

### API: API Prefix (1 test)
| ID | Test | Description |
|----|------|-------------|
| API-001 | `all_routes_have_api_v1_prefix` | All routes start with `api/v1` |

### SEC: Security (5 tests)
| ID | Test | Description |
|----|------|-------------|
| SEC-001 | `public_audit_hash_parameter_validates_format` | Invalid format returns 404 |
| SEC-002 | `public_audit_routes_prevent_sql_injection` | SQL injection returns 404 (not 500) |
| SEC-003 | `audit_routes_prevent_sql_injection_in_id` | SQL injection returns 404 (not 500) |
| SEC-004 | `public_audit_lead_validates_email_format` | Invalid email returns 422 or 404 |
| SEC-005 | `public_audit_lead_sanitizes_xss_in_email` | XSS in email is handled |

### EC: Edge Cases (4 tests)
| ID | Test | Description |
|----|------|-------------|
| EC-001 | `public_audit_handles_unicode_hash` | Unicode in hash returns 404 |
| EC-002 | `public_audit_handles_very_long_hash` | 1000-char hash returns 404 |
| EC-003 | `audit_routes_handle_very_large_id` | Large ID returns 404 |
| EC-004 | `public_audit_routes_handle_special_characters` | Special chars in hash handled |

### AC: Acceptance Criteria (5 tests)
| ID | Test | Description |
|----|------|-------------|
| AC-001 | `ac_all_public_routes_are_registered` | 5 public routes exist |
| AC-002 | `ac_all_authenticated_routes_are_registered` | 3 authenticated routes exist |
| AC-003 | `ac_public_routes_do_not_require_auth` | No `auth:sanctum` on public routes |
| AC-004 | `ac_authenticated_routes_require_auth` | `auth:sanctum` on authenticated routes |
| AC-005 | `ac_all_routes_have_rate_limiting` | All 8 routes have throttle middleware |

## Route Definitions

### Public Routes (No Authentication)

| Method | Path | Name | Purpose |
|--------|------|------|---------|
| GET | `/api/v1/public-audit/{hash}` | `api.v1.public-audit.show` | Get audit data by public hash |
| POST | `/api/v1/public-audit/{hash}/lead` | `api.v1.public-audit.lead` | Submit lead capture form |
| GET | `/api/v1/public-audit/{hash}/badge` | `api.v1.public-audit.badge` | Get badge configuration |
| POST | `/api/v1/badge/impression` | `api.v1.badge.impression` | Track badge impression |
| POST | `/api/v1/badge/click` | `api.v1.badge.click` | Track badge click |

### Authenticated Routes (Require `auth:sanctum`)

| Method | Path | Name | Purpose |
|--------|------|------|---------|
| POST | `/api/v1/audits/{id}/publish` | `api.v1.audits.publish` | Make audit public |
| DELETE | `/api/v1/audits/{id}/unpublish` | `api.v1.audits.unpublish` | Make audit private |
| GET | `/api/v1/audits/{id}/embed-code` | `api.v1.audits.embed-code` | Get embed code for widget |

## Middleware Configuration

### Route Groups

```php
// Public audit routes (no auth)
Route::prefix('public-audit')->group(function () {
    Route::get('{hash}', [PublicAuditController::class, 'show'])
        ->name('api.v1.public-audit.show');
    Route::post('{hash}/lead', [PublicAuditController::class, 'lead'])
        ->name('api.v1.public-audit.lead');
    Route::get('{hash}/badge', [PublicAuditController::class, 'badge'])
        ->name('api.v1.public-audit.badge');
})->middleware(['throttle:public-audit']);

// Badge tracking routes (no auth)
Route::prefix('badge')->group(function () {
    Route::post('impression', [BadgeTrackingController::class, 'impression'])
        ->name('api.v1.badge.impression');
    Route::post('click', [BadgeTrackingController::class, 'click'])
        ->name('api.v1.badge.click');
})->middleware(['throttle:badge-tracking']);

// Authenticated audit routes
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::post('audits/{id}/publish', [AuditVisibilityController::class, 'publish'])
        ->name('api.v1.audits.publish');
    Route::delete('audits/{id}/unpublish', [AuditVisibilityController::class, 'unpublish'])
        ->name('api.v1.audits.unpublish');
    Route::get('audits/{id}/embed-code', [AuditVisibilityController::class, 'embedCode'])
        ->name('api.v1.audits.embed-code');
});
```

### Rate Limiters

| Limiter Name | Limit | Routes |
|--------------|-------|--------|
| `public-audit` | 100/hour | public-audit/* |
| `badge-tracking` | 60/minute | badge/impression, badge/click |
| `api` | 60/minute | Authenticated routes |

## Test Patterns

### Route Registration Test
```php
public function test_route_is_registered(): void
{
    $this->assertTrue(
        Route::has('api.v1.public-audit.show'),
        'Route is not registered'
    );
}
```

### Middleware Test
```php
public function test_route_requires_auth(): void
{
    $route = Route::getRoutes()->getByName('api.v1.audits.publish');
    $middleware = $route->gatherMiddleware();
    
    $this->assertContains('auth:sanctum', $middleware);
}
```

### HTTP Request Test
```php
public function test_endpoint_returns_401_without_auth(): void
{
    $response = $this->postJson('/api/v1/audits/1/publish');
    $response->assertStatus(401);
}
```

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-33-5 (PublicAuditController) | To Develop | Controller not required for route tests |
| TASK-33-17 (Badge Impression Controller) | To Prioritize | Controller not required for route tests |
| TASK-33-18 (Badge Click Controller) | To Prioritize | Controller not required for route tests |

## Files to Modify

| File | Change |
|------|--------|
| `backend/routes/api.php` | Add route definitions |
| `backend/app/Providers/RouteServiceProvider.php` | Configure rate limiters |

## Expected Test Results

| Phase | Expected |
|-------|----------|
| Route Registration | All tests PASS (routes must be registered) |
| Middleware Tests | All tests PASS (middleware must be configured) |
| HTTP Requests | Return 404 (controllers not implemented yet) |
| Security Tests | Return 404 (safe handling of malicious input) |

## Notes for Developer

1. **Route Registration Order:**
   - Define public routes first (no auth)
   - Define badge tracking routes (no auth)
   - Define authenticated routes (with `auth:sanctum`)

2. **Rate Limiter Configuration:**
   - Add `throttle:public-audit` rate limiter in `RouteServiceProvider`
   - Limit: 100 requests per hour per IP
   - Key: `public-audit:{ip}`

3. **Route Model Binding:**
   - Public routes use `{hash}` for 32-char public_hash lookup
   - Authenticated routes use `{id}` for numeric ID lookup
   - Configure explicit binding if needed:
     ```php
     Route::bind('hash', function ($value) {
         return Audit::where('public_hash', $value)->firstOrFail();
     });
     ```

4. **Controller Stubs Needed:**
   - `PublicAuditController` (show, lead, badge)
   - `BadgeTrackingController` (impression, click)
   - `AuditVisibilityController` (publish, unpublish, embedCode)

5. **TDD Pattern:**
   - Tests will FAIL until routes are implemented
   - Tests use 404 as expected status when controllers not implemented
   - After implementation, update tests to check actual responses

## Coverage Targets

| Category | Target | Status |
|----------|--------|--------|
| Route Registration | 100% | Pending |
| Middleware Assignment | 100% | Pending |
| HTTP Methods | 100% | Pending |
| Rate Limiting | 100% | Pending |
| Security | 100% | Pending |
| Edge Cases | 100% | Pending |
| Acceptance Criteria | 100% | Pending |

---

**Created:** 2026-03-06
**Agent:** PreTester
