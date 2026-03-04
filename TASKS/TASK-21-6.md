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

### [2026-03-04 14:30 GMT+2] ProjectManager - Prioritization Complete
**Priority Confirmed: Medium** ✅
**Risk Confirmed: Low** ✅

**Rationale for Priority (Medium):**
- **Enhancement task** - Improves performance but not required for core functionality
- **Reduces API costs** - Caching saves OpenRouter API calls and tokens
- **Improves response times** - Cache hits return instantly vs 1-5s AI generation
- **Non-blocking** - AI suggestions work without cache (just slower and more expensive)
- **Dependencies satisfied** - TASK-21-1 ✅ and TASK-21-8 ✅ both Done

**Rationale for Risk (Low):**
- **Standard Laravel patterns** - Cache facade, database caching, TTL management
- **No external dependencies** - Internal caching only, no third-party APIs
- **Easily reversible** - `AI_CACHE_ENABLED=false` disables cache instantly
- **No security implications** - Caches non-sensitive AI suggestions
- **Graceful degradation** - Cache miss falls back to AI provider

**Dependency Analysis:**
| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-21-1 (AI Service Structure) | ✅ Done | Provides AISuggestionService and interfaces |
| TASK-21-8 (Database Migrations) | ✅ Done | Creates `ai_suggestions_cache` table |
| AISuggestionCache Model | ✅ Exists | Model created in TASK-21-8 |

**Cache Architecture:**
```
Request → Generate Hash → Check Cache → HIT: Return cached response
                                      → MISS: Call AI provider → Store in cache → Return response
```

**Key Features to Implement:**
1. **Hash Generation** - SHA-256 based on indicator + normalized context + provider
2. **TTL Management** - Configurable via `AI_CACHE_TTL_HOURS` (default: 24)
3. **Invalidation** - Manual (admin endpoint) + automatic (TTL expiration)
4. **Metrics** - Hit/miss ratio, cache size, eviction rate

**Performance Impact:**
- Cache hit: <10ms response time (vs 1-5s AI generation)
- Cost savings: 1 cached response = 1 saved API call
- Expected cache hit rate: 60-80% for common indicators

**Execution Order within SPEC-021:**
1. TASK-21-1 through TASK-21-5: Done ✅
2. TASK-21-6 (this task): To Write Tests
3. TASK-21-7, TASK-21-8: Parallel with this task
4. TASK-21-9+: Vercel provider and polish

**Deliverables:**
| Component | File Path |
|-----------|-----------|
| AISuggestionCache Service | `backend/app/Services/AI/AISuggestionCache.php` |
| Config updates | `backend/config/ai.php` (cache settings) |
| Admin endpoint | `POST /api/v1/admin/ai/cache/invalidate` |
| Clear command | `php artisan ai:cache:clear` |

**Handoff to PreTester:** Ready for test design. Focus on:
- Hash generation consistency (same input = same hash)
- Cache hit/miss flow integration with AISuggestionService
- TTL expiration behavior
- Invalidation endpoints (admin auth required)
- Configuration validation (TTL bounds, enabled flag)
- Performance metrics logging

Status: `To Prioritize` → `To Write Tests`

---
*Created from SPEC-021 on 2026-03-04*
