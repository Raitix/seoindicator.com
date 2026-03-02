# TESTS/TASK-21-6.md — AI Suggestion Cache Tests

## Task Reference
- **Task:** [TASK-21-6](../TASKS/TASK-21-6.md) - Implement Caching Layer
- **Specification:** [SPEC-021](../SPECIFICATIONS/SPEC-021.md) - AI Suggestions via OpenRouter/Vercel Gateway
- **Agent:** PreTester
- **Date:** 2026-03-02

---

## Overview

Test suite for the AI Suggestion Cache service. The cache layer stores AI suggestion responses for identical requests to reduce API calls, improve response times, and conserve free tier quotas.

### Test Coverage

| Category | Tests | Status |
|----------|-------|--------|
| AC-1: Cache Service Created | 7 | ✅ Ready |
| AC-2: Request Hashing | 8 | ✅ Ready |
| AC-3: Cache Storage | 3 | ✅ Ready |
| AC-7: Unit Tests - Operations | 8 | ✅ Ready |
| AC-7: Unit Tests - TTL | 1 | ✅ Ready |
| AC-7: Unit Tests - Data Integrity | 2 | ✅ Ready |
| AC-7: Unit Tests - Edge Cases | 5 | ✅ Ready |
| AC-8: Feature Tests - Cache Hit | 2 | ✅ Ready |
| AC-8: Feature Tests - Cache Miss | 3 | ✅ Ready |
| AC-8: Feature Tests - Cache Bypass | 2 | ✅ Ready |
| AC-8: Feature Tests - Debug Mode | 2 | ✅ Ready |
| AC-8: Feature Tests - Integration | 3 | ✅ Ready |
| AC-8: Feature Tests - Performance | 1 | ✅ Ready |
| Documentation | 2 | ✅ Ready |
| **Total** | **49** | |

---

## Test Files

| File | Type | Tests |
|------|------|-------|
| `backend/tests/Unit/Services/AI/AISuggestionCacheTest.php` | Unit | 41 |
| `backend/tests/Feature/Services/AI/AISuggestionCacheIntegrationTest.php` | Feature | 18 |

---

## Test Scenarios

### Unit Tests - AC-1: Cache Service Created

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CACHE-001 | Service file exists | N/A | File at `app/Services/AI/AISuggestionCache.php` |
| CACHE-002 | Service class instantiable | N/A | Instance of `AISuggestionCache` |
| CACHE-003 | get method exists | N/A | Returns `?SuggestionResult` |
| CACHE-004 | put method exists | N/A | Returns `void` |
| CACHE-005 | has method exists | N/A | Returns `bool` |
| CACHE-006 | forget method exists | N/A | Returns `void` |
| CACHE-007 | clear method exists | N/A | Returns `void` |

### Unit Tests - AC-2: Request Hashing

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CACHE-010 | Hash is deterministic | Same indicator + context | Same hash |
| CACHE-011 | Hash is 64 characters | Any input | 64 char string |
| CACHE-012 | Hash is hexadecimal | Any input | `[a-f0-9]{64}` |
| CACHE-013 | Different indicators | 'title' vs 'meta_description' | Different hashes |
| CACHE-014 | Different context | Different URLs | Different hashes |
| CACHE-015 | Context key order | Different key order | Same hash (ksort) |
| CACHE-016 | Empty context | `[]` | Valid 64-char hash |
| CACHE-017 | Nested context | Nested arrays | Consistent hash |

### Unit Tests - AC-3: Cache Storage

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CACHE-020 | Cache key prefix | Any hash | Starts with `ai_suggestion_` |
| CACHE-021 | TTL from config | `ttl_hours: 48` | 172800 seconds |
| CACHE-022 | TTL default | null config | 86400 seconds (24h) |

### Unit Tests - AC-7: Operations

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CACHE-030 | get missing key | Non-existent key | `null` |
| CACHE-031 | put stores result | SuggestionResult | `has()` returns true |
| CACHE-032 | get retrieves result | Cached result | Same data |
| CACHE-033 | has returns false | Missing key | `false` |
| CACHE-034 | has returns true | Cached key | `true` |
| CACHE-035 | forget removes item | Cached key | `has()` returns false |
| CACHE-036 | clear removes all | Multiple cached items | All `has()` return false |
| CACHE-037 | Clear only AI cache | Mixed cache types | Non-AI cache preserved |

### Unit Tests - AC-7: TTL Expiration

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CACHE-040 | Entry expires after TTL | TTL elapsed | `has()` returns false |

### Unit Tests - AC-7: Data Integrity

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CACHE-050 | All data preserved | Complex result | All fields match |
| CACHE-051 | Complex context | Nested arrays | Handled correctly |

### Unit Tests - AC-7: Edge Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CACHE-060 | Unicode in context | Cyrillic, Latvian | Hash works |
| CACHE-061 | Special characters | Quotes, symbols | Hash works |
| CACHE-062 | Large context | ~3000 chars | Hash works |
| CACHE-063 | Numeric values | Int, float, bool | Handled correctly |
| CACHE-064 | Null values | null in context | Handled correctly |

### Feature Tests - AC-8: Cache Hit

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| INT-001 | Cache hit returns cached | Pre-cached result | No provider call, cached score |
| INT-002 | Provider info preserved | Different provider in cache | Original provider returned |

### Feature Tests - AC-8: Cache Miss

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| INT-010 | Cache miss calls provider | Empty cache | Provider called, result cached |
| INT-011 | Second call uses cache | After first call | No second provider call |
| INT-012 | Different context | Different URLs | Separate cache entries |

