# TASK-27-3: Admin Stats Service with Caching

## STATUS
`Prioritization In Progress`

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

---
*Created by SpecSystemAnalyst from SPEC-027*
