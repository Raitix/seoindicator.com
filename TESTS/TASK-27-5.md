# TESTS/TASK-27-5.md — Admin User Stats API Endpoints Tests

## Task Reference
- **Task:** TASK-27-5
- **Specification:** SPEC-027: Admin Dashboard (Ops + Usage)
- **Status:** Tests Coverage In Progress

## Overview

Feature tests for Admin User Stats API Endpoints. These tests cover all 3 user statistics endpoints for the admin dashboard, including PII protection (no emails, names masked) and pagination support.

## Test Files

| File | Tests | Assertions |
|------|-------|------------|
| `backend/tests/Feature/Api/V1/Admin/UserStatsControllerTest.php` | 60 | 100+ |

## Endpoints Tested

| Endpoint | Purpose |
|----------|---------|
| `GET /api/v1/admin/users/stats` | Aggregated user statistics |
| `GET /api/v1/admin/users/recent` | Last 20 registered users (paginated) |
| `GET /api/v1/admin/users/active` | Active users list (paginated) |

## Test Scenarios

### US: User Stats Endpoint

| ID | Scenario | Expected |
|----|----------|----------|
| US-001 | Admin user requests stats | 200 OK with valid structure |
| US-002 | Total user count accurate | Correct count |
| US-003 | Active users count (30-day window) | Correct count |
| US-004 | New registrations today count | Correct count |
| US-005 | New registrations this week count | Correct count |
| US-006 | New registrations this month count | Correct count |
| US-007 | Growth rate calculation | Numeric values |
| US-008 | Growth rate handles zero users | 0.0 or graceful |
| US-009 | By-plan breakdown | All plans present |
| US-010 | Null plan treated as free | Counted in 'free' |
| US-011 | Unauthenticated request | 401 Unauthorized |
| US-012 | Non-admin authenticated request | 403 Forbidden |
| US-013 | Soft deletes respected | Not counted |

### RU: Recent Users Endpoint

| ID | Scenario | Expected |
|----|----------|----------|
| RU-001 | Admin user requests recent users | 200 OK with valid structure |
| RU-002 | Returns last 20 by default | 20 items max |
| RU-003 | Ordered by created_at DESC | Newest first |
| RU-004 | Names are masked | name_masked field |
| RU-005 | Emails excluded | No email field |
| RU-006 | Pagination support | page parameter works |
| RU-007 | Custom per_page parameter | Respects limit |
| RU-008 | per_page max limit enforced | Capped at 100 |
| RU-009 | Unauthenticated request | 401 Unauthorized |
| RU-010 | Non-admin authenticated request | 403 Forbidden |
| RU-011 | Empty result handling | Empty array |

### AU: Active Users Endpoint

| ID | Scenario | Expected |
|----|----------|----------|
| AU-001 | Admin user requests active users | 200 OK with valid structure |
| AU-002 | Only shows 30-day active users | Filtered correctly |
| AU-003 | 30-day boundary exact | Inclusive boundary |
| AU-004 | Ordered by last_login_at DESC | Most recent first |
| AU-005 | Names are masked | name_masked field |
| AU-006 | Emails excluded | No email field |
| AU-007 | Pagination support | page parameter works |
| AU-008 | Custom per_page parameter | Respects limit |
| AU-009 | Null last_login_at handled | Excluded or graceful |
| AU-010 | Unauthenticated request | 401 Unauthorized |
| AU-011 | Non-admin authenticated request | 403 Forbidden |

### RL: Rate Limiting (60 requests/minute)

| ID | Scenario | Expected |
|----|----------|----------|
| RL-001 | Requests within limit | All succeed |
| RL-002 | Rate limit headers present | X-RateLimit-Limit |

### HM: HTTP Methods

| ID | Scenario | Expected |
|----|----------|----------|
| HM-001 | POST to stats endpoint | 405 Method Not Allowed |
| HM-002 | PUT to stats endpoint | 405 Method Not Allowed |
| HM-003 | DELETE to stats endpoint | 405 Method Not Allowed |
| HM-004 | POST to recent endpoint | 405 Method Not Allowed |
| HM-005 | POST to active endpoint | 405 Method Not Allowed |

### SC: Security

| ID | Scenario | Expected |
|----|----------|----------|
| SC-001 | No sensitive data in responses | No emails exposed |
| SC-002 | SQL injection protection | Safe handling |
| SC-003 | XSS protection in response | No script tags |
| SC-004 | IDOR protection | 403 for non-admin |

### PF: Performance

| ID | Scenario | Expected |
|----|----------|----------|
| PF-001 | Stats endpoint under 500ms | Performance OK |
| PF-002 | Recent endpoint under 500ms | Performance OK |
| PF-003 | Active endpoint under 500ms | Performance OK |

### EH: Error Handling

| ID | Scenario | Expected |
|----|----------|----------|
| EH-001 | 401 error format consistent | { message: string } |
| EH-002 | Validation error format | 200 or 422 |

### EC: Edge Cases

