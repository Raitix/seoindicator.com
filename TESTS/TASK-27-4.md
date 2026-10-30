# TESTS/TASK-27-4: User Stats Aggregation Queries

## Task Reference
- **Task:** [TASK-27-4](../TASKS/TASK-27-4.md)
- **Specification:** [SPEC-027](../SPECIFICATIONS/SPEC-027.md)
- **Feature:** FEAT-027 (Admin Dashboard)

## Overview
Unit tests for `UserStatsRepository` - database queries for user statistics aggregation. Provides counts for total users, active users, new registrations, and growth metrics.

## Test Files

| File | Tests | Assertions | Purpose |
|------|-------|-------------|---------|
| `tests/Unit/Repositories/UserStatsRepositoryTest.php` | 62 | 111 | Repository method tests |

## Test Scenarios

### US-001: Repository Instantiation
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| US-001-01 | Repository can be instantiated | `UserStatsRepository` instance |

### US-002: getTotalUserCount()
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| US-002-01 | No users | Empty DB | `0` |
| US-002-02 | 5 users | 5 users created | `5` |
| US-002-03 | Returns integer | Any count | `int` |
| US-002-04 | All roles included | user, admin, super_admin | All counted |
| US-002-05 | Soft deletes excluded | 3 active, 2 deleted | `3` (if soft deletes used) |

### US-003: getActiveUsersCount() (logged in within 30 days)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| US-003-01 | No users | Empty DB | `0` |
| US-003-02 | Active users | 5 days, 15 days, 29 days ago | `3` |
| US-003-03 | Null last_login | 1 active, 1 null | `1` |
| US-003-04 | Boundary: exact 30 days | 30 days ago | `1` (included) |
| US-003-05 | Boundary: 31 days | 31 days ago | `0` (excluded) |
| US-003-06 | Returns integer | Any count | `int` |

### US-004: getNewRegistrationsToday()
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| US-004-01 | No registrations today | None today | `0` |
| US-004-02 | 3 registrations today | 3 today, 1 yesterday | `3` |
| US-004-03 | Boundary: midnight | Near midnight | Correct day |
| US-004-04 | Returns integer | Any count | `int` |

### US-005: getNewRegistrationsThisWeek()
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| US-005-01 | No registrations this week | None this week | `0` |
| US-005-02 | 2 this week, 1 last week | Mixed dates | `2` |
| US-005-03 | Boundary: week start | Monday vs Sunday | Correct week |
| US-005-04 | Returns integer | Any count | `int` |

### US-006: getNewRegistrationsThisMonth()
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| US-006-01 | No registrations this month | None this month | `0` |
| US-006-02 | 2 this month, 1 last month | Mixed dates | `2` |
| US-006-03 | Boundary: month start | 1st vs last day | Correct month |
| US-006-04 | Returns integer | Any count | `int` |

### US-007: getGrowthRates()
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| US-007-01 | Returns array structure | Any data | `{daily, weekly, monthly}` |
| US-007-02 | Daily growth: 5 today, 4 yesterday | Today: 5, Yesterday: 4 | `25.0` |
| US-007-03 | Weekly growth: 10 this week, 8 last week | This week: 10, Last: 8 | `25.0` |
| US-007-04 | Monthly growth: 20 this, 15 last | This: 20, Last: 15 | `33.33` |
| US-007-05 | Zero previous period | Today: 5, Yesterday: 0 | Handled gracefully |
| US-007-06 | Zero both periods | No users | `0.0` for all |
| US-007-07 | Negative growth | Today: 3, Yesterday: 5 | `-40.0` |
| US-007-08 | Returns floats | Any data | `float` |
| US-007-09 | Rounds to 1 decimal | 7 today, 3 yesterday | `133.3` |

### US-008: getUsersByPlan()
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| US-008-01 | Returns array | Any data | `array` |
| US-008-02 | No users | Empty DB | `{free: 0, pro: 0, enterprise: 0}` |
| US-008-03 | 100 free users | 100 free | `{free: 100}` |
| US-008-04 | 50 pro users | 50 pro | `{pro: 50}` |
| US-008-05 | 10 enterprise users | 10 enterprise | `{enterprise: 10}` |
| US-008-06 | Mixed plans | 100 free, 50 pro, 10 enterprise | All correct counts |
| US-008-07 | Null plan defaults to free | 2 null plans | Counted as free |
| US-008-08 | Returns integers | Any data | `int` values |
| US-008-09 | Unknown plan handled | Unknown plan | Handled gracefully |

### US-009: getFullStats()
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| US-009-01 | Returns complete structure | Any data | All required keys |
| US-009-02 | Growth rate structure | Any data | `{monthly, weekly, daily}` |
| US-009-03 | By plan structure | Any data | `{free, pro, enterprise}` |
| US-009-04 | Matches SPEC-027 format | Test data | Correct format |
| US-009-05 | All correct types | Any data | Int/float as expected |

### US-010: Performance Tests (< 100ms)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| US-010-01 | getTotalUserCount | 1000 users | < 100ms |
| US-010-02 | getActiveUsersCount | 1000 users | < 100ms |
| US-010-03 | getFullStats | 750 users | < 100ms |
| US-010-04 | getUsersByPlan | 1000 users | < 100ms |

### US-011: Edge Cases
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| US-011-01 | Timezone aware dates | Boundary dates | Correct counts |
| US-011-02 | Large counts | 10000 users | Accurate count |
| US-011-03 | Concurrent queries | 10 parallel queries | Consistent results |
| US-011-04 | Unicode in user data | Japanese name/email | Works correctly |
| US-011-05 | Null last_login_at | Mixed nulls | Active excludes nulls |
| US-011-06 | Empty database | No users | Zero defaults |

