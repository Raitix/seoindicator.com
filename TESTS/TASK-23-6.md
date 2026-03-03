# TESTS/TASK-23-6: Backlink Snapshot Service Tests

## Task Reference
- Task: [TASK-23-6](../TASKS/TASK-23-6.md)
- Specification: [SPEC-023](../SPECIFICATIONS/SPEC-023.md)
- Feature: [FEAT-023](../FEATURES/FEAT-023.md)

---

## Overview

Unit tests for `BacklinkSnapshotService` - a service that generates and manages historical backlink snapshots for trend analysis. Tests cover snapshot creation, historical data retrieval, and metric calculations.

---

## Test Files

| File | Tests | Assertions | Status |
|------|-------|------------|--------|
| `backend/tests/Unit/Services/BacklinkSnapshotServiceTest.php` | 85+ | 150+ | Pending |

---

## Test Scenarios

### 1. Service Instantiation (SI)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SI-001 | Service can be instantiated via app container | Container::make() | Instance of BacklinkSnapshotService |
| SI-002 | Service has required dependencies injected | - | BacklinkRepository, ReferringDomainRepository, AnchorTextRepository, BacklinkSnapshotRepository |
| SI-003 | Service resolves from service container | app(BacklinkSnapshotService::class) | Returns singleton instance |

### 2. createSnapshot() - Basic (CSB)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CSB-001 | Creates snapshot with all metrics | Project with backlinks | BacklinkSnapshot with all fields populated |
| CSB-002 | Returns BacklinkSnapshot model | Project | Instance of BacklinkSnapshot |
| CSB-003 | Snapshot date is today | Project | snapshot_date = today's date |
| CSB-004 | Stores snapshot in database | Project | Record exists in backlink_snapshots table |
| CSB-005 | Associates snapshot with project | Project (id=5) | project_id = 5 |

### 3. createSnapshot() - Total Backlinks (CST)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CST-001 | Counts active backlinks only | 10 active, 5 inactive backlinks | total_backlinks = 10 |
| CST-002 | Excludes inactive backlinks | 10 backlinks, 3 is_active=false | total_backlinks = 7 |
| CST-003 | Zero backlinks returns 0 | No backlinks for project | total_backlinks = 0 |
| CST-004 | Large count handled correctly | 10000+ backlinks | total_backlinks = exact count |
| CST-005 | Counts from correct project | Multiple projects | Only counts project's backlinks |

### 4. createSnapshot() - Referring Domains Count (CSR)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CSR-001 | Counts distinct referring domains | 3 domains with multiple backlinks each | referring_domains_count = 3 |
| CSR-002 | Uses referring_domains table count | 5 referring_domains records | referring_domains_count = 5 |
| CSR-003 | Zero domains returns 0 | No referring domains | referring_domains_count = 0 |
| CSR-004 | Filters by project | Multiple projects | Only counts project's domains |
| CSR-005 | Handles duplicate domains | Same domain multiple times | Unique count |

### 5. createSnapshot() - Dofollow/Nofollow Counts (CSN)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CSN-001 | Counts dofollow links | 8 dofollow, 2 nofollow | dofollow_count = 8 |
| CSN-002 | Counts nofollow links | 8 dofollow, 2 nofollow | nofollow_count = 2 |
| CSN-003 | Unknown link type not counted | 5 dofollow, 3 nofollow, 2 unknown | dofollow=5, nofollow=3 |
| CSN-004 | Only active backlinks counted | 5 dofollow active, 2 inactive | dofollow_count = 5 |
| CSN-005 | Zero dofollow/nofollow | All unknown link_type | both counts = 0 |
| CSN-006 | All dofollow | 10 dofollow, 0 nofollow | dofollow=10, nofollow=0 |
| CSN-007 | All nofollow | 0 dofollow, 10 nofollow | dofollow=0, nofollow=10 |

### 6. createSnapshot() - Average Domain Trust (CSA)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CSA-001 | Calculates average trust | Trust scores: 40, 60, 80 | avg_domain_trust = 60.00 |
| CSA-002 | Handles zero trust scores | Scores: 0, 50, 100 | avg_domain_trust = 50.00 |
| CSA-003 | Zero domains returns 0 | No referring domains | avg_domain_trust = 0.00 |
| CSA-004 | Precision to 2 decimals | Scores: 33, 67 | avg_domain_trust = 50.00 |
| CSA-005 | Uses referring_domains.domain_trust_score | 3 domains with scores | Correct average |
| CSA-006 | Large dataset performance | 1000+ domains | Returns result < 100ms |
| CSA-007 | Filters by project | Multiple projects | Only project's domains averaged |

