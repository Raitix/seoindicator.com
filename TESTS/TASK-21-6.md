# TESTS/TASK-21-6.md — Caching Layer Tests

## Task Reference
- Task: [TASK-21-6](../TASKS/TASK-21-6.md)
- Specification: [SPEC-021](../SPECIFICATIONS/SPEC-021.md)
- Feature: [FEAT-021](../FEATURES/FEAT-021.md)

---

## Overview

Comprehensive test suite for AI Suggestions caching layer. Tests cover:
- AISuggestionCache service with get/set/invalidate/clear methods
- SHA-256 hash generation for cache keys
- Cache integration with AISuggestionService
- TTL-based cache invalidation
- Configuration options
- Performance metrics logging
- Admin API endpoints for cache management
- Artisan command for cache clearing

---

## Test Files

| File | Type | Tests | Description |
|------|------|-------|-------------|
| `backend/tests/Unit/Services/AI/AISuggestionCacheTest.php` | Unit | 80+ | Core cache service tests |
| `backend/tests/Feature/Services/AI/AISuggestionCacheIntegrationTest.php` | Integration | 25+ | Service integration tests |
| `backend/tests/Feature/Api/V1/Admin/AICacheControllerTest.php` | Feature | 45+ | Admin API endpoint tests |
| `backend/tests/Feature/Console/AICacheClearCommandTest.php` | Feature | 20+ | Artisan command tests |

**Total: 170+ tests**

---

## Test Scenarios

### AC-1: AISuggestionCache Service Created

| ID | Scenario | Method | Expected |
|----|----------|--------|----------|
| SVC-001 | Service instantiation | `app(AISuggestionCache::class)` | Instance created |
| SVC-002 | get method exists | `method_exists($service, 'get')` | true |
| SVC-003 | set method exists | `method_exists($service, 'set')` | true |
| SVC-004 | invalidate method exists | `method_exists($service, 'invalidate')` | true |
| SVC-005 | clear method exists | `method_exists($service, 'clear')` | true |
| SVC-006 | generateHash method exists | `method_exists($service, 'generateHash')` | true |

### AC-2: Request Hash Generation

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| HASH-001 | SHA-256 format | indicator, context, provider | 64 char hex string |
| HASH-002 | Consistent for same input | Same inputs twice | Identical hashes |
| HASH-003 | Differs for different indicators | 'title' vs 'meta_description' | Different hashes |
| HASH-004 | Differs for different context | ['title' => 'A'] vs ['title' => 'B'] | Different hashes |
| HASH-005 | Differs for different providers | 'openrouter' vs 'vercel' | Different hashes |
| HASH-006 | Context normalized (sorted keys) | Same data, different order | Identical hashes |
| HASH-007 | Empty context handled | [] | Valid hash |
| HASH-008 | Unicode handled | ['title' => 'テスト'] | Valid hash |
| HASH-009 | Special characters handled | ['title' => '<script>'] | Valid hash |
| HASH-010 | Hash not reversible | Any hash | Cannot extract input |

### AC-3: Cache Integration with AISuggestionService

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| INT-001 | Check cache before provider | Cached request | Provider NOT called |
| INT-002 | Call provider on miss | Uncached request | Provider called |
| INT-003 | Store successful response | Provider returns success | Cache populated |
| INT-004 | Cache key includes provider | Same input, different provider | Different keys |
| INT-005 | Provider not called on hit | Cache hit | Provider NOT called |
| INT-006 | Cache bypassed when disabled | AI_CACHE_ENABLED=false | Provider always called |
| INT-007 | Not cached on provider error | Provider returns error | Cache NOT populated |
| INT-008 | Not cached on fallback | Fallback response | Cache NOT populated |

### AC-4: Cache Invalidation

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| INV-001 | Manual invalidation via hash | Valid hash | Entry removed |
| INV-002 | Invalidation by indicator | 'title' | All title entries removed |
| INV-003 | Automatic TTL expiration | Expired entry | Returns null |
| INV-004 | Clear all cache | clear() | All entries removed |
| INV-005 | Admin endpoint invalidation | POST /admin/ai/cache/invalidate | Entry removed |
| INV-006 | Admin clear all | DELETE /admin/ai/cache | All cleared |
| INV-007 | Artisan command | `php artisan ai:cache:clear` | All cleared |

### AC-5: Configuration

| ID | Scenario | Config | Expected |
|----|----------|--------|----------|
| CFG-001 | TTL configurable | AI_CACHE_TTL_HOURS=48 | 48 hour TTL |
| CFG-002 | Default TTL | Not set | 24 hours |
| CFG-003 | Cache can be disabled | AI_CACHE_ENABLED=false | Cache bypassed |
| CFG-004 | Cache enabled by default | Not set | true |
| CFG-005 | Invalid TTL rejected | TTL=-1 | Handled gracefully |
| CFG-006 | Valid TTL range | TTL=168 (7 days) | Accepted |

### AC-6: Performance Metrics

| ID | Scenario | Metric | Expected |
|----|----------|--------|----------|
| MET-001 | Hit/miss ratio | getStats() | Contains hits, misses, hit_ratio |
| MET-002 | Cache size | getStats() | Contains total_entries |
| MET-003 | Eviction rate | getStats() | Contains evictions, eviction_rate |
| MET-004 | Hit ratio calculation | 2 hits, 1 miss | ratio ≈ 0.67 |
| MET-005 | Stats reset | resetStats() | Counters reset to 0 |
| MET-006 | Metrics logged | logMetrics() | Log entry created |

---

## Mock Data

