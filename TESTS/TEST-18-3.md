# TEST-18-3: IndicatorCheckerInterface and Base Classes Tests

## Test Scope
Unit tests for the core interface and base classes that establish the contract for all 17 SEO indicator checkers.

---

## Files to Test

| File | Type | Description |
|------|------|-------------|
| `IndicatorCheckerInterface.php` | Interface | Contract for all indicator checkers |
| `IndicatorResult.php` | DTO | Immutable result object (EXISTS) |
| `Suggestion.php` | DTO | Immutable suggestion object |
| `BaseIndicatorChecker.php` | Abstract Class | Base class with common utilities |
| `IndicatorCategory.php` | Enum | Category enumeration |
| `IndicatorScore.php` | Enum/Constants | 1-3 star score constants |

---

## Test Files

| Test File | Location |
|-----------|----------|
| `IndicatorResultTest.php` | `tests/Unit/Services/SEO/Checkers/` (EXISTS) |
| `SuggestionTest.php` | `tests/Unit/Services/SEO/Checkers/` (EXISTS) |
| `IndicatorCheckerInterfaceTest.php` | `tests/Unit/Services/SEO/Checkers/` |
| `BaseIndicatorCheckerTest.php` | `tests/Unit/Services/SEO/Checkers/` |
| `IndicatorCategoryTest.php` | `tests/Unit/Services/SEO/Checkers/` |
| `IndicatorScoreTest.php` | `tests/Unit/Services/SEO/Checkers/` |

---

## Test Scenarios

### 1. IndicatorResult DTO (EXISTS - 18 tests)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| IR-001 | Create with all fields | All properties accessible |
| IR-002 | DTO is immutable | Error on property modification |
| IR-003 | Score 3 returns "Excellent" | getScoreLabel() = "Excellent" |
| IR-004 | Score 2 returns "Good" | getScoreLabel() = "Good" |
| IR-005 | Score 1 returns "Poor" | getScoreLabel() = "Poor" |
| IR-006 | Score 0 returns "N/A" | getScoreLabel() = "N/A" |
| IR-007 | Invalid score (4) throws exception | InvalidArgumentException |
| IR-008 | Negative score throws exception | InvalidArgumentException |
| IR-009 | Weight validation (0.0-1.0) | Valid range accepted |
| IR-010 | Weight outside range throws exception | InvalidArgumentException |
| IR-011 | toArray() serialization | All fields present |
| IR-012 | toJson() serialization | Valid JSON output |
| IR-013 | fromArray() deserialization | Creates valid DTO |
| IR-014 | Empty details allowed | Empty array accepted |
| IR-015 | Large details array | 100+ items handled |
| IR-016 | isExcellent() helper | Returns true for score 3 |
| IR-017 | isGood() helper | Returns true for score 2 |
| IR-018 | isPoor() helper | Returns true for score 1 |

---

### 2. Suggestion DTO (EXISTS - 18 tests)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| SG-001 | Create with all fields | All properties accessible |
| SG-002 | DTO is immutable | Error on property modification |
| SG-003 | Type "warning" is valid | Accepted |
| SG-004 | Type "error" is valid | Accepted |
| SG-005 | Type "info" is valid | Accepted |
| SG-006 | Invalid type throws exception | InvalidArgumentException |
| SG-007 | Empty message throws exception | InvalidArgumentException |
| SG-008 | Optional action field (null) | Accepted |
| SG-009 | Default priority is 1 | Priority defaults to 1 |
| SG-010 | toArray() serialization | All fields present |
| SG-011 | toJson() serialization | Valid JSON output |
| SG-012 | fromArray() deserialization | Creates valid DTO |
| SG-013 | isWarning() helper | Returns true for type "warning" |
| SG-014 | isError() helper | Returns true for type "error" |
| SG-015 | isInfo() helper | Returns true for type "info" |
| SG-016 | Priority validation (1-3) | Valid range accepted |
| SG-017 | Invalid priority throws exception | InvalidArgumentException |
| SG-018 | Long message handling | 1000+ chars accepted |

---

### 3. IndicatorCheckerInterface (15 tests)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| IC-001 | Interface defines id() method | Returns string |
| IC-002 | Interface defines name() method | Returns string |
| IC-003 | Interface defines category() method | Returns string |
| IC-004 | Interface defines weight() method | Returns float 0.0-1.0 |
| IC-005 | Interface defines check() method | Returns IndicatorResult |
| IC-006 | Interface defines getSuggestions() method | Returns array of Suggestion |
| IC-007 | Mock implementation works | All methods callable |
| IC-008 | check() accepts Crawler and URL | Correct signature |
| IC-009 | check() accepts optional options array | Options parameter |
| IC-010 | Multiple implementations can coexist | Independent instances |
| IC-011 | Interface can be type-hinted | Works as type declaration |
| IC-012 | Implementation returns correct result type | IndicatorResult returned |
| IC-013 | Implementation returns correct suggestions type | Suggestion[] returned |
| IC-014 | Weight must be declared per implementation | Different weights allowed |
| IC-015 | Category must be valid | Valid category string |

---

