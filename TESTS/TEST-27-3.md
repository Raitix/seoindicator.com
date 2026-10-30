# TEST-27-3: Admin Stats Service with Caching

## Task Reference
- **Task:** TASK-27-3
- **Feature:** FEAT-027 (Admin Dashboard)
- **Specification:** SPEC-027
- **Status:** Tests Complete ✅

## Overview

Unit tests for `AdminStatsService` - the main orchestrator service that aggregates all admin dashboard metrics with appropriate caching for each metric type.

## Test Files

| File | Tests | Assertions | Status |
|------|-------|------------|--------|
| `backend/tests/Unit/Services/Admin/AdminStatsServiceTest.php` | 38 | 60 | ✅ PASS |

## Test Scenarios

### Service Instantiation (2 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| SI-01 | Service can be instantiated via container | Instance of AdminStatsService |
| SI-02 | Required dependencies are injected | Properties: userAggregator, usageAggregator, creditAggregator, healthChecker |

### getUserStats() Tests (4 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| US-01 | Returns array from user aggregator | Array with total, active, newThisMonth, growthRate, byPlan |
| US-02 | Caches result for 5 minutes (300s) | Cache::remember called with TTL 300 |
| US-03 | Returns cached value on subsequent calls | Same result without calling aggregator again |
| US-04 | Returns empty array on aggregator exception | [] |

### getUsageStats() Tests (3 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| UG-01 | Returns array from usage aggregator | Array with requests, tokens, analyses |
| UG-02 | Caches result for 5 minutes (300s) | Cache::remember called with TTL 300 |
| UG-03 | Returns empty array on aggregator exception | [] |

### getCreditStats() Tests (3 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| CS-01 | Returns array from credit aggregator | Array with balances, spend, revenue |
| CS-02 | Caches result for 1 minute (60s) | Cache::remember called with TTL 60 |
| CS-03 | Returns empty array on aggregator exception | [] |

### getHealthStats() Tests (3 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| HS-01 | Returns array from health checker | Array with status, uptime, services, alerts |
| HS-02 | Caches result for 30 seconds | Cache::remember called with TTL 30 |
| HS-03 | Returns unhealthy status on exception | ['status' => 'unhealthy', 'error' => message] |

### getCachedMetric() Tests (3 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| CM-01 | Stores result in cache with default TTL | Cache::remember with TTL 300 |
| CM-02 | Uses custom TTL when provided | Cache::remember with custom TTL |
| CM-03 | Executes calculator on cache miss | Calculator called, result returned |

### Cache Invalidation Tests (5 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| CI-01 | refreshAllCaches clears all 4 keys | Cache::forget called for all keys |
| CI-02 | refreshUserCache clears only user cache | Cache::forget for admin.stats.users |
| CI-03 | refreshUsageCache clears only usage cache | Cache::forget for admin.stats.usage |
| CI-04 | refreshCreditCache clears only credit cache | Cache::forget for admin.stats.credits |
| CI-05 | refreshHealthCache clears only health cache | Cache::forget for admin.stats.health |

### getAllStats() Tests (2 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| AS-01 | Returns all stat categories | Array with keys: users, usage, credits, health |
| AS-02 | Returns correct structure for each category | Nested arrays with expected data |

### TTL Configuration Tests (4 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| TTL-01 | Cache TTL constants are correctly defined | USER=300, USAGE=300, CREDIT=60, HEALTH=30 |
| TTL-02 | Different cache keys prevent collision | All 4 keys are unique |
| TTL-03 | Health stats has shortest TTL | HEALTH < USER, USAGE, CREDIT |
| TTL-04 | Credit stats shorter than user/usage | CREDIT < USER, CREDIT < USAGE |

### Data Integrity Tests (2 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| DI-01 | getUserStats preserves numeric precision | Float values maintain precision |
| DI-02 | getCreditStats preserves decimal precision | Currency values maintain precision |

### Performance Tests (1 test)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| PF-01 | All stats retrieval completes under 200ms | Elapsed time < 200ms |

### Acceptance Criteria Tests (6 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| AC-01 | Service class created at correct location | File exists at app/Services/Admin/AdminStatsService.php |
| AC-02 | Implements getCachedMetric with key and TTL | Method exists with params: key, calculator, ttl (default 300) |
| AC-03 | Cache hit returns cached value | Subsequent calls return same result |
| AC-04 | Cache miss calls calculator and stores result | Calculator executed, result cached |
| AC-05 | Manual invalidation clears cache | Cache::forget called for specific key |
| AC-06 | Uses Laravel Cache facade | Import statement present |

