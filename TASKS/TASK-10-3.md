# TASK-10-3: SERP Feature Bonus Algorithm

## STATUS
`To Prioritize`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Create the `SerpFeatureScoreCalculator` class in PHP to calculate visibility bonuses based on presence of SERP features (e.g., featured snippets, local packs). Assign weighted bonuses to features for score calculation.

## Responsibilities
1. **Feature Bonuses**
   - Assign feature-specific bonus points (e.g., featured snippets = 20 points, local packs = 15 points).
   - Cap total bonus points for a single keyword at 50.

2. **Volume Weighted Bonus**
   - Average bonuses across all keywords, scaling scores to a 0-100 range.

## Code Example
```php
class SerpFeatureScoreCalculator
{
    private const BONUS_POINTS = [
        'featured_snippet' => 20,
        'local_pack' => 15,
        'sitelinks' => 10,
        'reviews' => 5,
        'video' => 5,
        'image_pack' => 5,
        'people_also_ask' => 3,
    ];

    public function calculate(Collection $keywords): float
    {
        $totalBonus = 0;
        $keywordsWithFeatures = 0;

        foreach ($keywords as $keyword) {
            $keywordBonus = 0;
            foreach ($keyword->serp_features ?? [] as $feature) {
                $keywordBonus += self::BONUS_POINTS[$feature] ?? 0;
            }

            if ($keywordBonus > 0) {
                $totalBonus += min($keywordBonus, 50);
                $keywordsWithFeatures++;
            }
        }

        return $keywordsWithFeatures > 0 
            ? min(100, ($totalBonus / $keywordsWithFeatures) * 2)
            : 0;
    }
}
```

## Acceptance Criteria
- [ ] Bonus calculations implemented for SERP features.
- [ ] Accurate bonus scoring verified by unit tests.
- [ ] Results normalized to a 0-100 scale.

## Effort Estimate
2-3 hours.

---

## Comments
### [2026-02-20 17:59] Agent: SpecSystemAnalyst
Task created to develop the SERP features contribution to the visibility score.