# TEST-36-3: ContentDecayScoringService Tests

## Overview
Comprehensive unit tests for the ContentDecayScoringService that calculates 0-100 decay scores from normalized signals with weight renormalization.

**Total Tests:** 175+

---

## Test File Location
- `backend/tests/Unit/Services/ContentDecay/ContentDecayScoringServiceTest.php`

---

## Test Categories

### SI: Service Instantiation (8 tests)
| ID | Description | Expected Result |
|----|-------------|-----------------|
| SI-001 | Class exists in correct namespace | `App\Services\ContentDecay\ContentDecayScoringService` |
| SI-002 | Class can be instantiated | No exceptions |
| SI-003 | Container binding works | `app(ContentDecayScoringService::class)` |
| SI-004 | Base weights constant defined | Array with 7 weight keys |
| SI-005 | Base weights sum to 1.0 | `abs(array_sum($weights) - 1.0) < 0.0001` |
| SI-006 | All expected weight keys present | click_loss, impression_loss, ctr_drop, position_decline, ranking_decline, audit_pressure, staleness |
| SI-007 | calculateDecayScore method exists | Public method with array param |
| SI-008 | Method returns expected structure | decay_score, severity, detection_confidence, contributing_factors, score_breakdown |

### CALC: Score Calculation (35 tests)
| ID | Description | Input | Expected Result |
|----|-------------|-------|-----------------|
| CALC-001 | Zero signals → zero decay | All signals = 0 | decay_score = 0, severity = "low" |
| CALC-002 | Minimal signals → low decay | click_loss = 0.1 | decay_score < 20, severity = "low" |
| CALC-003 | All signals minimal → low-medium | All signals = 0.1 | decay_score in 0-39 range |
| CALC-004 | Moderate signals → high decay | All signals = 0.5 | decay_score in 40-69 range |
| CALC-005 | Severe signals → critical decay | All signals = 1.0 | decay_score >= 70, severity = "critical" |
| CALC-006 | Click loss dominant | click_loss = 1.0, others = 0 | decay_score ≈ 35 (weight) |
| CALC-007 | Impression loss dominant | impression_loss = 1.0, others = 0 | decay_score ≈ 15 (weight) |
| CALC-008 | CTR drop dominant | ctr_drop = 1.0, others = 0 | decay_score ≈ 15 (weight) |
| CALC-009 | Position decline dominant | position_decline = 1.0, others = 0 | decay_score ≈ 15 (weight) |
| CALC-010 | Ranking decline dominant | ranking_decline = 1.0, others = 0 | decay_score ≈ 10 (weight) |
| CALC-011 | Audit pressure dominant | audit_pressure = 1.0, others = 0 | decay_score ≈ 5 (weight) |
| CALC-012 | Staleness dominant | staleness = 1.0, others = 0 | decay_score ≈ 5 (weight) |
| CALC-013 | Only negative contributes | Positive signals = 0.5 | Treated as 0 contribution |
| CALC-014 | Mixed positive/negative | Some negative, some positive | Only negative counts |
| CALC-015 | Click loss calculation | prev=100, curr=70 | (100-70)/100 = 0.3 |
| CALC-016 | Impression loss calculation | prev=1000, curr=600 | (1000-600)/1000 = 0.4 |
| CALC-017 | CTR drop calculation | prev=5.0%, curr=3.0% | (5.0-3.0)/5.0 = 0.4 |
| CALC-018 | Position decline calculation | prev=3, curr=8 | (8-3)/10 = 0.5 |
| CALC-019 | Position decline capped | prev=1, curr=20 | Clamped to 1.0 |
| CALC-020 | Zero previous clicks handled | prev=0, curr=10 | Division by 1 = 0 (no loss) |
| CALC-021 | Zero previous CTR handled | prev=0%, curr=1% | Division by 0.01 = 0 (no loss) |
| CALC-022 | Realistic scenario A | GSC data only | decay_score calculated, confidence = 0.85 |
| CALC-023 | Realistic scenario B | GSC + rankings | decay_score calculated, confidence = 0.93 |
| CALC-024 | Realistic scenario C | All signals | decay_score calculated, confidence = 1.00 |
| CALC-025 | Contributing factors populated | Any decay > 0 | Array with factor names |
| CALC-026 | Score breakdown populated | Any input | Array with normalized values |
| CALC-027 | Decay score is 0-100 | All edge cases | Clamped to range |
| CALC-028 | Confidence within 0.85-1.00 | All scenarios | Within range |
| CALC-029 | Negative signal ignored | click_loss = -0.5 | Treated as 0 |
| CALC-030 | Signal > 1.0 clamped | click_loss = 1.5 | Treated as 1.0 |
| CALC-031 | Empty signals array | [] | decay_score = 0 |
| CALC-032 | Partial signals array | Only click_loss | Only that factor calculated |
| CALC-033 | Extra signals ignored | Unknown signal | Ignored in calculation |
| CALC-034 | Floating point precision | 0.33333333 | Handled correctly |
| CALC-035 | Large numbers handled | click_loss with millions | Normalized correctly |

