# TASK-23-9: Backlinks API Endpoints - Overview

## STATUS
`To Prioritize`

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
- [ ] Unit and feature tests

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
