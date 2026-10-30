# TEST-27-5: Admin User Stats API Endpoints

## Task Reference
- **Task:** [TASK-27-5](../TASKS/TASK-27-5.md)
- **Specification:** [SPEC-027](../SPECIFICATIONS/SPEC-027.md)
- **Feature:** Admin Dashboard (Ops + Usage)

## Overview

Test coverage for Admin User Stats API endpoints that provide user statistics, recent users list, and active users list for the admin dashboard.

## Test Files

| File | Tests | Description |
|------|-------|-------------|
| `backend/tests/Feature/Api/V1/Admin/UserStatsControllerTest.php` | 85+ | Feature tests for all 3 endpoints |
| `backend/app/Services/Admin/Contracts/UserStatsRepositoryInterface.php` | - | Interface for mocking |

## Test Categories

| Category | Tests | Description |
|----------|-------|-------------|
| US-STR | 6 | Route Structure - all endpoints registered |
| US-AUTH | 12 | Authentication - 401 for unauthenticated |
| US-AUTHZ | 12 | Authorization - 403 for non-admin |
| US-STATS | 15 | Stats endpoint - response structure, data types |
| US-RECENT | 15 | Recent users endpoint - pagination, ordering |
| US-ACTIVE | 15 | Active users endpoint - pagination, filtering |
| US-PII | 8 | PII Exclusion - no emails, masked names |
| US-RATE | 4 | Rate limiting - 60 req/min |
| US-PERF | 4 | Performance - response times |
| US-SEC | 8 | Security - SQL injection, XSS |
| US-EDGE | 10 | Edge cases - empty data, unicode |
| US-INT | 5 | Integration - full workflows |

## API Endpoints

| Method | Endpoint | Description | Rate Limit |
|--------|----------|-------------|------------|
| GET | `/api/v1/admin/users/stats` | Aggregated user stats | 60/min |
| GET | `/api/v1/admin/users/recent` | Last 20 registered users | 60/min |
| GET | `/api/v1/admin/users/active` | Active users list | 60/min |

## Response Formats

### GET /api/v1/admin/users/stats

```json
{
  "success": true,
  "data": {
    "total_users": 1500,
    "active_users": 850,
    "new_today": 12,
    "new_this_week": 45,
    "new_this_month": 156,
    "growth_rates": {
      "daily": 0.8,
      "weekly": 3.1,
      "monthly": 11.6
    },
    "users_by_plan": {
      "free": 1200,
      "pro": 250,
      "enterprise": 50
    }
  }
}
```

### GET /api/v1/admin/users/recent

```json
{
  "success": true,
  "data": [
    {
      "id": 1234,
      "masked_name": "J*** D**",
      "created_at": "2026-03-15T10:30:00Z",
      "plan": "pro",
      "is_active": true
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 1500,
    "last_page": 75
  }
}
```

### GET /api/v1/admin/users/active

```json
{
  "success": true,
  "data": [
    {
      "id": 1234,
      "masked_name": "J*** D**",
      "last_login_at": "2026-03-15T10:30:00Z",
      "plan": "pro"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 850,
    "last_page": 43
  }
}
```

## Test Scenarios

### US-001 to US-006: Route Structure

| ID | Scenario | Expected |
|----|----------|----------|
| US-001 | Stats endpoint exists | 200 for admin |
| US-002 | Recent endpoint exists | 200 for admin |
| US-003 | Active endpoint exists | 200 for admin |
| US-004 | Route names correct | `admin.users.stats`, `admin.users.recent`, `admin.users.active` |
| US-005 | Middleware applied | `auth:sanctum`, `admin`, `throttle:60,1` |
| US-006 | Prefix correct | `/api/v1/admin/users/*` |

### US-010 to US-021: Authentication

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| US-010 | Unauthenticated stats | No token | 401 |
| US-011 | Unauthenticated recent | No token | 401 |
| US-012 | Unauthenticated active | No token | 401 |
| US-013 | Invalid token stats | Bad token | 401 |
| US-014 | Invalid token recent | Bad token | 401 |
| US-015 | Invalid token active | Bad token | 401 |
| US-016 | Expired token stats | Expired | 401 |
| US-017 | Expired token recent | Expired | 401 |
| US-018 | Expired token active | Expired | 401 |
| US-019 | Authenticated non-admin stats | User token | 403 |
| US-020 | Authenticated non-admin recent | User token | 403 |
| US-021 | Authenticated non-admin active | User token | 403 |

