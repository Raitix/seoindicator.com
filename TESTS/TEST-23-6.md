# TEST-23-6: Backlink Snapshot Service

## Task Reference
- **Task:** TASK-23-6
- **Feature:** FEAT-023 (Backlinks Indicator)
- **Specification:** SPEC-023
- **Status:** Tests Coverage In Progress

---

## Overview
Tests for `BacklinkSnapshotService` - a service that generates and manages historical backlink snapshots. Creates daily snapshots with aggregated metrics for trend analysis.

---

## Test Files

| File | Type | Tests | Status |
|------|------|-------|--------|
| `backend/tests/Unit/Services/BacklinkSnapshotServiceTest.php` | Unit | 105+ | EXISTS |

---

## Service Methods Under Test

### 1. `createSnapshot(Project $project): BacklinkSnapshot`
Creates a daily snapshot with aggregated backlink metrics.

### 2. `getHistory(int $projectId, string $from, string $to): Collection`
Returns snapshot history for a date range.

### 3. `calculateAnchorDiversity(int $projectId): float`
Calculates anchor text diversity score (0-100).

### 4. `calculateAverageTrust(int $projectId): float`
Calculates average domain trust from referring domains.

---

## Test Categories

### Category 1: Service Instantiation (SI) - 3 tests

| ID | Scenario | Expected |
|----|----------|----------|
| SI-001 | Service can be instantiated via container | Instance created |
| SI-002 | Service has required dependencies | All repos injected |
| SI-003 | Service resolves from container | New instance each time |

### Category 2: createSnapshot() - Basic (CSB) - 5 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CSB-001 | Creates snapshot with all metrics | Valid project | BacklinkSnapshot with all fields |
| CSB-002 | Returns BacklinkSnapshot model | Valid project | Instance of BacklinkSnapshot |
| CSB-003 | Snapshot date is today | Valid project | `snapshot_date` = today |
| CSB-004 | Stores snapshot in database | Valid project | Snapshot persisted |
| CSB-005 | Associates snapshot with project | Project ID 5 | `project_id` = 5 |

### Category 3: createSnapshot() - Total Backlinks (CST) - 5 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CST-001 | Counts active backlinks only | 10 active | `total_backlinks` = 10 |
| CST-002 | Excludes inactive backlinks | 10 total, 3 inactive | `total_backlinks` = 7 |
| CST-003 | Zero backlinks returns zero | No backlinks | `total_backlinks` = 0 |
| CST-004 | Large count handled correctly | 15000 backlinks | `total_backlinks` = 15000 |
| CST-005 | Counts from correct project | Project ID 42 | Uses project 42's data |

### Category 4: createSnapshot() - Referring Domains (CSR) - 3 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CSR-001 | Counts distinct referring domains | 3 domains | `referring_domains_count` = 3 |
| CSR-002 | Uses referring_domains table count | 5 domains | `referring_domains_count` = 5 |
| CSR-003 | Zero domains returns zero | No domains | `referring_domains_count` = 0 |

### Category 5: createSnapshot() - Dofollow/Nofollow (CSN) - 7 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CSN-001 | Counts dofollow links | 8 dofollow, 2 nofollow | `dofollow_count` = 8 |
| CSN-002 | Counts nofollow links | 8 dofollow, 2 nofollow | `nofollow_count` = 2 |
| CSN-003 | Unknown link type not counted | 5 dofollow, 3 nofollow, 2 unknown | Only 5+3 counted |
| CSN-004 | Only active backlinks counted | Active only | Excludes inactive |
| CSN-005 | Zero dofollow/nofollow | All unknown | Both = 0 |
| CSN-006 | All dofollow | 10 dofollow, 0 nofollow | `dofollow` = 10, `nofollow` = 0 |
| CSN-007 | All nofollow | 0 dofollow, 10 nofollow | `dofollow` = 0, `nofollow` = 10 |

