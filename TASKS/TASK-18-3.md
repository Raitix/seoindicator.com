# TASK-18-3: IndicatorCheckerInterface and Base Classes

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## Description
Create the core interface and base classes for indicator checkers, establishing the contract all 17 checkers will implement.

## Acceptance Criteria
- [ ] IndicatorCheckerInterface created with required methods (id, name, category, weight, check, getSuggestions)
- [ ] IndicatorResult DTO/Value Object created
- [ ] Suggestion DTO/Value Object created
- [ ] Abstract BaseIndicatorChecker class with common utilities
- [ ] IndicatorCategory enum (content, technical, accessibility, performance)
- [ ] Score enum/constants (1-3 star system)
- [ ] Interface clearly defines weight() returning float 0.0-1.0
- [ ] Unit tests for DTOs and base class

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
