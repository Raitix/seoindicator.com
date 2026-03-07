# TASK-34-7: Query Job and Scheduling

## STATUS
`To Write Tests`

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

## Comments

### ProjectManager Prioritization (2026-03-07 18:58 UTC)

**Priority: HIGH (confirmed)** ✅

**Rationale:**
- **Core Background Processing for GEO Feature** - Query jobs are the execution engine for all AI visibility queries
- **Enables Automated Tracking** - Without this task, no scheduled query runs, analytics aggregation, or alerts
- **MVP Critical Path** - Task is in Phase 1 MVP (Core Tracking) of SPEC-034
- **Business Value** - Enables hands-off automated brand visibility monitoring
- **Queue System Available** - FEAT-019 (Queue System) exists with Laravel Horizon/Redis

**Risk: MEDIUM (confirmed)** ✅

**Rationale:**
- **Standard Laravel Jobs** - Well-documented queue patterns, ShouldQueue, retry logic
- **External API Dependency** - Indirect via AIQueryEngine (handles rate limits, timeouts)
- **No Payment Processing** - Background jobs for query execution only
- **Error Handling Well-Defined** - Task specifies max tries, backoff, dead letter handling

**Dependency Analysis:**

| Dependency | Status | Ready |
|------------|--------|-------|
| TASK-34-3 (AIQueryEngine) | To Write Tests | ❌ NOT IMPLEMENTED |
| TASK-34-5 (AIVisibilityService) | To Write Tests | ❌ NOT IMPLEMENTED |
| FEAT-019 (Queue System) | Available | ✅ EXISTS |

**TDD Pattern Applied:**

PreTester CAN design tests immediately using mocked interfaces:
1. **Mocked AIQueryEngine** - Define expected `query()` return structure
2. **Mocked AIVisibilityService** - Define expected `runQueries()` behavior
3. **Mocked MentionParser** - Define expected mention extraction
4. **Test Categories:**
   - `AIVisibilityQueryJob` execution with mocked services
   - Cache hit/miss scenarios (7-day TTL)
   - Cache key generation (`ai_visibility:query:{md5(platform:query)}`)
   - Job configuration (tries: 3, backoff: 60s, queue: `ai-visibility`)
   - `RunAIVisibilityQueries` scheduling logic (weekly, Sundays 02:00)
   - `AggregateAIVisibilityAnalytics` aggregation (daily, 03:00)
   - `CheckAIVisibilityAlerts` threshold detection (every 6 hours)
   - `WithoutOverlapping` middleware
   - Error handling and dead letter scenarios

**Jobs to Create:**

| Job | Purpose | Schedule |
|-----|---------|----------|
| `AIVisibilityQueryJob` | Execute single query | Dispatched per-project |
| `RunAIVisibilityQueries` | Dispatch all project queries | Weekly Sundays 02:00 |
| `AggregateAIVisibilityAnalytics` | Aggregate into analytics table | Daily 03:00 |
| `CheckAIVisibilityAlerts` | Detect threshold breaches | Every 6 hours |

**Downstream Impact:**

| Task | Depends On TASK-34-7 |
|------|---------------------|
| TASK-34-11 (Unit Tests for Services) | Tests jobs along with services |
| TASK-34-12 (Feature Tests for API) | Tests full flow including jobs |
| All frontend dashboards | Depend on data from scheduled jobs |

**Execution Order:**
1. TASK-34-1 (Migrations) → Done first
2. TASK-34-2 (Models) → To Write Tests
3. TASK-34-3 (AIQueryEngine) → To Write Tests (parallel)
4. TASK-34-4 (MentionParser) → To Write Tests (parallel)
5. TASK-34-5 (AIVisibilityService) → To Write Tests (parallel)
6. TASK-34-6 (Controller) → To Write Tests (parallel)
7. TASK-34-7 (This task) → To Write Tests (parallel with TDD mocks)

**Parallel Development Pattern:**
- PreTester designs job tests NOW with mocked service interfaces
- Tests don't need actual services - use DTO/interface mocks
- All 4 core tasks (34-3, 34-5, 34-6, 34-7) can have tests designed in parallel
- Integration testing happens when all dependencies reach `To Test Locally`

**Free Tools Compliance:**
- Uses Laravel native queue system (free)
- Redis driver for queue (self-hosted)
- No external paid services for job processing

**Handoff to PreTester:** Ready for test design. Focus on:
- Job dispatch and configuration tests
- Cache layer with 7-day TTL and key generation
- `WithoutOverlapping` middleware for duplicate prevention
- Scheduled task registration in `app/Console/Kernel.php`
- Error handling: max tries, backoff, dead letter
- Mocked AIQueryEngine and AIVisibilityService interfaces
- Queue: `ai-visibility` configuration

**Status:** `Prioritization In Progress` → `To Write Tests`

---

*Created by SpecSystemAnalyst on 2026-03-07*
