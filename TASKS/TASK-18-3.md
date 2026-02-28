# TASK-18-3: IndicatorCheckerInterface and Base Classes

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Low`

## Description
Create the core interface and base classes for indicator checkers, establishing the contract all 17 checkers will implement.

## Acceptance Criteria
- [x] IndicatorCheckerInterface created with required methods (id, name, category, weight, check, getSuggestions)
- [x] IndicatorResult DTO/Value Object created
- [x] Suggestion DTO/Value Object created
- [x] Abstract BaseIndicatorChecker class with common utilities
- [x] IndicatorCategory enum (content, technical, accessibility, performance)
- [x] Score enum/constants (1-3 star system)
- [x] Interface clearly defines weight() returning float 0.0-1.0
- [x] Unit tests for DTOs and base class

## Dependencies
- None (foundation task)

## Effort Estimate
`Medium` (2-3 hours)

## Technical Notes
- Interface should be in App\Services\SEO\Checkers namespace
- Use PHP 8.2+ features (enums, readonly properties)
- IndicatorResult should be immutable
- Base class can provide common DOM parsing utilities

```php
interface IndicatorCheckerInterface
{
    public function id(): string;
    public function name(): string;
    public function category(): string;
    public function weight(): float;
    public function check(Crawler $crawler, string $url, array $options = []): IndicatorResult;
    public function getSuggestions(IndicatorResult $result): array;
}
```

---

## Comments
### [2026-02-28] SpecSystemAnalyst
Created from SPEC-018 Section 10.1. Foundation for all indicator checkers.

### [2026-02-28] PreTester
**Test coverage complete.** All 132 unit tests pass with 319 assertions.

**Implementation Files:**
- `backend/app/Services/SEO/Checkers/IndicatorCheckerInterface.php` - Interface with all required methods
- `backend/app/Services/SEO/Checkers/IndicatorResult.php` - Immutable DTO with score/weight validation
- `backend/app/Services/SEO/Checkers/Suggestion.php` - Immutable DTO with type/priority validation
- `backend/app/Services/SEO/Checkers/BaseIndicatorChecker.php` - Abstract class with DOM helpers
- `backend/app/Services/SEO/Checkers/IndicatorCategory.php` - String-backed enum (4 categories)
- `backend/app/Services/SEO/Checkers/IndicatorScore.php` - Int-backed enum (0-3 scores)

**Test Files:**
- `IndicatorResultTest.php` - 16 tests
- `SuggestionTest.php` - 28 tests
- `IndicatorCheckerInterfaceTest.php` - 15 tests
- `BaseIndicatorCheckerTest.php` - 24 tests
- `IndicatorCategoryTest.php` - 20 tests
- `IndicatorScoreTest.php` - 29 tests

**Key Features Verified:**
- Interface defines weight() returning float 0.0-1.0
- DTOs are immutable (readonly properties)
- Score validation (0-3 range)
- Weight validation (0.0-1.0 range)
- Template method pattern in BaseIndicatorChecker
- DOM parsing utilities (extractText, extractAttribute, countElements, etc.)
- Error handling with graceful fallback

Ready for Developer to implement concrete checkers.
