# TEST-23-9: Backlinks API Endpoints - Overview

## Task Reference
- **Task:** TASK-23-9
- **Feature:** FEAT-023 (Backlinks Indicator)
- **Specification:** SPEC-023
- **Status:** Tests Coverage In Progress

---

## Overview
Tests for the backlinks overview API endpoint that provides summary metrics, recent backlinks, and top referring domains for a project.

### Endpoint
`GET /api/v1/projects/{projectId}/backlinks`

### Response Structure
```json
{
  "success": true,
  "data": {
    "summary": {
      "totalBacklinks": 1250,
      "referringDomains": 89,
      "dofollowRatio": 0.72,
      "avgDomainTrust": 45.2,
      "growthRate": 12.5,
      "profileScore": 68
    },
    "recentBacklinks": [
      {
        "source_url": "https://example.com/article",
        "source_domain": "example.com",
        "target_url": "https://mysite.com/page",
        "anchor_text": "great article",
        "link_type": "dofollow",
        "first_seen_at": "2026-02-27T10:00:00Z"
      }
    ],
    "topReferringDomains": [
      {
        "domain": "example.com",
        "backlinks_count": 45,
        "domain_trust_score": 72,
        "first_seen_at": "2026-01-15T10:00:00Z"
      }
    ]
  }
}
```

---

## Test Files

| File | Type | Tests | Assertions |
|------|------|-------|------------|
| `backend/tests/Feature/Api/V1/BacklinkOverviewControllerTest.php` | Feature | 55+ | 120+ |
| `backend/tests/Unit/Http/Controllers/BacklinkOverviewControllerTest.php` | Unit | 15+ | 30+ |

---

## SUMMARY TESTS

### Authorization Tests (AUTH)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AUTH-01 | Unauthenticated user | GET without auth token | 401 Unauthorized |
| AUTH-02 | Project owner access | GET as project owner | 200 OK |
| AUTH-03 | Non-owner access | GET as different user | 403 Forbidden |
| AUTH-04 | Non-existent project | GET with invalid projectId | 404 Not Found |
| AUTH-05 | Archived project | GET on archived project | 200 OK (read allowed) |

### Summary Structure Tests (SUM)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SUM-01 | totalBacklinks field | GET with backlinks | Integer count |
| SUM-02 | referringDomains field | GET with domains | Integer count |
| SUM-03 | dofollowRatio field | GET with mixed links | Float 0.0-1.0 |
| SUM-04 | avgDomainTrust field | GET with domains | Float 0-100 |
| SUM-05 | growthRate field | GET with snapshots | Float percentage |
| SUM-06 | profileScore field | GET with data | Integer 0-100 |
| SUM-07 | Empty project summary | GET with no backlinks | Zeros/nulls for all |

### Summary Calculation Tests (CALC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CALC-01 | Total backlinks count | 100 backlinks | totalBacklinks: 100 |
| CALC-02 | Referring domains count | 10 unique domains | referringDomains: 10 |
| CALC-03 | Dofollow ratio calculation | 72 dofollow, 28 nofollow | dofollowRatio: 0.72 |
| CALC-04 | Average domain trust | Domains with 50, 60, 70 trust | avgDomainTrust: 60.0 |
| CALC-05 | Growth rate positive | 100 → 120 backlinks | growthRate: 20.0 |
| CALC-06 | Growth rate negative | 120 → 100 backlinks | growthRate: -16.67 |
| CALC-07 | Growth rate no history | First snapshot | growthRate: null or 0 |
| CALC-08 | Profile score calculation | All metrics present | Score 0-100 |
| CALC-09 | Profile score no data | No snapshots | profileScore: null or 0 |

