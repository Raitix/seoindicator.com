# TASK-23-6: Backlink Snapshot Service

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## Type
Backend

## Description
Create service to generate and manage historical backlink snapshots. Enables trend analysis and tracks backlink profile changes over time.

## Related
- Specification: [SPEC-023](../SPECIFICATIONS/SPEC-023.md)
- Feature: [FEAT-023](../FEATURES/FEAT-023.md)
- Depends on: TASK-23-2

---

## Acceptance Criteria
- [ ] `BacklinkSnapshotService` class created
- [ ] Generates daily snapshots with all metrics
- [ ] Calculates anchor diversity score
- [ ] Handles missing data gracefully
- [ ] Provides historical data retrieval
- [ ] Unit tests for snapshot generation

---

## Technical Details

### Service Methods
```php
class BacklinkSnapshotService
{
    /**
     * Create snapshot for a project
     */
    public function createSnapshot(Project $project): BacklinkSnapshot;
    
    /**
     * Get snapshot history for date range
     */
    public function getHistory(int $projectId, string $from, string $to): Collection;
    
    /**
     * Calculate anchor diversity score (0-100)
     */
    public function calculateAnchorDiversity(int $projectId): float;
    
    /**
     * Calculate average domain trust
     */
    public function calculateAverageTrust(int $projectId): float;
}
```

### Snapshot Metrics
| Metric | Calculation |
|--------|-------------|
| `total_backlinks` | Count of active backlinks |
| `referring_domains_count` | Distinct source domains |
| `dofollow_count` | Backlinks with link_type='dofollow' |
| `nofollow_count` | Backlinks with link_type='nofollow' |
| `avg_domain_trust` | Average of referring_domains.domain_trust_score |
| `anchor_diversity_score` | 100 - (top anchor % * 100) |

### Anchor Diversity Score
```php
// Higher score = more diverse anchor text
// Score 0 = all backlinks use same anchor
// Score 100 = perfectly even distribution
function calculateAnchorDiversity(int $projectId): float
{
    $anchors = AnchorText::where('project_id', $projectId)
        ->orderByDesc('backlinks_count')
        ->get();
    
    if ($anchors->isEmpty()) return 0;
    
    $totalBacklinks = $anchors->sum('backlinks_count');
    $topAnchorPercent = $anchors->first()->backlinks_count / $totalBacklinks;
    
    return 100 - ($topAnchorPercent * 100);
}
```

---

## Dependencies
- TASK-23-2 (Backlink Models)

## Effort Estimate
`Small` (2-3 hours)

---

## Comments
_Agent communication log_

### [Mon 2026-03-02 23:14 GMT+2] Agent: ProjectManager
**Prioritization Complete**

- **Priority Upgraded**: `Medium` → `High` — Foundation service blocking TASK-23-11 (API Endpoints - History & Sync)
- **Risk Confirmed**: `Low` — Pure business logic service, no external APIs, uses existing models from TASK-23-2
- **Dependency Verified**: TASK-23-2 (Backlink Models) — Status: `Done` ✅ — Fully satisfied

**Dependency Chain for SPEC-023 (Snapshot Layer):**
```
TASK-23-2 (Models) ──→ TASK-23-6 (Snapshot Service) ──→ TASK-23-11 (API History & Sync)
                            ↑
TASK-23-4 (Sync Job) ───────┘ (creates snapshots during sync)
```

**Critical Path Analysis:**
- TASK-23-11 is `Tested Locally` but its tests mock this service — implementation required for production
- TASK-23-4 (Sync Job) references `BacklinkSnapshotService` integration but service doesn't exist yet
- This service is the **missing link** between data sync and API exposure

**Key Service Methods:**
1. `createSnapshot(Project)` — Generates daily snapshot with all 6 metrics
2. `getHistory(projectId, from, to)` — Historical data retrieval for trend charts
3. `calculateAnchorDiversity(projectId)` — Diversity score (0-100 scale)
4. `calculateAverageTrust(projectId)` — Average domain trust from referring domains

**Metrics to Aggregate:**
| Metric | Source Table |
|--------|--------------|
| total_backlinks | backlinks (is_active=true) |
| referring_domains_count | referring_domains (distinct) |
| dofollow_count | backlinks (link_type='dofollow') |
| nofollow_count | backlinks (link_type='nofollow') |
| avg_domain_trust | referring_domains.domain_trust_score |
| anchor_diversity_score | Calculated from anchor_texts |

**Execution Order:** PreTester can design tests immediately — all dependencies satisfied

**Handoff:** PreTester for TDD test design
