# TASK-23-9: Backlinks API Endpoints - Overview

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Low`

## Type
Backend

## Description
Create REST API endpoint for backlinks overview. Returns summary metrics, recent backlinks, and top referring domains for a project.

## Related
- Specification: [SPEC-023](../SPECIFICATIONS/SPEC-023.md)
- Feature: [FEAT-023](../FEATURES/FEAT-023.md)
- Depends on: TASK-23-2, TASK-23-8

---

## Acceptance Criteria
- [ ] `BacklinkController` created with `index` method
- [ ] Returns summary with total backlinks, domains, ratios
- [ ] Returns recent backlinks list (last 10)
- [ ] Returns top referring domains (top 5)
- [ ] Authorization via ProjectPolicy
- [x] Unit and feature tests

---

## Technical Details

### Endpoint
```
GET /api/v1/projects/{projectId}/backlinks
```

### Response
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

### Controller Implementation
```php
class BacklinkController extends Controller
{
    public function __construct(
        private BacklinkRepository $backlinks,
        private ReferringDomainRepository $domains
    ) {}
    
    public function index(Project $project): JsonResponse
    {
        $this->authorize('view', $project);
        
        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $this->getSummary($project),
                'recentBacklinks' => $this->backlinks->recent($project->id, 10),
                'topReferringDomains' => $this->domains->top($project->id, 5),
            ],
        ]);
    }
}
```

---

## Dependencies
- TASK-23-2 (Backlink Models)
- TASK-23-8 (Profile Scorer)

## Effort Estimate
`Small` (2-3 hours)

---

## Comments
_Agent communication log_

### [2026-02-28 22:38] Agent: ProjectManager
**Prioritization In Progress**

- **Priority Confirmed**: `High` — API endpoint for backlinks overview, essential for frontend dashboard
- **Risk**: `Low` — Standard REST endpoint with authorization, no external dependencies

**Dependency Analysis:**
| Dependency | Status | Impact |
|------------|--------|--------|
| TASK-23-2 (Backlink Models) | `To Write Tests` | ✅ Prioritized - can proceed to development |
| TASK-23-8 (Profile Scorer) | `To Write Tests` | ✅ Prioritized - can proceed to development |

**Execution Order:**
```
TASK-23-2 (Models) ──┬──→ TASK-23-9 (API Endpoints - Overview) ──→ TASK-23-10 (API - Domains/Anchors)
TASK-23-8 (Scorer) ─┘
```

**Controller Implementation:**
- `BacklinkController::index()` returns 3 data blocks: summary, recent backlinks, top domains
- Authorization via `ProjectPolicy::view()`
- Summary metrics pulled from `BacklinkSnapshot` and `BacklinkProfileScorer`
- Repository methods: `BacklinkRepository::recent()`, `ReferringDomainRepository::top()`

**Response Structure:**
- `summary`: totalBacklinks, referringDomains, dofollowRatio, avgDomainTrust, growthRate, profileScore
- `recentBacklinks`: last 10 backlinks with source_url, domain, target_url, anchor_text, link_type
- `topReferringDomains`: top 5 domains with backlinks_count, domain_trust_score

**Free Tools Compliance:** ✅ No external APIs required.

Handing off to PreTester for test design.

### [2026-02-28 23:48] Agent: PreTester
**Tests Written - Ready for Development**

**Test Documentation:** `TESTS/TEST-23-9.md`
**Test File:** `backend/tests/Feature/Api/V1/BacklinkOverviewControllerTest.php`

**Test Summary:**
| Category | Tests | Status |
|----------|-------|--------|
| Authorization | 5 | ✅ Written |
| Summary Structure | 7 | ✅ Written |
| Summary Calculations | 9 | ✅ Written |
| Recent Backlinks | 10 | ✅ Written |
| Top Referring Domains | 8 | ✅ Written |
| Data Integrity | 5 | ✅ Written |
| Edge Cases | 9 | ✅ Written |
| Security | 5 | ✅ Written |
| **Total** | **58** | ✅ |

**Test Execution Results:**
- All tests return 404 (expected - route/controller not implemented yet)
- TDD approach: tests written before implementation
- Tests will pass once controller and route are implemented

**Key Test Scenarios:**
1. Authorization tests verify ProjectPolicy integration
2. Summary tests verify all fields and calculations (dofollow ratio, growth rate, profile score)
3. Recent backlinks tests verify ordering and field presence
4. Top domains tests verify ordering by backlinks count
5. Security tests cover SQL injection, XSS, and IDOR attacks

**Developer Notes:**
- Route: `GET /api/v1/projects/{projectId}/backlinks`
- Controller: `BacklinkController::index()`
- Dependencies already implemented:
  - `BacklinkRepository::getRecent()` ✅
  - `ReferringDomainRepository::getTopByBacklinks()` ✅
  - `BacklinkSnapshotRepository::getLatest()` ✅
- Growth rate calculation needs comparison with previous snapshot
- Profile score integration depends on TASK-23-8 (tests ready, awaiting implementation)

Handing off to Developer for implementation.
