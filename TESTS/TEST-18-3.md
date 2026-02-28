# TEST-18-3: IndicatorCheckerInterface and Base Classes

## Task Reference
- **Task:** TASK-18-3
- **Feature:** FEAT-018 (Legacy 17-Indicator SEO Analysis Core Engine)
- **Status:** Tests Coverage In Progress

---

## Overview
Tests for the core interface and base classes that establish the contract for all 17 indicator checkers. Tests cover DTOs, enums, interface compliance, and base class functionality.

---

## Test Files

| File | Type | Tests | Assertions |
|------|------|-------|------------|
| `backend/tests/Unit/Services/SEO/Checkers/IndicatorCheckerInterfaceTest.php` | Unit | 15+ | 30+ |
| `backend/tests/Unit/Services/SEO/Checkers/IndicatorResultTest.php` | Unit | 20+ | 40+ |
| `backend/tests/Unit/Services/SEO/Checkers/SuggestionTest.php` | Unit | 15+ | 30+ |
| `backend/tests/Unit/Services/SEO/Checkers/BaseIndicatorCheckerTest.php` | Unit | 20+ | 40+ |
| `backend/tests/Unit/Enums/IndicatorCategoryTest.php` | Unit | 10+ | 20+ |
| `backend/tests/Unit/Enums/IndicatorScoreTest.php` | Unit | 10+ | 20+ |

---

## Test Scenarios

### IndicatorCheckerInterface Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IC-01 | Interface defines id() | Reflection | Method exists with string return |
| IC-02 | Interface defines name() | Reflection | Method exists with string return |
| IC-03 | Interface defines category() | Reflection | Method exists with string return |
| IC-04 | Interface defines weight() | Reflection | Method exists with float return |
| IC-05 | Interface defines check() | Reflection | Method exists with IndicatorResult return |
| IC-06 | Interface defines getSuggestions() | Reflection | Method exists with array return |
| IC-07 | Implementation returns correct types | Concrete implementation | All methods return correct types |
| IC-08 | Weight range validation | weight() = 0.08 | Float between 0.0 and 1.0 |
| IC-09 | Category valid value | category() = 'content' | Valid enum value |
| IC-10 | Check accepts Crawler | check($crawler, $url) | Method accepts DomCrawler |

### IndicatorResult DTO Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IR-01 | Create with all fields | Valid constructor | DTO created |
| IR-02 | Immutable - cannot modify | Try to set property | Error/exception |
| IR-03 | getId() returns string | getId() | Indicator ID |
| IR-04 | getName() returns string | getName() | Indicator name |
| IR-05 | getCategory() returns string | getCategory() | Category enum value |
| IR-06 | getScore() returns int | getScore() | 1-3 |
| IR-07 | getWeight() returns float | getWeight() | 0.0-1.0 |
| IR-08 | getPassed() returns bool | getPassed() | Boolean |
| IR-09 | getDetails() returns array | getDetails() | Array of details |
| IR-10 | hasSuggestions() returns bool | hasSuggestions() | Boolean |
| IR-11 | toArray() serializes correctly | toArray() | Array representation |
| IR-12 | toJson() serializes correctly | toJson() | JSON string |
| IR-13 | fromArray() creates DTO | fromArray($data) | New DTO instance |
| IR-14 | Score 3 = Excellent | score=3 | isExcellent() = true |
| IR-15 | Score 2 = Good | score=2 | isGood() = true |
| IR-16 | Score 1 = Poor | score=1 | isPoor() = true |
| IR-17 | Score 0 = N/A | score=0 | isNotApplicable() = true |
| IR-18 | Invalid score rejected | score=4 | Exception/error |
| IR-19 | Empty details allowed | details=[] | Valid DTO |
| IR-20 | Large details array | details with 100 items | Valid DTO |

### Suggestion DTO Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SG-01 | Create with all fields | Valid constructor | DTO created |
| SG-02 | Immutable - cannot modify | Try to set property | Error/exception |
| SG-03 | getType() returns string | getType() | 'warning', 'error', 'info' |
| SG-04 | getMessage() returns string | getMessage() | Suggestion text |
| SG-05 | getAction() returns string | getAction() | Action text |
| SG-06 | getPriority() returns int | getPriority() | Priority level |
| SG-07 | toArray() serializes correctly | toArray() | Array representation |
| SG-08 | toJson() serializes correctly | toJson() | JSON string |
| SG-09 | fromArray() creates DTO | fromArray($data) | New DTO instance |
| SG-10 | Type validation - warning | type='warning' | Valid |
| SG-11 | Type validation - error | type='error' | Valid |
| SG-12 | Type validation - info | type='info' | Valid |
| SG-13 | Invalid type rejected | type='invalid' | Exception/error |
| SG-14 | Empty message rejected | message='' | Exception/error |
| SG-15 | Optional action field | action=null | Valid DTO |

