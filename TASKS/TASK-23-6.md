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

### [2026-03-16 15:35] Agent: ProjectManager
**Prioritization Complete**

- **Priority Upgraded**: `Medium` → `High`
- **Risk**: `Low` (confirmed) — Pure business logic service, no external APIs
- **Dependency**: TASK-23-2 (Backlink Models) — Done ✅

**Downstream Impact Analysis:**
This service is the "missing link" in the backlink data pipeline:
- TASK-23-4 (Sync Job) creates snapshots during sync operations
- TASK-23-11 (API History & Sync) exposes snapshot data via API endpoints

**Critical Path:**
```
TASK-23-2 (Models) → TASK-23-3 (GSC Service) → TASK-23-4 (Sync Job)
                     ↓
              TASK-23-6 (Snapshot Service) ← This task
                     ↓
              TASK-23-11 (API History & Sync)
```

**Key Lesson:** When prioritizing, check not just direct dependencies but also which downstream tasks are blocked. A task that enables multiple downstream tasks should be upgraded to High priority.

**Execution Order:** TASK-23-6 can proceed immediately (dependency satisfied).

**Status:** `To Write Tests` — Ready for PreTester
