# TEST-27-5: Admin User Stats API Endpoints

## Task Reference
- **Task:** TASK-27-5: Admin User Stats API Endpoints
- **Specification:** SPEC-027: Admin Dashboard (Ops + Usage)
- **Dependencies:** TASK-27-2 (AdminAuthorizationMiddleware - Done), TASK-27-4 (UserStatsRepository - To Deploy)

## Overview
Feature tests for Admin User Stats API endpoints. Tests cover route registration, authentication, authorization, response structure, PII protection, rate limiting, and edge cases.

## Test Files
| File | Tests | Description |
|------|-------|-------------|
| `backend/tests/Feature/Api/V1/Admin/UserStatsControllerTest.php` | 85+ | Controller feature tests |

## Test Scenarios

### Route Registration (RR)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| RR-001 | Stats route exists | 200 OK for admin |
| RR-002 | Recent users route exists | 200 OK for admin |
| RR-003 | Active users route exists | 200 OK for admin |
| RR-004 | All routes use admin middleware | 403 for non-admin |

### Authentication (AUTH)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| AUTH-001 | Stats returns 401 for unauthenticated | 401 UNAUTHENTICATED |
| AUTH-002 | Recent users returns 401 for unauthenticated | 401 UNAUTHENTICATED |
| AUTH-003 | Active users returns 401 for unauthenticated | 401 UNAUTHENTICATED |
| AUTH-004 | Admin user can access stats | 200 OK |
| AUTH-005 | Super admin can access stats | 200 OK |

### Authorization (AUTHZ)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| AUTHZ-001 | Regular user denied stats | 403 FORBIDDEN |
| AUTHZ-002 | Regular user denied recent users | 403 FORBIDDEN |
| AUTHZ-003 | Regular user denied active users | 403 FORBIDDEN |
| AUTHZ-004 | Unauthenticated response format | `{success: false, error: {code, message}}` |
| AUTHZ-005 | Forbidden response format | `{success: false, error: {code, message}}` |

### Stats Endpoint (STATS)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| STATS-001 | Returns 200 for admin | 200 OK |
| STATS-002 | Returns correct JSON structure | All fields present |
| STATS-003 | Returns success true | `success: true` |
| STATS-004 | Total count includes all users | Correct count |
| STATS-005 | Active count users with recent login | Correct count |
| STATS-006 | Growth rate values are numeric | Float values |
| STATS-007 | Plan distribution values are integers | Integer values |
| STATS-008 | Zero users returns zeros | All zeros |
| STATS-009 | Uses service correctly | Mock returns expected |

### Recent Users Endpoint (RECENT)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| RECENT-001 | Returns 200 for admin | 200 OK |
| RECENT-002 | Returns paginated structure | Pagination fields present |
| RECENT-003 | Default per_page is 20 | `per_page: 20` |
| RECENT-004 | Orders by created_at desc | Most recent first |
| RECENT-005 | Custom per_page parameter | Custom per_page value |
| RECENT-006 | Pagination page parameter | Correct page number |
| RECENT-007 | Excludes email from response | No email field |
| RECENT-008 | Masks user name | Name not fully exposed |
| RECENT-009 | Includes required fields | id, created_at present |
| RECENT-010 | Max per_page limit | Capped at 100 |

### Active Users Endpoint (ACTIVE)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| ACTIVE-001 | Returns 200 for admin | 200 OK |
| ACTIVE-002 | Returns paginated structure | Pagination fields present |
| ACTIVE-003 | Only includes users with recent login | 30-day window |
| ACTIVE-004 | Default per_page is 20 | `per_page: 20` |
| ACTIVE-005 | Orders by last_login_at desc | Most recent login first |
| ACTIVE-006 | Custom per_page parameter | Custom per_page value |
| ACTIVE-007 | Excludes email from response | No email field |
| ACTIVE-008 | Includes required fields | id, last_login_at present |
| ACTIVE-009 | Max per_page limit | Capped at 100 |
| ACTIVE-010 | Boundary 30 days excluded | Exactly 30 days included |

### HTTP Methods (HM)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| HM-001 | Stats returns 405 for POST | 405 Method Not Allowed |
| HM-002 | Stats returns 405 for PUT | 405 Method Not Allowed |
| HM-003 | Stats returns 405 for DELETE | 405 Method Not Allowed |
| HM-004 | Recent returns 405 for POST | 405 Method Not Allowed |
| HM-005 | Active returns 405 for POST | 405 Method Not Allowed |

### Rate Limiting (RL)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| RL-001 | Stats rate limit headers present | X-RateLimit-Limit, X-RateLimit-Remaining |
| RL-002 | Recent rate limit headers present | X-RateLimit-Limit, X-RateLimit-Remaining |
| RL-003 | Active rate limit headers present | X-RateLimit-Limit, X-RateLimit-Remaining |
| RL-004 | Rate limit 60 per minute | Limit = 60 |

