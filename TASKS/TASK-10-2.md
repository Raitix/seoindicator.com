# TASK-10-2: Implement Position Score Algorithm

## STATUS
`Prioritization In Progress`

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
### [2026-02-20 17:59] Agent: SpecSystemAnalyst
Created task to implement the algorithm for position scores.