### Cached Response Example
```php
$cachedResponse = [
    'indicator' => 'title',
    'score' => 85,
    'issues' => ['Title is too short'],
    'suggestions' => [
        ['type' => 'improve', 'text' => 'Add keywords', 'reason' => 'Improve SEO']
    ],
    'best_practices' => ['Keep under 60 characters'],
    'provider' => 'openrouter',
    'model' => 'llama-3.1-8b-instruct',
    'generated_at' => '2026-03-04T12:00:00Z'
];
```

### Hash Generation Example
```php
$indicator = 'title';
$context = ['title' => 'Test Page', 'keyword' => 'example'];
$provider = 'openrouter';

$hash = $cacheService->generateHash($indicator, $context, $provider);
// Result: 'a1b2c3d4e5f6...' (64 char SHA-256 hash)
```

### Cache Key Pattern
```
ai_suggestions:{hash}
```

### API Request Examples

**Invalidate by hash:**
```json
POST /api/v1/admin/ai/cache/invalidate
{
    "hash": "a1b2c3d4e5f6..."
}
```

**Invalidate by indicator:**
```json
POST /api/v1/admin/ai/cache/invalidate
{
    "indicator": "title"
}
```

**Clear all:**
```
DELETE /api/v1/admin/ai/cache
```

**Get stats:**
```
GET /api/v1/admin/ai/cache/stats
```

---

## Coverage Targets

| Component | Target | Notes |
|-----------|--------|-------|
| AISuggestionCache Service | 95% | Core caching logic |
| Cache Integration | 90% | Service integration |
| Admin Endpoints | 85% | API controller |
| Artisan Command | 80% | CLI interface |

---

## Test Execution

```bash
# Run all cache tests
php artisan test --filter=AISuggestionCache

# Run unit tests only
php artisan test tests/Unit/Services/AI/AISuggestionCacheTest.php

# Run integration tests
php artisan test tests/Feature/Services/AI/AISuggestionCacheIntegrationTest.php

# Run admin API tests
php artisan test tests/Feature/Api/V1/Admin/AICacheControllerTest.php

# Run command tests
php artisan test tests/Feature/Console/AICacheClearCommandTest.php

# Run with coverage
php artisan test --coverage --filter=AISuggestionCache
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-21-1 (AI Service Structure) | Done | AISuggestionService, AIResponse DTOs |
| TASK-21-8 (Database Migrations) | Done | ai_suggestions_cache table |
| Laravel Cache facade | Available | Redis/file/array driver |
| Laravel HTTP Client | Available | For provider mocking |

---

## Notes for Developer

### Implementation Checklist

1. **AISuggestionCache Service** (`backend/app/Services/AI/AISuggestionCache.php`)
   - `generateHash(string $indicator, array $context, string $provider): string`
   - `get(string $hash): ?array`
   - `set(string $hash, array $response, int $ttlHours = 24): void`
   - `invalidate(string $hash): bool`
   - `invalidateByIndicator(string $indicator): int`
   - `clear(): int`
   - `getStats(): array`
   - `resetStats(): void`
   - `logMetrics(): void`

2. **Cache Key Format**
   - Pattern: `ai_suggestions:{hash}`
   - Hash: SHA-256 of normalized JSON

3. **Context Normalization**
   - Sort keys alphabetically
   - JSON encode with consistent flags
   - Handle nested arrays recursively

4. **Configuration** (`config/ai.php`)
   ```php
   'cache' => [
       'enabled' => env('AI_CACHE_ENABLED', true),
       'ttl_hours' => env('AI_CACHE_TTL_HOURS', 24),
   ]
   ```

5. **Admin Controller** (`backend/app/Http/Controllers/Api/V1/Admin/AICacheController.php`)
   - POST `/api/v1/admin/ai/cache/invalidate` - Invalidate entry
   - DELETE `/api/v1/admin/ai/cache` - Clear all
   - GET `/api/v1/admin/ai/cache/stats` - Get statistics

6. **Artisan Command** (`backend/app/Console/Commands/AICacheClear.php`)
   - `php artisan ai:cache:clear`
   - Options: `--indicator=`, `--force`, `--stats`, `--dry-run`

### Key Design Decisions

1. **Cache Store**: Use Laravel Cache facade (supports Redis, file, array)
2. **Hash Algorithm**: SHA-256 for collision resistance
3. **Key Normalization**: Sort keys to ensure consistent hashing
4. **TTL**: Default 24 hours, configurable via env
5. **Metrics**: Track hits, misses, evictions for monitoring

### Security Considerations

1. **Admin-only endpoints**: Require admin role for cache management
2. **Input validation**: Validate hash format and indicator types
3. **Rate limiting**: Apply to admin endpoints
4. **No sensitive data**: Stats don't include cache content

---

## Comments
_Agent log_

### [2026-03-04 15:35 GMT+2] Agent: PreTester
Test suite created for TASK-21-6 Caching Layer.

**Tests Created:**
- Unit: 80+ tests for AISuggestionCache service
- Integration: 25+ tests for service integration
- Feature: 45+ tests for admin API endpoints
- Feature: 20+ tests for artisan command

**Total: 170+ tests**

**Acceptance Criteria Coverage:**
- AC-1: AISuggestionCache Service Created ✅
- AC-2: Request Hash Generation ✅
- AC-3: Cache Integration with AISuggestionService ✅
- AC-4: Cache Invalidation ✅
- AC-5: Configuration ✅
- AC-6: Performance Metrics ✅

**Key Test Patterns:**
- Mockery for provider mocking
- Cache facade for direct cache manipulation
- Sanctum for admin authentication
- Performance assertions (< 5ms for hash, < 1ms for get)
- All 10 indicators tested
- Unicode and edge case handling

Status: `To Write Tests` → `Tests Coverage In Progress`
