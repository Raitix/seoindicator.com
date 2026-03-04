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
Implement a caching layer for AI suggestions to reduce API calls and improve response times. Cache stores responses for identical requests with a configurable TTL (default 24 hours).

---

## Acceptance Criteria

### AC-1: AISuggestionCache Service Created
- [ ] File exists at `backend/app/Services/AI/AISuggestionCache.php`
- [ ] Method `get(string $requestHash): ?array`
- [ ] Method `set(string $requestHash, array $response, int $ttlHours = 24): void`
- [ ] Method `invalidate(string $requestHash): void`
- [ ] Method `clear(): void`

### AC-2: Request Hash Generation
- [ ] Consistent hash based on indicator + context
- [ ] Uses SHA-256 for hash generation
- [ ] Context is normalized before hashing (sorted keys)

### AC-3: Cache Integration with AISuggestionService
- [ ] Check cache before calling AI provider
- [ ] Store successful responses in cache
- [ ] Cache key includes provider name

### AC-4: Cache Invalidation
- [ ] Manual invalidation endpoint for admins
- [ ] Automatic expiration based on TTL
- [ ] Clear all cache command available

### AC-5: Configuration
- [ ] Cache TTL configurable via `AI_CACHE_TTL_HOURS` env variable
- [ ] Default TTL: 24 hours
- [ ] Cache can be disabled via `AI_CACHE_ENABLED=false`

### AC-6: Performance Metrics
- [ ] Log cache hit/miss ratio
- [ ] Track cache size
- [ ] Monitor cache eviction rate

---

## Dependencies
- TASK-21-1: AI Service Structure (Done)
- TASK-21-8: Database Migrations (Documentation)

---

## Effort Estimate
**Medium** (2-3 hours)

---

## Technical Notes

### Cache Key Generation
```php
public function generateHash(string $indicator, array $context, string $provider): string
{
    $data = [
        'indicator' => $indicator,
        'context' => $this->normalizeContext($context),
        'provider' => $provider,
    ];
    return hash('sha256', json_encode($data));
}
```

### Cache Flow
```
1. Generate request hash
2. Check cache for hash
3. If hit → return cached response (skip AI call)
4. If miss → call AI provider
5. Store response in cache
6. Return response
```

---

## Comments
_Agent log_

### [2026-03-04 13:35 GMT+2] Agent: ProjectManager
Task file created from SPEC-021 specification. Previously empty file (0 bytes).
This task was never properly created by SpecSystemAnalyst - file existed but had no content.
Status set to `To Prioritize` for normal workflow processing.

---
*Created from SPEC-021 on 2026-03-04*