### RENORM: Weight Renormalization (30 tests)
| ID | Description | Missing Signal | Expected Behavior |
|----|-------------|----------------|-------------------|
| RENORM-001 | No renormalization needed | None | Original weights used |
| RENORM-002 | ranking_decline missing weight | ranking_decline = null | Other weights scaled up |
| RENORM-003 | audit_pressure missing | audit_pressure = null | Other weights scaled up |
| RENORM-004 | staleness missing | staleness = null | Other weights scaled up |
| RENORM-005 | ranking_decline renorm math | Missing 0.10 weight | Sum = 0.90, scale = 1/0.90 |
| RENORM-006 | audit_pressure renorm math | Missing 0.05 weight | Sum = 0.95, scale = 1/0.95 |
| RENORM-007 | Two signals missing | ranking + staleness | Both removed from calc |
| RENORM-008 | Three signals missing | ranking + audit + staleness | All three renormalized |
| RENORM-009 | Renormalized weights sum to 1.0 | Any combination | Sum = 1.0 |
| RENORM-010 | Core signals never renormalized | Only GSC available | click_loss, impression_loss, ctr_drop, position_decline |
| RENORM-011 | Missing ranking_decline detection | null value detected | Flagged for renorm |
| RENORM-012 | Missing audit_pressure detection | null value detected | Flagged for renorm |
| RENORM-013 | Missing staleness detection | null value detected | Flagged for renorm |
| RENORM-014 | Missing AI visibility = ranking_decline | AI not available | ranking_decline removed |
| RENORM-015 | Missing audit data = audit_pressure | Audit not available | audit_pressure removed |
| RENORM-016 | Click loss weight after ranking renorm | ranking_decline missing | click_loss = 0.35/0.90 ≈ 0.389 |
| RENORM-017 | Impression loss weight after staleness renorm | staleness missing | impression_loss = 0.15/0.95 ≈ 0.158 |
| RENORM-018 | All optional missing | Only GSC | Weights: 0.35/0.65, 0.15/0.65, etc. |
| RENORM-019 | Renorm preserves relative ratios | Two missing | Ratios maintained |
| RENORM-020 | Renorm with zero values | Zero optional signal | Not renormalized, just 0 |
| RENORM-021 | Renorm calculation consistency | Same inputs multiple times | Same result |
| RENORM-022 | Missing signal in breakdown | ranking_decline missing | Not in score_breakdown |
| RENORM-023 | Partial data vs no data | ranking_decline = 0 vs null | 0 is valid, null triggers renorm |
| RENORM-024 | GSC-only scenario weights | Only GSC available | Renormalized to 1.0 |
| RENORM-025 | GSC + rankings scenario weights | No audit/staleness | Partial renormalization |
| RENORM-026 | Full data scenario weights | All available | No renormalization |
| RENORM-027 | Renorm with 4 missing signals | Only click_loss, position | Extreme renorm |
| RENORM-028 | Renorm edge case - single signal | Only one signal | Weight = 1.0 for that signal |
| RENORM-029 | Renorm affects confidence | Missing signals | Lower confidence |
| RENORM-030 | Renorm documented in breakdown | Renorm applied | Breakdown shows normalized values |

