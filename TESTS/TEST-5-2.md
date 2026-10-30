# TEST-5-2: Suggestion Model and Repository

## Task Reference
- **Task**: [TASK-5-2](../TASKS/TASK-5-2.md)
- **Specification**: [SPEC-005](../SPECIFICATIONS/SPEC-005.md)
- **Feature**: [FEAT-005](../FEATURES/FEAT-005.md)

## Overview
Tests for the KeywordSuggestion Eloquent model and KeywordSuggestionRepository. Provides data access layer for the keyword discovery feature. Tests follow TDD principles - written before implementation.

---

## Test Files

| File | Type | Description |
|------|------|-------------|
| `backend/tests/Unit/Models/KeywordSuggestionTest.php` | Unit | Model tests: relationships, scopes, accessors, configuration |
| `backend/tests/Unit/Repositories/KeywordSuggestionRepositoryTest.php` | Unit | Repository tests: CRUD operations, queries, state changes |

---

## Test Scenarios

### 1. KeywordSuggestion Model Tests (19 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| M-001 | BelongsTo site relationship | `$suggestion->site` | Returns Site model instance |
| M-002 | Scope bySite($siteId) | `KeywordSuggestion::bySite(1)->get()` | Returns only suggestions for site 1 |
| M-003 | Scope bySource($source) | `KeywordSuggestion::bySource('suggest')->get()` | Returns only suggestions with source='suggest' |
| M-004 | Scope notDismissed() | `KeywordSuggestion::notDismissed()->get()` | Returns suggestions where is_dismissed=false |
| M-005 | Scope notTracking() | `KeywordSuggestion::notTracking()->get()` | Returns suggestions where is_tracking=false |
| M-006 | Scope highOpportunity($min) | `KeywordSuggestion::highOpportunity(70)->get()` | Returns suggestions with opportunity_score >= 70 |
| M-007 | Accessor: tracking_status | `$suggestion->tracking_status` | Returns 'tracking', 'available', or 'dismissed' |
| M-008 | Fillable attributes | Mass assignment | Only: site_id, keyword, source, search_volume, difficulty_score, opportunity_score, is_tracking, is_dismissed |
| M-009 | Casts: boolean fields | `$suggestion->is_tracking` | Returns boolean, not integer |
| M-010 | Casts: timestamp fields | `$suggestion->discovered_at` | Returns Carbon instance |

### Model Scopes Detail

**bySite(int $siteId)** - Filter by site
```php
// Returns Builder for chaining
KeywordSuggestion::bySite(1)->get();
```

**bySource(string $source)** - Filter by source
```php
// Valid sources: 'suggest', 'competitor', 'related'
KeywordSuggestion::bySource('suggest')->get();
```

**notDismissed()** - Exclude dismissed suggestions
```php
// Default scope for most queries
KeywordSuggestion::notDismissed()->get();
```

**notTracking()** - Exclude tracking suggestions
```php
// Find suggestions not yet being tracked
KeywordSuggestion::notTracking()->get();
```

**highOpportunity(int $minScore = 70)** - Filter by minimum opportunity score
```php
// Find high-value suggestions
KeywordSuggestion::highOpportunity(70)->get();
```

### Model Accessor: tracking_status

Returns string based on suggestion state:
| Condition | Returns |
|-----------|---------|
| is_tracking = true | 'tracking' |
| is_dismissed = true | 'dismissed' |
| otherwise | 'available' |

---

### 2. KeywordSuggestionRepository Tests (26 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| R-001 | find($id) | Existing ID | Returns KeywordSuggestion or null |
| R-002 | create($data) | Valid data array | Creates and returns new suggestion |
| R-003 | update($id, $data) | ID + data array | Updates and returns true |
| R-004 | delete($id) | Existing ID | Returns true on success |
| R-005 | findBySite($siteId) | Site ID | Returns collection of suggestions |
| R-006 | findDismissed($siteId) | Site ID | Returns collection of dismissed suggestions |
| R-007 | markAsTracking($id) | Suggestion ID | Sets is_tracking=true, returns true |
| R-008 | dismiss($id) | Suggestion ID | Sets is_dismissed=true, returns true |
| R-009 | findByKeyword($siteId, $keyword) | Site ID + keyword | Returns suggestion or null |
| R-010 | bulkCreate($suggestions) | Array of suggestions | Creates all, returns count |
| R-011 | exists($siteId, $keyword) | Site ID + keyword | Returns true if suggestion exists |
| R-012 | getPaginated($siteId, $params) | Site ID + params | Returns LengthAwarePaginator |
| R-013 | getBySource($siteId, $source) | Site ID + source | Returns filtered collection |
| R-014 | countBySite($siteId, $excludeDismissed) | Site ID + flag | Returns integer count |

### Repository Methods Detail

**find(int $id): ?KeywordSuggestion**
```php
$suggestion = $repository->find(1);
```

**create(array $data): KeywordSuggestion**
```php
$suggestion = $repository->create([
    'site_id' => 1,
    'keyword' => 'best seo tools',
    'source' => 'suggest',
    'opportunity_score' => 75,
]);
```

**update(int $id, array $data): bool**
```php
$success = $repository->update(1, ['is_tracking' => true]);
```

**delete(int $id): bool**
```php
$success = $repository->delete(1);
```

**findBySite(int $siteId): Collection**
```php
$suggestions = $repository->findBySite(1);
```

