# TASK-18-7: ScoreCalculatorService

## STATUS
`To Develop`

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

### [2026-02-28] PreTester
**Test Design Complete** - Created comprehensive test suite:

**Test Files:**
- `backend/tests/Unit/Services/SEO/ScoreCalculatorServiceTest.php` (30 tests)
- `backend/tests/Unit/Services/SEO/ScoreResultTest.php` (24 tests)
- `TESTS/TEST-18-7.md` (test documentation)

**Test Coverage:**
- Score calculation (5 tests): weighted average, boundary values
- Category scores (4 tests): per-category calculation, missing categories
- Edge cases (6 tests): empty input, N/A indicators, single indicator
- Breakdown (2 tests): indicator contribution tracking
- Summary (3 tests): passed/warning/failed counts
- Validation (2 tests): input validation, weight normalization
- Result object (3 tests): DTO serialization
- Configuration (2 tests): custom weights
- ScoreResult DTO (24 tests): immutability, helpers, serialization

**Key Design Decisions:**
1. N/A indicators (score 0) are excluded from calculation
2. Weights are NOT re-normalized when indicators excluded
3. ScoreResult DTO is immutable with helper methods
4. Score labels: Good (80+), Moderate (60-79), Poor (0-59)

**Handoff to Developer:** Tests define expected interface. Service should be implemented at:
- `App\Services\SEO\ScoreCalculatorService`
- `App\Services\SEO\ScoreResult`
