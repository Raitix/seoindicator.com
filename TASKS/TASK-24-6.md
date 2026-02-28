# TASK-24-6: PWAScorerService

## STATUS
`To Write Tests`

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