## Mock Data Patterns

### User Stats Mock
```php
$expectedUserStats = [
    'total' => 1250,
    'active' => 890,
    'newThisMonth' => 156,
    'newThisWeek' => 42,
    'newToday' => 8,
    'growthRate' => [
        'monthly' => 14.2,
        'weekly' => 5.8,
        'daily' => 1.2,
    ],
    'byPlan' => [
        'free' => 1000,
        'pro' => 200,
        'enterprise' => 50,
    ],
];
```

### Usage Stats Mock
```php
$expectedUsageStats = [
    'requests' => [
        'total' => 156789,
        'today' => 2340,
        'thisWeek' => 15680,
        'thisMonth' => 48920,
    ],
    'tokens' => [
        'total' => 12500000,
        'today' => 156000,
        'byModel' => [
            'gpt-4' => 5000000,
            'gpt-3.5-turbo' => 6000000,
        ],
    ],
    'analyses' => [
        'total' => 12340,
        'today' => 156,
    ],
];
```

### Credit Stats Mock
```php
$expectedCreditStats = [
    'balances' => [
        'totalHeld' => 15678.50,
        'averagePerUser' => 12.54,
        'usersWithBalance' => 890,
        'lowBalanceCount' => 234,
    ],
    'spend' => [
        'total' => 45678.90,
        'today' => 567.80,
    ],
    'revenue' => [
        'total' => 78900.00,
        'thisMonth' => 15600.00,
    ],
];
```

### Health Stats Mock
```php
$expectedHealthStats = [
    'status' => 'healthy',
    'uptime' => 99.98,
    'lastChecked' => '2026-03-02T10:00:00Z',
    'services' => [
        'database' => [
            'status' => 'healthy',
            'latencyMs' => 12,
            'connections' => 45,
        ],
        'queue' => [
            'status' => 'healthy',
            'pending' => 23,
            'failed' => 2,
            'workers' => 5,
        ],
        'cache' => [
            'status' => 'healthy',
            'hitRate' => 94.5,
        ],
        'storage' => [
            'status' => 'healthy',
            'usedPercent' => 34,
        ],
    ],
    'alerts' => [],
];
```

## Cache Keys

| Key | TTL | Purpose |
|-----|-----|---------|
| `admin.stats.users` | 300s (5 min) | User statistics |
| `admin.stats.usage` | 300s (5 min) | Usage statistics |
| `admin.stats.credits` | 60s (1 min) | Credit statistics |
| `admin.stats.health` | 30s | Health check status |

## TTL Rationale

| Metric | TTL | Reason |
|--------|-----|--------|
| Users | 5 min | User counts change slowly |
| Usage | 5 min | Usage aggregations are batched |
| Credits | 1 min | Real-time balance/spend tracking |
| Health | 30s | Near real-time system status |

## Test Execution

```bash
cd backend
php artisan test --filter=AdminStatsServiceTest
```

## Dependencies

| Component | Type | Purpose |
|-----------|------|---------|
| UserAggregationService | Service | User statistics aggregation |
| UsageAggregationService | Service | Usage statistics aggregation |
| CreditAggregationService | Service | Credit statistics aggregation |
| HealthCheckService | Service | System health monitoring |
| Illuminate\Support\Facades\Cache | Facade | Laravel cache driver |

## Coverage Targets

| Component | Statements | Branches | Functions | Lines |
|-----------|------------|----------|-----------|-------|
| AdminStatsService | 100% | 90% | 100% | 100% |

## Notes for Developer

1. **TTL Strategy**: Health stats have shortest TTL (30s) for near real-time monitoring, credit stats (60s) for balance tracking, user/usage stats (5 min) for stable metrics.

2. **Error Handling**: All stat methods gracefully handle exceptions:
   - User/Usage/Credit stats return `[]` on error
   - Health stats return `['status' => 'unhealthy', 'error' => message]`

3. **Cache Keys**: All use `admin.stats.*` namespace for easy identification and bulk clearing.

4. **Dependencies**: Service requires 4 aggregator services injected via constructor.

---

**PreTester Agent Log:**
- 2026-03-15: Fixed 8 risky tests by adding explicit assertions
- 2026-03-15: Added 13 new tests for TTL, getAllStats, and acceptance criteria
- 2026-03-15: All 38 tests passing (60 assertions)