### IndicatorCategory Enum Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CA-01 | Content category exists | IndicatorCategory::Content | 'content' |
| CA-02 | Technical category exists | IndicatorCategory::Technical | 'technical' |
| CA-03 | Accessibility category exists | IndicatorCategory::Accessibility | 'accessibility' |
| CA-04 | Performance category exists | IndicatorCategory::Performance | 'performance' |
| CA-05 | All categories count | count(cases) | 4 |
| CA-06 | Label method | ->label() | Human-readable label |
| CA-07 | Color method | ->color() | Hex color code |
| CA-08 | Icon method | ->icon() | Icon identifier |
| CA-09 | Weight sum = 1.0 | Sum of all weights | 1.0 (approximately) |
| CA-10 | Try from valid string | from('content') | Enum instance |

### IndicatorScore Enum/Constants Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SC-01 | Score EXCELLENT = 3 | Score::EXCELLENT | 3 |
| SC-02 | Score GOOD = 2 | Score::GOOD | 2 |
| SC-03 | Score POOR = 1 | Score::POOR | 1 |
| SC-04 | Score NOT_APPLICABLE = 0 | Score::NOT_APPLICABLE | 0 |
| SC-05 | Label for each score | Score::EXCELLENT->label() | 'Excellent' |
| SC-06 | Color for each score | Score::EXCELLENT->color() | Green hex |
| SC-07 | Validation - valid score | isValid(2) | true |
| SC-08 | Validation - invalid score | isValid(5) | false |
| SC-09 | Try from valid int | from(3) | Enum instance |
| SC-10 | Try from invalid int | from(5) | Exception |

### BaseIndicatorChecker Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BC-01 | Implements interface | Instance check | Implements IndicatorCheckerInterface |
| BC-02 | Abstract - cannot instantiate | new BaseIndicatorChecker() | Error |
| BC-03 | extractTitle() helper | HTML with title | Title text |
| BC-04 | extractTitle() - no title | HTML without title | null |
| BC-05 | extractMetaDescription() helper | HTML with meta | Description text |
| BC-06 | extractMetaDescription() - no meta | HTML without meta | null |
| BC-07 | extractH1s() helper | HTML with H1s | Array of H1 texts |
| BC-08 | extractH1s() - no H1s | HTML without H1s | Empty array |
| BC-09 | extractImages() helper | HTML with images | Array of image data |
| BC-10 | extractLinks() helper | HTML with links | Array of link data |
| BC-11 | normalizeUrl() helper | URL string | Normalized URL |
| BC-12 | isHttps() helper | URL string | Boolean |
| BC-13 | getUrlLength() helper | URL string | Integer |
| BC-14 | countKeywords() helper | Text, keywords | Integer count |
| BC-15 | createResult() helper | Score, details | IndicatorResult instance |
| BC-16 | createSuggestion() helper | Type, message | Suggestion instance |
| BC-17 | calculateScore() helper | Criteria array | Score 1-3 |
| BC-18 | validateUrl() helper | URL string | Boolean |
| BC-19 | getUrlPath() helper | URL string | Path segment |
| BC-20 | hasKeywordInUrl() helper | URL, keywords | Boolean |

---

## Mock Data

### Valid IndicatorResult
```php
[
    'id' => 'url',
    'name' => 'URL Structure',
    'category' => 'technical',
    'score' => 3,
    'weight' => 0.08,
    'passed' => true,
    'details' => [
        'urlLength' => 35,
        'hasKeywords' => true,
        'isHttps' => true,
    ],
]
```

### Valid Suggestion
```php
[
    'type' => 'warning',
    'message' => 'Title tag is too short.',
    'action' => 'Add more descriptive text.',
    'priority' => 2,
]
```

---

## Coverage Targets

| Category | Target | Description |
|----------|--------|-------------|
| Interface Compliance | 100% | All interface methods |
| DTO Immutability | 100% | Cannot modify after creation |
| Enum Values | 100% | All cases covered |
| Base Class Helpers | 90% | All helper methods |
| Validation | 100% | Invalid inputs rejected |
| Serialization | 100% | toArray/toJson works |

---

## Test Execution

```bash
# Run interface tests
php artisan test --filter=IndicatorCheckerInterfaceTest

# Run DTO tests
php artisan test --filter=IndicatorResultTest
php artisan test --filter=SuggestionTest

# Run enum tests
php artisan test --filter=IndicatorCategoryTest
php artisan test --filter=IndicatorScoreTest

# Run base class tests
php artisan test --filter=BaseIndicatorCheckerTest

# Run all checker tests
php artisan test --filter=Indicator
```

---

## Dependencies
- None (foundation task)

---

## Notes for Developer
- Use PHP 8.2+ readonly properties for DTOs
- Use PHP 8.1+ backed enums for categories and scores
- DTOs should implement JsonSerializable
- Base class should be abstract with protected helper methods
- Consider using readonly classes for immutability

---

## Comments

### [2026-02-28] PreTester
- Test documentation created
- 80+ test scenarios covering interface, DTOs, enums, base class
- Ready for test implementation
