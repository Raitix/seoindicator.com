# TASK-21-6: Implement Caching Layer

## STATUS
`To Prioritize`

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

(No entries yet)

---
*Created by SpecSystemAnalyst on 2026-03-01*