### 7. createSnapshot() - Anchor Diversity Score (CSD)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CSD-001 | Calculates diversity correctly | Top anchor 25% of backlinks | anchor_diversity_score = 75.00 |
| CSD-002 | Single anchor = 0 diversity | All backlinks same anchor | anchor_diversity_score = 0.00 |
| CSD-003 | Perfect distribution = 100 | Equal distribution across anchors | anchor_diversity_score = 100.00 |
| CSD-004 | No anchors returns 0 | No anchor texts recorded | anchor_diversity_score = 0.00 |
| CSD-005 | Top anchor 50% = 50 score | Top anchor has 50% of backlinks | anchor_diversity_score = 50.00 |
| CSD-006 | Formula: 100 - (top_anchor_percent * 100) | Top anchor 30% | score = 70.00 |
| CSD-007 | Handles many anchors | 100+ unique anchors | Correct calculation |
| CSD-008 | Uses anchor_texts table | Records exist | Uses backlinks_count field |

### 8. createSnapshot() - Edge Cases (CSE)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CSE-001 | Project with no backlinks | New project | Creates snapshot with all zeros |
| CSE-002 | Project with only inactive backlinks | All is_active=false | total_backlinks = 0, others = 0 |
| CSE-003 | Concurrent snapshot creation | Same day, same project | Updates existing snapshot |
| CSE-004 | Large backlink dataset | 50,000+ backlinks | Completes within timeout |
| CSE-005 | Database transaction on error | Invalid data | Transaction rolled back |
| CSE-006 | Null/empty anchor text | Some backlinks without anchor | Handled gracefully |

### 9. createSnapshot() - Idempotency (CSI)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CSI-001 | Upserts by project_id and date | Same project, same date | Updates existing snapshot |
| CSI-002 | Returns existing snapshot if exists | Snapshot already exists today | Updates metrics, same ID |
| CSI-003 | Creates new for different date | Same project, different date | New snapshot created |
| CSI-004 | Preserves created_at on update | Update existing snapshot | created_at unchanged |

### 10. getHistory() - Basic Retrieval (GHB)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GHB-001 | Returns Collection | Project ID, date range | Illuminate\Support\Collection |
| GHB-002 | Ordered by date ascending | Multiple snapshots | Oldest first |
| GHB-003 | Returns empty collection | No snapshots in range | Empty Collection |
| GHB-004 | Returns all snapshots in range | 30 days range, 30 snapshots | Collection with 30 items |
| GHB-005 | Each item is BacklinkSnapshot | Valid range | Collection of BacklinkSnapshot models |

### 11. getHistory() - Date Filtering (GHF)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GHF-001 | Filters by from date | from="2026-01-15" | Snapshots >= 2026-01-15 |
| GHF-002 | Filters by to date | to="2026-01-20" | Snapshots <= 2026-01-20 |
| GHF-003 | Inclusive date range | from="2026-01-15", to="2026-01-15" | Snapshot on 2026-01-15 |
| GHF-004 | Date format YYYY-MM-DD | Standard date string | Correctly parsed |
| GHF-005 | Invalid date format | from="invalid" | Throws InvalidArgumentException |
| GHF-006 | From date after to date | from > to | Returns empty collection |
| GHF-007 | Handles 365+ day range | Full year range | All snapshots returned |

### 12. getHistory() - Project Filtering (GHP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GHP-001 | Filters by project_id | projectId=5 | Only project 5 snapshots |
| GHP-002 | Multiple projects exist | 3 projects with snapshots | Only requested project returned |
| GHP-003 | Non-existent project | projectId=99999 | Empty collection |
| GHP-004 | Project with no snapshots | Valid project, no snapshots | Empty collection |

### 13. getHistory() - Performance (GHPF)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GHPF-001 | Uses date index | Large dataset | Query uses idx_snapshot_date |
| GHPF-002 | Uses project index | Large dataset | Query uses idx_project_date |
| GHPF-003 | Efficient for 365 days | 365 snapshots | Returns in < 100ms |
| GHPF-004 | Limits result set | Very large range | Returns all (no limit) |