### Security (SEC)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| SEC-001 | No password in response | Password fields absent |
| SEC-002 | No API tokens in response | Token fields absent |
| SEC-003 | SQL injection in per_page | 422 or safe handling |
| SEC-004 | SQL injection in page | 200 or 422 |
| SEC-005 | No sensitive user data exposed | No emails, passwords |

### Edge Cases (EDGE)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| EDGE-001 | Empty database stats | 200 OK, total = 1 (admin) |
| EDGE-002 | Empty database recent users | 200 OK, total = 0 |
| EDGE-003 | Empty database active users | 200 OK, total = 0 |
| EDGE-004 | Large user count stats | 200 OK |
| EDGE-005 | Negative per_page handled | 200 or 422 |
| EDGE-006 | Invalid page number handled | 200 OK, empty data |
| EDGE-007 | Unicode in user names | 200 OK |
| EDGE-008 | Null plan treated as free | 200 OK |

### Performance (PERF)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| PERF-001 | Stats response under 500ms | < 500ms |
| PERF-002 | Recent users response under 500ms | < 500ms |
| PERF-003 | Active users response under 500ms | < 500ms |

### Acceptance Criteria (AC)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| AC-001 | Controller exists | Class exists |
| AC-002 | Stats endpoint returns aggregated stats | Correct structure |
| AC-003 | Recent endpoint returns paginated users | Pagination structure |
| AC-004 | Active endpoint returns paginated users | Pagination structure |
| AC-005 | All endpoints protected by admin middleware | 403 for non-admin |
| AC-006 | Rate limiting 60 per minute | Limit = 60 |
| AC-007 | Responses exclude PII | No emails, passwords |
| AC-008 | Feature tests cover all endpoints | Tests exist |

## API Endpoints

### GET /api/v1/admin/users/stats
Returns aggregated user statistics.

**Response:**
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

### GET /api/v1/admin/users/recent
Returns paginated list of recently registered users.

**Query Parameters:**
- `per_page` (int, optional): Items per page (default: 20, max: 100)
- `page` (int, optional): Page number

**Response:**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 123,
        "name": "John D.",
        "role": "user",
        "plan": "pro",
        "created_at": "2026-03-15T10:00:00Z"
      }
    ],
    "current_page": 1,
    "per_page": 20,
    "total": 150,
    "last_page": 8
  }
}
```

### GET /api/v1/admin/users/active
Returns paginated list of active users (logged in within 30 days).

**Query Parameters:**
- `per_page` (int, optional): Items per page (default: 20, max: 100)
- `page` (int, optional): Page number

**Response:**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 123,
        "name": "John D.",
        "role": "user",
        "plan": "pro",
        "created_at": "2026-03-15T10:00:00Z",
        "last_login_at": "2026-03-15T12:00:00Z"
      }
    ],
    "current_page": 1,
    "per_page": 20,
    "total": 89,
    "last_page": 5
  }
}
```

## Mock Data Patterns

### Admin User
```php
$admin = User::factory()->admin()->create();
Sanctum::actingAs($admin, ['access']);
```

### Regular User
```php
$user = User::factory()->create();
Sanctum::actingAs($user, ['access']);
```

### Active User
```php
$activeUser = User::factory()->create(['last_login_at' => now()->subDays(10)]);
```

### Inactive User
```php
$inactiveUser = User::factory()->create(['last_login_at' => now()->subDays(60)]);
```

### User with Plan
```php
$proUser = User::factory()->create(['plan' => 'pro']);
```

## Test Execution

```bash
cd backend
php artisan test --filter=UserStatsControllerTest
```

## Dependencies
- TASK-27-2: AdminAuthorizationMiddleware (Done) - Protects routes
- TASK-27-4: UserStatsRepository (To Deploy) - Provides stats data
- UserAggregationService - Service layer for aggregating stats

## Notes for Developer
1. Create controller at `app/Http/Controllers/Api/V1/Admin/UserStatsController.php`
2. Inject `UserAggregationService` via constructor
3. Use Laravel API resources for response transformation
4. Apply `throttle:60,1` middleware for rate limiting
5. Exclude PII from responses (no emails, mask names)
6. Default pagination: 20 items per page
7. Max pagination: 100 items per page

## Acceptance Criteria Coverage
- [x] Controller created (tested via class_exists)
- [x] GET `/api/v1/admin/users/stats` returns aggregated user stats
- [x] GET `/api/v1/admin/users/recent` returns last 20 registered users (paginated)
- [x] GET `/api/v1/admin/users/active` returns active users list (paginated)
- [x] All endpoints protected by AdminAuthorizationMiddleware
- [x] Rate limiting: 60 requests/minute
- [x] Responses exclude PII (no emails, names masked)
- [x] Feature tests cover all endpoints

---
*Created by PreTester from TASK-27-5*
