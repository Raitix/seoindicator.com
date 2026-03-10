# TASK-36-5: ContentRefreshPlannerService

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## SPECIFICATION
SPEC-036: Content Decay Detection & Refresh Planner

---

## Description
Create the recommendation engine that maps reason codes to concrete refresh actions and generates task suggestions.

## Dependencies
- TASK-36-2 (Eloquent Models) - To Write Tests
- TASK-36-4 (ContentDecayReasonService) - To Write Tests

## Acceptance Criteria
- [ ] `ContentRefreshPlannerService` class created with:
  - `generateRecommendations(ContentDecayCandidate $candidate): array` method returning:
    - `primary_recommendation` (string)
    - `supporting_recommendations` (array of up to 3 strings)
    - `task_suggestions` (array of ContentRefreshTask-like objects)
- [ ] Recommendation mapping table implemented:
  | Trigger | Recommended Action |
  |---------|-------------------|
  | CTR down, impressions stable | Rewrite title and meta description |
  | Position down, ranking loss | Refresh core sections for affected queries |
  | Audit issues present | Fix technical blockers before content rewrite |
  | Stale content | Update examples, screenshots, stats, and date references |
  | AI visibility down | Add definitions, FAQs, citations, quotes, and structured summaries |
  | Competitor gain suspected | Add comparison / alternative sections |
  | Internal link weakness | Add links from stronger related pages |
- [ ] Multiple triggers combine into composite recommendations
- [ ] Task suggestions include:
  - `title` (actionable task title)
  - `recommendation_type` (category of task)
  - `description` (detailed guidance)
  - `due_date` (suggested timeline based on severity)
  - `priority` (derived from severity)
- [ ] Page-type-aware recommendations:
  - Blog posts: content refresh focus
  - Product pages: competitive/comparison focus
  - Landing pages: CTA and conversion focus
  - Tool pages: feature and documentation focus
- [ ] Unit tests verify recommendation mapping
- [ ] Unit tests verify composite recommendations
- [ ] Unit tests verify page-type variations

## Effort Estimate
**3 hours** - Recommendation mapping logic

## Technical Notes
### Recommendation Templates
```php
[
    'ctr_down' => [
        'title' => 'Rewrite title and meta description',
        'description' => 'Your CTR has dropped by {percent}%. Update your title tag to be more compelling and ensure your meta description accurately reflects the page content.',
        'due_days' => 7,
        'priority' => 'high'
    ],
    'audit_issues_present' => [
        'title' => 'Fix technical SEO issues',
        'description' => 'This page has {count} unresolved audit issues. Address technical problems before investing in content updates.',
        'due_days' => 3,
        'priority' => 'critical'
    ],
    // ... more templates
]
```

### Composite Example
```php
// CTR down + stale content + competitor gain
[
    'primary' => 'Comprehensive page refresh needed',
    'supporting' => [
        'Update title and meta description to improve CTR',
        'Refresh outdated examples and statistics',
        'Add competitor comparison section'
    ],
    'tasks' => [...]
]
```

## Files to Create/Modify
- `backend/app/Services/ContentDecay/ContentRefreshPlannerService.php`
- `backend/tests/Unit/Services/ContentDecay/ContentRefreshPlannerServiceTest.php`

---

## Comments

### ProjectManager (2026-03-10 21:53)
**Prioritization Complete**

**Priority: HIGH** (confirmed - already set)
- Core service for Content Decay feature - converts reason codes into ACTIONABLE recommendations
- This is the "action layer" of the feature - without it, decay detection is just data, not guidance
- Enables the entire value proposition: users know WHAT to do, not just that something is wrong
- Downstream impact: Used by API Controllers (TASK-36-10) and displayed in Frontend (TASK-36-11, 36-12)

**Risk: LOW** (confirmed - already set)
- Pure business logic service - no external APIs
- Well-defined interface: `generateRecommendations(ContentDecayCandidate $candidate): array`
- Standard Laravel service with configurable templates
- Testable with mocked candidate data structures

**Dependency Analysis:**
| Dependency | Status | TDD Impact |
|------------|--------|------------|
| TASK-36-2 (Eloquent Models) | To Write Tests | Tests CAN be designed with mocked `ContentDecayCandidate` interface |
| TASK-36-4 (Reason Service) | To Write Tests | Tests CAN be designed with mocked reason code arrays |

**TDD Pattern Applied:**
- PreTester CAN design tests immediately with mocked data structures
- The service operates on `ContentDecayCandidate` objects and reason codes
- Recommendation templates are pure configuration - no database needed for unit tests
- Composite recommendation logic is pure computation - easily testable
- Page-type variations are deterministic mapping logic

**Recommendation Architecture:**
| Trigger | Action Priority | Category | Due Days |
|---------|----------------|----------|----------|
| audit_issues_present | CRITICAL | Technical | 3 |
| ctr_down | HIGH | Meta/CTR | 7 |
| stale_content | HIGH | Content | 14 |
| avg_position_down | MEDIUM | Rankings | 10 |
| competitor_gain_suspected | MEDIUM | Competitive | 14 |
| ai_visibility_down | MEDIUM | GEO | 21 |
| internal_link_weakness | LOW | Structure | 21 |

**Key Features:**
1. **Single-trigger recommendations:** Direct mapping from reason code to action
2. **Composite recommendations:** Combine multiple triggers into unified guidance
3. **Page-type awareness:** Different advice for blog/product/landing/tool pages
4. **Task suggestions:** Generate actionable `ContentRefreshTask` objects with due dates

**Execution Order for SPEC-036:**
1. TASK-36-1 (Migrations) → Done
2. TASK-36-2 (Models) → To Write Tests
3. TASK-36-3 (Scoring Service) → To Write Tests (parallel)
4. TASK-36-4 (Reason Service) → To Write Tests (parallel)
5. TASK-36-5 (Planner Service) → **This task** (parallel with 36-3, 36-4)
6. TASK-36-6 (Detection Orchestrator) → To Prioritize (integrates all services)
7. TASK-36-9 (Queue Jobs) → TASK-36-10 (API) → Frontend

**Parallel Development:**
- TASK-36-3, 36-4, 36-5 can all proceed to PreTester simultaneously
- All three services have well-defined interfaces
- Tests designed with mocks, implementation can proceed in parallel
- Detection Orchestrator (TASK-36-6) integrates all three

**Status:** `To Write Tests` → Ready for PreTester

---

*Created by SpecSystemAnalyst agent on 2026-03-10*
