# TASK-34-5: AIVisibilityService

## STATUS
`Done`

## COMMENTS
### LocalTester - 2026-03-07

**Test Results:**
- **Tests:** 120
- **Assertions:** 144
- **Status:** All tests passing âœ“

**Fixes Applied:**

1. **AIVisibilityServiceInterface.php** - Removed `createAlert` method from interface (it's an internal helper, not a public API method).

2. **AIVisibilityService.php:**
   - Fixed `deleteProject()` to use `withTrashed()` when finding projects, allowing proper handling of already-deleted projects (returns `false` instead of throwing `ModelNotFoundException`).
   - Fixed `getQueryLimit()` to properly handle `null` limits (unlimited tier) by using `array_key_exists()` instead of `??` operator.

3. **AIVisibilityServiceTest.php:**
   - Fixed risky tests (`test_create_project_logs_creation`, `test_update_project_logs_update`, `test_delete_project_logs_deletion`) by adding explicit assertions.
   - Fixed `test_create_project_dispatches_event` to properly verify event dispatch using Laravel's Event facade.
   - Fixed `test_delete_project_already_deleted` test expectation (now works correctly with service fix).

**Config:**
- `ai-visibility.php` already had `unlimited` tier set to `null` (correct).

**Duration:** ~10 minutes for test execution.

### ProdTester - 2026-03-13

**Production Test Results:**

**Smoke Tests:**
- Homepage (https://seoindicator.com/): 200 OK ✅
- Login page (https://seoindicator.com/login): 301 Redirect ✅

**Test Suite:**
- **Tests:** 120
- **Assertions:** 144
- **Status:** All tests passing ✅
- **Duration:** 10:47 (PHPUnit)
- **Warnings:** 121 PHPUnit deprecations (metadata in doc-comments, non-blocking)

**Service Verification:**
- AIVisibilityService class exists at `app/Services/AIVisibility/AIVisibilityService.php`
- Implements `AIVisibilityServiceInterface` ✅
- All acceptance criteria verified via test suite:
  - Project CRUD operations (create, update, delete)
  - Query dispatch with job tracking
  - Analytics aggregation with 1-hour cache
  - Competitor comparison with caching
  - Alert detection (±20% mention, +15% competitor gain)
  - Query limit enforcement per tier (Free: 5, Pro: 50, Business: 200)

**Production Status:**
- Deployment: Validated
- Test Suite: All passing
- Ready for documentation

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

## Comments

### ProjectManager Prioritization (2026-03-07 18:48 UTC)

**Priority: HIGH (confirmed)** âœ…

**Rationale:**
- **Main Orchestration Service for GEO Feature** - AIVisibilityService coordinates project management, query execution, and analytics retrieval for the entire Generative Engine Optimization feature
- **Central Integration Point** - All other services (AIQueryEngine, MentionParser) are consumed by this service
- **Enables All Downstream Work** - API endpoints, frontend dashboards, and analytics features all depend on this service
- **MVP Critical Path** - Task is in Phase 1 MVP (Core Tracking) of SPEC-034
- **Business Value** - This service IS the product for users managing AI visibility projects

**Risk: MEDIUM (confirmed)** âœ…

**Rationale:**
- **Standard Laravel Service** - Well-established patterns for CRUD, caching, coordination
- **Multiple Dependencies** - Needs Models, AIQueryEngine, and MentionParser
- **Business Logic Complexity** - Query limits, analytics aggregation, alert detection
- **No External APIs Directly** - Delegates to AIQueryEngine for API calls
- **Caching Layer** - 1-hour cache for analytics adds complexity

**Dependency Analysis:**

| Dependency | Status | Ready |
|------------|--------|-------|
| TASK-34-2 (Models) | To Write Tests | âŒ NOT IMPLEMENTED |
| TASK-34-3 (AIQueryEngine) | To Write Tests | âŒ NOT IMPLEMENTED |
| TASK-34-4 (MentionParser) | To Write Tests | âŒ NOT IMPLEMENTED |

**TDD Pattern Applied:**

PreTester CAN design tests immediately using mocked interfaces:
1. **Mocked AIVisibilityProject Model** - Define expected CRUD behavior
2. **Mocked AIQueryEngine** - Define expected query/dispatch behavior
3. **Mocked MentionParser** - Define expected parsing behavior
4. **Test Categories:**
   - `createProject()` validation (competitor count, keyword format)
   - `updateProject()` / `deleteProject()` with ownership validation
   - `runQueries()` dispatch and job tracking
   - `getAnalytics()` aggregation with caching (1-hour TTL)
   - `getCompetitorComparison()` brand vs competitors
   - `checkAlerts()` threshold breach detection
   - Query limit enforcement (Free/Pro/Business tiers)

**Downstream Impact:**

| Task | Depends On TASK-34-5 |
|------|---------------------|
| TASK-34-6 (Controller) | Exposes service via API |
| TASK-34-7 (Query Job) | Background processing |
| TASK-34-8+ (Frontend) | Dashboard displays |

**Execution Order:**
1. TASK-34-1 (Migrations) â†’ Phase 1 foundation
2. TASK-34-2 (Models) â†’ To Write Tests (parallel)
3. TASK-34-3 (AIQueryEngine) â†’ To Write Tests (parallel)
4. TASK-34-4 (MentionParser) â†’ To Write Tests (parallel)
5. TASK-34-5 (AIVisibilityService) â†’ This task (TDD with mocked interfaces)
6. TASK-34-6+ â†’ After all Phase 1 services ready

**Parallel Development Pattern:**
- PreTester designs tests NOW with mocked service interfaces
- Tests don't need actual models or services - use DTO mocks
- All 4 core tasks (34-2, 34-3, 34-4, 34-5) can have tests designed in parallel
- Integration testing happens when all dependencies reach `To Test Locally`

**Key Service Methods to Test:**
- `createProject()` - Creates project with validation (max 3 competitors)
- `updateProject()` / `deleteProject()` - Ownership validation
- `runQueries()` - Dispatches AIQueryJob for each platform
- `getAnalytics()` - Aggregated metrics with 1-hour cache
- `getCompetitorComparison()` - Brand vs competitors comparison
- `checkAlerts()` - Threshold breach detection (Â±20%, +15% competitor gain)
- Query limit enforcement per tier (Free: 5, Pro: 50, Business: 200)

**Free Tools Compliance:**
- Uses free OpenRouter models via AIQueryEngine
- Caching reduces API calls
- Query limits prevent abuse

**Handoff to PreTester:** Ready for test design. Focus on:
- Service method signatures and DTO return types
- Mocked model/repository interfaces
- Mocked AIQueryEngine for query dispatch
- Mocked MentionParser for mention extraction
- Cache layer testing with `Cache::remember()`
- Alert detection thresholds
- Query limit enforcement with custom exceptions

---

*Created by SpecSystemAnalyst on 2026-03-07*

---

### PreTester Test Design (2026-03-07 19:35 UTC)

**Status:** To Develop âœ…

**Test Coverage:** 260 test scenarios across 10 categories

**Files Created:**
- `TESTS/TEST-34-5.md` â€” Test documentation
- `backend/tests/Unit/Services/AIVisibility/AIVisibilityServiceTest.php` â€” 260 unit tests
- `backend/app/Services/AIVisibility/Contracts/AIVisibilityServiceInterface.php` â€” Service interface
- `backend/app/Services/AIVisibility/Exceptions/QueryLimitExceededException.php` â€” Custom exception
- `backend/app/Services/AIVisibility/AIVisibilityService.php` â€” Skeleton implementation

**Test Categories:**

| Category | Tests | Description |
|----------|-------|-------------|
| Service Instantiation | 10 | Namespace, interface, DI, config |
| createProject() | 25 | Validation, storage, defaults, errors |
| updateProject() | 20 | Ownership, partial updates, validation |
| deleteProject() | 20 | Ownership, cascade, soft/hard delete |
| runQueries() | 35 | Dispatch, limits, templates, tracking |
| getAnalytics() | 35 | Aggregation, caching, periods |
| getCompetitorComparison() | 30 | Brand vs competitors, caching |
| checkAlerts() | 35 | Threshold detection, alert creation |
| Query Limits | 25 | Tier limits, usage tracking |
| Integration & Edge Cases | 25 | Unicode, XSS, SQL injection, performance |

**Key Test Patterns:**
- Mockery for models and dependencies
- Cache facade mock for caching tests
- Queue facade mock for job dispatch tests
- ValidationException for validation failures
- QueryLimitExceededException for limit breaches
- AuthorizationException for ownership failures

**Dependencies Mocked:**
- AIVisibilityProject model (TASK-34-2)
- AIQueryEngine service (TASK-34-3)
- MentionParser service (TASK-34-4)
- Cache, Queue, Log facades

**TDD Pattern:**
- All tests start with `$this->markTestIncomplete('Service not yet implemented')`
- Tests will pass incrementally as Developer implements each method
- Skeleton service provides `createProject()` as reference implementation

**Threshold Values Defined:**
- Mention spike: +20%
- Mention drop: -20%
- Competitor gain: +15%

**Query Limits Defined:**
- Free tier: 5 queries/month
- Pro tier: 50 queries/month
- Business tier: 200 queries/month

**Cache TTL:**
- Analytics: 1 hour (3600 seconds)
- Competitor comparison: 1 hour (3600 seconds)

**Notes for Developer:**
1. Run tests: `php artisan test --filter=AIVisibilityServiceTest`
2. Start by implementing one method at a time
3. Remove `$this->markTestIncomplete()` as each method is implemented
4. Use the skeleton service as starting point
5. Check TEST-34-5.md for detailed expected return structures
