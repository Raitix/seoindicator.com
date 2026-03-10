# TASK-36-3: ContentDecayScoringService

## STATUS
`To Prioritize`

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
- TASK-36-2 (Eloquent Models) - To Prioritize

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

*Created by SpecSystemAnalyst agent on 2026-03-10*
