# TEST-5-13: Unit Tests - Keyword Discovery Components

## Task Reference
- **Task**: [TASK-5-13](../TASKS/TASK-5-13.md)
- **Specification**: [SPEC-005](../SPECIFICATIONS/SPEC-005.md)
- **Feature**: [FEAT-005](../FEATURES/FEAT-005.md)

## Overview
Comprehensive unit tests for all keyword discovery components: models, repositories, services, fetchers, utilities, and API controllers. Tests follow TDD principles and define expected behavior for implementation.

---

## Test Files

| File | Type | Description |
|------|------|-------------|
| `backend/tests/Unit/Models/KeywordSuggestionTest.php` | Unit | Model tests: relationships, scopes, accessors |
| `backend/tests/Unit/Repositories/KeywordSuggestionRepositoryTest.php` | Unit | Repository tests: CRUD operations, queries |
| `backend/tests/Unit/Services/OpportunityScorerTest.php` | Unit | Scoring utility tests |
| `backend/tests/Unit/Services/Fetchers/GoogleSuggestFetcherTest.php` | Unit | Google Suggest API fetcher tests (mocked) |
| `backend/tests/Unit/Services/Fetchers/QuestionGeneratorTest.php` | Unit | Question keyword generator tests |
| `backend/tests/Unit/Services/SuggestionServiceTest.php` | Unit | Aggregation service tests (mocked) |
| `backend/tests/Feature/Api/V1/SuggestionControllerTest.php` | Feature | API controller tests |

---

## Test Scenarios

### 1. KeywordSuggestion Model Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| M-001 | BelongsTo site relationship | `$suggestion->site` | Returns Site model |
| M-002 | Scope bySite | `KeywordSuggestion::bySite(1)->get()` | Returns only suggestions for site 1 |
| M-003 | Scope bySource | `KeywordSuggestion::bySource('suggest')->get()` | Returns only suggestions with source='suggest' |
| M-004 | Scope notDismissed | `KeywordSuggestion::notDismissed()->get()` | Returns suggestions where is_dismissed=false |
| M-005 | Scope notTracking | `KeywordSuggestion::notTracking()->get()` | Returns suggestions where is_tracking=false |
| M-006 | Scope highOpportunity | `KeywordSuggestion::highOpportunity(70)->get()` | Returns suggestions with opportunity_score >= 70 |
| M-007 | Accessor: tracking_status | `$suggestion->tracking_status` | Returns 'tracking', 'available', or 'dismissed' |
| M-008 | Fillable attributes | Mass assignment | Only allowed fields are fillable |
| M-009 | Casts: boolean fields | `$suggestion->is_tracking` | Returns boolean, not integer |
| M-010 | Casts: timestamp fields | `$suggestion->discovered_at` | Returns Carbon instance |

### 2. KeywordSuggestionRepository Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| R-001 | Find by ID | `find(1)` | Returns KeywordSuggestion or null |
| R-002 | Create suggestion | Valid data array | Creates and returns new suggestion |
| R-003 | Update suggestion | ID + data array | Updates and returns suggestion |
| R-004 | Delete suggestion | ID | Returns true on success |
| R-005 | Find by site | `findBySite(1)` | Returns collection of suggestions for site |
| R-006 | Find dismissed | `findDismissed(1)` | Returns collection of dismissed suggestions |
| R-007 | Mark as tracking | `markAsTracking(1)` | Sets is_tracking=true, returns true |
| R-008 | Dismiss | `dismiss(1)` | Sets is_dismissed=true, returns true |
| R-009 | Find by keyword | `findByKeyword(1, 'seo tools')` | Returns suggestion or null |
| R-010 | Bulk create | Array of suggestions | Creates all, returns count |
| R-011 | Exists check | `exists(1, 'seo tools')` | Returns true if suggestion exists |
| R-012 | Get paginated | `getPaginated(1, ['page' => 1])` | Returns LengthAwarePaginator |
| R-013 | Filter by source | `getBySource(1, 'suggest')` | Returns only 'suggest' source |
| R-014 | Count by site | `countBySite(1)` | Returns integer count |

