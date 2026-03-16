# TASK-34-2: AIVisibilityProject Model

## STATUS
`To Test Locally`

## PRIORITY
`High`

## RISK
`Low`

## Specification
SPEC-034: AI Visibility Tracking (Generative Engine Optimization)

---

## Description

Create Eloquent models for all AI Visibility entities: `AIVisibilityProject`, `AIQueryResult`, `AIMentionAnalytics`, and `AIVisibilityAlert`.

---

## Acceptance Criteria

- [ ] `AIVisibilityProject` model created
  - [ ] Fillable attributes: `brand_name`, `keywords`, `competitors`, `query_templates`, `tracking_frequency`, `is_active`, `last_queried_at`
  - [ ] Casts: `keywords` â†’ array, `competitors` â†’ array, `query_templates` â†’ array, `is_active` â†’ boolean, `last_queried_at` â†’ datetime
  - [ ] BelongsTo relationship to `User`
  - [ ] HasMany relationships: `queryResults`, `analytics`, `alerts`
  - [ ] Scope: `active()` for `is_active = true`
  - [ ] Scope: `dueForQuery()` for projects needing queries

- [ ] `AIQueryResult` model created
  - [ ] Fillable attributes: `project_id`, `platform`, `model`, `query_text`, `query_category`, `response_text`, `brand_mentioned`, `brand_context`, `brand_sentiment`, `competitor_mentions`, `citations`, `query_tokens`, `response_tokens`, `latency_ms`, `queried_at`, `cache_hit`
  - [ ] Casts: `competitor_mentions` â†’ array, `citations` â†’ array, `brand_mentioned` â†’ boolean, `cache_hit` â†’ boolean, `queried_at` â†’ datetime
  - [ ] BelongsTo relationship to `AIVisibilityProject`
  - [ ] Scope: `mentioned()` for `brand_mentioned = true`
  - [ ] Scope: `forPlatform($platform)`

- [ ] `AIMentionAnalytics` model created
  - [ ] Fillable attributes: `project_id`, `platform`, `period_start`, `period_end`, `total_queries`, `brand_mentions`, `mention_rate`, `competitor_mentions`, `share_of_voice`, `avg_sentiment_score`, `top_query_categories`, `mention_trend`
  - [ ] Casts: `competitor_mentions` â†’ array, `top_query_categories` â†’ array, `period_start` â†’ date, `period_end` â†’ date
  - [ ] BelongsTo relationship to `AIVisibilityProject`
  - [ ] Unique constraint on `project_id + platform + period_start + period_end`

- [ ] `AIVisibilityAlert` model created
  - [ ] Fillable attributes: `project_id`, `alert_type`, `threshold`, `previous_value`, `current_value`, `change_percent`, `is_read`, `is_resolved`, `triggered_at`, `resolved_at`
  - [ ] Casts: `is_read` â†’ boolean, `is_resolved` â†’ boolean, `triggered_at` â†’ datetime, `resolved_at` â†’ datetime
  - [ ] BelongsTo relationship to `AIVisibilityProject`
  - [ ] Scope: `unread()` for `is_read = false`
  - [ ] Scope: `unresolved()` for `is_resolved = false`

- [ ] All models have proper namespacing: `App\Models\AIVisibility\*`

---

## Dependencies
- TASK-34-1 (Database Migrations)

---

## Effort Estimate
**2 hours**

---

## Technical Notes

- Use model directory structure: `app/Models/AIVisibility/`
- Add PHPDoc type hints for IDE support
- Consider using Laravel's `AsArrayObject` cast for complex JSON fields
- Add `getCompetitorListAttribute()` accessor for easy competitor access

---

## Comments

### ProjectManager Prioritization (2026-03-07 18:35 UTC)

**Priority: HIGH (confirmed)**
- Foundation model task for SPEC-034 - creates all 4 Eloquent models
- Enables entire downstream pipeline: services â†’ API â†’ frontend
- Model tasks are essential infrastructure for feature MVP

**Risk: LOW (confirmed)**
- Standard Eloquent models with relationships and scopes
- No external APIs, no complex business logic
- JSON casts for flexible data structures (keywords, competitors, citations)
- Well-documented Laravel patterns

**Dependency Analysis:**
- TASK-34-1 (Database Migrations): `To Write Tests` - NOT YET IMPLEMENTED
- TDD Pattern: PreTester CAN design model tests with mocked interfaces
- Tests verify: fillable, casts, relationships, scopes, accessors
- PreTester doesn't need actual database tables - uses mocks

