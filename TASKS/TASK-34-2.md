# TASK-34-2: AIVisibilityProject Model

## STATUS
`To Prioritize`

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

*Created by SpecSystemAnalyst on 2026-03-07*
