# TASK-10-3: SERP Feature Bonus Algorithm

## STATUS
`To Test Prod`

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
- [x] Bonus calculations implemented for SERP features.
- [x] Accurate bonus scoring verified by unit tests.
- [x] Results normalized to a 0-100 scale.

## Effort Estimate
2-3 hours.

---

## Comments
### [2026-03-01 21:42] DevOps: Deployment Complete ✅
- **GitHub Actions Run:** 22527632578
- **Build & Test:** ✓ Passed (1m14s)
- **Deploy:** ✓ Passed (31s)
- **Frontend:** Static export uploaded
- **Backend:** git pull + composer install executed via SSH
- **Health Check:** https://seoindicator.com/api/health → status: ok, database: connected
- **Files Deployed:** `backend/app/Services/SEO/SerpFeatureScoreCalculator.php`
- **Next Status:** To Test Prod

### [2026-03-01 21:35] LocalTester: Local QA Complete ✅
- **Test Execution:** All 39 tests PASSED (65 assertions)
- **Test Categories Verified:**
  - TC-001: Single keyword with single SERP feature (7 tests) ✅
  - TC-002: Single keyword with multiple SERP features (5 tests) ✅
  - TC-003: Multiple keywords with SERP features (4 tests) ✅
  - TC-004: Edge cases (6 tests) ✅
  - TC-005: Score normalization (3 tests) ✅
  - TC-006: Security & adversarial tests (5 tests) ✅
  - Additional edge cases (9 tests) ✅
- **Security Tests Verified:**
  - SQL injection protection ✅
  - XSS protection ✅
  - Special characters handling ✅
  - Case sensitivity validation ✅
- **Performance Test:** 10,000 keywords processed in <1 second ✅
- **Coverage:** All acceptance criteria met
- **Decision:** PASS → Approved for deployment
- **Next Status:** To Deploy

### [2026-03-01 10:15] Developer: Implementation Complete
- **Class Created:** `backend/app/Services/SEO/SerpFeatureScoreCalculator.php`
- **Implementation Details:**
  - Placed in `App\Services\SEO` namespace for consistency with other score calculators
  - `calculate(Collection $keywords): float` - Main calculation method
  - `getFeatureBonus(string $feature): int` - Get bonus for specific feature
  - `getAllFeatureBonuses(): array` - Get all feature bonus mappings
  - Private helper `calculateKeywordBonus(object $keyword): int` for per-keyword calculation
- **Constants:**
  - `BONUS_POINTS` - Maps 7 SERP features to their bonus values
  - `MAX_BONUS_PER_KEYWORD = 50` - Per-keyword cap
  - `MAX_SCORE = 100` - Final score ceiling
- **Test Results:** 39 tests, 65 assertions - ALL PASSING ✅
- **Tests Updated:** Modified test file to use actual class instead of inline implementation
- **Next Status:** To Test Locally

### [2026-02-28 20:52] PreTester: Tests Complete
- **Test File:** `backend/tests/Unit/Services/SerpFeatureScoreCalculatorTest.php`
- **Tests Created:** 36 tests, 48 assertions
- **Test Categories:**
  - TC-001: Single keyword with single SERP feature (7 tests)
  - TC-002: Single keyword with multiple SERP features (5 tests)
  - TC-003: Multiple keywords with SERP features (4 tests)
  - TC-004: Edge cases (6 tests)
  - TC-005: Score normalization (3 tests)
  - TC-006: Security & adversarial tests (5 tests)
  - Additional edge cases (6 tests)
- **Security Tests:**
  - SQL injection in feature names
  - XSS in feature names
  - Case sensitivity validation
  - Performance test with 10,000 keywords (<1 second)
- **Test Documentation:** `PROJECT-SEOINDICATOR/TESTS/TEST-10-3.md`
- **All Tests Passing:** ✅
- **Next Status:** To Develop

### [2026-02-28 15:10] ProjectManager: Prioritization Complete
- **Priority Confirmed:** Medium
- **Dependency Check:** TASK-10-1 (Visibility Scores Table) is Done ✅
- **Independence:** TASK-10-3 is independent of TASK-10-2 (Position Score) - both can be developed in parallel
- **Risk Assessment:** Low - pure algorithm logic, no external dependencies, no database changes
- **Business Value:** SERP Feature Score contributes 25% to overall Visibility Score
- **Execution Context:** Part of Phase 2 calculator implementations within SPEC-010
- **SERP Features Covered:** featured_snippet (20pts), local_pack (15pts), sitelinks (10pts), reviews (5pts), video (5pts), image_pack (5pts), people_also_ask (3pts)
- **Next Status:** To Write Tests

### [2026-02-20 17:59] Agent: SpecSystemAnalyst
Task created to develop the SERP features contribution to the visibility score.