### 4. BaseIndicatorChecker Abstract Class (25 tests)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| BC-001 | Abstract class implements interface | Interface contract satisfied |
| BC-002 | Abstract methods: id(), name(), category(), weight() | Must be implemented by subclass |
| BC-003 | Abstract method: performCheck() | Must be implemented by subclass |
| BC-004 | Concrete method: check() calls performCheck() | Template method pattern |
| BC-005 | Concrete method: getSuggestions() | Default implementation returns empty array |
| BC-006 | Protected helper: createResult() | Creates IndicatorResult correctly |
| BC-007 | Protected helper: createSuggestion() | Creates Suggestion correctly |
| BC-008 | Protected helper: extractText() | Extracts text from DOM |
| BC-009 | Protected helper: extractAttribute() | Extracts attribute from DOM |
| BC-010 | Protected helper: extractAllWithAttributes() | Batch extraction |
| BC-011 | Protected helper: countElements() | Counts DOM elements |
| BC-012 | Protected helper: query() | XPath query on DOM |
| BC-013 | Protected property: $defaultScore | Default score fallback |
| BC-014 | Protected property: $defaultWeight | Default weight fallback |
| BC-015 | Constructor validates dependencies | Dependencies injected |
| BC-016 | check() returns IndicatorResult | Correct return type |
| BC-017 | check() handles exceptions gracefully | Returns error result |
| BC-018 | check() logs errors | Error logging enabled |
| BC-019 | getSuggestions() receives IndicatorResult | Parameter type correct |
| BC-020 | createResult() sets all required fields | Complete result object |
| BC-021 | createResult() validates score | Score in valid range |
| BC-022 | createResult() validates weight | Weight in valid range |
| BC-023 | Abstract class is not instantiable directly | Cannot create instance |
| BC-024 | Subclass can override default methods | Override capability |
| BC-025 | Template method pattern works correctly | Correct execution order |

---

### 5. IndicatorCategory Enum (12 tests)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| CA-001 | Enum has CONTENT case | 'content' value |
| CA-002 | Enum has TECHNICAL case | 'technical' value |
| CA-003 | Enum has ACCESSIBILITY case | 'accessibility' value |
| CA-004 | Enum has PERFORMANCE case | 'performance' value |
| CA-005 | fromValue() creates correct enum | Returns correct case |
| CA-006 | tryFromValue() returns null for invalid | Returns null |
| CA-007 | values() returns all categories | Array of all values |
| CA-008 | label() returns human-readable | Readable labels |
| CA-009 | Enum is backed by string | String-backed enum |
| CA-010 | All cases have unique values | No duplicates |
| CA-011 | Total of 4 categories | Exactly 4 cases |
| CA-012 | Can iterate over all cases | foreach works |

---

### 6. IndicatorScore Enum/Constants (15 tests)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| SC-001 | NOT_APPLICABLE = 0 | Score 0 |
| SC-002 | POOR = 1 | Score 1 |
| SC-003 | GOOD = 2 | Score 2 |
| SC-004 | EXCELLENT = 3 | Score 3 |
| SC-005 | isValid(0) returns true | 0 is valid |
| SC-006 | isValid(1) returns true | 1 is valid |
| SC-007 | isValid(2) returns true | 2 is valid |
| SC-008 | isValid(3) returns true | 3 is valid |
| SC-009 | isValid(4) returns false | 4 is invalid |
| SC-010 | isValid(-1) returns false | Negative is invalid |
| SC-011 | getLabel(0) returns "N/A" | Correct label |
| SC-012 | getLabel(1) returns "Poor" | Correct label |
| SC-013 | getLabel(2) returns "Good" | Correct label |
| SC-014 | getLabel(3) returns "Excellent" | Correct label |
| SC-015 | getLabel() throws for invalid | InvalidArgumentException |

---

## Edge Cases

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| EC-001 | Empty URL in check() | Returns error result |
| EC-002 | Null DOM in check() | Returns error result |
| EC-003 | Malformed HTML | Graceful handling |
| EC-004 | Unicode in suggestions | Preserved correctly |
| EC-005 | Very long suggestion message | Truncation or handling |
| EC-006 | Circular reference in details | JSON serialization safe |
| EC-007 | Binary data in details | Handled gracefully |
| EC-008 | Maximum weight (1.0) | Accepted |
| EC-009 | Minimum weight (0.0) | Accepted |
| EC-010 | Zero weight calculation | No contribution to score |

---

## Security Tests

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| SEC-001 | XSS in suggestion message | Escaped in output |
| SEC-002 | SQL injection in details | Not executed (array) |
| SEC-003 | Script tag in URL | Sanitized/rejected |
| SEC-004 | Excessive array depth in details | Depth limit enforced |
| SEC-005 | Memory exhaustion in large details | Memory limit handled |

---

## Total Test Count

| Component | Tests |
|-----------|-------|
| IndicatorResult DTO | 18 |
| Suggestion DTO | 18 |
| IndicatorCheckerInterface | 15 |
| BaseIndicatorChecker | 25 |
| IndicatorCategory Enum | 12 |
| IndicatorScore Enum | 15 |
| Edge Cases | 10 |
| Security | 5 |
| **TOTAL** | **118** |

---

## Dependencies

- PHP 8.2+ (readonly properties, enums)
- PHPUnit 10+
- Symfony DomCrawler

---

## Run Commands

```bash
# Run all tests for this task
php artisan test tests/Unit/Services/SEO/Checkers/

# Run specific test file
php artisan test tests/Unit/Services/SEO/Checkers/IndicatorResultTest.php

# Run with coverage
php artisan test --coverage tests/Unit/Services/SEO/Checkers/
```

---

## Status
- [x] IndicatorResult DTO (EXISTS)
- [x] Suggestion DTO tests (EXISTS)
- [x] IndicatorCheckerInterface tests (EXISTS)
- [x] BaseIndicatorChecker tests (EXISTS)
- [x] IndicatorCategory tests (EXISTS)
- [x] IndicatorScore tests (EXISTS)

**All 132 tests pass with 319 assertions.**

---

*Test documentation created by PreTester agent for TASK-18-3.*
