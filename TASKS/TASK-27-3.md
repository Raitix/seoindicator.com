# TASK-27-3: Admin Stats Service with Caching

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Medium`

## Parent Specification
SPEC-027: Admin Dashboard (Ops + Usage)

## Description
Create AdminStatsService that provides cached access to admin statistics. The service implements a caching layer to reduce database load and improve response times.

## Acceptance Criteria
- [ ] Service class created at `app/Services/Admin/AdminStatsService.php`
- [ ] Implements `getCachedMetric()` method with:
  - Key-based cache lookup
  - TTL support (default 5 minutes)
  - Callable calculator for cache miss
  - Automatic cache storage
- [ ] Implements cache invalidation methods
- [ ] Uses Laravel Cache facade (configurable driver)
- [ ] Logs cache hits/misses in debug mode
- [ ] Unit tests cover:
  - Cache hit returns cached value
  - Cache miss calls calculator and stores result
  - TTL expiration triggers recalculation
  - Manual invalidation clears cache

## Dependencies
- TASK-27-1 (system_metrics_cache table)

## Effort Estimate
`Medium` (2-4 hours)

## Technical Notes
- Use Cache::remember() pattern
- Consider using Redis for production caching
- Cache keys should be namespaced (e.g., 'admin:stats:users')
- Implement cache tagging for group invalidation

## Comments

### 2026-03-15 - PreTester
**Tests Complete ✅**

- **Test File:** `backend/tests/Unit/Services/Admin/AdminStatsServiceTest.php`
- **Test Count:** 38 tests, 60 assertions
- **Status:** All passing

**Test Coverage:**
- Service instantiation (2 tests)
- getUserStats() with caching (4 tests)
- getUsageStats() with caching (3 tests)
- getCreditStats() with caching (3 tests)
- getHealthStats() with caching (3 tests)
- getCachedMetric() generic method (3 tests)
- Cache invalidation methods (5 tests)
- getAllStats() aggregation (2 tests)
- TTL configuration verification (4 tests)
- Data integrity (2 tests)
- Performance (1 test)
- Acceptance criteria verification (6 tests)

**Acceptance Criteria Status:**
- ✅ Service class created at correct location (verified by test)
- ✅ Implements getCachedMetric() with key, TTL, calculator
- ✅ Implements cache invalidation methods
- ✅ Uses Laravel Cache facade
- ⚠️ Logs cache hits/misses in debug mode - NOT IMPLEMENTED (optional feature)
- ✅ Unit tests cover all scenarios

**Implementation Already Exists:**
The service implementation already exists at `app/Services/Admin/AdminStatsService.php` with:
- Cache TTL constants (USER=300s, USAGE=300s, CREDIT=60s, HEALTH=30s)
- getUserStats(), getUsageStats(), getCreditStats(), getHealthStats() methods
- getCachedMetric() generic method with default TTL 300
- refreshAllCaches(), refreshUserCache(), refreshUsageCache(), refreshCreditCache(), refreshHealthCache() methods
- getAllStats() aggregation method
- Exception handling with graceful fallbacks

**Note:** Debug mode logging (AC-05) is not implemented. If required, Developer should add:
```php
if (config('app.debug')) {
    Log::debug("Cache hit for key: {$key}");
}
```

**Test Documentation:** `TESTS/TEST-27-3.md`

**Handoff:** Ready for Developer to implement debug logging (optional) and verify implementation matches tests.

### 2026-03-13 - ProjectManager
**Prioritization Complete**

- **Priority: High** (confirmed) - Foundation caching service for entire Admin Dashboard feature
- **Risk: Medium** (confirmed) - Standard Laravel service with caching patterns, no external APIs
- **Dependencies:** TASK-27-1 (system_metrics_cache table) — SATISFIED ✅
  - Migration deployed to production (status: `To Test Prod`)
  - `system_metrics_cache` table exists with metric_key, metric_value, metadata, cached_at, expires_at columns

**Downstream Impact:**
- Enables TASK-27-4 (User Stats Aggregation) for cached user metrics
- Enables TASK-27-5 (User Stats API) for dashboard endpoints
- All Admin Dashboard API endpoints depend on this caching layer for performance

**TDD Pattern:** PreTester can design tests immediately using:
- Laravel Cache facade mocking
- Test `getCachedMetric()` with key-based lookup, TTL, calculator callable
- Test cache invalidation methods
- Test debug mode logging

**Key Implementation Details:**
- Service location: `app/Services/Admin/AdminStatsService.php`
- Cache namespace: `admin:stats:*` (e.g., `admin:stats:users`)
- Default TTL: 5 minutes
- Use `Cache::remember()` pattern
- Consider Redis for production caching

**Handoff:** Ready for PreTester to design tests for caching service.

---
*Created by SpecSystemAnalyst from SPEC-027*