### 14. calculateAnchorDiversity() - Basic (CADB)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CADB-001 | Returns float | Project ID | float value 0.0-100.0 |
| CADB-002 | Score 0-100 range | Any input | 0 <= score <= 100 |
| CADB-003 | Uses anchor_texts table | Anchors with backlinks_count | Correct calculation |
| CADB-004 | Orders anchors by backlinks_count desc | Multiple anchors | Uses top anchor percentage |

### 15. calculateAnchorDiversity() - Calculations (CADC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CADC-001 | Single anchor = 0 | 100 backlinks, 1 anchor | 0.0 |
| CADC-002 | Two equal anchors = 50 | 50 each | 50.0 |
| CADC-003 | Top anchor 100% = 0 | 1 anchor has all | 0.0 |
| CADC-004 | Top anchor 25% = 75 | Top: 25, Others: 75 | 75.0 |
| CADC-005 | Top anchor 10% = 90 | Top: 10, Others: 90 | 90.0 |
| CADC-006 | No anchors = 0 | Empty table | 0.0 |
| CADC-007 | Many anchors | 50+ anchors | Correct percentage |
| CADC-008 | Decimal precision | Fractional percentages | Rounded to 2 decimals |

### 16. calculateAnchorDiversity() - Edge Cases (CADE)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CADE-001 | No project found | Invalid project ID | Returns 0.0 |
| CADE-002 | Anchors with zero backlinks | backlinks_count=0 | Not included in calculation |
| CADE-003 | Mixed zero and non-zero | Some zero backlinks_count | Only non-zero counted |
| CADE-004 | Unicode anchor text | Chinese/Russian anchors | Handled correctly |
| CADE-005 | Very long anchor text | 500 char anchor | Calculation unaffected |
| CADE-006 | Large backlinks_count | Millions of backlinks | No overflow |

### 17. calculateAverageTrust() - Basic (CATB)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CATB-001 | Returns float | Project ID | float value 0.0-100.0 |
| CATB-002 | Score 0-100 range | Any input | 0 <= avg <= 100 |
| CATB-003 | Uses referring_domains table | Domains with trust_score | Correct average |
| CATB-004 | Filters by project | Multiple projects | Only project's domains |

### 18. calculateAverageTrust() - Calculations (CATC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CATC-001 | Single domain | Trust score: 75 | 75.0 |
| CATC-002 | Multiple domains | 40, 60, 80 | 60.0 |
| CATC-003 | Zero trust domains | All scores = 0 | 0.0 |
| CATC-004 | No domains | Empty table | 0.0 |
| CATC-005 | Mixed scores | 0, 50, 100 | 50.0 |
| CATC-006 | All max scores | All = 100 | 100.0 |
| CATC-007 | Decimal precision | Scores: 33, 67 | 50.0 |

### 19. calculateAverageTrust() - Edge Cases (CATE)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CATE-001 | Non-existent project | Project ID 99999 | 0.0 |
| CATE-002 | Domains with null trust | Some NULL scores | Excluded from average |
| CATE-003 | All null trust scores | All NULL | 0.0 |
| CATE-004 | Large dataset | 10000+ domains | Returns result < 100ms |
| CATE-005 | Negative trust score (invalid) | score = -5 | Treated as 0 or excluded |

### 20. Error Handling (EH)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EH-001 | Database connection lost | Simulate DB failure | Throws DatabaseException |
| EH-002 | Invalid project ID type | String instead of int | Throws TypeError |
| EH-003 | Invalid date format | "not-a-date" | Throws InvalidArgumentException |
| EH-004 | Repository returns null | No data found | Returns default value (0, empty collection) |
| EH-005 | Transaction rollback on error | Exception during snapshot | No partial data saved |

### 21. Security Tests (SEC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SEC-001 | SQL injection in project ID | "1 OR 1=1" | Parameterized query, safe |
| SEC-002 | SQL injection in date | "2026-01-01'; DROP TABLE" | Parameterized query, safe |
| SEC-003 | No sensitive data in logs | Various inputs | No credentials/keys logged |
| SEC-004 | Mass assignment protection | Extra fields in snapshot | Only fillable fields used |
| SEC-005 | Data isolation | Project A user | Cannot access Project B data |

