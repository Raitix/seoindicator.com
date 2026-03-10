# TASK-36-3: ContentDecayScoringService

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Medium`

## SPECIFICATION
SPEC-036: Content Decay Detection & Refresh Planner

---

## Description
Create the core scoring service that calculates the 0-100 decay score from normalized signals with weight renormalization when optional signals are missing.

## Dependencies
- TASK-36-2 (Eloquent Models) - To Write Tests ⏳

## Acceptance Criteria
- [ ] `ContentDecayScoringService` class created with:
  - Base weights: click_loss (0.35), impression_loss (0.15), ctr_drop (0.15), position_decline (0.15), ranking_decline (0.10), audit_pressure (0.05), staleness (0.05)
  - `calculateDecayScore(array $signals): array` method returning:
    - `decay_score` (0-100)
    - `severity` (low/medium/high/critical)
    - `detection_confidence` (0.85-1.00)
    - `contributing_factors` (array of factors that contributed)
    - `score_breakdown` (normalized values per factor)
- [ ] Weight renormalization when signals missing:
  - If ranking data missing → renormalize weights across available factors
  - If audit data missing → renormalize weights across available factors
  - If AI visibility missing → renormalize weights across available factors
- [ ] Confidence multiplier logic:
  - GSC only: 0.85
  - GSC + rankings: 0.93
  - GSC + rankings + audit/annotations: 1.00
- [ ] Severity bands:
  - 70-100: Critical
  - 40-69: High
  - 20-39: Medium
  - 0-19: Low
- [ ] Only negative movement contributes to decay (positive = 0 contribution)
- [ ] Score is deterministic and reproducible for same inputs
- [ ] Unit tests verify scoring with various signal combinations
- [ ] Unit tests verify weight renormalization
- [ ] Unit tests verify confidence multiplier calculation

## Effort Estimate
**4 hours** - Core scoring logic with renormalization

## Technical Notes
### Score Formula
```
decay_score = 100 × weighted_sum(available_negative_signals) × confidence_multiplier
```

### Normalization Rules
- `click_loss` = max((previous - current) / max(previous, 1), 0)
- `impression_loss` = max((previous - current) / max(previous, 1), 0)
- `ctr_drop` = max((previous - current) / max(previous, 0.01), 0)
- `position_decline` = clamp((current - previous) / 10, 0, 1)
- `ranking_decline` = normalized visibility/rank loss when available
- `audit_pressure` = normalized unresolved issue count
- `staleness` = normalized age signal, capped at 1.0

### Weight Renormalization Example
If ranking_decline is missing:
```
new_weights = original_weights / (1 - weight_ranking_decline)
```

## Files to Create/Modify
- `backend/app/Services/ContentDecay/ContentDecayScoringService.php`
- `backend/tests/Unit/Services/ContentDecay/ContentDecayScoringServiceTest.php`

---

## Comments

### ProjectManager (2026-03-10)
**Prioritization Complete**

**Priority: HIGH** (confirmed - already set)
- **Foundation service** for SPEC-036 Content Decay Detection & Refresh Planner
- Core scoring logic that enables the entire detection pipeline
- Without this service, TASK-36-6 (ContentDecayDetectionService) cannot function
- The `calculateDecayScore()` method is the heart of the decay detection feature

**Risk: MEDIUM** (confirmed - already set)
- Business logic service with complex scoring formulas
- Weight renormalization logic is non-trivial
- Confidence multiplier calculation requires careful testing
- No external API dependencies - pure calculation logic

**Dependencies Analysis:**
| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-36-2 (Eloquent Models) | To Write Tests ⏳ | Tests being designed, implementation pending |

**TDD Pattern Applied:**
- PreTester CAN design tests immediately - this is a **pure calculation service**
- No models needed for testing - accepts signal arrays as input
- Scoring formulas are deterministic and reproducible
- Test categories:
  1. Score calculation with all signals present
  2. Weight renormalization when optional signals missing
  3. Confidence multiplier calculation (3 data source scenarios)
  4. Severity band classification
  5. Edge cases (zero signals, all negative, all positive)

**Downstream Impact:**
| Task | Dependency Type | Impact |
|------|-----------------|--------|
| TASK-36-6 (DetectionService) | Required | Cannot detect decay without scoring |
| TASK-36-15 (Unit Tests) | Tested | Tests will verify this service |

**Execution Order for SPEC-036 Core Pipeline:**
1. TASK-36-1 (Migrations) ← To Develop
2. TASK-36-2 (Models) ← To Write Tests (tests being designed)
3. **TASK-36-3 (ScoringService) ← This task** (can proceed to PreTester NOW)
4. TASK-36-4 (ReasonService) ← Can parallelize with this task
5. TASK-36-5 (PlannerService) ← Depends on 36-4
6. TASK-36-6 (DetectionService) ← Integrates all services

**Key Service Features:**
- **7 weighted factors** with renormalization when optional signals missing
- **4 severity bands**: Critical (70-100), High (40-69), Medium (20-39), Low (0-19)
- **3 confidence levels**: GSC only (0.85), +rankings (0.93), +audit (1.00)
- **Deterministic**: Same inputs = same outputs (important for reproducibility)

**PreTester Queue Status:**
- Multiple tasks at "To Write Tests"
- Queue has capacity - task can proceed immediately

**Status:** `To Write Tests` → Ready for PreTester

---

*Created by SpecSystemAnalyst agent on 2026-03-10*
