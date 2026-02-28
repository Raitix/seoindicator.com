# TASK-23-8: Backlink Profile Health Scoring

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Medium`

## Type
Backend

## Description
Create service to calculate overall backlink profile health score. Combines diversity, trust, growth, and anchor health metrics into single 0-100 score.

## Related
- Specification: [SPEC-023](../SPECIFICATIONS/SPEC-023.md)
- Feature: [FEAT-023](../FEATURES/FEAT-023.md)
- Depends on: TASK-23-6, TASK-23-7

---

## Acceptance Criteria
- [ ] `BacklinkProfileScorer` service class created
- [ ] Calculates diversity score (0-25)
- [ ] Calculates trust score (0-25)
- [ ] Calculates growth score (0-25)
- [ ] Calculates anchor health score (0-25)
- [ ] Combines into overall profile score
- [ ] Unit tests for scoring components

---

## Technical Details

### Scoring Algorithm
```php
class BacklinkProfileScorer
{
    /**
     * Calculate overall backlink profile health (0-100)
     */
    public function score(Project $project): float
    {
        $metrics = $this->getMetrics($project);
        
        $diversityScore = $this->scoreDiversity($metrics);      // 0-25
        $trustScore = $this->scoreTrust($metrics);              // 0-25
        $growthScore = $this->scoreGrowth($metrics);             // 0-25
        $anchorHealth = $this->scoreAnchorHealth($metrics);      // 0-25
        
        return $diversityScore + $trustScore + $growthScore + $anchorHealth;
    }
}
```

### Component Scoring

**Diversity Score (0-25):**
```php
// Ratio of referring domains to total backlinks
// Higher ratio = more diverse
$diversityScore = min(25, 
    ($referringDomains / max(1, $totalBacklinks)) * 100
);
```

**Trust Score (0-25):**
```php
// Average domain trust of referring domains
$trustScore = min(25, $avgDomainTrust / 4);
```

**Growth Score (0-25):**
```php
// Month-over-month change in backlinks
// Positive growth = higher score
$growthScore = min(25, max(0, 12.5 + ($growthRate / 2)));
```

**Anchor Health Score (0-25):**
```php
// Lower exact-match anchor % = higher score
$exactMatchRatio = $exactMatchAnchors / max(1, $totalAnchors);
$anchorHealth = min(25, 25 - ($exactMatchRatio * 50));
```

### Integration with Visibility Score
```php
// In VisibilityScoreService
protected function calculateBacklinkComponent(Project $project): float
{
    $backlinkScore = $project->backlinkProfileScore(); // 0-100
    return $backlinkScore * 0.20; // 20% weight
}
```

---

## Dependencies
- TASK-23-6 (Backlink Snapshot Service)
- TASK-23-7 (Domain Trust Scorer)

## Effort Estimate
`Medium` (3-4 hours)

---

## Comments
_Agent communication log_

### [2026-02-28 21:43] Agent: ProjectManager
**Prioritization Complete**

- **Priority Confirmed**: `High` — Core scoring service combining diversity, trust, growth, and anchor health into overall profile score. Critical for SEO visibility integration.
- **Risk**: `Medium` — Algorithm implementation with 4 scoring components, no external API dependencies

**Dependency Analysis:**
| Dependency | Status | Impact |
|------------|--------|--------|
| TASK-23-6 (Snapshot Service) | `To Prioritize` | ⚠️ BLOCKING - Must complete first |
| TASK-23-7 (Domain Trust Scorer) | `To Write Tests` | ⚠️ BLOCKING - Must complete first |

**Execution Order:**
```
TASK-23-6 (Snapshots) ──┬──→ TASK-23-8 (Profile Scorer) ──→ TASK-23-15 (Visibility Integration)
TASK-23-7 (Trust) ──────┘
```

**Scoring Components (0-25 each):**
| Component | Calculation | Source |
|-----------|-------------|--------|
| Diversity | `(referring_domains / backlinks) * 100` | Snapshot metrics |
| Trust | `avg_domain_trust / 4` | TASK-23-7 output |
| Growth | `12.5 + (growth_rate / 2)` | Historical snapshots |
| Anchor Health | `25 - (exact_match_ratio * 50)` | Anchor analysis |

**Visibility Score Integration:**
- Profile score contributes **20% weight** to overall SEO visibility score
- TASK-23-15 will integrate this into `VisibilityScoreService`

**Free Tools Compliance:** ✅ Uses heuristic calculations only, no paid APIs.

Handing off to PreTester for test design.

### [2026-02-28 23:40] Agent: PreTester
**Test Design Complete**

Created comprehensive test suite for BacklinkProfileScorer service:

**Test Files Created:**
- `TESTS/TEST-23-8.md` — Test documentation with 58 unit tests + 15 integration tests
- `backend/tests/Unit/Services/SEO/BacklinkProfileScorerTest.php` — Unit tests (58 tests, 140+ assertions)
- `backend/tests/Integration/Services/SEO/BacklinkProfileScorerIntegrationTest.php` — Integration tests (15 tests)

**Test Coverage:**
| Category | Tests | Description |
|----------|-------|-------------|
| Diversity Score (DS) | 8 | Ratio calculations, edge cases |
| Trust Score (TS) | 8 | Trust average scoring |
| Growth Score (GS) | 10 | Growth/decline scenarios |
| Anchor Health (AH) | 8 | Exact match ratio penalties |
| Composite Score (CS) | 8 | Overall profile combinations |
| Edge Cases (EC) | 7 | Large numbers, null values, decimals |
| Score Breakdown (SB) | 3 | Result structure validation |
| Security (SC) | 5 | Injection, XSS, overflow |
| Integration | 15 | Project model, snapshots, anchors |

**Key Design Decisions:**
1. Score range: 0-100 overall, 0-25 per component
2. Null/missing data returns neutral/default values (12.5 for growth/anchor)
3. Division by zero protected in diversity calculation
4. Growth rate null (first snapshot) = neutral score (12.5)
5. Anchor data null = neutral score (12.5)
6. All component scores capped at 0-25 range

**Return Structure:**
```php
[
    'overall_score' => float,      // 0-100
    'diversity_score' => float,    // 0-25
    'trust_score' => float,        // 0-25
    'growth_score' => float,       // 0-25
    'anchor_health_score' => float, // 0-25
    'component_details' => [
        'referring_domains' => int,
        'total_backlinks' => int,
        'avg_domain_trust' => float,
        'growth_rate' => float,
        'exact_match_ratio' => float,
    ]
]
```

**Dependencies:**
- Requires `BacklinkSnapshot` model (TASK-23-3)
- Requires `BacklinkSnapshotService` (TASK-23-6) for metrics retrieval
- Requires `DomainTrustScorer` (TASK-23-7) for trust score population

**Status:** Tests ready for implementation. Handing off to Developer.