### US-012: Security Tests
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| US-012-01 | SQL injection via plan | `free'; DROP TABLE users; --` | Safe execution |
| US-012-02 | Parameter binding used | Any query | Parameterized |
| US-012-03 | No sensitive data in results | User with email/password | Only counts returned |

### US-013: Cache Integration
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| US-013-01 | Cached results respected | 10 users then 5 more | Consistent behavior |
| US-013-02 | Cache key namespaced | Any data | Proper key prefix |

## Mock Data Samples

### User Creation Pattern
```php
// User with specific creation date
User::factory()->create([
    'created_at' => Carbon::parse('2026-03-02 10:00:00'),
]);

// User with last login
User::factory()->create([
    'last_login_at' => Carbon::now()->subDays(15),
]);

// User with plan
User::factory()->create([
    'plan' => 'pro',
]);
```

### Expected Response Format (from SPEC-027)
```json
{
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
```

## Coverage Targets
- **Method Coverage:** 100% (all repository methods)
- **Branch Coverage:** 100% (all conditional paths)
- **Line Coverage:** ≥ 95%

## Test Execution
```bash
# Run all tests for this task
php artisan test --filter=UserStatsRepositoryTest

# Run with coverage
php artisan test --filter=UserStatsRepositoryTest --coverage

# Run specific test category
php artisan test --filter="UserStatsRepositoryTest::getTotalUserCount"
```

## Dependencies

### Blocked By
- **TASK-27-3** (AdminStatsService) - Tests mock the caching interface

### Schema Requirements
The `users` table needs these additional columns:
- `last_login_at` (TIMESTAMP, nullable) - For active users calculation
- `plan` (VARCHAR, nullable, default 'free') - For plan breakdown

### Required Indexes
- `users.created_at` - For registration date queries
- `users.last_login_at` - For active users query

## Notes for Developer

### Implementation Checklist
1. Create migration for `last_login_at` and `plan` columns
2. Add indexes on `created_at` and `last_login_at`
3. Create `UserStatsRepository` at `app/Repositories/UserStatsRepository.php`
4. Implement all methods with Eloquent/Query Builder
5. Integrate with `AdminStatsService` for caching (TASK-27-3)
6. Handle division by zero in growth rate calculations

### Key Implementation Patterns
```php
// Active users query
User::where('last_login_at', '>=', Carbon::now()->subDays(30))->count();

// New registrations today
User::whereDate('created_at', Carbon::today())->count();

// Growth rate calculation (handle zero)
$previous = $this->getNewRegistrationsYesterday();
$current = $this->getNewRegistrationsToday();
$growth = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0.0;

// Users by plan
User::select('plan', DB::raw('count(*) as count'))
    ->groupBy('plan')
    ->pluck('count', 'plan')
    ->toArray();
```

### Edge Case Handling
- **Division by zero:** Return 0.0 when previous period has zero registrations
- **Null plan:** Default to 'free' plan
- **Null last_login_at:** Exclude from active users count

## Comments

### 2026-03-15 - PreTester
**Tests Verified ✅**

- **Test File:** `backend/tests/Unit/Repositories/UserStatsRepositoryTest.php`
- **Test Count:** 62 tests, 111 assertions
- **Status:** All passing (21.87s execution time)

**Test Coverage:**
- Repository instantiation (1 test)
- getTotalUserCount() (5 tests)
- getActiveUsersCount() (6 tests)
- getNewRegistrationsToday() (4 tests)
- getNewRegistrationsThisWeek() (4 tests)
- getNewRegistrationsThisMonth() (4 tests)
- getGrowthRates() (9 tests)
- getUsersByPlan() (9 tests)
- getFullStats() (5 tests)
- Performance tests < 100ms (4 tests)
- Edge cases (6 tests)
- Security tests (3 tests)
- Cache integration (2 tests)

**Acceptance Criteria Status:**
- ✅ Repository methods for total user count
- ✅ Repository methods for active users count (30 days)
- ✅ Repository methods for new registrations (today/week/month)
- ✅ Repository methods for growth rate calculation
- ✅ Repository methods for users by plan breakdown
- ✅ Queries optimized with proper indexes (verified via performance tests)
- ✅ Queries use Eloquent/Query Builder efficiently
- ✅ Results cached via getCachedFullStats() method
- ✅ Unit tests verify calculation accuracy
- ✅ Performance test confirms queries complete < 100ms

**Implementation Already Exists:**
The repository implementation already exists at `app/Repositories/UserStatsRepository.php` with all required methods:
- getTotalUserCount(), getActiveUsersCount()
- getNewRegistrationsToday(), getNewRegistrationsThisWeek(), getNewRegistrationsThisMonth()
- getGrowthRates() with daily/weekly/monthly calculations
- getUsersByPlan() with free/pro/enterprise breakdown
- getFullStats() returning complete structure
- getCachedFullStats() with 5-minute TTL
- clearCache() for cache invalidation

**Test Documentation:** `TESTS/TASK-27-4.md`

**Handoff:** Ready for LocalTester to verify implementation matches tests.

### 2026-03-02 - PreTester
- Created 68 unit tests covering all acceptance criteria
- Tests follow existing repository test patterns (CreditRepositoryTest)
- Performance tests verify < 100ms requirement
- Security tests ensure no SQL injection or data leaks
- Edge cases cover boundary conditions and large datasets
- Tests use RefreshDatabase for clean state
- Carbon::setTestNow() for deterministic date testing

---
*Created by PreTester from TASK-27-4*