### Recent Backlinks Tests (RECENT)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RECENT-01 | Returns last 10 | 15 backlinks exist | 10 most recent |
| RECENT-02 | Returns fewer if less | 5 backlinks exist | All 5 returned |
| RECENT-03 | Ordered by first_seen_at | Multiple backlinks | Descending order |
| RECENT-04 | source_url field | GET any | String URL |
| RECENT-05 | source_domain field | GET any | String domain |
| RECENT-06 | target_url field | GET any | String URL |
| RECENT-07 | anchor_text field | GET any | String or null |
| RECENT-08 | link_type field | GET any | "dofollow" or "nofollow" |
| RECENT-09 | first_seen_at field | GET any | ISO 8601 timestamp |
| RECENT-10 | Empty recent backlinks | No backlinks | Empty array |

### Top Referring Domains Tests (TOP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TOP-01 | Returns top 5 | 10 domains exist | 5 with most backlinks |
| TOP-02 | Returns fewer if less | 3 domains exist | All 3 returned |
| TOP-03 | Ordered by backlinks_count | Multiple domains | Descending order |
| TOP-04 | domain field | GET any | String domain |
| TOP-05 | backlinks_count field | GET any | Integer count |
| TOP-06 | domain_trust_score field | GET any | Integer 0-100 |
| TOP-07 | first_seen_at field | GET any | ISO 8601 timestamp |
| TOP-08 | Empty top domains | No domains | Empty array |

---

## DATA INTEGRITY TESTS

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DI-01 | Only project's backlinks | GET for project A | Only A's data |
| DI-02 | Cross-project isolation | Two projects same user | Separate data |
| DI-03 | Cross-user isolation | Two users, different projects | Each sees own |
| DI-04 | Active backlinks only | Mixed active/inactive | Only active counted |
| DI-05 | Counts match actual data | Known backlinks count | Accurate totals |

---

## EDGE CASES

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EDGE-01 | No backlinks | Empty project | summary zeros, empty arrays |
| EDGE-02 | No snapshots | Backlinks but no snapshots | growthRate: null |
| EDGE-03 | Single backlink | 1 backlink | Correct single-item response |
| EDGE-04 | Unicode domains | 例え.jp domain | Handled correctly |
| EDGE-05 | Unicode anchor text | 点击这里 anchor | Handled correctly |
| EDGE-06 | Null anchor text | Backlink with null anchor | Included with null |
| EDGE-07 | Large dataset | 10K+ backlinks | Response < 1 second |
| EDGE-08 | All nofollow | 100% nofollow | dofollowRatio: 0.0 |
| EDGE-09 | All dofollow | 100% dofollow | dofollowRatio: 1.0 |

---

## SECURITY TESTS

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SEC-01 | SQL injection in projectId | GET /projects/1' OR '1'='1 | 404 (route constraint) |
| SEC-02 | XSS in anchor_text | Stored XSS in anchor | Escaped in response |
| SEC-03 | XSS in domain | Stored XSS in domain | Escaped in response |
| SEC-04 | IDOR attack | GET another user's project | 403 Forbidden |
| SEC-05 | Rate limiting | 100+ requests/minute | 429 Too Many Requests |

---

## CONTROLLER UNIT TESTS

| ID | Method | Test | Expected |
|----|--------|------|----------|
| UC-01 | index() | Returns JsonResponse | Instance of JsonResponse |
| UC-02 | index() | Calls ProjectPolicy | authorize('view', $project) |
| UC-03 | index() | Calls BacklinkRepository | getRecent() called |
| UC-04 | index() | Calls ReferringDomainRepository | getTopByBacklinks() called |
| UC-05 | index() | Calls BacklinkSnapshotRepository | getLatest() called |
| UC-06 | index() | Returns summary data | summary structure present |
| UC-07 | index() | Returns recent backlinks | recentBacklinks array |
| UC-08 | index() | Returns top domains | topReferringDomains array |
| UC-09 | index() | Calculates dofollow ratio | Correct calculation |
| UC-10 | index() | Handles empty project | Zeros/nulls gracefully |

---

## MOCK DATA

