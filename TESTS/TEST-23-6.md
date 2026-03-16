# TEST-23-6: BacklinkSnapshotService Tests

## Task Reference
- Task: [TASK-23-6](../TASKS/TASK-23-6.md)
- Specification: SPEC-023
- Feature: FEAT-023

## Overview
Tests for `BacklinkSnapshotService` which generates and manages historical backlink snapshots for trend analysis.

---

## Test Files

| File | Tests | Assertions | Status |
|------|-------|------------|--------|
| `backend/tests/Unit/Services/Backlinks/BacklinkSnapshotServiceTest.php` | 85+ | 150+ | TDD (to be created) |

---

## Service Interface

```php
namespace App\Services\Backlinks;

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

---

## Test Scenarios

### SI: Service Instantiation (5 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SI-001 | Service can be resolved from container | `app(BacklinkSnapshotService::class)` | Instance returned |
| SI-002 | Service has createSnapshot method | `method_exists($service, 'createSnapshot')` | true |
| SI-003 | Service has getHistory method | `method_exists($service, 'getHistory')` | true |
| SI-004 | Service has calculateAnchorDiversity method | `method_exists($service, 'calculateAnchorDiversity')` | true |
| SI-005 | Service has calculateAverageTrust method | `method_exists($service, 'calculateAverageTrust')` | true |

### CS: createSnapshot() Tests (20 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CS-001 | Creates snapshot with all metrics | Project with backlinks | BacklinkSnapshot with all fields populated |
| CS-002 | Returns BacklinkSnapshot instance | Project | BacklinkSnapshot class |
| CS-003 | Calculates total_backlinks correctly | 100 active backlinks | total_backlinks = 100 |
| CS-004 | Excludes inactive backlinks | 100 active + 50 inactive | total_backlinks = 100 |
| CS-005 | Calculates referring_domains_count correctly | 10 unique domains | referring_domains_count = 10 |
| CS-006 | Calculates dofollow_count correctly | 80 dofollow, 20 nofollow | dofollow_count = 80 |
| CS-007 | Calculates nofollow_count correctly | 80 dofollow, 20 nofollow | nofollow_count = 20 |
| CS-008 | Calculates avg_domain_trust correctly | Domains with scores 50, 70, 90 | avg_domain_trust = 70.00 |
| CS-009 | Calculates anchor_diversity_score correctly | Top anchor = 30% | anchor_diversity_score = 70.00 |
| CS-010 | Sets snapshot_date to today | Project | snapshot_date = today |
| CS-011 | Handles project with no backlinks | Empty project | All counts = 0, avg_trust = 0 |
| CS-012 | Handles project with no referring domains | Backlinks without domains | avg_domain_trust = 0 |
| CS-013 | Handles project with no anchor texts | No anchors | anchor_diversity_score = 0 |
| CS-014 | Uses repository upsert for duplicate date | Same project, same date | Updates existing snapshot |
| CS-015 | Sets created_at timestamp | Project | created_at set to now |
| CS-016 | Associates snapshot with project | Project | snapshot.project_id = project.id |
| CS-017 | Handles large datasets efficiently | 10000 backlinks | Completes in < 5 seconds |
| CS-018 | Uses database transaction | Project | Wrapped in DB::transaction |
| CS-019 | Throws exception on invalid project | null | InvalidArgumentException |
| CS-020 | Handles missing backlinks_count gracefully | ReferringDomain with null count | Treats as 0 |

### GH: getHistory() Tests (15 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GH-001 | Returns collection of snapshots | projectId, date range | Collection |
| GH-002 | Filters by project_id | projectId | Only snapshots for project |
| GH-003 | Filters by date range | from=2026-01-01, to=2026-01-31 | Snapshots in January |
| GH-004 | Orders by snapshot_date ascending | Multiple snapshots | Oldest first |
| GH-005 | Returns empty collection for no data | projectId without snapshots | Empty Collection |
| GH-006 | Returns empty collection for invalid project | projectId=99999 | Empty Collection |
| GH-007 | Includes all snapshot fields | Snapshot | All fields present |
| GH-008 | Handles single date range | from=to | Single or no snapshot |
| GH-009 | Handles wide date range | 1 year range | All snapshots in range |
| GH-010 | Uses repository for data access | projectId | Calls repository method |
| GH-011 | Validates date format | invalid date | InvalidArgumentException |
| GH-012 | Validates from before to | from > to | InvalidArgumentException |
| GH-013 | Handles date boundaries | from=2026-02-28, to=2026-03-01 | Correct boundary handling |
| GH-014 | Returns snapshots with computed fields | Snapshot | avg_domain_trust as float |
| GH-015 | Handles timezone correctly | Date range | Correct local date handling |

### CAD: calculateAnchorDiversity() Tests (15 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CAD-001 | Returns 100 for perfect diversity | Equal distribution | 100.00 |
| CAD-002 | Returns 0 for single anchor | One anchor, all backlinks | 0.00 |
| CAD-003 | Returns correct score for 50% top anchor | Top = 50% | 50.00 |
| CAD-004 | Returns correct score for 30% top anchor | Top = 30% | 70.00 |
| CAD-005 | Returns correct score for 10% top anchor | Top = 10% | 90.00 |
| CAD-006 | Handles empty anchors | No anchor texts | 0.00 |
| CAD-007 | Handles single anchor text | One anchor | 0.00 |
| CAD-008 | Handles two anchors equally | 50/50 split | 50.00 |
| CAD-009 | Handles multiple anchors | 10 anchors varying counts | Correct calculation |
| CAD-010 | Uses backlinks_count from AnchorText model | AnchorText records | Correct aggregation |
| CAD-011 | Returns float with 2 decimal places | Calculation | Rounded to 2 decimals |
| CAD-012 | Handles zero backlinks_count | Anchor with 0 backlinks | Excludes from calculation |
| CAD-013 | Handles large backlinks_count | 100000+ backlinks | Correct calculation |
| CAD-014 | Orders anchors by backlinks_count | Multiple anchors | Top anchor identified correctly |
| CAD-015 | Isolated to project | Multiple projects | Correct project anchors only |

### CAT: calculateAverageTrust() Tests (15 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CAT-001 | Returns average trust score | Domains with 50, 70, 90 | 70.00 |
| CAT-002 | Returns 0 for no domains | No referring domains | 0.00 |
| CAT-003 | Returns score for single domain | One domain, score=80 | 80.00 |
| CAT-004 | Handles zero trust scores | Domains with 0 | 0.00 |
| CAT-005 | Handles mixed trust scores | 0, 50, 100 | 50.00 |
| CAT-006 | Returns float with 2 decimal places | Calculation | Rounded to 2 decimals |
| CAT-007 | Handles large domain count | 1000 domains | Correct average |
| CAT-008 | Uses domain_trust_score column | ReferringDomain records | Correct field used |
| CAT-009 | Isolated to project | Multiple projects | Correct project domains only |
| CAT-010 | Handles null trust scores | Domain with null score | Excludes from average |
| CAT-011 | Handles all null trust scores | All domains null | 0.00 |
| CAT-012 | Uses AVG aggregation | Multiple domains | Single query |
| CAT-013 | Filters by project_id | projectId | Correct WHERE clause |
| CAT-014 | Returns decimal type | Calculation | float/double |
| CAT-015 | Handles very high scores | Scores 95-100 | Correct average |

### HD: Handle Missing Data Tests (10 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| HD-001 | No backlinks for project | Empty backlinks | snapshot with zeros |
| HD-002 | No referring domains | Empty domains | avg_domain_trust = 0 |
| HD-003 | No anchor texts | Empty anchors | anchor_diversity_score = 0 |
| HD-004 | Backlinks without anchor text | null anchor_text | Excluded from anchor calc |
| HD-005 | Domains without trust score | null domain_trust_score | Excluded from trust calc |
| HD-006 | Only inactive backlinks | is_active=false | total_backlinks = 0 |
| HD-007 | Mixed active/inactive | 100 active, 50 inactive | Only active counted |
| HD-008 | Empty project object | Project with no data | Snapshot with zeros |
| HD-009 | Handles database errors | Query exception | Graceful error handling |
| HD-010 | Logs warning for missing data | No backlinks | Warning logged |

### INT: Integration Tests (10 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| INT-001 | Full snapshot creation workflow | Project with all data | Complete snapshot |
| INT-002 | Multiple snapshots over time | 30 days of snapshots | Correct history |
| INT-003 | Trend calculation uses history | Two snapshots | Correct trend |
| INT-004 | Concurrent snapshot creation | Two processes | No race condition |
| INT-005 | Snapshot used by API | API endpoint | Correct JSON response |
| INT-006 | Repository integration | Repository calls | Correct data flow |
| INT-007 | Model relationships | Snapshot->project | BelongsTo relationship |
| INT-008 | Factory integration | Factory states | Correct test data |
| INT-009 | Full project lifecycle | Create, update, delete | Snapshot cascade |
| INT-010 | Performance with real data | 10000 backlinks | < 5 seconds |

### SEC: Security Tests (5 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SEC-001 | SQL injection in project_id | Malicious string | Parameter binding |
| SEC-002 | XSS in anchor text | Script tags | Not executed |
| SEC-003 | Data isolation between projects | projectId=1, projectId=2 | Correct isolation |
| SEC-004 | No sensitive data exposure | Snapshot data | No PII in response |
| SEC-005 | Input validation | Invalid inputs | Graceful rejection |

### EDGE: Edge Cases (10 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EDGE-001 | Very large backlinks count | 1000000 backlinks | Handles correctly |
| EDGE-002 | Unicode anchor text | Japanese characters | Handled correctly |
| EDGE-003 | Very long anchor text | 1000+ characters | Truncated/stored correctly |
| EDGE-004 | Future snapshot_date | Tomorrow | Accepted (for scheduled) |
| EDGE-005 | Past snapshot_date | Last year | Accepted |
| EDGE-006 | Same snapshot twice | Duplicate call | Upsert behavior |
| EDGE-007 | Zero values | All zeros | Correct zeros |
| EDGE-008 | Negative values prevented | Negative counts | Clamped to 0 |
| EDGE-009 | Concurrent updates | Race condition | Consistent state |
| EDGE-010 | Memory efficiency | Large dataset | Memory stable |

---

## Coverage Targets

| Metric | Target |
|--------|--------|
| Statements | 90% |
| Branches | 85% |
| Functions | 100% |
| Lines | 90% |

---

## Test Execution

```bash
# Run all tests
cd backend
php artisan test --filter=BacklinkSnapshotServiceTest