### CONF: Confidence Multiplier (20 tests)
| ID | Description | Data Sources | Expected Confidence |
|----|-------------|--------------|---------------------|
| CONF-001 | GSC only | click, impression, ctr, position | 0.85 |
| CONF-002 | GSC + rankings | Above + ranking_decline | 0.93 |
| CONF-003 | GSC + rankings + audit/annotations | All signals | 1.00 |
| CONF-004 | GSC + audit only | No rankings | 0.85 (rankings required for higher) |
| CONF-005 | Confidence affects final score | 0.85 confidence | Score * 0.85 |
| CONF-006 | Confidence stored in result | Any scenario | detection_confidence field |
| CONF-007 | Confidence is float | Check type | float between 0.85-1.00 |
| CONF-008 | GSC-only detection | No ranking/audit signals | Correctly identified |
| CONF-009 | GSC+rankings detection | ranking_decline present | Correctly identified |
| CONF-010 | Full data detection | All signals present | Correctly identified |
| CONF-011 | Partial ranking data | Some ranking data | Counts as rankings present |
| CONF-012 | Partial audit data | Some audit data | Counts as audit present |
| CONF-013 | Annotation counts as audit | annotations present | audit_pressure calculated |
| CONF-014 | Confidence with renormalization | Missing optional signals | Both applied |
| CONF-015 | Low confidence still valid | 0.85 confidence | Valid result returned |
| CONF-016 | Full confidence optimal | 1.00 confidence | Best quality result |
| CONF-017 | Confidence in breakdown | Any scenario | Shown in result |
| CONF-018 | Confidence threshold logic | Exactly 0.85 | No higher without rankings |
| CONF-019 | Confidence threshold logic | Exactly 0.93 | No higher without audit |
| CONF-020 | Confidence is deterministic | Same inputs | Same confidence |

### SEV: Severity Bands (25 tests)
| ID | Description | Decay Score | Expected Severity |
|----|-------------|-------------|-------------------|
| SEV-001 | Zero score | 0 | "low" |
| SEV-002 | Very low score | 5 | "low" |
| SEV-003 | Low boundary | 19 | "low" |
| SEV-004 | Medium lower boundary | 20 | "medium" |
| SEV-005 | Medium score | 25 | "medium" |
| SEV-006 | Medium upper boundary | 39 | "medium" |
| SEV-007 | High lower boundary | 40 | "high" |
| SEV-008 | High score | 55 | "high" |
| SEV-009 | High upper boundary | 69 | "high" |
| SEV-010 | Critical lower boundary | 70 | "critical" |
| SEV-011 | Critical score | 85 | "critical" |
| SEV-012 | Maximum score | 100 | "critical" |
| SEV-013 | Severity stored in result | Any | severity field present |
| SEV-014 | Severity is string | Check type | "low"|"medium"|"high"|"critical" |
| SEV-015 | Low severity color class | "low" | Can be used for UI |
| SEV-016 | Medium severity color class | "medium" | Can be used for UI |
| SEV-017 | High severity color class | "high" | Can be used for UI |
| SEV-018 | Critical severity color class | "critical" | Can be used for UI |
| SEV-019 | Severity affects contributing factors | Higher severity | More factors listed |
| SEV-020 | Boundary precision | 19.999 | "low" |
| SEV-021 | Boundary precision | 20.000 | "medium" |
| SEV-022 | Boundary precision | 39.999 | "medium" |
| SEV-023 | Boundary precision | 40.000 | "high" |
| SEV-024 | Boundary precision | 69.999 | "high" |
| SEV-025 | Boundary precision | 70.000 | "critical" |

### CONTRIB: Contributing Factors (20 tests)
| ID | Description | Scenario | Expected Behavior |
|----|-------------|----------|-------------------|
| CONTRIB-001 | Zero decay → empty factors | No decay | Empty array |
| CONTRIB-002 | Single factor decay | Only click_loss | ["click_loss"] |
| CONTRIB-003 | Multiple factors | Multiple signals | Array with factor names |
| CONTRIB-004 | Factor names match signal keys | Any decay | Correct names |
| CONTRIB-005 | Factors sorted by contribution | Multiple factors | Highest first |
| CONTRIB-006 | Small contribution excluded | < 5% contribution | Not listed |
| CONTRIB-007 | All factors for critical | All signals max | All 7 factors |
| CONTRIB-008 | Factor contribution percentage | Each factor | Percentage shown |
| CONTRIB-009 | Factor breakdown structure | Any factor | name + percentage |
| CONTRIB-010 | Click loss factor naming | click_loss present | "click_loss" |
| CONTRIB-011 | Impression loss factor naming | impression_loss present | "impression_loss" |
| CONTRIB-012 | CTR drop factor naming | ctr_drop present | "ctr_drop" |
| CONTRIB-013 | Position decline factor naming | position_decline present | "position_decline" |
| CONTRIB-014 | Ranking decline factor naming | ranking_decline present | "ranking_decline" |
| CONTRIB-015 | Audit pressure factor naming | audit_pressure present | "audit_pressure" |
| CONTRIB-016 | Staleness factor naming | staleness present | "staleness" |
| CONTRIB-017 | Missing signal not in factors | ranking_decline = null | Not in factors |
| CONTRIB-018 | Zero contribution not in factors | signal = 0 | Not in factors |
| CONTRIB-019 | Factors match breakdown keys | Both present | Same keys |
| CONTRIB-020 | Human-readable factor names | Optional | Can be formatted for display |