### Category 6: createSnapshot() - Average Domain Trust (CSA) - 4 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CSA-001 | Calculates average trust | Scores: 50, 60, 70 | `avg_domain_trust` = 60.0 |
| CSA-002 | Handles zero trust scores | All 0 | `avg_domain_trust` = 0.0 |
| CSA-003 | Zero domains returns zero | No domains | `avg_domain_trust` = 0.0 |
| CSA-004 | Precision to two decimals | 50.123 | `avg_domain_trust` = 50.12 |

### Category 7: createSnapshot() - Anchor Diversity (CSD) - 4 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CSD-001 | Calculates diversity correctly | Top anchor 25% | `anchor_diversity_score` = 75.0 |
| CSD-002 | Single anchor zero diversity | Top anchor 100% | `anchor_diversity_score` = 0.0 |
| CSD-003 | Perfect distribution | Top anchor 10% | `anchor_diversity_score` = 90.0 |
| CSD-004 | No anchors returns zero | No anchors | `anchor_diversity_score` = 0.0 |

### Category 8: createSnapshot() - Edge Cases (CSE) - 3 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CSE-001 | Project with no backlinks | Empty project | All metrics = 0 |
| CSE-002 | Project with only inactive backlinks | All inactive | All metrics = 0 |
| CSE-003 | Concurrent snapshot updates existing | Same day call | Upserts correctly |

### Category 9: createSnapshot() - Idempotency (CSI) - 2 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CSI-001 | Upserts by project and date | Same project, same day | Updates existing |
| CSI-002 | Returns existing snapshot if exists | Existing snapshot | Same ID returned |

### Category 10: getHistory() - Basic (GHB) - 5 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GHB-001 | Returns collection | Valid range | Collection instance |
| GHB-002 | Ordered by date ascending | Multiple snapshots | Oldest first |
| GHB-003 | Returns empty collection | No snapshots | Empty collection |
| GHB-004 | Returns all snapshots in range | 30 days | 30 snapshots |
| GHB-005 | Each item is BacklinkSnapshot | Valid range | All items correct type |

### Category 11: getHistory() - Date Filtering (GHF) - 6 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GHF-001 | Filters by from_date | Range 2026-01-15 to 2026-01-31 | From date applied |
| GHF-002 | Filters by to_date | Range 2026-01-01 to 2026-01-20 | To date applied |
| GHF-003 | Inclusive date range | Same from and to | Includes that day |
| GHF-004 | Date format YYYY-MM-DD | Valid format | Parsed correctly |
| GHF-005 | Invalid date format throws exception | 'invalid-date' | InvalidArgumentException |
| GHF-006 | From date after to date | Reversed range | Empty collection |

### Category 12: getHistory() - Project Filtering (GHP) - 4 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GHP-001 | Filters by project_id | Project ID 42 | Only project 42 snapshots |
| GHP-002 | Multiple projects isolated | Projects 1, 2 | Separate results |
| GHP-003 | Non-existent project | ID 99999 | Empty collection |
| GHP-004 | Project with no snapshots | New project | Empty collection |

### Category 13: calculateAnchorDiversity() - Basic (CADB) - 3 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CADB-001 | Returns float | Any project | Float type |
| CADB-002 | Score in zero to hundred range | Any project | 0.0-100.0 |
| CADB-003 | Uses anchor_texts table | Project ID | Correct table queried |

### Category 14: calculateAnchorDiversity() - Calculations (CADC) - 5 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CADC-001 | Single anchor zero diversity | Top anchor 100% | 0.0 |
| CADC-002 | Two equal anchors fifty | Top anchor 50% | 50.0 |
| CADC-003 | Top anchor 25 percent | Top anchor 25% | 75.0 |
| CADC-004 | No anchors zero | No anchors | 0.0 |
| CADC-005 | Top anchor 10 percent | Top anchor 10% | 90.0 |

### Category 15: calculateAnchorDiversity() - Edge Cases (CADE) - 2 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CADE-001 | No project found | ID 99999 | 0.0 |
| CADE-002 | Unicode anchor text | Multi-byte chars | Calculated correctly |

