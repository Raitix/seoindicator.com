# TASK-23-8: Backlink Profile Health Scoring

## STATUS
`To Prioritize`

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