### US-030 to US-041: Authorization

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| US-030 | Admin user stats | Admin token | 200 |
| US-031 | Admin user recent | Admin token | 200 |
| US-032 | Admin user active | Admin token | 200 |
| US-033 | Super admin stats | Super admin | 200 |
| US-034 | Super admin recent | Super admin | 200 |
| US-035 | Super admin active | Super admin | 200 |
| US-036 | Regular user stats | Regular user | 403 |
| US-037 | Regular user recent | Regular user | 403 |
| US-038 | Regular user active | Regular user | 403 |
| US-039 | Error format 401 | Unauthenticated | `{ message: "Unauthenticated" }` |
| US-040 | Error format 403 | Non-admin | `{ message: "Forbidden" }` |
| US-041 | Admin check via role | `role=admin` | Success |

### US-050 to US-064: Stats Endpoint

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| US-050 | Basic response structure | Admin token | 200 + JSON structure |
| US-051 | Total users field | Admin token | Integer >= 0 |
| US-052 | Active users field | Admin token | Integer >= 0 |
| US-053 | New today field | Admin token | Integer >= 0 |
| US-054 | New this week field | Admin token | Integer >= 0 |
| US-055 | New this month field | Admin token | Integer >= 0 |
| US-056 | Growth rates structure | Admin token | Object with daily/weekly/monthly |
| US-057 | Growth rates values | Admin token | Float values |
| US-058 | Users by plan structure | Admin token | Object with free/pro/enterprise |
| US-059 | Users by plan values | Admin token | Integer values |
| US-060 | Zero users scenario | Empty DB | All zeros |
| US-061 | Large numbers | Many users | Correct formatting |
| US-062 | Success wrapper | Admin token | `{ success: true, data: {...} }` |
| US-063 | Service exception handling | Mock error | 500 + error message |
| US-064 | Cache headers | Admin token | Cache-Control present |

### US-070 to US-084: Recent Users Endpoint

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| US-070 | Basic response structure | Admin token | 200 + paginated JSON |
| US-071 | Default pagination | No params | 20 items per page |
| US-072 | Custom pagination | `per_page=10` | 10 items |
| US-073 | Max pagination | `per_page=100` | Max 100 items |
| US-074 | Page parameter | `page=2` | Correct offset |
| US-075 | Ordering | Default | Created DESC |
| US-076 | User object structure | Admin token | id, masked_name, created_at, plan, is_active |
| US-077 | Masked name format | Name "John Doe" | "J*** D**" |
| US-078 | No email in response | Admin token | Email field absent |
| US-079 | Meta pagination | Admin token | current_page, per_page, total, last_page |
| US-080 | Empty result | No users | Empty array + meta |
| US-081 | Plan filter | `plan=pro` | Only pro users |
| US-082 | Active filter | `is_active=true` | Only active users |
| US-083 | Combined filters | Multiple params | AND logic |
| US-084 | Invalid filter | `plan=invalid` | Empty result or validation error |

### US-090 to US-104: Active Users Endpoint

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| US-090 | Basic response structure | Admin token | 200 + paginated JSON |
| US-091 | Default pagination | No params | 20 items per page |
| US-092 | Custom pagination | `per_page=15` | 15 items |
| US-093 | Ordering | Default | last_login_at DESC |
| US-094 | User object structure | Admin token | id, masked_name, last_login_at, plan |
| US-095 | Masked name format | Name "Jane Smith" | "J*** S****" |
| US-096 | No email in response | Admin token | Email field absent |
| US-097 | Last login present | Admin token | ISO 8601 timestamp |
| US-098 | Meta pagination | Admin token | current_page, per_page, total, last_page |
| US-099 | Empty result | No active users | Empty array + meta |
| US-100 | Plan filter | `plan=enterprise` | Only enterprise users |
| US-101 | Days filter | `days=7` | Active within 7 days |
| US-102 | Combined filters | Multiple params | AND logic |
| US-103 | Invalid days | `days=-1` | Validation error |
| US-104 | Last login null handling | Never logged in | Excluded or null |

### US-110 to US-117: PII Exclusion

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| US-110 | Stats no PII | Admin token | No emails in response |
| US-111 | Recent no emails | Admin token | No email field in user objects |
| US-112 | Active no emails | Admin token | No email field in user objects |
| US-113 | Recent masked names | Admin token | Names partially masked |
| US-114 | Active masked names | Admin token | Names partially masked |
| US-115 | Masking preserves first char | "Alice" | "A****" |
| US-116 | Masking handles short names | "Jo" | "J*" |
| US-117 | Masking handles unicode | "François" | "F*******" |

### US-120 to US-123: Rate Limiting

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| US-120 | Within limit | 59 requests | 200 |
| US-121 | At limit | 60th request | 429 |
| US-122 | Rate limit headers | Any request | X-RateLimit-* headers |
| US-123 | Rate limit reset | After 1 min | Counter reset |