### Feature Tests - AC-8: Cache Bypass

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| INT-020 | no_cache=true | Cached data exists | Provider called, fresh result |
| INT-021 | Bypass doesn't update cache | Cached + bypass | Original cache preserved |

### Feature Tests - AC-8: Debug Mode

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| INT-030 | Debug mode bypasses cache | `ai.debug: true` | Provider called |
| INT-031 | Debug mode doesn't cache | `ai.debug: true` | Cache not populated |

### Feature Tests - AC-8: Integration

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| INT-040 | Service uses cache | Normal flow | Cache populated |
| INT-041 | Multiple indicators | Different types | Independent caching |
| INT-042 | URL isolation | Different URLs | Separate entries |

### Feature Tests - AC-8: Performance

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| INT-050 | Cache hit faster | Time comparison | Cache hit 10x+ faster |

---

## Mock Data

### Sample SuggestionResult

```php
new SuggestionResult(
    indicator: 'title',
    score: 85,
    issues: ['Too short', 'Missing keywords'],
    suggestions: [
        ['type' => 'improve', 'text' => 'Add primary keyword', 'reason' => 'Boosts relevance'],
    ],
    bestPractices: ['Keep title 50-60 characters'],
    provider: 'openrouter',
    model: 'llama-3.1',
    generatedAt: new \DateTimeImmutable()
);
```

### Sample Context

```php
$context = [
    'url' => 'https://example.com',
    'title' => 'Current Page Title',
    'length' => 60,
    'options' => [
        'maxLength' => 60,
        'keywords' => ['product', 'brand'],
    ],
];
```

---

## Coverage Targets

| Component | Target | Notes |
|-----------|--------|-------|
| AISuggestionCache | 100% | All methods covered |
| Hash generation | 100% | All edge cases |
| TTL logic | 100% | Config and default |
| Integration | 100% | All scenarios |

---

## Test Execution

```bash
# Run all cache tests
cd backend
php artisan test --group=task-21-6

# Run unit tests only
php artisan test tests/Unit/Services/AI/AISuggestionCacheTest.php

# Run feature tests only
php artisan test tests/Feature/Services/AI/AISuggestionCacheIntegrationTest.php

# Run with coverage
php artisan test --group=task-21-6 --coverage
```

---

## Dependencies

### Required Classes (to be implemented by Developer)

1. **AISuggestionCache** (`app/Services/AI/AISuggestionCache.php`)
   - `get(string $indicator, array $context): ?SuggestionResult`
   - `put(string $indicator, array $context, SuggestionResult $result): void`
   - `has(string $indicator, array $context): bool`
   - `forget(string $indicator, array $context): void`
   - `clear(): void`
   - `generateHash(string $indicator, array $context): string` (private)
   - `getCacheKey(string $hash): string` (private)
   - `getTtlSeconds(): int` (private)

### Existing Dependencies

- `App\Services\AI\DTOs\SuggestionResult` - Already exists
- `Illuminate\Support\Facades\Cache` - Laravel built-in
- `config('ai.cache.ttl_hours')` - Already configured

---

## Notes for Developer

### Implementation Priority

1. **Hash Generation** - Core functionality, must be deterministic
2. **get/put/has** - Basic cache operations
3. **Integration** - Update AISuggestionService to use cache
4. **forget/clear** - Cache management methods

### Key Implementation Details

```php
// Hash generation pattern
private function generateHash(string $indicator, array $context): string
{
    ksort($context); // Sort keys for consistency
    $data = $indicator . ':' . json_encode($context);
    return hash('sha256', $data);
}

// Cache key format
private function getCacheKey(string $hash): string
{
    return 'ai_suggestion_' . $hash;
}

// TTL from config
private function getTtlSeconds(): int
{
    $hours = config('ai.cache.ttl_hours', 24);
    return $hours * 3600;
}
```

### Integration Pattern

```php
// In AISuggestionService::getSuggestions()
public function getSuggestions(string $indicator, array $context = [], bool $noCache = false): SuggestionResult
{
    // Check debug mode
    if ($this->isDebugMode()) {
        return $this->generateFresh($indicator, $context);
    }
    
    // Check cache bypass
    if ($noCache) {
        return $this->generateFresh($indicator, $context);
    }
    
    // Check cache
    $cached = $this->cache->get($indicator, $context);
    if ($cached !== null) {
        return $cached;
    }
    
    // Generate fresh result
    $result = $this->generateFresh($indicator, $context);
    
    // Store in cache
    $this->cache->put($indicator, $context, $result);
    
    return $result;
}
```

---

## Comments

### [2026-03-02 12:40 GMT+2] PreTester - Tests Created

**Test Files Created:**
- `backend/tests/Unit/Services/AI/AISuggestionCacheTest.php` (41 tests)
- `backend/tests/Feature/Services/AI/AISuggestionCacheIntegrationTest.php` (18 tests)

**Coverage:**
- AC-1: Cache Service Created ✅
- AC-2: Request Hashing ✅
- AC-3: Cache Storage ✅
- AC-4: Integration (via feature tests) ✅
- AC-7: Unit Tests ✅
- AC-8: Feature Tests ✅

**Key Test Patterns:**
- Reflection for testing private hash generation
- Mockery for cache facade mocking
- Integration tests mock provider to verify cache behavior
- Edge cases cover unicode, special chars, large contexts

**Status:** Ready for Developer implementation.

---
*Created by PreTester on 2026-03-02*
