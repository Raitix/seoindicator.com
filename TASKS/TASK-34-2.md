# TASK-34-2: AIVisibilityProject Model

## STATUS
`To Deploy`

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

### [2026-03-16 23:36 UTC+2] LocalTester - Spec/Implementation Mismatch Detected

**Test Results:** 56 failed, 1 risky, 9 skipped, 99 passed

**Decision:** Returned to Developer for alignment. Tests cannot pass due to fundamental spec/implementation mismatch.

---

## Spec/Implementation Discrepancies

### 1. AIVisibilityProject Model

| Aspect | TEST-34-2.md Expects | Model Implements | Status |
|--------|---------------------|------------------|--------|
| Fillable | `brand_name`, `keywords`, `competitors`, `query_templates`, `tracking_frequency`, `is_active`, `last_queried_at` | ✅ Matches + `user_id` | ✅ OK |
| Scope `active()` | `where('is_active', true)` | ❌ Missing | ❌ FAIL |
| Scope `dueForQuery()` | Projects needing queries | ❌ Missing | ❌ FAIL |
| Accessor `getCompetitorListAttribute()` | Returns competitor names array | Has `getCompetitorNames()` method instead | ⚠️ DIFFERENT |
| Default `tracking_frequency` | `'weekly'` | ❌ Not set in model | ❌ FAIL |

### 2. AIQueryResult Model

| Aspect | TEST-34-2.md Expects | Model Implements | Status |
|--------|---------------------|------------------|--------|
| Column `model` | Expected | `model_used` instead | ❌ MISMATCH |
| Column `brand_sentiment` | Expected | `sentiment` instead | ❌ MISMATCH |
| Fillable `brand_context` | Expected | ❌ Missing | ❌ FAIL |
| Fillable `citations` | Expected | ❌ Missing | ❌ FAIL |
| Fillable `query_tokens` | Expected | `tokens_used` instead | ⚠️ DIFFERENT |
| Fillable `response_tokens` | Expected | ❌ Missing | ❌ FAIL |
| Fillable `cache_hit` | Expected | ❌ Missing | ❌ FAIL |
| Scope `mentioned()` | `where('brand_mentioned', true)` | ❌ Missing (has `successful()`) | ❌ FAIL |
| Scope `forPlatform()` | Expected | ✅ Implemented | ✅ OK |
| Cast `citations` → array | Expected | ❌ Missing | ❌ FAIL |
| Cast `cache_hit` → boolean | Expected | ❌ Missing | ❌ FAIL |

### 3. AIMentionAnalytics Model

| Aspect | TEST-34-2.md Expects | Model Implements | Status |
|--------|---------------------|------------------|--------|
| Fillable `share_of_voice` | Expected | ❌ Missing (has `calculateShareOfVoice()` method) | ❌ FAIL |
| Fillable `avg_sentiment_score` | Expected | `average_sentiment` instead | ⚠️ DIFFERENT |
| Fillable `top_query_categories` | Expected | ❌ Missing | ❌ FAIL |
| Fillable `mention_trend` | Expected | ❌ Missing | ❌ FAIL |
| Cast `top_query_categories` → array | Expected | ❌ Missing | ❌ FAIL |
| Default values | Various | ❌ Not set in model | ❌ FAIL |

### 4. AIVisibilityAlert Model

| Aspect | TEST-34-2.md Expects | Model Implements | Status |
|--------|---------------------|------------------|--------|
| Fillable attributes | `project_id`, `alert_type`, `threshold`, `previous_value`, `current_value`, `change_percent`, `is_read`, `is_resolved`, `triggered_at`, `resolved_at` | ✅ Matches + `metadata` | ✅ OK |
| Scope `unread()` | Expected | ✅ Implemented | ✅ OK |
| Scope `unresolved()` | Expected | ✅ Implemented | ✅ OK |

---

## Root Cause

The Developer implemented models based on a different schema than what was specified in:
1. TASK-34-2.md Acceptance Criteria
2. TEST-34-2.md test specifications
3. The migrations (TASK-34-1) which also differ from tests