# Run with coverage
php artisan test --filter=BacklinkSnapshotServiceTest --coverage
```

---

## Dependencies

| Dependency | Status |
|------------|--------|
| TASK-23-2 (Backlink Models) | Done ✅ |
| Backlink model | Available ✅ |
| ReferringDomain model | Available ✅ |
| AnchorText model | Available ✅ |
| BacklinkSnapshot model | Available ✅ |
| BacklinkSnapshotRepository | Available ✅ |
| Project model | Available ✅ |
| Model Factories | Available ✅ |

---

## Notes for Developer

### Implementation Checklist

1. Create `app/Services/Backlinks/BacklinkSnapshotService.php`
2. Inject `BacklinkSnapshotRepository` via constructor
3. Implement `createSnapshot(Project $project)`:
   - Query active backlinks count
   - Query unique referring domains count
   - Query dofollow/nofollow counts
   - Call `calculateAnchorDiversity()`
   - Call `calculateAverageTrust()`
   - Use repository `upsert()` for persistence
4. Implement `getHistory(int $projectId, string $from, string $to)`:
   - Validate date format
   - Validate from <= to
   - Call repository `getByDateRange()`
5. Implement `calculateAnchorDiversity(int $projectId)`:
   - Query AnchorText ordered by backlinks_count DESC
   - Calculate: 100 - (top_anchor_backlinks / total_backlinks * 100)
   - Handle edge cases (no anchors, zero total)
6. Implement `calculateAverageTrust(int $projectId)`:
   - Query ReferringDomain AVG(domain_trust_score)
   - Handle null/zero cases

### Key Calculations

**Anchor Diversity Score:**
```php
$topAnchorPercent = $anchors->first()->backlinks_count / $totalBacklinks;
return 100 - ($topAnchorPercent * 100);
```

**Average Domain Trust:**
```php
return ReferringDomain::where('project_id', $projectId)
    ->whereNotNull('domain_trust_score')
    ->avg('domain_trust_score') ?? 0;
```

### Snapshot Metrics Source

| Metric | Source | Query |
|--------|--------|-------|
| total_backlinks | backlinks | COUNT WHERE is_active=true |
| referring_domains_count | referring_domains | COUNT DISTINCT domain |
| dofollow_count | backlinks | COUNT WHERE link_type='dofollow' AND is_active=true |
| nofollow_count | backlinks | COUNT WHERE link_type='nofollow' AND is_active=true |
| avg_domain_trust | referring_domains | AVG(domain_trust_score) |
| anchor_diversity_score | anchor_texts | 100 - (top_anchor % * 100) |

---

## Comments
_Agent communication log_

### [2026-03-16 16:05] Agent: PreTester
**Tests Coverage In Progress**

Creating test documentation and unit tests for BacklinkSnapshotService.

**Test Categories:**
- Service Instantiation (5 tests)
- createSnapshot() (20 tests)
- getHistory() (15 tests)
- calculateAnchorDiversity() (15 tests)
- calculateAverageTrust() (15 tests)
- Handle Missing Data (10 tests)
- Integration (10 tests)
- Security (5 tests)
- Edge Cases (10 tests)

**Total:** 105 test scenarios