### BREAKDOWN: Score Breakdown (20 tests)
| ID | Description | Scenario | Expected Behavior |
|----|-------------|----------|-------------------|
| BREAKDOWN-001 | Breakdown structure | Any input | Associative array |
| BREAKDOWN-002 | Breakdown keys match signals | All signals | All 7 keys |
| BREAKDOWN-003 | Breakdown values are normalized | Any signal | 0.0-1.0 |
| BREAKDOWN-004 | Breakdown includes weights | Each factor | weight + normalized value |
| BREAKDOWN-005 | Breakdown includes raw values | Each factor | raw input preserved |
| BREAKDOWN-006 | Missing signal not in breakdown | null signal | Key absent |
| BREAKDOWN-007 | Zero signal in breakdown | signal = 0 | Key present, value = 0 |
| BREAKDOWN-008 | Breakdown sums correctly | All breakdown | Weighted sum matches score |
| BREAKDOWN-009 | Breakdown shows contribution | Each factor | contribution value |
| BREAKDOWN-010 | Breakdown is JSON serializable | Check | json_encode works |
| BREAKDOWN-011 | Click loss breakdown format | click_loss present | {value, weight, contribution} |
| BREAKDOWN-012 | Impression loss breakdown format | impression_loss present | {value, weight, contribution} |
| BREAKDOWN-013 | CTR drop breakdown format | ctr_drop present | {value, weight, contribution} |
| BREAKDOWN-014 | Position decline breakdown format | position_decline present | {value, weight, contribution} |
| BREAKDOWN-015 | Ranking decline breakdown format | ranking_decline present | {value, weight, contribution} |
| BREAKDOWN-016 | Audit pressure breakdown format | audit_pressure present | {value, weight, contribution} |
| BREAKDOWN-017 | Staleness breakdown format | staleness present | {value, weight, contribution} |
| BREAKDOWN-018 | Renormalized weight in breakdown | After renorm | Updated weight shown |
| BREAKDOWN-019 | Breakdown useful for debugging | Any | Can be inspected |
| BREAKDOWN-020 | Breakdown matches contributing factors | Both present | Consistent data |

### DET: Deterministic Behavior (15 tests)
| ID | Description | Input | Expected Result |
|----|-------------|-------|-----------------|
| DET-001 | Same input, same output (run 1) | Fixed signals | Known output |
| DET-002 | Same input, same output (run 2) | Same signals | Identical output |
| DET-003 | Same input, same output (run 3) | Same signals | Identical output |
| DET-004 | Different input, different output | Different signals | Different result |
| DET-005 | Decimal precision consistent | Decimals | Same rounding |
| DET-006 | Order independence | Shuffled signals | Same result |
| DET-007 | Extra keys ignored | Extra unknown signals | Same result |
| DET-008 | Null handling consistent | null values | Treated as missing |
| DET-009 | Zero handling consistent | 0 values | Treated as no decay |
| DET-010 | Large numbers consistent | Large values | Same normalized result |
| DET-011 | Floating point stability | 0.1 + 0.2 | No precision issues |
| DET-012 | Multiple calls same session | 10 calls | All identical |
| DET-013 | Multiple calls different sessions | Restart | Still identical |
| DET-014 | JSON serialization reproducible | encode/decode | Same result |
| DET-015 | Hash/cache key friendly | Known inputs | Can be cached |

### EDGE: Edge Cases (20 tests)
| ID | Description | Input | Expected Behavior |
|----|-------------|-------|-------------------|
| EDGE-001 | Empty signals array | [] | decay_score = 0 |
| EDGE-002 | Null signals | null | Handled gracefully |
| EDGE-003 | All zeros | All signals = 0 | decay_score = 0 |
| EDGE-004 | All negative | All signals = -1 | decay_score = 0 |
| EDGE-005 | All max | All signals = 1.0 | Max decay |
| EDGE-006 | Mix of null and values | Partial data | Renormalization applies |
| EDGE-007 | String values | Signals as strings | Converted to float |
| EDGE-008 | Boolean values | true/false | Converted to 1/0 |
| EDGE-009 | Very small decimals | 0.0000001 | Handled correctly |
| EDGE-010 | Very large numbers | 1000000 | Normalized correctly |
| EDGE-011 | Unicode in factor names | Not applicable | No unicode expected |
| EDGE-012 | Special characters | Not applicable | No special chars expected |
| EDGE-013 | Inf values | INF | Treated as 1.0 |
| EDGE-014 | NaN values | NAN | Treated as 0 |
| EDGE-015 | Single factor extreme | One factor = 1.0 | Correct renorm |
| EDGE-016 | All optional missing | Only GSC | Correct renorm |
| EDGE-017 | Concurrent calculation | Same time | No race conditions |
| EDGE-018 | Memory efficiency | Large arrays | No memory leak |
| EDGE-019 | Performance threshold | 1000 calculations | < 100ms total |
| EDGE-020 | Integration ready | Service output | Usable by DetectionService |