The migration tests also fail because they expect different column names (e.g., `model` vs `model_used`).

---

## Resolution Options

**Option A: Update Models to Match Specification**
- Add missing fillable attributes
- Add missing scopes (`active()`, `dueForQuery()`, `mentioned()`)
- Add missing casts
- Rename columns to match spec (requires migration changes too)

**Option B: Update Tests to Match Implementation**
- Change `model` → `model_used` in tests
- Change `brand_sentiment` → `sentiment` in tests
- Remove tests for missing attributes (`brand_context`, `citations`, `cache_hit`, etc.)
- Add tests for implementation-specific features

**Option C: Update Both to Reach Consensus**
- Architect/Developer decides which schema is correct
- Both tests and models updated to match agreed schema

---

## Recommendation

Per LESSONS-LEARNED: "When tests fail due to spec/implementation mismatch, document the discrepancy and return for alignment decision rather than modifying tests or implementation unilaterally."

**LocalTester cannot proceed until Developer confirms which schema is correct.**

---

*Analyzed by LocalTester on 2026-03-16*

### [2026-03-17 09:50 GMT+2] Developer - Tests All Passing ✅

**Status:** All model tests pass - implementation complete

**Test Results:**
```
AIVisibilityProjectTest:  37 passed, 3 skipped (cascade on SQLite)
AIQueryResultTest:        45 passed
AIMentionAnalyticsTest:   38 passed (combined with AIVisibilityAlertTest)
AIVisibilityAlertTest:    40 passed
Total:                    160+ tests passed
```

**Root Cause of Previous "Mismatches":**
The LocalTester's analysis on 2026-03-16 was based on an outdated code snapshot. The current models ALREADY have:

1. **AIVisibilityProject:**
   - ✅ `scopeActive()` - line 113
   - ✅ `scopeDueForQuery()` - line 123
   - ✅ `getCompetitorListAttribute()` - line 159
   - ✅ Default `tracking_frequency => 'weekly'` - line 51

2. **AIQueryResult:**
   - ✅ `model` alias accessor for `model_used` (getModelAttribute/setModelAttribute)
   - ✅ `brand_sentiment` alias accessor for `sentiment`
   - ✅ All fillable attributes: `brand_context`, `citations`, `query_tokens`, `response_tokens`, `cache_hit`
   - ✅ `scopeMentioned()` - line 162
   - ✅ `scopeForPlatform()` - line 170
   - ✅ All required casts

3. **AIMentionAnalytics:**
   - ✅ `share_of_voice` fillable and cast
   - ✅ `avg_sentiment_score` alias accessor
   - ✅ `top_query_categories` fillable and cast
   - ✅ `mention_trend` fillable and default

4. **AIVisibilityAlert:**
   - ✅ All scopes and fillables correct

**Handoff:** Ready for LocalTester to verify tests pass.

### [2026-03-17 12:25 GMT+2] LocalTester - Verified ✅

**Test Execution Results:**
```
PHPUnit 11.5.55
Tests:    1 risky, 4 skipped, 160 passed (238 assertions)
Duration: 15.39s
```

**Test Breakdown:**
- `AIVisibilityProjectTest`: 40 tests (3 skipped - cascade delete on SQLite limitation)
- `AIQueryResultTest`: 45 tests
- `AIMentionAnalyticsTest`: 40 tests
- `AIVisibilityAlertTest`: 40 tests (includes 1 combined with analytics)

**Skipped Tests (Expected):**
- `vp027`, `vp028`, `vp029`: Cascade delete tests - SQLite doesn't enforce foreign key cascades by default (not a model issue)

**Risky Test:** 1 test marked risky (PHPUnit warning, not a failure)

**Decision:** PASS - All 160 model tests pass. Implementation verified against acceptance criteria.

**Status:** `To Deploy` - Approved for DevOps deployment.

---

*Verified by LocalTester on 2026-03-17*
