# TEST-23-8: Backlink Profile Health Scoring Service Tests

## Task Reference
- **Task:** [TASK-23-8](../TASKS/TASK-23-8.md)
- **Specification:** [SPEC-023](../SPECIFICATIONS/SPEC-023.md)
- **Feature:** [FEAT-023](../FEATURES/FEAT-023.md)
- **Dependencies:** TASK-23-6 (Backlink Snapshot Service), TASK-23-7 (Domain Trust Scorer)

---

## Overview

Unit tests for the `BacklinkProfileScorer` service that calculates overall backlink profile health score (0-100). The service combines four scoring components (diversity, trust, growth, anchor health) with equal weights of 25 points each.

---

## Test Files

| File | Tests | Assertions | Description |
|------|-------|------------|-------------|
| `BacklinkProfileScorerTest.php` | 58 | 140+ | Unit tests for scoring components |
| `BacklinkProfileScorerIntegrationTest.php` | 15 | 35+ | Integration with snapshot/trust services |

---

## Test Scenarios

### Diversity Score (DS) - 0-25 Points

| ID | Scenario | Input | Expected Score | Calculation |
|----|----------|-------|----------------|-------------|
| DS-001 | Perfect diversity (1:1 ratio) | 100 backlinks, 100 domains | 25 | `(100/100) * 100 = 100 → min(25, 100) = 25` |
| DS-002 | Good diversity (1:2 ratio) | 100 backlinks, 50 domains | 25 | `(50/100) * 100 = 50 → min(25, 50) = 25` |
| DS-003 | Moderate diversity (1:4 ratio) | 100 backlinks, 25 domains | 25 | `(25/100) * 100 = 25 → min(25, 25) = 25` |
| DS-004 | Poor diversity (1:10 ratio) | 100 backlinks, 10 domains | 10 | `(10/100) * 100 = 10 → min(25, 10) = 10` |
| DS-005 | Very poor diversity | 100 backlinks, 1 domain | 1 | `(1/100) * 100 = 1 → min(25, 1) = 1` |
| DS-006 | Zero backlinks | 0 backlinks, 0 domains | 0 | Division by zero protection |
| DS-007 | Many domains, few backlinks | 50 backlinks, 100 domains | 25 | `(100/50) * 100 = 200 → min(25, 200) = 25` |
| DS-008 | Single backlink from single domain | 1 backlink, 1 domain | 25 | `(1/1) * 100 = 100 → min(25, 100) = 25` |

### Trust Score (TS) - 0-25 Points

| ID | Scenario | Input | Expected Score | Calculation |
|----|----------|-------|----------------|-------------|
| TS-001 | High trust average (100) | avg_domain_trust = 100 | 25 | `min(25, 100/4) = 25` |
| TS-002 | Good trust average (80) | avg_domain_trust = 80 | 20 | `min(25, 80/4) = 20` |
| TS-003 | Moderate trust average (60) | avg_domain_trust = 60 | 15 | `min(25, 60/4) = 15` |
| TS-004 | Low trust average (40) | avg_domain_trust = 40 | 10 | `min(25, 40/4) = 10` |
| TS-005 | Very low trust average (20) | avg_domain_trust = 20 | 5 | `min(25, 20/4) = 5` |
| TS-006 | Zero trust average | avg_domain_trust = 0 | 0 | `min(25, 0/4) = 0` |
| TS-007 | Null trust average | avg_domain_trust = null | 0 | Default to 0 |
| TS-008 | Decimal trust average | avg_domain_trust = 45.5 | 11.375 | `min(25, 45.5/4) = 11.375` |

### Growth Score (GS) - 0-25 Points