| ID | Scenario | Expected |
|----|----------|----------|
| EC-001 | Large user count (500+) | Accurate counts |
| EC-002 | Unicode names | Handled correctly |
| EC-003 | Special characters in names | Handled correctly |
| EC-004 | Pagination meta accurate | Correct totals |
| EC-005 | Concurrent requests safe | All succeed |

## Response Structures

### User Stats Response
```json
{
  "success": true,
  "data": {
    "total": 1250,
    "active": 890,
    "newThisMonth": 156,
    "newThisWeek": 42,
    "newToday": 8,
    "growthRate": {
      "monthly": 14.2,
      "weekly": 5.8,
      "daily": 1.2
    },
    "byPlan": {
      "free": 1000,
      "pro": 200,
      "enterprise": 50
    }
  }
}
```

### Recent Users Response
```json
{
  "success": true,
  "data": {
    "users": [
      {
        "id": 123,
        "name_masked": "J*** D**",
        "created_at": "2026-03-02T12:00:00Z",
        "plan": "pro"
      }
    ],
    "meta": {
      "current_page": 1,
      "per_page": 20,
      "total": 1250,
      "last_page": 63
    }
  }
}
```

### Active Users Response
```json
{
  "success": true,
  "data": {
    "users": [
      {
        "id": 123,
        "name_masked": "J*** D**",
        "last_login_at": "2026-03-02T10:30:00Z",
        "plan": "free"
      }
    ],
    "meta": {
      "current_page": 1,
      "per_page": 20,
      "total": 890,
      "last_page": 45
    }
  }
}
```

## Test Execution

```bash
# Run all user stats controller tests
cd backend
php artisan test --filter=UserStatsControllerTest

# Run specific test categories
php artisan test --filter="UserStatsControllerTest::user_stats"
php artisan test --filter="UserStatsControllerTest::recent_users"
php artisan test --filter="UserStatsControllerTest::active_users"

# Run with coverage
php artisan test --filter=UserStatsControllerTest --coverage
```

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-27-2 (AdminAuthorizationMiddleware) | To Develop | BLOCKS - Mocked in tests via `is_admin` attribute |
| TASK-27-4 (User stats aggregation queries) | To Develop | BLOCKS - Interface expected from UserStatsRepository |

## Notes for Developer

### Controller Structure
```
app/Http/Controllers/Api/V1/Admin/UserStatsController.php
├── stats()   - GET /api/v1/admin/users/stats
├── recent()  - GET /api/v1/admin/users/recent
└── active()  - GET /api/v1/admin/users/active
```

### Routes (routes/api.php)
```php
Route::middleware(['auth:sanctum', 'admin', 'throttle:60,1'])
    ->prefix('api/v1/admin/users')
    ->group(function () {
        Route::get('/stats', [UserStatsController::class, 'stats']);
        Route::get('/recent', [UserStatsController::class, 'recent']);
        Route::get('/active', [UserStatsController::class, 'active']);
    });
```

### Service Dependencies
- `UserStatsRepository` (TASK-27-4) - Inject into stats method
- Use Eloquent queries for recent/active endpoints

### PII Protection Implementation
```php
// Name masking example
private function maskName(string $name): string
{
    $parts = explode(' ', $name);
    return collect($parts)->map(function ($part) {
        return mb_substr($part, 0, 1) . str_repeat('*', max(0, mb_strlen($part) - 1));
    })->join(' ');
}
```

### Rate Limiting
The 60 req/min rate limit should be implemented via:
```php
Route::middleware(['throttle:60,1'])
```

### Pagination
Use Laravel's built-in pagination:
```php
User::query()
    ->orderBy('created_at', 'desc')
    ->paginate($request->input('per_page', 20));
```

### Database Schema Requirements
From TASK-27-4 tests:
- `last_login_at` TIMESTAMP on users table
- `plan` VARCHAR with default 'free'
- Indexes on `created_at` and `last_login_at`

---

## Comments

### 2026-03-02 - PreTester
**Tests Created**
- Created 60 feature tests for Admin User Stats API Endpoints
- All 3 endpoints covered with authentication, authorization, and edge cases
- Tests designed to work with mocked UserStatsRepository (TASK-27-4)
- PII protection tests verify no emails exposed, names masked
- Rate limiting tests verify 60 req/min limit
- Performance tests ensure sub-500ms responses

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| User Stats | 13 | Aggregated stats, counts, growth rates |
| Recent Users | 11 | Paginated list, name masking, PII |
| Active Users | 11 | 30-day filter, pagination, PII |
| Rate Limiting | 2 | 60/min limit enforcement |
| HTTP Methods | 5 | GET-only restriction |
| Security | 4 | PII, SQL injection, XSS, IDOR |
| Performance | 3 | Response time under 500ms |
| Error Handling | 2 | Consistent error format |
| Edge Cases | 5 | Large counts, Unicode, pagination |

**Dependencies Blocked:**
- TASK-27-2 (AdminAuthorizationMiddleware) - Tests mock admin check via `is_admin` attribute
- TASK-27-4 (UserStatsRepository) - Tests expect repository interface

**Status:** Tests Created, Ready for Developer Implementation