### 22. Integration Tests (INT)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| INT-001 | Full snapshot creation flow | Project with all data | Complete snapshot with all metrics |
| INT-002 | History retrieval matches created | Create 5 snapshots, retrieve | All 5 returned correctly |
| INT-003 | Diversity matches snapshot | calculateAnchorDiversity = snapshot value | Values match |
| INT-004 | Average trust matches snapshot | calculateAverageTrust = snapshot value | Values match |
| INT-005 | Multiple projects isolated | 2 projects with data | No cross-contamination |

---

## Mock Data

### Sample Project
```php
$project = Project::factory()->create([
    'id' => 1,
    'domain' => 'example.com',
]);
```

### Sample Backlinks
```php
Backlink::factory()->count(10)->create([
    'project_id' => 1,
    'is_active' => true,
    'link_type' => 'dofollow',
]);

Backlink::factory()->count(5)->create([
    'project_id' => 1,
    'is_active' => false,
    'link_type' => 'nofollow',
]);
```

### Sample Referring Domains
```php
ReferringDomain::factory()->create([
    'project_id' => 1,
    'domain' => 'referrer1.com',
    'backlinks_count' => 5,
    'domain_trust_score' => 75,
]);

ReferringDomain::factory()->create([
    'project_id' => 1,
    'domain' => 'referrer2.com',
    'backlinks_count' => 3,
    'domain_trust_score' => 45,
]);
```

### Sample Anchor Texts
```php
AnchorText::factory()->create([
    'project_id' => 1,
    'anchor_text' => 'click here',
    'backlinks_count' => 25,
    'is_branded' => false,
]);

AnchorText::factory()->create([
    'project_id' => 1,
    'anchor_text' => 'Example Brand',
    'backlinks_count' => 75,
    'is_branded' => true,
]);
// Top anchor = 75/100 = 75% → diversity score = 25
```

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Service Instantiation | 100% | 3 |
| createSnapshot() | 100% | 40+ |
| getHistory() | 100% | 18+ |
| calculateAnchorDiversity() | 100% | 12+ |
| calculateAverageTrust() | 100% | 12+ |
| Error Handling | 100% | 5+ |
| Security | 100% | 5+ |
| Integration | 100% | 5+ |
| **Total** | **100%** | **85+** |

---

## Test Execution

```bash
# Run all tests for this task
cd backend
php artisan test --filter=BacklinkSnapshotServiceTest

# Run with coverage
php artisan test --filter=BacklinkSnapshotServiceTest --coverage

# Run specific test category
php artisan test --filter="BacklinkSnapshotServiceTest::testCreateSnapshot"
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-23-1 (Migrations) | Done | Schema defined |
| TASK-23-2 (Models/Repos) | Done | Models and repositories exist |
| Backlink model | Done | With active scope |
| ReferringDomain model | Done | With domain_trust_score |
| AnchorText model | Done | With backlinks_count |
| BacklinkSnapshot model | Done | With date scope |
| Project model | Done | Existing model |

---

## Notes for Developer

### Service Location
- File: `backend/app/Services/BacklinkSnapshotService.php`

### Required Methods
```php
class BacklinkSnapshotService
{
    public function createSnapshot(Project $project): BacklinkSnapshot;
    public function getHistory(int $projectId, string $from, string $to): Collection;
    public function calculateAnchorDiversity(int $projectId): float;
    public function calculateAverageTrust(int $projectId): float;
}
```

### Key Implementation Details

1. **createSnapshot()**: Should use database transaction
2. **Idempotency**: Upsert by project_id + snapshot_date
3. **Anchor Diversity Formula**: `100 - (top_anchor_backlinks / total_backlinks * 100)`
4. **Average Trust**: Exclude NULL trust scores from calculation
5. **Zero Handling**: Return 0.0 for empty datasets (not NaN)

### Performance Considerations
- Use Eloquent query optimization for large datasets
- Consider caching frequently accessed calculations
- Use database indexes (project_id, snapshot_date)

---

## Comments
_Agent communication log_

### [Mon 2026-03-02 23:28 GMT+2] Agent: PreTester
**Test Design Complete**

Created comprehensive test documentation covering:
- 85+ test scenarios across 22 categories
- All acceptance criteria covered
- Edge cases and security tests included
- Integration tests for full workflow

**Key Test Patterns:**
- Mockery for repository mocking
- Factory states for different scenarios
- Database transactions for data integrity
- Parameterized queries for security

**Handoff:** Developer for implementation