**Downstream Impact:**
- Enables: TASK-34-3 (AIQueryEngine), TASK-34-4 (MentionParser), TASK-34-5 (AIVisibilityService)
- Enables: TASK-34-6+ (API endpoints, frontend components)
- All SPEC-034 services depend on these models

**TDD Pattern Applied:**
- PreTester can design tests NOW with expected model interfaces:
  - Mock `AIVisibilityProject` with fillable/cast expectations
  - Mock relationship methods (belongsTo, hasMany)
  - Mock scope methods (active(), dueForQuery())
  - Test property access without database
- Implementation waits for TASK-34-1 to reach `To Test Locally`

**Execution Order:** TASK-34-1 â†’ TASK-34-2 (sequential Phase 1 foundation)

**Phase Context:** Phase 1 MVP - Core Tracking
- Model task follows migrations (TASK-34-1)
- Enables all service tasks (TASK-34-3, 34-4, 34-5)
- Sequential dependency chain: Migrations â†’ Models â†’ Services

---

*Created by SpecSystemAnalyst on 2026-03-07*

---

## Test Coverage

| Model | Tests | Status |
|-------|-------|--------|
| AIVisibilityProject | 40 | âœ… Complete |
| AIQueryResult | 45 | âœ… Complete |
| AIMentionAnalytics | 40 | âœ… Complete |
| AIVisibilityAlert | 40 | âœ… Complete |
| **Total** | **165** | âœ… 100% AC coverage |

### Test Files Created
- `TESTS/TEST-34-2.md` - Test documentation
- `backend/tests/Unit/Models/AIVisibility/AIVisibilityProjectTest.php` - AIVisibilityProject model tests
- `backend/tests/Unit/Models/AIVisibility/AIQueryResultTest.php` - AIQueryResult model tests
- `backend/tests/Unit/Models/AIVisibility/AIMentionAnalyticsTest.php` - AIMentionAnalytics model tests
- `backend/tests/Unit/Models/AIVisibility/AIVisibilityAlertTest.php` - AIVisibilityAlert model tests

### Test Categories

**AIVisibilityProject (VP-001 to VP-040):**
- Model existence and namespace
- Fillable attributes (brand_name, keywords, competitors, etc.)
- Casts (array, boolean, datetime)
- Relationships (belongsTo User, hasMany results/analytics/alerts)
- Scopes (active, dueForQuery)
- Accessors (getCompetitorListAttribute)
- Default values and nullables
- Cascade delete behavior
- Edge cases (large JSON, Unicode)

**AIQueryResult (QR-001 to QR-045):**
- Model existence and namespace
- Fillable attributes (platform, model, query_text, response_text, etc.)
- Casts (array, boolean, datetime)
- Relationships (belongsTo AIVisibilityProject)
- Scopes (mentioned, forPlatform)
- Default values and nullables
- Enum validation (brand_sentiment)
- Edge cases (large response_text, Unicode)

**AIMentionAnalytics (MA-001 to MA-040):**
- Model existence and namespace
- Fillable attributes (platform, period_start, period_end, metrics)
- Casts (array, date, decimal)
- Relationships (belongsTo AIVisibilityProject)
- Default values and nullables
- Unique constraint (project+platform+period)
- Decimal precision tests
- Trend enum validation

**AIVisibilityAlert (VA-001 to VA-040):**
- Model existence and namespace
- Fillable attributes (alert_type, threshold, values, etc.)
- Casts (boolean, datetime, decimal)
- Relationships (belongsTo AIVisibilityProject)
- Scopes (unread, unresolved)
- Default values and nullables
- Alert type enum validation
- State change tests (mark as read, resolve)
- Negative change_percent handling

---

*Tests designed by PreTester on 2026-03-07*

### [2026-03-16 16:40 GMT+2] Developer - Models Already Implemented

**Status:** Implementation Complete ✅

**Existing Models:**
| Model | Status | Description |
|-------|--------|-------------|
| `AIVisibilityProject.php` | ✅ | Brand tracking projects with relationships and scopes |
| `AIQueryResult.php` | ✅ | Query results with platform and sentiment data |
| `AIMentionAnalytics.php` | ✅ | Aggregated analytics per platform/period |
| `AIVisibilityAlert.php` | ✅ | Alert records with threshold tracking |
| `AIMention.php` | ✅ | Per-mention tracking (added later for detailed tracking) |

**All Acceptance Criteria Met:**
- ✅ Proper namespacing: `App\Models\AIVisibility\*`
- ✅ Fillable attributes defined
- ✅ JSON casts for complex fields
- ✅ Relationships configured (belongsTo, hasMany)
- ✅ Scopes implemented (active, dueForQuery, mentioned, forPlatform, unread, unresolved)

**Handoff:** Ready for LocalTester to run model tests.
