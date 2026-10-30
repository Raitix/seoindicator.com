# TEST-10-3: SerpFeatureScoreCalculator Tests

## Task Reference
- **Task:** TASK-10-3
- **Feature:** FEAT-010 (Visibility Score Calculator)
- **Specification:** SPEC-010

## Overview
Tests for the `SerpFeatureScoreCalculator` class that calculates visibility bonuses based on presence of SERP features (featured snippets, local packs, etc.) and assigns weighted bonuses.

## Test Files

| File | Type | Description |
|------|------|-------------|
| `backend/tests/Unit/Services/SerpFeatureScoreCalculatorTest.php` | Unit | Core calculation logic tests |

## Test Scenarios

### TC-001: Single Keyword with Single SERP Feature
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| 001 | Keyword with featured_snippet | 1 keyword, serp_features=['featured_snippet'] | Score = 40 (20 × 2) |
| 002 | Keyword with local_pack | 1 keyword, serp_features=['local_pack'] | Score = 30 (15 × 2) |
| 003 | Keyword with sitelinks | 1 keyword, serp_features=['sitelinks'] | Score = 20 (10 × 2) |
| 004 | Keyword with reviews | 1 keyword, serp_features=['reviews'] | Score = 10 (5 × 2) |
| 005 | Keyword with video | 1 keyword, serp_features=['video'] | Score = 10 (5 × 2) |
| 006 | Keyword with image_pack | 1 keyword, serp_features=['image_pack'] | Score = 10 (5 × 2) |
| 007 | Keyword with people_also_ask | 1 keyword, serp_features=['people_also_ask'] | Score = 6 (3 × 2) |

### TC-002: Single Keyword with Multiple SERP Features
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| 010 | Two features | serp_features=['featured_snippet', 'local_pack'] | Score = 70 ((20+15) × 2) |
| 011 | Three features | serp_features=['featured_snippet', 'local_pack', 'sitelinks'] | Score = 90 ((20+15+10) × 2) |
| 012 | All features | serp_features with all 7 features | Score = 100 (capped at 100) |
| 013 | Bonus cap at 50 per keyword | serp_features exceeding 50 points raw | Max 50 bonus per keyword |

### TC-003: Multiple Keywords with SERP Features
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| 020 | Two keywords with features | 2 keywords, each with featured_snippet | Score = 40 (avg of 40, 40) |
| 021 | Mixed features | 2 keywords: featured_snippet + local_pack | Score = 35 (avg of 40, 30) |
| 022 | Keywords without features | 2 keywords: one with features, one without | Score = avg only of keywords with features |

### TC-004: Edge Cases
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| 030 | Empty collection | Collection with no keywords | Score = 0 |
| 031 | Keywords with no features | All keywords have serp_features = [] | Score = 0 |
| 032 | Keywords with null features | serp_features = null | Score = 0 |
| 033 | Unknown SERP feature | serp_features=['unknown_feature'] | Score = 0 (unknown = 0 points) |
| 034 | Empty array features | serp_features=[] | Score = 0 |

### TC-005: Score Normalization
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| 040 | High bonus normalized | Multiple high-value features | Score ≤ 100 |
| 041 | Low bonus normalized | Single low-value feature | Score = feature_points × 2 |

### TC-006: Security & Adversarial Tests
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| 050 | SQL injection in feature name | serp_features=["'; DROP TABLE --"] | Score = 0 (no match) |
| 051 | XSS in feature name | serp_features=["<script>alert(1)</script>"] | Score = 0 (no match) |
| 052 | Empty string feature | serp_features=[""] | Score = 0 |
| 053 | Numeric feature | serp_features=[123] | Type mismatch handled |
| 054 | Very large collection | 10000 keywords | Performance < 1 second |

## Bonus Points Reference

| SERP Feature | Bonus Points |
|--------------|--------------|
| `featured_snippet` | 20 |
| `local_pack` | 15 |
| `sitelinks` | 10 |
| `reviews` | 5 |
| `video` | 5 |
| `image_pack` | 5 |
| `people_also_ask` | 3 |

## Calculation Formula

```
1. For each keyword:
   - Sum bonus points for each SERP feature present
   - Cap keyword bonus at 50 points
   
2. Average bonuses across keywords with features:
   - totalBonus / keywordsWithFeatures
   
3. Scale to 0-100:
   - score = min(100, averageBonus * 2)
   
4. Return 0 if no keywords have features
```

## Coverage Targets

| Metric | Target |
|--------|--------|
| Line Coverage | ≥ 95% |
| Branch Coverage | ≥ 90% |
| Method Coverage | 100% |

## Dependencies
- Laravel Collection
- PHPUnit/Pest

---

## Comments
### [2026-02-28 20:50] PreTester: Starting Test Design
- Reading task and project context
- Designing test scenarios for SerpFeatureScoreCalculator
- Creating TEST-10-3.md documentation
