# TEST-18-8: SuggestionGeneratorService

## Task Reference
- **Task:** TASK-18-8
- **Feature:** FEAT-018 (Legacy 17-Indicator SEO Analysis Core Engine)
- **Status:** Tests Coverage In Progress

---

## Overview
Tests for the `SuggestionGeneratorService` that aggregates suggestions from all indicator checkers and prioritizes them by impact.

---

## Test Files

| File | Type | Tests | Assertions |
|------|------|-------|------------|
| `backend/tests/Unit/Services/SEO/SuggestionGeneratorServiceTest.php` | Unit | 35+ | 70+ |

---

## Test Scenarios

### Suggestion Aggregation Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SA-01 | Aggregate from single indicator | 1 IndicatorResult with 3 suggestions | 3 suggestions returned |
| SA-02 | Aggregate from multiple indicators | 5 IndicatorResults with varying suggestions | All suggestions combined |
| SA-03 | Indicator with no suggestions | IndicatorResult with empty suggestions array | No suggestions for that indicator |
| SA-04 | All indicators have no suggestions | 5 IndicatorResults, all empty | Empty collection returned |
| SA-05 | Empty indicators array | [] | Empty collection returned |

### Priority Sorting Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PS-01 | Sort by priority high to low | Mixed priority suggestions | High priority first |
| PS-02 | Sort within same priority | Multiple high priority | Sorted by indicator weight |
| PS-03 | Error suggestions first | Error + Warning + Info | Errors first |
| PS-04 | Warning suggestions second | Warning + Info + Success | Warnings after errors |
| PS-05 | Info suggestions third | Info + Success | Info before success |

### Category Grouping Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CG-01 | Group by category | Suggestions from multiple categories | Grouped by indicator category |
| CG-02 | Category order | Content + Technical + Accessibility | Categories in defined order |
| CG-03 | Empty category | No suggestions for category | Category not in output |
| CG-04 | Mixed categories | All 4 categories | All categories present |

### Limit Configuration Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| LC-01 | Limit per indicator | 10 suggestions, limit 5 | Max 5 suggestions per indicator |
| LC-02 | Default limit | No limit specified | Default limit applied (5) |
| LC-03 | Limit = 0 | Limit set to 0 | All suggestions returned |
| LC-04 | Limit exceeds count | 3 suggestions, limit 10 | All 3 suggestions returned |

### Impact Calculation Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IC-01 | High impact indicator | Weight 0.10, score 1 (poor) | High priority suggestion |
| IC-02 | Medium impact indicator | Weight 0.05, score 2 (good) | Medium priority suggestion |
| IC-03 | Low impact indicator | Weight 0.02, score 3 (excellent) | Low priority suggestion |
| IC-04 | Impact based on score delta | Score 1 with potential 3 | Higher priority |

### Suggestion Type Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ST-01 | Error type suggestion | type: 'error' | Included in errors list |
| ST-02 | Warning type suggestion | type: 'warning' | Included in warnings list |
| ST-03 | Info type suggestion | type: 'info' | Included in info list |
| ST-04 | Success type suggestion | type: 'success' | Included in success list |
| ST-05 | Invalid type | type: 'unknown' | Defaulted to 'info' |

### Quick Wins Detection Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| QW-01 | Quick win identification | Easy fix, high impact | Marked as quick win |
| QW-02 | Not a quick win | Hard fix, low impact | Not marked |
| QW-03 | Quick wins category | Multiple quick wins | Separate 'quick_wins' array |
| QW-04 | Quick win threshold | fixDifficulty: 'easy' | Included in quick wins |

### Output Format Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| OF-01 | SuggestionCollection structure | Valid input | Has items, grouped, summary |
| OF-02 | Individual suggestion structure | Valid suggestion | Has type, message, action, priority |
| OF-03 | Summary statistics | Multiple suggestions | Counts by type and category |
| OF-04 | Total count | 15 suggestions | totalCount = 15 |

### Edge Case Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-01 | Null indicator result | null in array | Skipped gracefully |
| EC-02 | Missing suggestions method | Indicator without getSuggestions | Empty suggestions for that indicator |
| EC-03 | Malformed suggestion | Missing required fields | Skipped or default values |
| EC-04 | Very long message | 1000 char message | Truncated or preserved |
| EC-05 | Unicode in message | Emoji and special chars | Preserved correctly |