### Category 16: calculateAverageTrust() - Basic (CATB) - 3 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CATB-001 | Returns float | Any project | Float type |
| CATB-002 | Score in zero to hundred range | Any project | 0.0-100.0 |
| CATB-003 | Uses referring_domains table | Project ID | Correct table queried |

### Category 17: calculateAverageTrust() - Calculations (CATC) - 5 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CATC-001 | Single domain | Score 75 | 75.0 |
| CATC-002 | Multiple domains | Scores 50, 60, 70 | 60.0 |
| CATC-003 | Zero trust domains | All 0 | 0.0 |
| CATC-004 | No domains | No records | 0.0 |
| CATC-005 | All max scores | All 100 | 100.0 |

### Category 18: calculateAverageTrust() - Edge Cases (CATE) - 2 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CATE-001 | Non-existent project | ID 99999 | 0.0 |
| CATE-002 | Null trust scores excluded | Mixed nulls | Only non-null averaged |

### Category 19: Error Handling (EH) - 4 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EH-001 | Database connection lost | Connection error | Exception thrown |
| EH-002 | Invalid project ID type | Wrong type | Handled gracefully |
| EH-003 | Invalid date format | 'not-a-date' | InvalidArgumentException |
| EH-004 | Repository returns null | Null result | Default value returned |

### Category 20: Security (SEC) - 5 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SEC-001 | SQL injection in project_id | Malicious input | Parameterized query |
| SEC-002 | SQL injection in date | Malicious date | Parameterized query |
| SEC-003 | No sensitive data in logs | Error scenario | No sensitive data logged |
| SEC-004 | Mass assignment protection | Extra fields | Only fillable used |
| SEC-005 | Data isolation | Project query | Only own data returned |

### Category 21: Integration (INT) - 5 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| INT-001 | Full snapshot creation flow | Valid project | All metrics populated |
| INT-002 | History retrieval matches created | Created snapshots | All returned |
| INT-003 | Diversity matches snapshot | Calculate then create | Consistent values |
| INT-004 | Average trust matches snapshot | Calculate then create | Consistent values |
| INT-005 | Multiple projects isolated | Two projects | Separate snapshots |

---

## Repository Dependencies

### Required Repository Methods

| Repository | Method | Purpose | Exists? |
|------------|--------|---------|---------|
| BacklinkRepository | `countActiveByProject(int $projectId): int` | Count active backlinks | ❌ Use `getActiveCount()` |
| BacklinkRepository | `countByType(int $projectId, string $type): int` | Count by link type | ❌ Use `countByLinkType()` |
| ReferringDomainRepository | `countByProject(int $projectId): int` | Count domains | ❌ Use `getTotalCount()` |
| ReferringDomainRepository | `getAverageTrustScore(int $projectId): float` | Avg trust | ✅ EXISTS |
| AnchorTextRepository | `getTopAnchorPercentage(int $projectId): float` | Top anchor % | ❌ NEEDS IMPLEMENTATION |
| BacklinkSnapshotRepository | `getByDateRange(int $projectId, string $from, string $to): Collection` | Date range query | ✅ EXISTS |
| BacklinkSnapshotRepository | `upsert(array $data): BacklinkSnapshot` | Create/update snapshot | ✅ EXISTS |

### Missing Repository Methods

The following methods are expected by tests but don't exist in repositories:

1. **AnchorTextRepository::getTopAnchorPercentage(int $projectId): float**
   - Should return the percentage of backlinks using the most common anchor text
   - Formula: top anchor backlinks count / total backlinks count
   - Alternative: Use existing `calculateDiversity()` method

2. **BacklinkRepository method for counting by type**
   - Tests expect `countByType($projectId, 'dofollow')` returning int
   - Existing `countByLinkType()` returns array of all types
   - Service can use: `countByLinkType()[$type] ?? 0`

---

## Service Implementation Notes

### createSnapshot() Implementation

