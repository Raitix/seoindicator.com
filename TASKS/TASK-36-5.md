# TASK-36-5: ContentRefreshPlannerService

## STATUS
`To Prioritize`

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
- TASK-36-2 (Eloquent Models) - To Prioritize
- TASK-36-4 (ContentDecayReasonService) - To Prioritize

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

*Created by SpecSystemAnalyst agent on 2026-03-10*
