# TASK-24-6: PWAScorerService

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Low`

## Description
Create service to calculate PWA readiness score (0-100) and determine readiness level based on manifest validation results.

## Acceptance Criteria
- [ ] PWAScorerService created with methods:
  - calculateScore(PWAAnalysis $analysis): int (0-100)
  - determineReadinessLevel(int $score): string (none, basic, enhanced, full)
  - getScoreBreakdown(PWAAnalysis $analysis): array
- [ ] Scoring algorithm as defined in SPEC-024:
  - Manifest presence: 20 points (10 for presence, 10 for validity)
  - Required fields: 15 points (name, short_name, start_url, display)
  - Recommended fields: 15 points (background_color, theme_color, description)
  - Required icons: 25 points (192x192: 10, 512x512: 10, 180x180: 5)
  - Icon validity bonus: 10 points
- [ ] Readiness levels:
  - none: 0-39
  - basic: 40-69
  - enhanced: 70-89
  - full: 90-100
- [ ] Score breakdown shows points per category
- [ ] Unit tests with edge cases (empty, partial, full manifests)

## Dependencies
- TASK-24-1 (Database migration)
- TASK-24-2 (PWAAnalysis model)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Pure calculation logic - no external calls
- Return rounded integer score
- Consider weighted scoring if requirements change

---

## Comments
_Agent communication log_

### [2026-02-28] Agent: SpecSystemAnalyst
Created task for PWA scoring. Pure logic service implementing the scoring algorithm from SPEC-024. Low risk, well-defined requirements.

### [2026-02-28 23:33 GMT+2] Agent: ProjectManager
**Prioritization Review Complete**

- **Priority Confirmed:** High (core scoring logic for PWA feature, directly impacts visibility score)
- **Risk:** Low (pure calculation logic, no external API calls, deterministic output)
- **Dependency Status:**
  - TASK-24-1 (Database migration): `To Write Tests` - in progress
  - TASK-24-2 (PWAAnalysis model): `To Write Tests` - in progress
- **Execution Order:** Phase 2 task - can start development once TASK-24-2 reaches `To Test Locally`
- **Scoring Algorithm:** Well-defined in SPEC-024:
  - Manifest: 20 pts (presence 10 + validity 10)
  - Required fields: 15 pts (name, short_name, start_url, display)
  - Recommended fields: 15 pts (background_color, theme_color, description)
  - Required icons: 25 pts (192x192: 10, 512x512: 10, 180x180: 5)
  - Icon validity bonus: 10 pts
- **Readiness Levels:** none (0-39), basic (40-69), enhanced (70-89), full (90-100)
- **Downstream Impact:** Blocks TASK-24-13 (Visibility Score Integration - 10% weight)

Status updated to `To Write Tests`. Ready for PreTester to create test cases including edge cases for empty, partial, and full manifests.

### [2026-03-01 00:58 GMT+2] Agent: PreTester
**Test Coverage Complete**

Created comprehensive test suite for PWAScorerService:

**Test Documentation:** `TESTS/TEST-24-6.md`
**Test File:** `backend/tests/Unit/Services/PWAScorerServiceTest.php`

**Test Categories (70+ tests):**

1. **calculateScore() Method Tests (CS-001 to CS-020) - 20 tests:**
   - Manifest scoring (presence, validity)
   - Required fields scoring (0-4 of 4 fields)
   - Recommended fields scoring (0-3 of 3 fields)
   - Icon size scoring (192x192, 512x512, 180x180)
   - Icon validity bonus (proportional)

2. **Full Score Scenarios (FS-001 to FS-005) - 5 tests:**
   - Perfect PWA (100 points)
   - Near perfect scenarios (95 points)
   - High score baseline (65-70 points)

3. **Score Boundary Tests (SB-001 to SB-008) - 8 tests:**
   - Score 0 boundary
   - Readiness level boundaries (39/40, 69/70, 89/90)
   - Score rounding (decimal handling)

4. **determineReadinessLevel() Method Tests (RL-001 to RL-012) - 12 tests:**
   - none: 0-39
   - basic: 40-69
   - enhanced: 70-89
   - full: 90-100

5. **getScoreBreakdown() Method Tests (BD-001 to BD-010) - 10 tests:**
   - Returns array structure
   - Manifest breakdown (presence + validity)
   - Required fields breakdown (each field status)
   - Recommended fields breakdown (each field status)
   - Icons breakdown (each size status)
   - Icon validity breakdown (ratio + points)
   - Total score matches calculateScore()

6. **Edge Cases (EC-001 to EC-010) - 10 tests:**
   - Null PWAAnalysis handling
   - Zero icons_total with positive icons_valid
   - Negative score cap
   - Over 100 score cap
   - Very large icons_total
   - Icons with errors
   - Inconsistent data (flags vs manifest_json)
   - Score precision (decimal scenarios)
   - Missing PWAAnalysis fields
   - Rapid re-calculation consistency

7. **Integration with PWAAnalysis Model (INT-001 to INT-005) - 5 tests:**
   - Model field access
   - Model icon fields
   - Model icon validity ratio (uses getIconValidityRatio())
   - Factory test data (fullPWA, basicPWA, withoutManifest)
   - Database persistence

8. **Readiness Level Integration Tests (RL-INT-001 to RL-INT-004) - 4 tests:**
   - Score-to-level mapping verification

**Service Interface Expected:**
```php
class PWAScorerService
{
    public function calculateScore(PWAAnalysis $analysis): int;
    public function determineReadinessLevel(int $score): string;
    public function getScoreBreakdown(PWAAnalysis $analysis): array;
}
```

**Scoring Algorithm (from SPEC-024):**
- Manifest: 20 pts (10 presence + 10 validity)
- Required fields: 15 pts (name, short_name, start_url, display - 3.75 each)
- Recommended fields: 15 pts (background_color, theme_color, description - 5 each)
- Icons: 25 pts (192x192: 10, 512x512: 10, 180x180: 5)
- Icon validity: 10 pts (proportional to icons_valid/icons_total)

**Notes for Developer:**
- Service should be created at `backend/app/Services/PWAScorerService.php`
- Pure calculation logic - no external calls, no database queries
- Score must be integer (0-100), properly rounded
- getScoreBreakdown() should return structured array for API response
- Use PWAAnalysis model's getIconValidityRatio() method for consistency
- Factory states available: fullPWA(), basicPWA(), withoutManifest(), withScore($score)

Status updated to `To Develop`. Ready for Developer to implement service.