**findDismissed(int $siteId): Collection**
```php
$dismissed = $repository->findDismissed(1);
```

**markAsTracking(int $id): bool**
```php
$success = $repository->markAsTracking(1);
```

**dismiss(int $id): bool**
```php
$success = $repository->dismiss(1);
```

**findByKeyword(int $siteId, string $keyword): ?KeywordSuggestion**
```php
$suggestion = $repository->findByKeyword(1, 'best seo tools');
```

**bulkCreate(array $suggestions): int**
```php
$count = $repository->bulkCreate([
    ['site_id' => 1, 'keyword' => 'keyword 1', 'source' => 'suggest'],
    ['site_id' => 1, 'keyword' => 'keyword 2', 'source' => 'suggest'],
]);
```

**exists(int $siteId, string $keyword): bool**
```php
$exists = $repository->exists(1, 'best seo tools');
```

**getPaginated(int $siteId, array $params = []): LengthAwarePaginator**
```php
$results = $repository->getPaginated(1, [
    'page' => 1,
    'perPage' => 20,
    'source' => 'suggest',        // optional filter
    'excludeDismissed' => true,   // default true
]);
```

**getBySource(int $siteId, string $source): Collection**
```php
$suggestions = $repository->getBySource(1, 'suggest');
```

**countBySite(int $siteId, bool $excludeDismissed = true): int**
```php
$count = $repository->countBySite(1);
```

**upsertOrCreate(array $data): KeywordSuggestion**
```php
// Creates new or updates existing (by site_id + keyword)
// Resets is_dismissed=false on rediscovery
$suggestion = $repository->upsertOrCreate([
    'site_id' => 1,
    'keyword' => 'existing keyword',
    'source' => 'suggest',
    'opportunity_score' => 85,
]);
```

---

## Mock Data

### Valid KeywordSuggestion
```php
[
    'id' => 1,
    'site_id' => 1,
    'keyword' => 'best seo tools 2026',
    'source' => 'suggest',
    'search_volume' => null,
    'difficulty_score' => null,
    'opportunity_score' => 75,
    'is_tracking' => false,
    'is_dismissed' => false,
    'discovered_at' => '2026-02-26 17:00:00',
    'updated_at' => '2026-02-26 17:00:00',
]
```

### Valid Source Values
- `suggest` - From Google Suggest API
- `competitor` - From competitor analysis
- `related` - From related searches

---

## Factory States

The KeywordSuggestionFactory provides these states:

| State | Effect |
|-------|--------|
| `tracking()` | Sets `is_tracking = true` |
| `dismissed()` | Sets `is_dismissed = true` |
| `highOpportunity()` | Sets `opportunity_score >= 70` |
| `lowOpportunity()` | Sets `opportunity_score < 70` |
| `fromSuggest()` | Sets `source = 'suggest'` |
| `fromCompetitor()` | Sets `source = 'competitor'` |
| `fromRelated()` | Sets `source = 'related'` |

---

## Coverage Targets

| Component | Target | Current Status |
|-----------|--------|----------------|
| KeywordSuggestion Model | 100% | Tests written, implementation pending |
| KeywordSuggestionRepository | 100% | Tests written, implementation pending |

---

## Test Execution

```bash
# Run model tests
cd backend
php artisan test tests/Unit/Models/KeywordSuggestionTest.php

# Run repository tests
php artisan test tests/Unit/Repositories/KeywordSuggestionRepositoryTest.php

# Run both
php artisan test --filter "KeywordSuggestion"

# Run with coverage
php artisan test tests/Unit/Models/KeywordSuggestionTest.php tests/Unit/Repositories/KeywordSuggestionRepositoryTest.php --coverage
```

---

## Dependencies

- TASK-5-1 (Database schema - keyword_suggestions table)
- Site model and factory (existing from FEAT-001)
- Laravel testing utilities (RefreshDatabase)

---

## Notes for Developer

1. **Model Location**: `backend/app/Models/KeywordSuggestion.php`

2. **Repository Location**: `backend/app/Repositories/KeywordSuggestionRepository.php`

3. **Follow Existing Patterns**: Use Keyword model and KeywordRepository as reference for implementation.

4. **Dependency Injection**: Repository should be injected via constructor or method parameter.

5. **PHPStan Types**: Add proper type hints for all methods and properties.

6. **Scopes**: Each scope returns a Builder instance for chaining:
   ```php
   KeywordSuggestion::bySite(1)->notDismissed()->highOpportunity(70)->get()
   ```

7. **Accessor**: Add `getTrackingStatusAttribute()` method for the tracking_status accessor.

8. **Boolean Casts**: Ensure is_tracking and is_dismissed are cast to boolean:
   ```php
   protected $casts = [
       'is_tracking' => 'boolean',
       'is_dismissed' => 'boolean',
       'discovered_at' => 'datetime',
   ];
   ```

9. **Fillable**: Do NOT include `id`, `discovered_at`, `created_at`, `updated_at` in fillable.

10. **Timestamps**: Model uses `discovered_at` instead of `created_at` for the main timestamp.

---

## Comments

### [2026-02-26 23:05] PreTester: Test Design Complete
- Tests already written in TEST-5-13 (Unit Tests task)
- Created task-specific documentation (TEST-5-2.md)
- Model test file: 19 test methods
- Repository test file: 26 test methods
- Tests failing as expected (model/repo don't exist) - TDD approach
- Status: To Write Tests → To Develop
