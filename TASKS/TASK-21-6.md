# TASK-21-6: Implement Caching Layer

## STATUS
`To Write Tests`

## PRIORITY
`Medium`

## RISK
`Low`

## Specification
[SPEC-021](../SPECIFICATIONS/SPEC-021.md) - AI Suggestions via OpenRouter/Vercel Gateway

## Description
Implement a caching layer to store AI suggestion responses for identical requests. This reduces API calls, improves response times, and conserves free tier quotas.

---

## Acceptance Criteria

### AC-1: Cache Service Created
- [ ] File exists at `backend/app/Services/AI/AISuggestionCache.php`
- [ ] Method `get(string $indicator, array $context): ?SuggestionResult`
- [ ] Method `put(string $indicator, array $context, SuggestionResult $result): void`
- [ ] Method `has(string $indicator, array $context): bool`
- [ ] Method `forget(string $indicator, array $context): void`
- [ ] Method `clear(): void` (clear all AI cache)

### AC-2: Request Hashing
- [ ] Creates deterministic hash from indicator + context
- [ ] Uses SHA-256 for consistent hashing
- [ ] Handles array context serialization consistently
- [ ] Hash is 64 characters

### AC-3: Cache Storage
- [ ] Uses Laravel Cache facade
- [ ] Configurable driver (default: file)
- [ ] TTL configurable via `config('ai.cache.ttl_hours')` (default: 24 hours)
- [ ] Cache key prefix: `ai_suggestion_`

### AC-4: Integration with AISuggestionService
- [ ] Service checks cache before calling provider
- [ ] Service stores result in cache after successful generation
- [ ] Cache bypass option via `?no_cache=true` (debug only)
- [ ] Cache not used in debug mode

### AC-5: Database Cache (Optional)
- [ ] Migration for `ai_suggestions_cache` table
- [ ] Alternative to file cache for multi-server setups
- [ ] Automatic cleanup of expired entries
- [ ] Index on `request_hash` for fast lookups

### AC-6: Cache Invalidation
- [ ] Automatic expiration via TTL
- [ ] Manual clear via artisan command: `php artisan ai:clear-cache`
- [ ] Clear on configuration change (optional)

### AC-7: Unit Tests
- [ ] Test file at `backend/tests/Unit/Services/AI/AISuggestionCacheTest.php`
- [ ] Tests for get/put/has operations
- [ ] Tests for hash consistency
- [ ] Tests for TTL expiration
- [ ] Tests for clear operation
- [ ] All tests passing

### AC-8: Feature Tests
- [ ] Tests for cache hit scenario
- [ ] Tests for cache miss scenario
- [ ] Tests for cache bypass
- [ ] All tests passing

---

## Dependencies
- TASK-21-1 (AI Service Structure)
- TASK-21-5 (AI Suggestion API Endpoint)

---

## Effort Estimate
**Small** (2-3 hours)

- Cache service: 1 hour
- Service integration: 0.5 hours
- Tests: 1.5 hours

---

## Technical Notes

### Hash Generation
```php
private function generateHash(string $indicator, array $context): string
{
    // Sort context for consistent hashing
    ksort($context);
    $data = $indicator . ':' . json_encode($context);
    return hash('sha256', $data);
}
```

### Cache Key Format
```
ai_suggestion_<hash>
```

### Database Schema (Optional)
```sql
CREATE TABLE ai_suggestions_cache (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_hash VARCHAR(64) NOT NULL UNIQUE,
    indicator VARCHAR(50) NOT NULL,
    response_json TEXT NOT NULL,
    provider VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    
    INDEX idx_hash (request_hash),
    INDEX idx_expires (expires_at)
);
```

---

## Comments
_Agent log_

### [2026-03-02 11:08 GMT+2] ProjectManager - Prioritization Complete
**Priority Confirmed: Medium** ✅
**Risk Confirmed: Low** ✅

**Rationale for Priority (Medium):**
- **Enhancement task** - Not foundation, but valuable optimization
- **Reduces API costs** - Caches identical requests, conserving free tier quotas
- **Improves response times** - Cache hits are instant vs AI API latency
- **Non-blocking** - No tasks depend on this; feature works without caching
- **Well-understood pattern** - Laravel Cache facade with TTL

**Rationale for Risk (Low):**
- Standard caching implementation using Laravel Cache facade
- No external dependencies (uses built-in cache drivers)
- Easily reversible - disable or clear cache without breaking functionality
- TTL-based expiration prevents stale data issues
- No security implications (cache key hashing is deterministic)

**Dependency Analysis:**
| Task | Status | Ready? |
|------|--------|--------|
| TASK-21-1 (AI Service Structure) | `Done` | ✅ |
| TASK-21-5 (AI Suggestion API Endpoint) | `Done` | ✅ |

**Execution Order within SPEC-021:**
1. TASK-21-1 through TASK-21-5: Done ✅
2. TASK-21-6 (this): Can proceed immediately
3. TASK-21-7, 21-8: Can parallelize with this task
4. TASK-21-9+: Vercel provider and polish

**Technical Notes for PreTester:**
- Cache service will use Laravel Cache facade
- Hash generation: SHA-256 of indicator + sorted context JSON
- Cache key prefix: `ai_suggestion_`
- TTL: 24 hours default (configurable via `config('ai.cache.ttl_hours')`)
- Optional database cache for multi-server setups
- Clear command: `php artisan ai:clear-cache`

**Handoff to PreTester:** Ready for test design. Focus on:
- AISuggestionCache service: get/put/has/forget/clear methods
- Hash generation: deterministic SHA-256 from indicator + context
- TTL expiration testing
- Integration with AISuggestionService (cache hit/miss scenarios)
- Cache bypass via `?no_cache=true` (debug only)
- Artisan command for cache clearing

Status: `To Prioritize` → `To Write Tests`

---
*Created by SpecSystemAnalyst on 2026-03-01*