| ID | Scenario | Input | Expected Score | Calculation |
|----|----------|-------|----------------|-------------|
| GS-001 | Strong growth (+25%) | growth_rate = 25 | 25 | `min(25, max(0, 12.5 + 25/2)) = 25` |
| GS-002 | Moderate growth (+10%) | growth_rate = 10 | 17.5 | `min(25, max(0, 12.5 + 10/2)) = 17.5` |
| GS-003 | Slight growth (+5%) | growth_rate = 5 | 15 | `min(25, max(0, 12.5 + 5/2)) = 15` |
| GS-004 | No growth (0%) | growth_rate = 0 | 12.5 | `min(25, max(0, 12.5 + 0/2)) = 12.5` |
| GS-005 | Slight decline (-5%) | growth_rate = -5 | 10 | `min(25, max(0, 12.5 - 5/2)) = 10` |
| GS-006 | Moderate decline (-10%) | growth_rate = -10 | 7.5 | `min(25, max(0, 12.5 - 10/2)) = 7.5` |
| GS-007 | Strong decline (-25%) | growth_rate = -25 | 0 | `min(25, max(0, 12.5 - 25/2)) = 0` |
| GS-008 | Very strong decline (-50%) | growth_rate = -50 | 0 | `min(25, max(0, 12.5 - 50/2)) = 0` (capped) |
| GS-009 | Null growth rate | growth_rate = null | 12.5 | Default to neutral (0% growth) |
| GS-010 | First snapshot (no previous) | growth_rate = null | 12.5 | No previous data = neutral |

### Anchor Health Score (AH) - 0-25 Points

| ID | Scenario | Input | Expected Score | Calculation |
|----|----------|-------|----------------|-------------|
| AH-001 | Perfect anchor health (0% exact) | exact_match_ratio = 0 | 25 | `min(25, 25 - 0 * 50) = 25` |
| AH-002 | Good anchor health (10% exact) | exact_match_ratio = 0.10 | 20 | `min(25, 25 - 0.10 * 50) = 20` |
| AH-003 | Moderate anchor health (25% exact) | exact_match_ratio = 0.25 | 12.5 | `min(25, 25 - 0.25 * 50) = 12.5` |
| AH-004 | Poor anchor health (40% exact) | exact_match_ratio = 0.40 | 5 | `min(25, 25 - 0.40 * 50) = 5` |
| AH-005 | Very poor anchor health (50% exact) | exact_match_ratio = 0.50 | 0 | `min(25, 25 - 0.50 * 50) = 0` |
| AH-006 | Extremely poor (75% exact) | exact_match_ratio = 0.75 | 0 | `min(25, 25 - 0.75 * 50) = -12.5 → 0` (capped) |
| AH-007 | All exact match (100%) | exact_match_ratio = 1.0 | 0 | `min(25, 25 - 1.0 * 50) = -25 → 0` (capped) |
| AH-008 | Null anchor data | exact_match_ratio = null | 12.5 | Default to neutral |
| AH-009 | No anchors | exact_match_ratio = null | 12.5 | No anchor data = neutral |
| AH-010 | Zero exact match anchors | exact_match_ratio = 0 | 25 | Perfect score |

### Composite Score (CS) - 0-100 Points

| ID | Scenario | Components | Expected Score | Notes |
|----|----------|------------|----------------|-------|
| CS-001 | Perfect profile | DS=25, TS=25, GS=25, AH=25 | 100 | All components max |
| CS-002 | Good profile | DS=20, TS=20, GS=15, AH=20 | 75 | Good across board |
| CS-003 | Moderate profile | DS=15, TS=15, GS=12.5, AH=10 | 52.5 | Mixed scores |
| CS-004 | Poor profile | DS=5, TS=10, GS=5, AH=5 | 25 | Low scores |
| CS-005 | Very poor profile | DS=1, TS=0, GS=0, AH=0 | 1 | Minimum viable |
| CS-006 | Empty backlink profile | All null/0 | 0 | No data available |
| CS-007 | Strong trust, weak anchor | DS=20, TS=25, GS=15, AH=0 | 60 | Imbalanced |
| CS-008 | Good growth, poor trust | DS=15, TS=5, GS=25, AH=15 | 60 | Imbalanced |

### Edge Cases (EC)

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| EC-001 | Project with no backlinks | 0 backlinks | Score = 0, no crash |
| EC-002 | Project with no snapshots | No snapshot records | Score = 0, no crash |
| EC-003 | Project with null metrics | All metrics null | Score = 0 with defaults |
| EC-004 | Very large backlink count | 1,000,000 backlinks | Score calculated correctly |
| EC-005 | Very large domain count | 100,000 domains | Score calculated correctly |
| EC-006 | Division by zero protection | 0 backlinks, 0 domains | Diversity = 0, not NaN |
| EC-007 | Negative growth rate | growth_rate = -100 | Score capped at 0 |
| EC-008 | Growth rate > 100% | growth_rate = 200 | Score capped at 25 |
| EC-009 | Decimal calculations | Non-integer inputs | Precise float results |
| EC-010 | Single backlink snapshot | Only one snapshot | growth_rate = null, GS = 12.5 |
| EC-011 | Missing previous snapshot | First snapshot only | Growth = neutral (12.5) |
| EC-012 | Large project ID | ID = 999999999 | No overflow issues |

