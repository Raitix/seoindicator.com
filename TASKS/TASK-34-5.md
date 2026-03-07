# TASK-34-5: AIVisibilityService

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Specification
SPEC-034: AI Visibility Tracking (Generative Engine Optimization)

---

## Description

Create the main `AIVisibilityService` that orchestrates project management, query execution, and analytics retrieval.

---

## Acceptance Criteria

- [ ] `AIVisibilityService` class created at `app/Services/AIVisibility/AIVisibilityService.php`
- [ ] Implements `AIVisibilityServiceInterface`

- [ ] `createProject()` method implemented
  - [ ] Accepts: `userId`, `data` (brand_name, keywords, competitors, etc.)
  - [ ] Validates competitor count (max 3)
  - [ ] Validates keyword format (array of strings)
  - [ ] Returns `AIVisibilityProject` model

- [ ] `updateProject()` method implemented
  - [ ] Accepts: `projectId`, `data`
  - [ ] Validates user ownership
  - [ ] Returns updated model

- [ ] `deleteProject()` method implemented
  - [ ] Accepts: `projectId`, `userId`
  - [ ] Validates user ownership
  - [ ] Soft deletes or hard deletes based on config

- [ ] `runQueries()` method implemented
  - [ ] Accepts: `projectId`
  - [ ] Dispatches `AIVisibilityQueryJob` for each platform
  - [ ] Generates queries from templates + brand/competitors
  - [ ] Returns array of dispatched job IDs
  - [ ] Updates `last_queried_at` on project

- [ ] `getAnalytics()` method implemented
  - [ ] Accepts: `projectId`, `period` (day, week, month)
  - [ ] Returns aggregated analytics:
    - Mention rate by platform
    - Share of voice
    - Sentiment breakdown
    - Trend direction
  - [ ] Caches results for 1 hour

- [ ] `getCompetitorComparison()` method implemented
  - [ ] Accepts: `projectId`
  - [ ] Returns brand vs competitors comparison:
    - Mention counts
    - Mention rates
    - Sentiment comparison
  - [ ] Caches results for 1 hour

- [ ] `checkAlerts()` method implemented
  - [ ] Accepts: `projectId`
  - [ ] Compares current vs previous period
  - [ ] Creates alerts for threshold breaches:
    - Mention spike (+20%)
    - Mention drop (-20%)
    - Competitor gain (+15%)
  - [ ] Returns array of triggered alerts

- [ ] Query limit enforcement
  - [ ] Free tier: 5 queries/month
  - [ ] Pro tier: 50 queries/month
  - [ ] Business tier: 200 queries/month
  - [ ] Throws exception when limit exceeded

- [ ] Unit tests created
  - [ ] Test project CRUD
  - [ ] Test query dispatch
  - [ ] Test analytics aggregation
  - [ ] Test competitor comparison
  - [ ] Test alert detection
  - [ ] Test query limits

---

## Dependencies
- TASK-34-2 (Models)
- TASK-34-3 (AIQueryEngine)
- TASK-34-4 (MentionParser)

---

## Effort Estimate
**4 hours**

---

## Technical Notes

- Use dependency injection for all dependencies
- Consider using Repository pattern for data access
- Cache analytics with `Cache::remember()`
- Query limits should be configurable via `config/ai-visibility.php`

---

*Created by SpecSystemAnalyst on 2026-03-07*