### Interface Compliance Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IC-01 | Accepts IndicatorResult array | IndicatorResult[] | Returns SuggestionCollection |
| IC-02 | Configurable via constructor | Options array | Options applied |
| IC-03 | Immutable options | Modify options after construction | Original options preserved |

---

## Mock Data

### Sample IndicatorResult with Suggestions
```php
$indicatorResult = new IndicatorResult(
    id: 'title_tag',
    name: 'Title Tag',
    category: 'content',
    score: 1,
    weight: 0.10,
    passed: false,
    details: [
        'title' => 'Example',
        'length' => 7,
    ],
    suggestions: [
        [
            'type' => 'error',
            'message' => 'Title tag is too short (7 chars). Optimal length is 50-60 characters.',
            'action' => 'Add more descriptive text including target keywords.',
            'fixDifficulty' => 'easy',
        ],
        [
            'type' => 'warning',
            'message' => 'Title should contain target keywords.',
            'action' => 'Research and include relevant keywords in your title.',
            'fixDifficulty' => 'medium',
        ],
    ]
);
```

### Sample SuggestionCollection Output
```php
$suggestionCollection = new SuggestionCollection(
    items: [
        [
            'indicatorId' => 'title_tag',
            'indicatorName' => 'Title Tag',
            'category' => 'content',
            'type' => 'error',
            'message' => 'Title tag is too short',
            'action' => 'Add more descriptive text',
            'priority' => 'high',
            'fixDifficulty' => 'easy',
            'isQuickWin' => true,
        ],
    ],
    grouped: [
        'content' => [ /* items */ ],
        'technical' => [ /* items */ ],
    ],
    summary: [
        'errors' => 5,
        'warnings' => 8,
        'info' => 3,
        'success' => 2,
        'quickWins' => 4,
        'total' => 18,
    ],
    quickWins: [ /* easy fixes */ ],
);
```

---

## Service Interface

```php
interface SuggestionGeneratorServiceInterface
{
    /**
     * Generate aggregated and prioritized suggestions from indicator results.
     *
     * @param IndicatorResult[] $indicatorResults
     * @param array $options
     *   - limitPerIndicator: int (max suggestions per indicator, default 5)
     *   - includeQuickWins: bool (separate quick wins, default true)
     *   - sortBy: string (priority, category, default 'priority')
     * @return SuggestionCollection
     */
    public function generate(array $indicatorResults, array $options = []): SuggestionCollection;

    /**
     * Get suggestions for a single indicator.
     *
     * @param IndicatorResult $result
     * @param IndicatorCheckerInterface $checker
     * @return array
     */
    public function getIndicatorSuggestions(
        IndicatorResult $result,
        IndicatorCheckerInterface $checker
    ): array;
}
```

---

## Result DTO

```php
class SuggestionCollection
{
    public array $items;        // All suggestions flattened
    public array $grouped;      // Suggestions grouped by category
    public array $summary;      // Counts by type
    public array $quickWins;    // Easy fixes with high impact
    
    public function count(): int;
    public function getErrors(): array;
    public function getWarnings(): array;
    public function getInfo(): array;
    public function getSuccess(): array;
    public function getQuickWins(): array;
    public function getByCategory(string $category): array;
    public function getByIndicator(string $indicatorId): array;
}
```

---

## Coverage Targets

| Category | Target | Description |
|----------|--------|-------------|
| Aggregation | 100% | All aggregation scenarios |
| Priority Sorting | 100% | All sorting cases |
| Category Grouping | 100% | All grouping scenarios |
| Limit Configuration | 100% | All limit configurations |
| Impact Calculation | 100% | All impact calculations |
| Edge Cases | 90% | Boundary conditions |

---

## Test Execution

```bash
# Run specific test file
php artisan test --filter=SuggestionGeneratorServiceTest

# Run with coverage
php artisan test --filter=SuggestionGeneratorServiceTest --coverage

# Run all FEAT-018 tests
php artisan test --filter=TEST-18
```

---

## Dependencies
- `IndicatorResult` DTO (TASK-18-3)
- `IndicatorCheckerInterface` (TASK-18-3)
- Laravel Service Container

---

## Notes for Developer
- Consider caching generated suggestions for repeated analyses
- Quick wins should be easy fixes with measurable impact
- Message templates should be clear and actionable
- Consider localization support for future
- Log suggestion generation for analytics

---

## Comments

### [2026-02-28] PreTester
- Test documentation created
- 35+ test scenarios covering aggregation, prioritization, grouping
- Ready for test implementation
