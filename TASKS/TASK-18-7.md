# TASK-18-7: ScoreCalculatorService

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Description
Create service to calculate weighted overall scores and category scores from indicator results.

## Acceptance Criteria
- [ ] Accepts array of IndicatorResult objects
- [ ] Calculates weighted overall score (0-100)
- [ ] Calculates category scores (content, technical, accessibility, performance)
- [ ] Uses indicator weights from configuration
- [ ] Handles missing/failed indicators (exclude from calculation)
- [ ] Provides score breakdown by indicator
- [ ] Returns ScoreResult with overall, category, breakdown
- [ ] Unit tests covering all scoring scenarios
- [ ] Edge case handling (all indicators failed, single indicator)

## Dependencies
- TASK-18-3 (IndicatorResult)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Formula: overallScore = sum(indicator.score * indicator.weight) / sum(indicator.weight) * 33.33
- Category score = weighted average of indicators in category
- Normalize weights if some indicators excluded

---

## Comments
### [2026-02-28] SpecSystemAnalyst
Created from SPEC-018 Section 10.2. Core scoring logic.
