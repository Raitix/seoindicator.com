# TASK-34-2: AIVisibilityProject Model

## STATUS
`To Write Tests`

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
  - [ ] Casts: `keywords` → array, `competitors` → array, `query_templates` → array, `is_active` → boolean, `last_queried_at` → datetime
  - [ ] BelongsTo relationship to `User`
  - [ ] HasMany relationships: `queryResults`, `analytics`, `alerts`
  - [ ] Scope: `active()` for `is_active = true`
  - [ ] Scope: `dueForQuery()` for projects needing queries

- [ ] `AIQueryResult` model created
  - [ ] Fillable attributes: `project_id`, `platform`, `model`, `query_text`, `query_category`, `response_text`, `brand_mentioned`, `brand_context`, `brand_sentiment`, `competitor_mentions`, `citations`, `query_tokens`, `response_tokens`, `latency_ms`, `queried_at`, `cache_hit`
  - [ ] Casts: `competitor_mentions` → array, `citations` → array, `brand_mentioned` → boolean, `cache_hit` → boolean, `queried_at` → datetime
  - [ ] BelongsTo relationship to `AIVisibilityProject`
  - [ ] Scope: `mentioned()` for `brand_mentioned = true`
  - [ ] Scope: `forPlatform($platform)`

- [ ] `AIMentionAnalytics` model created
  - [ ] Fillable attributes: `project_id`, `platform`, `period_start`, `period_end`, `total_queries`, `brand_mentions`, `mention_rate`, `competitor_mentions`, `share_of_voice`, `avg_sentiment_score`, `top_query_categories`, `mention_trend`
  - [ ] Casts: `competitor_mentions` → array, `top_query_categories` → array, `period_start` → date, `period_end` → date
  - [ ] BelongsTo relationship to `AIVisibilityProject`
  - [ ] Unique constraint on `project_id + platform + period_start + period_end`

- [ ] `AIVisibilityAlert` model created
  - [ ] Fillable attributes: `project_id`, `alert_type`, `threshold`, `previous_value`, `current_value`, `change_percent`, `is_read`, `is_resolved`, `triggered_at`, `resolved_at`
  - [ ] Casts: `is_read` → boolean, `is_resolved` → boolean, `triggered_at` → datetime, `resolved_at` → datetime
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
- Enables entire downstream pipeline: services → API → frontend
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

**Execution Order:** TASK-34-1 → TASK-34-2 (sequential Phase 1 foundation)

**Phase Context:** Phase 1 MVP - Core Tracking
- Model task follows migrations (TASK-34-1)
- Enables all service tasks (TASK-34-3, 34-4, 34-5)
- Sequential dependency chain: Migrations → Models → Services

---

*Created by SpecSystemAnalyst on 2026-03-07*
