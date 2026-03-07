# TASK-34-7: Query Job and Scheduling

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

Create background queue jobs for executing AI visibility queries and scheduled tasks for automated query runs.

---

## Acceptance Criteria

- [ ] `AIVisibilityQueryJob` created at `app/Jobs/AIVisibility/`
  - [ ] Implements `ShouldQueue`
  - [ ] Accepts: `projectId`, `platform`, `query`, `useFreeModel`
  - [ ] Tries: 3, Backoff: 60 seconds
  - [ ] Queue: `ai-visibility`

- [ ] Job handles cache check
  - [ ] Generates cache key from query hash
  - [ ] Returns cached result if available (7-day TTL)
  - [ ] Marks result as `cache_hit = true`

- [ ] Job executes query
  - [ ] Calls `AIQueryEngine::query()`
  - [ ] Parses mentions with `MentionParser`
  - [ ] Stores result in `ai_query_results` table
  - [ ] Caches result for 7 days

- [ ] `RunAIVisibilityQueries` job created
  - [ ] Dispatches individual query jobs for all active projects
  - [ ] Respects tracking frequency (daily/weekly/monthly)
  - [ ] Only queries projects due for query run
  - [ ] Scheduled: Weekly on Sundays at 02:00

- [ ] `AggregateAIVisibilityAnalytics` job created
  - [ ] Aggregates query results into `ai_mention_analytics`
  - [ ] Calculates mention rates, share of voice, sentiment
  - [ ] Scheduled: Daily at 03:00

- [ ] `CheckAIVisibilityAlerts` job created
  - [ ] Compares current vs previous analytics
  - [ ] Creates alerts for threshold breaches
  - [ ] Scheduled: Every 6 hours

- [ ] Scheduled tasks registered in `app/Console/Kernel.php`

- [ ] Error handling
  - [ ] Failed jobs logged with context
  - [ ] Retry logic respects backoff
  - [ ] Dead letter handling after max tries

- [ ] Unit tests created
  - [ ] Test job execution with mocked services
  - [ ] Test cache hit/miss scenarios
  - [ ] Test scheduling configuration

---

## Dependencies
- TASK-34-3 (AIQueryEngine)
- TASK-34-5 (AIVisibilityService)
- FEAT-019 (Queue System)

---

## Effort Estimate
**3 hours**

---

## Technical Notes

- Use Laravel's queue system with Redis driver
- Cache key format: `ai_visibility:query:{md5(platform:query)}`
- Consider using `WithoutOverlapping` middleware to prevent duplicate runs
- Monitor queue health via Laravel Horizon (if available)

---

*Created by SpecSystemAnalyst on 2026-03-07*