### 3. OpportunityScorer Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| S-001 | Base score | 'seo' | Returns 50 (base) |
| S-002 | Long-tail bonus (3+ words) | 'best seo tools 2026' | Returns 60 (50 + 10) |
| S-003 | Long-tail bonus (5+ words) | 'best free seo tools for beginners' | Returns 65 (50 + 10 + 5) |
| S-004 | Question prefix bonus | 'how to do seo' | Returns 65 (50 + 15) |
| S-005 | Commercial intent bonus | 'best seo tools' | Returns 60 (50 + 10) |
| S-006 | Multiple bonuses | 'how to choose best seo tools' | Returns 85 (50 + 15 + 10 + 10) |
| S-007 | Max score cap | Very long commercial question | Returns max 100 |
| S-008 | Case insensitive | 'BEST SEO TOOLS' | Same score as lowercase |
| S-009 | Empty keyword | '' | Returns 50 (base) |
| S-010 | Single word | 'seo' | Returns 50 (base) |
| S-011 | All prefixes | 'what is seo', 'how to seo', etc. | Each gets +15 bonus |
| S-012 | All commercial terms | 'best seo', 'top seo', 'seo review', 'compare seo' | Each gets +10 bonus |

### 4. GoogleSuggestFetcher Tests (Mocked HTTP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| F-001 | Fetch suggestions | 'seo tools' | Returns array of suggestion strings |
| F-002 | Parse JSON response | Mock response | Correctly parses [query, suggestions] format |
| F-003 | Handle empty response | Mock empty | Returns empty array |
| F-004 | Handle rate limit (429) | Mock 429 response | Throws RateLimitException |
| F-005 | Handle server error (500) | Mock 500 response | Throws FetchException |
| F-006 | Handle network error | Mock connection fail | Throws NetworkException |
| F-007 | URL encoding | 'seo tools 2026' | Query parameter is URL encoded |
| F-008 | Caching (same query) | 'seo tools' twice | Second call returns cached result |
| F-009 | Cache expiration | Expired cache | Makes fresh API call |
| F-010 | Special characters | 'seo & marketing' | Handles special characters safely |
| F-011 | Unicode support | 'seo österreich' | Handles Unicode correctly |
| F-012 | Timeout handling | Mock slow response | Throws TimeoutException |

### 5. QuestionGenerator Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| Q-001 | Generate all questions | 'seo' | Returns 7 question variations |
| Q-002 | Question prefix: what is | 'seo' | 'what is seo' in output |
| Q-003 | Question prefix: how to | 'seo' | 'how to seo' in output |
| Q-004 | Question prefix: why | 'seo' | 'why seo' in output |
| Q-005 | Question prefix: when | 'seo' | 'when seo' in output |
| Q-006 | Question prefix: where | 'seo' | 'where seo' in output |
| Q-007 | Question prefix: who | 'seo' | 'who seo' in output |
| Q-008 | Question prefix: which | 'seo' | 'which seo' in output |
| Q-009 | Empty keyword | '' | Returns empty array |
| Q-010 | Multi-word keyword | 'seo tools' | Correctly combines with prefixes |
| Q-011 | Case preservation | 'SEO Tools' | Preserves original case |
| Q-012 | Trim whitespace | ' seo ' | Trims whitespace in output |

### 6. SuggestionService Tests (Mocked Dependencies)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SS-001 | Generate from question source | siteId, keyword, ['question'] | Returns count of created suggestions |
| SS-002 | Calculate opportunity scores | Any suggestions | Calls OpportunityScorer for each |
| SS-003 | Store in repository | Generated suggestions | Stores via KeywordSuggestionRepository |
| SS-004 | Deduplicate existing | Existing keyword | Updates existing instead of duplicate |
| SS-005 | Undismiss rediscovered | Previously dismissed | Sets is_dismissed=false for rediscovered |
| SS-006 | Handle empty results | No suggestions generated | Returns 0, no storage |
| SS-007 | Multiple sources | ['question', 'suggest'] | Aggregates from all sources |
| SS-008 | Log performance | Any generation | Logs timing and counts |
| SS-009 | Handle fetcher exception | Mock fetcher throws | Catches, logs, continues with other sources |
| SS-010 | Transaction rollback | Storage fails | Rolls back all changes |

### 7. API Controller Tests

