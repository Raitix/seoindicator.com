# TASK-10-2: Implement Position Score Algorithm

## STATUS
`Tests Coverage In Progress`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Develop the `PositionScoreCalculator` class in PHP to compute keyword position scores. This algorithm assigns scores to rankings based on their search positions and calculates weighted averages using keyword search volume data.

## Responsibilities
1. **Position Scoring Rules**
   - Top 3 positions: Score = 100.
   - Positions 4-10: Weighted deduction based on rank.
   - Positions 11-100: Gradual score reduction.
   - No score for rankings outside the top 100.

2. **Weighted Average**
   - Calculate a volume-weighted average score across multiple keywords.

## Code Example
```php
class PositionScoreCalculator
{
    public function calculate(int $position): int
    {
        return match(true) {
            $position <= 3 => 100,
            $position <= 10 => 100 - (($position - 3) * 3.33),
            $position <= 20 => 80 - (($position - 10) * 3),
            $position <= 50 => 50 - (($position - 20) * 1),
            $position <= 100 => 20 - (($position - 50) * 0.38),
            default => 0,
        };
    }

    public function calculateWeightedAverage(Collection $keywords): float
    {
        $weightedSum = 0;
        $totalVolume = 0;

        foreach ($keywords as $keyword) {
            $score = $this->calculate($keyword->current_position);
            $volume = $keyword->search_volume ?? 1;
            $weightedSum += $score * $volume;
            $totalVolume += $volume;
        }

        return $totalVolume > 0 ? $weightedSum / $totalVolume : 0;
    }
}
```

## Acceptance Criteria
- [ ] Position-based scoring implemented as specified.
- [ ] Unit tests verify scoring rules for all positions.
- [ ] Weighted average accurately incorporates search volume.

## Dependencies
- TASK-10-1: Schema must exist for data access.

## Effort Estimate
2-3 hours.

---

## Comments
### [2026-02-28 20:50] PreTester: Tests Complete
- **Status:** Tests Coverage In Progress → To Develop
- **Test Files Created:**
  - `backend/tests/Unit/Services/SEO/PositionScoreCalculatorTest.php` - 35+ tests for position scoring
  - `backend/tests/Unit/Services/SEO/PositionScoreWeightedAverageTest.php` - 25+ tests for weighted average
- **Test Documentation:** `TESTS/TEST-10-2.md`
- **Coverage:** All position ranges (1-3, 4-10, 11-20, 21-50, 51-100, >100)
- **Edge Cases:** Null positions, negative positions, float positions, zero/null volumes
- **Data Providers:** Position score matrix, volume combinations, boundary positions
- **Notes for Developer:**
  - Class location: `backend/app/Services/SEO/PositionScoreCalculator.php`
  - `calculate(int $position): int` - Returns integer score 0-100
  - `calculateWeightedAverage(Collection $keywords): float` - Returns float with 2 decimal precision
  - Null search_volume defaults to weight 1
  - Position 0 means "not found" (score 0)
  - All positions > 100 return score 0
  - Tests use PHPUnit attributes for PHP 8.x compatibility

### [2026-02-28 14:15] ProjectManager: Prioritization Complete
- **Priority Confirmed:** Medium
- **Dependency Check:** TASK-10-1 (Visibility Scores Table) is Done ✅
- **Risk Assessment:** Low - pure algorithm logic, no external dependencies
- **Execution Order:** Position Score is the first scoring component (50% weight in combined visibility score)
- **Next Status:** To Write Tests

### [2026-02-20 17:59] Agent: SpecSystemAnalyst
Created task to implement the algorithm for position scores.