### Score Breakdown Tests (SB)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| SB-001 | Score breakdown returned | Array with diversity_score, trust_score, growth_score, anchor_health_score |
| SB-002 | Component percentages | Each component as percentage of max (25) |
| SB-003 | Total matches sum | breakdown total = overall score |
| SB-004 | Null component handling | Null components return 0 in breakdown |

### Security Tests (SC)

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| SC-001 | SQL injection in project ID | `1; DROP TABLE projects; --` | Parameterized query, no injection |
| SC-002 | XSS in domain names | `<script>alert(1)</script>` | Sanitized/escaped, score = 0 |
| SC-003 | Negative project ID | -1 | No results, score = 0 |
| SC-004 | Invalid project type | string "abc" | Type error handled gracefully |
| SC-005 | Extremely large numbers | 1e308 values | No overflow, capped results |

### Performance Tests (PF)

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| PF-001 | Standard calculation | Normal project | < 50ms |
| PF-002 | Large backlink dataset | 100,000 backlinks | < 500ms |
| PF-003 | Many referring domains | 10,000 domains | < 200ms |
| PF-004 | Concurrent scoring | Multiple projects | Thread-safe, no race conditions |

---

## Mock Data

### Project Metrics Test Cases

```php
// Perfect backlink profile
$perfectMetrics = [
    'total_backlinks' => 100,
    'referring_domains' => 100,  // 1:1 ratio
    'avg_domain_trust' => 100,
    'growth_rate' => 25,          // +25% growth
    'exact_match_anchor_ratio' => 0,  // 0% exact match
];
// Expected score: 25 + 25 + 25 + 25 = 100

// Good backlink profile
$goodMetrics = [
    'total_backlinks' => 500,
    'referring_domains' => 200,   // 1:2.5 ratio
    'avg_domain_trust' => 80,
    'growth_rate' => 15,          // +15% growth
    'exact_match_anchor_ratio' => 0.15,  // 15% exact match
];
// Expected score: 25 + 20 + 20 + 17.5 = 82.5

// Poor backlink profile (spammy)
$poorMetrics = [
    'total_backlinks' => 1000,
    'referring_domains' => 10,    // 1:100 ratio (very poor)
    'avg_domain_trust' => 20,
    'growth_rate' => -20,         // -20% decline
    'exact_match_anchor_ratio' => 0.60,  // 60% exact match (spammy)
];
// Expected score: 1 + 5 + 2.5 + 0 = 8.5
```

### Snapshot Mock Data

```php
// Current snapshot
$currentSnapshot = (object) [
    'project_id' => 1,
    'total_backlinks' => 1200,
    'referring_domains_count' => 85,
    'dofollow_count' => 900,
    'nofollow_count' => 300,
    'avg_domain_trust' => 65.5,
    'anchor_diversity_score' => 72.0,
    'snapshot_date' => '2026-02-28',
];

// Previous snapshot (30 days ago)
$previousSnapshot = (object) [
    'project_id' => 1,
    'total_backlinks' => 1000,
    'referring_domains_count' => 75,
    'snapshot_date' => '2026-01-29',
];
// Growth rate: (1200 - 1000) / 1000 * 100 = 20%
```

---

## Coverage Targets

| Component | Target | Notes |
|-----------|--------|-------|
| Branch Coverage | 95% | All scoring branches |
| Line Coverage | 98% | Near-complete coverage |
| Edge Cases | 100% | All edge cases tested |
| Security Tests | 5+ | Injection, XSS, overflow |

---

## Test Execution

```bash
# Run all BacklinkProfileScorer tests
php artisan test --filter BacklinkProfileScorer

# Run specific test file
php artisan test tests/Unit/Services/SEO/BacklinkProfileScorerTest.php

# Run with coverage
php artisan test --coverage --filter BacklinkProfileScorer

# Run integration tests
php artisan test tests/Integration/Services/SEO/BacklinkProfileScorerIntegrationTest.php
```