```php
public function createSnapshot(Project $project): BacklinkSnapshot
{
    $totalBacklinks = $this->backlinkRepository->getActiveCount($project->id);
    $referringDomains = $this->referringDomainRepository->getTotalCount($project->id);
    
    $linkTypes = $this->backlinkRepository->countByLinkType($project->id);
    $dofollowCount = $linkTypes['dofollow'] ?? 0;
    $nofollowCount = $linkTypes['nofollow'] ?? 0;
    
    $avgTrust = $this->referringDomainRepository->getAverageTrustScore($project->id);
    $diversity = $this->calculateAnchorDiversity($project->id);
    
    return $this->snapshotRepository->upsert([
        'project_id' => $project->id,
        'snapshot_date' => now()->toDateString(),
        'total_backlinks' => $totalBacklinks,
        'referring_domains_count' => $referringDomains,
        'dofollow_count' => $dofollowCount,
        'nofollow_count' => $nofollowCount,
        'avg_domain_trust' => $avgTrust,
        'anchor_diversity_score' => $diversity,
    ]);
}
```

### getHistory() Implementation

```php
public function getHistory(int $projectId, string $from, string $to): Collection
{
    return $this->snapshotRepository->getByDateRange($projectId, $from, $to);
}
```

### calculateAnchorDiversity() Implementation

```php
public function calculateAnchorDiversity(int $projectId): float
{
    // Option 1: Add getTopAnchorPercentage() to repository
    $topPercentage = $this->anchorTextRepository->getTopAnchorPercentage($projectId);
    return 100.0 - ($topPercentage * 100.0);
    
    // Option 2: Use existing calculateDiversity()
    // This returns a ratio, need to convert to 0-100 scale
    $diversity = $this->anchorTextRepository->calculateDiversity($projectId);
    // Existing method returns unique/total ratio
    // We need: 100 - (top_anchor_percentage * 100)
}
```

### calculateAverageTrust() Implementation

```php
public function calculateAverageTrust(int $projectId): float
{
    return $this->referringDomainRepository->getAverageTrustScore($projectId);
}
```

---

## Coverage Targets

| Category | Target | Current |
|----------|--------|---------|
| Service Instantiation | 100% | ✅ |
| createSnapshot() | 100% | Tests exist |
| getHistory() | 100% | Tests exist |
| calculateAnchorDiversity() | 100% | Tests exist |
| calculateAverageTrust() | 100% | Tests exist |
| Error Handling | 100% | Tests exist |
| Security | 100% | Tests exist |
| Integration | 100% | Tests exist |

---

## Test Execution

```bash
cd backend
php artisan test --filter=BacklinkSnapshotServiceTest
```

---

## Dependencies
- TASK-23-2 (Backlink Models and Repositories)
- BacklinkRepository
- ReferringDomainRepository
- AnchorTextRepository
- BacklinkSnapshotRepository

---

## Handoff to Developer

### What's Done
- ✅ Test file exists with 105+ tests
- ✅ Test coverage for all 4 service methods
- ✅ Edge cases, error handling, security tests

### What Needs Implementation
1. **Service Methods** - All 4 methods have stubs only:
   - `createSnapshot()`
   - `getHistory()`
   - `calculateAnchorDiversity()`
   - `calculateAverageTrust()`

2. **Optional Repository Enhancement** (or adjust service):
   - Add `getTopAnchorPercentage()` to AnchorTextRepository
   - OR modify service to use existing `calculateDiversity()`

### Test Adjustments Needed
Tests may need minor adjustments if:
- Repository method names differ from test expectations
- Service implementation uses different approach for anchor diversity

---

## Comments

### [2026-03-16 17:18] Agent: PreTester
- Test file analysis complete
- 105+ tests already exist in `BacklinkSnapshotServiceTest.php`
- Tests use mock-based approach (isolated unit tests)
- Service implementation is incomplete (only stubs)
- Identified gap: `getTopAnchorPercentage()` method doesn't exist in AnchorTextRepository
- Developer can either add method to repository or adjust service to use existing `calculateDiversity()`