### Sample Project with Backlinks
```php
$project = Project::factory()->create(['user_id' => $user->id]);

// Create backlinks
Backlink::create([
    'project_id' => $project->id,
    'source_url' => 'https://example.com/article',
    'source_domain' => 'example.com',
    'target_url' => 'https://' . $project->domain . '/page',
    'anchor_text' => 'great article',
    'link_type' => 'dofollow',
    'first_seen_at' => now()->subDays(5),
    'last_checked_at' => now(),
    'is_active' => true,
]);

// Create referring domain
ReferringDomain::create([
    'project_id' => $project->id,
    'domain' => 'example.com',
    'backlinks_count' => 45,
    'domain_trust_score' => 72,
    'first_seen_at' => now()->subMonth(),
    'last_seen_at' => now(),
]);

// Create snapshot
BacklinkSnapshot::create([
    'project_id' => $project->id,
    'total_backlinks' => 1250,
    'referring_domains_count' => 89,
    'dofollow_count' => 900,
    'nofollow_count' => 350,
    'avg_domain_trust' => 45.2,
    'anchor_diversity_score' => 0.75,
    'snapshot_date' => now()->toDateString(),
]);
```

---

## Coverage Targets

| Category | Target | Description |
|----------|--------|-------------|
| Authorization | 100% | All auth scenarios |
| Summary Structure | 100% | All summary fields verified |
| Summary Calculations | 100% | All calculation logic |
| Recent Backlinks | 100% | All recent scenarios |
| Top Domains | 100% | All top domain scenarios |
| Data Integrity | 100% | Cross-project isolation |
| Edge Cases | 90% | Boundary conditions |
| Security | 100% | SQL injection, XSS, IDOR |

---

## Test Execution

```bash
# Run overview endpoint tests
php artisan test --filter=BacklinkOverviewControllerTest

# Run all backlink API tests
php artisan test --filter=Backlink

# Run with coverage
php artisan test --filter=BacklinkOverviewControllerTest --coverage

# Run on SQLite
DB_CONNECTION=sqlite php artisan test --filter=BacklinkOverviewControllerTest
```

---

## Dependencies
- TASK-23-2: Backlink Models and Repositories (Done)
- TASK-23-8: Backlink Profile Scorer (To Develop - tests ready)
- ProjectPolicy (authorization)
- Project model and factory
- Backlink model and factory
- ReferringDomain model and factory
- BacklinkSnapshot model and factory

---

## Notes for Developer

### Route Registration
```php
// routes/api.php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/projects/{project}/backlinks', [BacklinkController::class, 'index']);
});
```

### Controller Structure
```php
class BacklinkController extends Controller
{
    public function __construct(
        private BacklinkRepository $backlinks,
        private ReferringDomainRepository $domains,
        private BacklinkSnapshotRepository $snapshots
    ) {}
    
    public function index(Project $project): JsonResponse
    {
        $this->authorize('view', $project);
        
        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $this->getSummary($project),
                'recentBacklinks' => $this->backlinks->getRecent($project->id, 10),
                'topReferringDomains' => $this->domains->getTopByBacklinks($project->id, 5),
            ],
        ]);
    }
    
    private function getSummary(Project $project): array
    {
        $snapshot = $this->snapshots->getLatest($project->id);
        
        return [
            'totalBacklinks' => $snapshot?->total_backlinks ?? 0,
            'referringDomains' => $snapshot?->referring_domains_count ?? 0,
            'dofollowRatio' => $this->calculateDofollowRatio($snapshot),
            'avgDomainTrust' => $snapshot?->avg_domain_trust ?? 0,
            'growthRate' => $this->calculateGrowthRate($project),
            'profileScore' => $this->getProfileScore($project),
        ];
    }
}
```

---

## Comments

### [2026-02-28 23:48] Agent: PreTester
- Test documentation created for backlinks overview endpoint
- 55+ test scenarios covering authorization, summary, recent backlinks, top domains, edge cases, and security
- TDD approach: tests written before implementation
- Dependencies: TASK-23-2 (Done), TASK-23-8 (To Develop with tests ready)
- Ready for test implementation