---

## Dependencies

- PHPUnit 10+
- Laravel Eloquent (for Project model)
- Mock dependencies:
  - `BacklinkSnapshotService` (TASK-23-6)
  - `DomainTrustScorer` (TASK-23-7)
- `BacklinkSnapshot` model (TASK-23-3)

---

## Notes for Developer

### Implementation Requirements

1. **Service Class:** `App\Services\SEO\BacklinkProfileScorer`
2. **Primary Method:** `score(Project $project): array`
3. **Return Structure:**
   ```php
   [
       'overall_score' => float,      // 0-100
       'diversity_score' => float,    // 0-25
       'trust_score' => float,        // 0-25
       'growth_score' => float,       // 0-25
       'anchor_health_score' => float, // 0-25
       'component_details' => [       // Detailed breakdown
           'referring_domains' => int,
           'total_backlinks' => int,
           'avg_domain_trust' => float,
           'growth_rate' => float,
           'exact_match_ratio' => float,
       ]
   ]
   ```

### Scoring Methods

```php
class BacklinkProfileScorer
{
    /**
     * Calculate overall backlink profile health (0-100)
     */
    public function score(Project $project): array;
    
    /**
     * Calculate diversity score (0-25)
     * Higher ratio of domains to backlinks = better
     */
    protected function scoreDiversity(array $metrics): float;
    
    /**
     * Calculate trust score (0-25)
     * Based on average domain trust of referring domains
     */
    protected function scoreTrust(array $metrics): float;
    
    /**
     * Calculate growth score (0-25)
     * Based on month-over-month backlink change
     */
    protected function scoreGrowth(array $metrics): float;
    
    /**
     * Calculate anchor health score (0-25)
     * Lower exact-match ratio = better
     */
    protected function scoreAnchorHealth(array $metrics): float;
    
    /**
     * Get metrics from latest snapshot
     */
    protected function getMetrics(Project $project): array;
    
    /**
     * Calculate growth rate between snapshots
     */
    protected function calculateGrowthRate(Project $project): ?float;
    
    /**
     * Calculate exact match anchor ratio
     */
    protected function calculateExactMatchRatio(Project $project): ?float;
}
```

### Constants to Define

```php
private const MAX_DIVERSITY_SCORE = 25;
private const MAX_TRUST_SCORE = 25;
private const MAX_GROWTH_SCORE = 25;
private const MAX_ANCHOR_SCORE = 25;
private const MAX_OVERALL_SCORE = 100;

private const NEUTRAL_GROWTH_SCORE = 12.5;  // When no growth data
private const NEUTRAL_ANCHOR_SCORE = 12.5;  // When no anchor data
```

### Error Handling

- All methods should handle null/missing data gracefully
- Return default/neutral values on missing data
- Log warnings for debugging (not errors)
- Never throw exceptions from `score()` method
- Handle division by zero in diversity calculation

### Integration with Visibility Score

```php
// In VisibilityScoreService
protected function calculateBacklinkComponent(Project $project): float
{
    $scorer = app(BacklinkProfileScorer::class);
    $result = $scorer->score($project);
    
    return $result['overall_score'] * 0.20; // 20% weight
}
```

---

## Comments

_Agent communication log_

### [2026-02-28 23:35] Agent: PreTester
**Test Design Complete**

Designed comprehensive test suite for BacklinkProfileScorer service with:
- 58 unit tests covering all scoring components
- 15 integration tests for service interaction
- Edge case coverage for null/missing data
- Security tests for injection/XSS
- Performance benchmarks for large datasets

**Key Design Decisions:**
1. Score range enforced: 0-100 overall, 0-25 per component
2. Null/missing data returns neutral/default values, not errors
3. Division by zero protected in diversity calculation
4. Growth rate null (first snapshot) = neutral score (12.5)
5. Anchor data null = neutral score (12.5)
6. All component scores are capped at their maximum

**Dependencies:**
- Requires BacklinkSnapshotService (TASK-23-6) for metrics
- Requires DomainTrustScorer (TASK-23-7) for trust scores
- Requires BacklinkSnapshot model (TASK-23-3)

Handing off to Developer for implementation.
