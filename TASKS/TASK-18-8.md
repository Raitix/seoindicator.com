# TASK-18-8: SuggestionGeneratorService

## STATUS
`To Prioritize`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Create service to aggregate suggestions from all indicator checkers and prioritize them by impact.

## Acceptance Criteria
- [ ] Accepts array of IndicatorResult objects
- [ ] Calls getSuggestions() on each checker
- [ ] Aggregates all suggestions into unified list
- [ ] Prioritizes suggestions by impact (high, medium, low)
- [ ] Groups suggestions by indicator category
- [ ] Limits suggestions per indicator (configurable)
- [ ] Returns SuggestionCollection with prioritized items
- [ ] Handles indicators with no suggestions
- [ ] Unit tests with various result scenarios

## Dependencies
- TASK-18-3 (IndicatorCheckerInterface)

## Effort Estimate
`Small` (1-2 hours)

## Technical Notes
- Suggestion types: error, warning, info, success
- Priority based on: indicator weight, score impact, fix difficulty
- Consider adding "quick wins" category for easy fixes

---

## Comments
### [2026-02-28] SpecSystemAnalyst
Created from SPEC-018 Section 10.2. Generates actionable suggestions for users.