### US-130 to US-133: Performance

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| US-130 | Stats response time | Admin token | < 200ms |
| US-131 | Recent response time | Admin token | < 200ms |
| US-132 | Active response time | Admin token | < 200ms |
| US-133 | Large dataset | 10K users | < 500ms |

### US-140 to US-147: Security

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| US-140 | SQL injection stats | `?plan=' OR '1'='1` | No injection, safe response |
| US-141 | SQL injection recent | `?plan='; DROP TABLE users;--` | No injection, safe response |
| US-142 | SQL injection active | `?plan=' UNION SELECT` | No injection, safe response |
| US-143 | XSS in filters | `<script>alert(1)</script>` | Escaped or rejected |
| US-144 | No sensitive data leak | Admin token | No passwords, tokens, secrets |
| US-145 | Enumeration prevention | Non-existent filter | Same response structure |
| US-146 | IDOR prevention | Other admin ID | Own data only |
| US-147 | CSRF protection | Without token | 401 (API uses Sanctum) |

### US-150 to US-159: Edge Cases

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| US-150 | Empty database | No users | Zeros/empty arrays |
| US-151 | Unicode usernames | "用户名" | Proper encoding |
| US-152 | Very long names | 255 char name | Truncated in mask |
| US-153 | Null values | Missing fields | Handled gracefully |
| US-154 | Concurrent requests | Multiple admins | No race conditions |
| US-155 | Invalid per_page | `per_page=abc` | Default used or error |
| US-156 | Negative page | `page=-1` | Default to 1 or error |
| US-157 | Large page number | `page=999999` | Empty result |
| US-158 | Missing optional params | No filters | All results |
| US-159 | Extra params ignored | Unknown params | Ignored |

### US-160 to US-164: Integration

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| US-160 | Full stats workflow | Admin -> stats | Complete data |
| US-161 | Full recent workflow | Admin -> recent | Paginated list |
| US-162 | Full active workflow | Admin -> active | Paginated list |
| US-163 | Cross-endpoint consistency | All 3 endpoints | Consistent masking |
| US-164 | Error recovery | Service down | Graceful degradation |

## Mock Data

### Mock User Stats
```php
$mockStats = [
    'total_users' => 1500,
    'active_users' => 850,
    'new_today' => 12,
    'new_this_week' => 45,
    'new_this_month' => 156,
    'growth_rates' => [
        'daily' => 0.8,
        'weekly' => 3.1,
        'monthly' => 11.6,
    ],
    'users_by_plan' => [
        'free' => 1200,
        'pro' => 250,
        'enterprise' => 50,
    ],
];
```

### Mock Recent User
```php
$mockRecentUser = [
    'id' => 1234,
    'masked_name' => 'J*** D**',
    'created_at' => '2026-03-15T10:30:00Z',
    'plan' => 'pro',
    'is_active' => true,
];
```

### Mock Active User
```php
$mockActiveUser = [
    'id' => 1234,
    'masked_name' => 'J*** S****',
    'last_login_at' => '2026-03-15T10:30:00Z',
    'plan' => 'enterprise',
];
```

## Coverage Targets

| Endpoint | Statements | Branches | Functions | Lines |
|----------|------------|----------|-----------|-------|
| UserStatsController | 90% | 85% | 100% | 90% |

## Test Execution

```bash
# Run all user stats tests
cd backend
php artisan test --filter=UserStatsControllerTest

# Run specific test category
php artisan test --filter="UserStatsControllerTest::test_stats_endpoint"
```

## Dependencies

| Task | Status | Description |
|------|--------|-------------|
| TASK-27-2 | Done ✅ | AdminAuthorizationMiddleware |
| TASK-27-4 | To Write Tests | UserStatsRepository (mocked in tests) |

## Notes for Developer

1. **PII Masking**: Implement a helper method `maskName(string $name): string` that:
   - Keeps first character
   - Replaces rest with asterisks
   - Handles spaces (mask each word separately)
   - Handles unicode characters

2. **Pagination**: Use Laravel's `LengthAwarePaginator` for consistent response format.

3. **Service Layer**: Inject `UserStatsRepositoryInterface` for testability.

4. **Response Structure**: Follow existing admin endpoint patterns with `{ success: true, data: {...} }`.

5. **Cache Consideration**: Stats endpoint may benefit from 1-5 minute caching.

## Comments

### 2026-03-15 - PreTester
Tests designed following TDD pattern with mocked repository interface. All 85+ test scenarios cover authentication, authorization, response structure, PII exclusion, pagination, security, and edge cases. Dependencies (TASK-27-4) are mocked via interface for parallel development.