### INT: Integration (10 tests)
| ID | Description | Context | Expected Behavior |
|----|-------------|---------|-------------------|
| INT-001 | Full workflow | All signals provided | Complete result structure |
| INT-002 | Partial workflow | GSC only | Valid result with lower confidence |
| INT-003 | Result serialization | json_encode | Valid JSON |
| INT-004 | Result deserialization | json_decode | Usable array |
| INT-005 | Service injection | app() helper | Service resolved |
| INT-006 | Real GSC data | Sample GSC metrics | Correct decay calculation |
| INT-007 | Real ranking data | Sample ranking data | Correct ranking_decline |
| INT-008 | Real audit data | Sample audit issues | Correct audit_pressure |
| INT-009 | Result usable by DetectionService | Output format | Compatible interface |
| INT-010 | Documentation matches | Compare with spec | Behaves as documented |

---

## Test Implementation Patterns

### Mock Pattern
```php
// No mocking needed - this is a pure calculation service
// Tests directly call the service with various input arrays
$service = new ContentDecayScoringService();
$result = $service->calculateDecayScore($signals);
```

### Signal Structure
```php
$signals = [
    'click_loss' => 0.3,           // Normalized 0-1
    'impression_loss' => 0.2,      // Normalized 0-1
    'ctr_drop' => 0.15,            // Normalized 0-1
    'position_decline' => 0.4,     // Normalized 0-1
    'ranking_decline' => null,     // Optional, null if missing
    'audit_pressure' => 0.1,       // Normalized 0-1
    'staleness' => 0.5,            // Normalized 0-1
];
```

### Expected Result Structure
```php
[
    'decay_score' => 35.5,                    // 0-100
    'severity' => 'medium',                   // low/medium/high/critical
    'detection_confidence' => 0.93,           // 0.85-1.00
    'contributing_factors' => [               // Sorted by contribution
        ['factor' => 'click_loss', 'contribution' => 45.0],
        ['factor' => 'position_decline', 'contribution' => 25.0],
    ],
    'score_breakdown' => [
        'click_loss' => [
            'value' => 0.3,
            'weight' => 0.35,
            'contribution' => 10.5,
        ],
        // ... other factors
    ],
];
```

### Base Weights
```php
const BASE_WEIGHTS = [
    'click_loss' => 0.35,
    'impression_loss' => 0.15,
    'ctr_drop' => 0.15,
    'position_decline' => 0.15,
    'ranking_decline' => 0.10,
    'audit_pressure' => 0.05,
    'staleness' => 0.05,
];
```

---

## Dependencies
- None (pure calculation service, no external dependencies)

---

## TDD Pattern
Tests are designed before implementation. Developer must implement:
1. `ContentDecayScoringService` class
2. `calculateDecayScore(array $signals): array` method
3. Weight renormalization logic
4. Confidence multiplier logic
5. Severity classification

---

## Coverage Target
- **Lines:** 100%
- **Branches:** 100%
- **Methods:** 100%

---

## Acceptance Criteria Mapping

| AC | Tests | Status |
|----|-------|--------|
| ContentDecayScoringService class created | SI-001 to SI-008 | ✅ Covered |
| calculateDecayScore() method with correct return | CALC-001 to CALC-035 | ✅ Covered |
| Weight renormalization when signals missing | RENORM-001 to RENORM-030 | ✅ Covered |
| Confidence multiplier logic (3 levels) | CONF-001 to CONF-020 | ✅ Covered |
| Severity bands (4 levels) | SEV-001 to SEV-025 | ✅ Covered |
| Only negative movement contributes | CALC-013, CALC-014, EDGE-004 | ✅ Covered |
| Score is deterministic | DET-001 to DET-015 | ✅ Covered |
| Unit tests verify scoring | All CALC tests | ✅ Covered |
| Unit tests verify renormalization | All RENORM tests | ✅ Covered |
| Unit tests verify confidence | All CONF tests | ✅ Covered |

---

*Created by PreTester agent on 2026-03-10*