| ID | Scenario | Method/Endpoint | Expected Output |
|----|----------|-----------------|-----------------|
| API-001 | List suggestions | GET /api/v1/sites/1/suggestions | 200 with paginated list |
| API-002 | Filter by source | GET ?source=suggest | 200 with filtered list |
| API-003 | Pagination | GET ?page=2&perPage=10 | 200 with correct page data |
| API-004 | Exclude dismissed (default) | GET /suggestions | Does not include is_dismissed=true |
| API-005 | Include tracking | GET ?includeTracking=true | Includes is_tracking=true |
| API-006 | Generate suggestions | POST /suggestions/generate | 202 with job ID |
| API-007 | Generate validation: missing seed | POST {} | 422 with validation error |
| API-008 | Generate validation: invalid source | POST {sources: ['invalid']} | 422 with validation error |
| API-009 | Track suggestion | POST /suggestions/1/track | 201 with keyword and suggestion |
| API-010 | Track already tracking | POST /suggestions/1/track | 200, no duplicate keyword |
| API-011 | Dismiss suggestion | POST /suggestions/1/dismiss | 200 with updated suggestion |
| API-012 | Dismiss not found | POST /suggestions/999/dismiss | 404 not found |
| API-013 | Bulk track | POST /suggestions/bulk-track | 201 with added/skipped arrays |
| API-014 | Bulk track validation | POST {suggestionIds: []} | 422 with validation error |
| API-015 | Export CSV | GET /suggestions/export?format=csv | 200 with CSV content |
| API-016 | Authorization: wrong user | GET other user's site | 403 forbidden |
| API-017 | Authorization: unauthenticated | GET without token | 401 unauthorized |

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

### Google Suggest API Response
```json
["seo tools", ["best seo tools", "free seo tools", "seo tools online", "seo tools for wordpress"]]
```

### Generated Questions
```php
['what is seo', 'how to seo', 'why seo', 'when seo', 'where seo', 'who seo', 'which seo']
```

---

## Coverage Targets

| Component | Target | Notes |
|-----------|--------|-------|
| KeywordSuggestion Model | 100% | All relationships, scopes, accessors |
| KeywordSuggestionRepository | 100% | All CRUD and query methods |
| OpportunityScorer | 100% | All scoring branches |
| GoogleSuggestFetcher | 100% | All HTTP scenarios (mocked) |
| QuestionGenerator | 100% | All prefixes |
| SuggestionService | 100% | All integration paths |
| SuggestionController | 100% | All endpoints and validations |
| **Overall** | **80%+** | Combined coverage |

---

## Test Execution

```bash
# Run all TASK-5-13 tests
cd backend
php artisan test --filter "KeywordSuggestion|OpportunityScorer|GoogleSuggest|QuestionGenerator|SuggestionService|SuggestionController"

# Run specific test file
php artisan test tests/Unit/Models/KeywordSuggestionTest.php
php artisan test tests/Unit/Services/OpportunityScorerTest.php

# Run with coverage
php artisan test --coverage --min=80
```

---

## Dependencies

- PHPUnit
- Laravel testing utilities (RefreshDatabase, etc.)
- Mockery for mocking HTTP responses
- Site factory (existing)
- User factory (existing)

---

## Notes for Developer

1. **Model Scopes**: Use chained scopes for complex queries: `KeywordSuggestion::bySite(1)->notDismissed()->highOpportunity(70)->get()`

2. **Repository Pattern**: Follow existing repository pattern in project. Use dependency injection.

3. **OpportunityScorer**: Pure function, no dependencies. Can be an invokable class for DI.

4. **GoogleSuggestFetcher**: Must mock HTTP calls in tests. Use Laravel HTTP fake or Mockery.

5. **QuestionGenerator**: Simple utility, no external dependencies.

6. **SuggestionService**: Orchestration service. Mock all dependencies (fetcher, generator, scorer, repository).

7. **API Authorization**: All endpoints require authentication. Use `$this->actingAs($user)` in tests.

8. **Factory**: Create `KeywordSuggestionFactory` with states for `tracking`, `dismissed`, `highOpportunity`.

---

## Comments

### [2026-02-26 22:53] PreTester: Work Started
- Status: Tests Coverage In Progress
- Creating test documentation and test files
- Will create 7 test files covering all components

