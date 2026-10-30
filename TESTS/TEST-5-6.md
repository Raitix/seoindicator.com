# TEST-5-6: Suggestion Aggregation Service Tests

## Task Reference
- **Task**: [TASK-5-6](../TASKS/TASK-5-6.md)
- **Specification**: [SPEC-005](../SPECIFICATIONS/SPEC-005.md)
- **Feature**: [FEAT-005](../FEATURES/FEAT-005.md)

## Overview
Unit tests for the SuggestionService that aggregates suggestions from multiple sources (Google Suggest, Question Generator) and stores them in the database with calculated opportunity scores.

---

## Test Files

| File | Type | Description |
|------|------|-------------|
| `backend/tests/Unit/Services/SuggestionServiceTest.php` | Unit | Aggregation service tests (mocked dependencies) |

---

## Test Scenarios

### 1. Generation Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SS-001 | Generate from question source | siteId, 'seo tools', ['question'] | Returns count of created suggestions |
| SS-002 | Calculate opportunity scores | Any suggestions | Calls OpportunityScorer for each |
| SS-003 | Store in repository | Generated suggestions | Stores via KeywordSuggestionRepository |

### 2. Deduplication Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SS-004 | Deduplicate existing | Existing keyword | Updates existing instead of duplicate |
| SS-005 | Undismiss rediscovered | Previously dismissed | Sets is_dismissed=false for rediscovered |

### 3. Empty Results Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SS-006 | Handle empty results | No suggestions generated | Returns 0, no storage |

### 4. Multiple Sources Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SS-007 | Multiple sources aggregation | ['question', 'suggest'] | Aggregates from all sources |
| - | Deduplicate across sources | Same keyword from multiple sources | Deduplicates correctly |

### 5. Logging Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SS-008 | Log performance | Any generation | Logs timing and counts |

### 6. Error Handling Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SS-009 | Handle fetcher exception | Mock fetcher throws | Catches, logs, continues with other sources |
| SS-010 | Storage failure | Repository throws | Logs error, returns 0 |

### 7. Feature Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| - | Max results limits output | maxResults: 3 with 5 suggestions | Returns 3 |
| - | Correct source label | Multiple sources | Each suggestion has correct source label |

### 8. Integration Test

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| - | With real dependencies | Real QuestionGenerator, OpportunityScorer | Works correctly |

---

## Mock Data

### Valid Repository Call
```php
[
    'site_id' => 1,
    'keyword' => 'best seo tools 2026',
    'source' => 'suggest',
    'opportunity_score' => 75,
    'is_dismissed' => false,
]
```

### Question Generator Output
```php
['what is seo', 'how to seo', 'why seo', 'when seo', 'where seo', 'who seo', 'which seo']
```

### Google Suggest Output
```php
['best seo tools', 'free seo tools', 'seo tools online']
```

---

## Coverage Targets

| Component | Target | Status |
|-----------|--------|--------|
| SuggestionService | 100% | ✅ 14 tests passing |
| generateSuggestions() | 100% | ✅ Covered |
| Error handling | 100% | ✅ Covered |
| Edge cases | 100% | ✅ Covered |

---

## Test Execution

```bash
# Run SuggestionService tests
cd backend
php artisan test tests/Unit/Services/SuggestionServiceTest.php

# Run with verbose output
php artisan test tests/Unit/Services/SuggestionServiceTest.php -v

# Run with coverage
php artisan test tests/Unit/Services/SuggestionServiceTest.php --coverage
```

---

## Dependencies

- PHPUnit
- Mockery for mocking dependencies
- App\Services\SuggestionService (implementation)
- App\Services\Fetchers\QuestionGenerator
- App\Services\Fetchers\GoogleSuggestFetcher
- App\Services\OpportunityScorer
- App\Repositories\KeywordSuggestionRepository

---

## Notes for Developer

1. **Dependency Injection**: The service uses constructor injection for all dependencies. Mock them in unit tests.

2. **Error Handling**: The service catches exceptions from individual sources and continues with other sources.

3. **Deduplication**: Keywords are deduplicated case-insensitively across sources.

4. **Rediscovery**: When a previously dismissed keyword is rediscovered, `is_dismissed` is set to `false`.

5. **Logging**: All operations are logged with timing information for monitoring.

6. **Max Results**: Optional `maxResults` parameter limits the number of suggestions stored.

---

## Comments

### [2026-02-27 08:55] PreTester: Tests Fixed
- Fixed SQLite transaction issues by removing RefreshDatabase trait
- Fixed Log facade mock expectations
- All 14 tests now passing
- Tests use mocked dependencies (no database required)

### [2026-02-26 23:12] PreTester: Test Design Complete
- Created 14 test methods
- Covers all acceptance criteria from TASK-5-6
- Uses Mockery for dependency mocking
- Integration test with real dependencies
