# TASK-34-1: Database Migrations

## STATUS
`Tests Ready`

## PRIORITY
`High`

## RISK
`Medium`

## Specification
SPEC-034: AI Visibility Tracking (Generative Engine Optimization)

---

## Description

Create database migrations for AI Visibility Tracking feature. This includes four tables: `ai_visibility_projects`, `ai_query_results`, `ai_mention_analytics`, and `ai_visibility_alerts`.

---

## Acceptance Criteria

- [ ] Migration `create_ai_visibility_projects_table` created
  - [ ] `id` (BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY)
  - [ ] `user_id` (BIGINT UNSIGNED, foreign key to users)
  - [ ] `brand_name` (VARCHAR 255, NOT NULL)
  - [ ] `keywords` (JSON, NOT NULL)
  - [ ] `competitors` (JSON, NOT NULL)
  - [ ] `query_templates` (JSON, nullable)
  - [ ] `tracking_frequency` (ENUM: daily, weekly, monthly, default: weekly)
  - [ ] `is_active` (BOOLEAN, default: true)
  - [ ] `last_queried_at` (TIMESTAMP, nullable)
  - [ ] Index on `user_id, is_active`
  - [ ] Index on `last_queried_at`
  - [ ] Foreign key cascade delete

- [ ] Migration `create_ai_query_results_table` created
  - [ ] `id` (BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY)
  - [ ] `project_id` (BIGINT UNSIGNED, foreign key)
  - [ ] `platform` (VARCHAR 50, NOT NULL)
  - [ ] `model` (VARCHAR 100, NOT NULL)
  - [ ] `query_text` (TEXT, NOT NULL)
  - [ ] `query_category` (VARCHAR 100, nullable)
  - [ ] `response_text` (TEXT, NOT NULL)
  - [ ] `brand_mentioned` (BOOLEAN, default: false)
  - [ ] `brand_context` (TEXT, nullable)
  - [ ] `brand_sentiment` (ENUM: positive, neutral, negative, mixed)
  - [ ] `competitor_mentions` (JSON, nullable)
  - [ ] `citations` (JSON, nullable)
  - [ ] `query_tokens` (INT UNSIGNED, nullable)
  - [ ] `response_tokens` (INT UNSIGNED, nullable)
  - [ ] `latency_ms` (INT UNSIGNED, nullable)
  - [ ] `queried_at` (TIMESTAMP, default: CURRENT_TIMESTAMP)
  - [ ] `cache_hit` (BOOLEAN, default: false)
  - [ ] Index on `project_id, platform`
  - [ ] Index on `queried_at`
  - [ ] Index on `brand_mentioned`
  - [ ] FULLTEXT index on `response_text`

- [ ] Migration `create_ai_mention_analytics_table` created
  - [ ] `id` (BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY)
  - [ ] `project_id` (BIGINT UNSIGNED, foreign key)
  - [ ] `platform` (VARCHAR 50, NOT NULL)
  - [ ] `period_start` (DATE, NOT NULL)
  - [ ] `period_end` (DATE, NOT NULL)
  - [ ] `total_queries` (INT UNSIGNED, default: 0)
  - [ ] `brand_mentions` (INT UNSIGNED, default: 0)
  - [ ] `mention_rate` (DECIMAL 5,2, default: 0.00)
  - [ ] `competitor_mentions` (JSON, nullable)
  - [ ] `share_of_voice` (DECIMAL 5,2, default: 0.00)
  - [ ] `avg_sentiment_score` (DECIMAL 3,2, default: 0.00)
  - [ ] `top_query_categories` (JSON, nullable)
  - [ ] `mention_trend` (ENUM: up, down, stable, default: stable)
  - [ ] UNIQUE index on `project_id, platform, period_start, period_end`

- [ ] Migration `create_ai_visibility_alerts_table` created
  - [ ] `id` (BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY)
  - [ ] `project_id` (BIGINT UNSIGNED, foreign key)
  - [ ] `alert_type` (ENUM: mention_spike, mention_drop, competitor_gain, sentiment_change)
  - [ ] `threshold` (DECIMAL 5,2, NOT NULL)
  - [ ] `previous_value` (DECIMAL 5,2, NOT NULL)
  - [ ] `current_value` (DECIMAL 5,2, NOT NULL)
  - [ ] `change_percent` (DECIMAL 5,2, NOT NULL)
  - [ ] `is_read` (BOOLEAN, default: false)
  - [ ] `is_resolved` (BOOLEAN, default: false)
  - [ ] `triggered_at` (TIMESTAMP, default: CURRENT_TIMESTAMP)
  - [ ] `resolved_at` (TIMESTAMP, nullable)
  - [ ] Index on `project_id, is_read`
  - [ ] Index on `triggered_at`

- [ ] Migrations run successfully on local database
- [ ] Rollback tested for all migrations

---

## Dependencies
- None (foundation task)

---

## Effort Estimate
**2 hours**

---

## Technical Notes

- Use Laravel Schema Builder for all migrations
- JSON columns use `$table->json('column_name')`
- FULLTEXT index requires `$table->fullText('response_text')`
- ENUM columns use `$table->enum('column', ['value1', 'value2'])`
- Foreign keys with cascade delete: `$table->foreignId('project_id')->constrained()->cascadeOnDelete()`

---

## Comments

### ProjectManager Prioritization (2026-03-07)

**Priority: HIGH (confirmed)**
- Foundation task for SPEC-034 - creates all 4 tables for AI Visibility Tracking
- Enables entire downstream pipeline: models → services → API → frontend
- No dependencies - can start immediately
- Non-blocking for other features but critical for this feature's MVP

**Risk: MEDIUM (confirmed)**
- Standard Laravel migrations - non-destructive (add tables only)
- JSON columns for flexible data structures (competitors, citations)
- FULLTEXT index on response_text for search capability
- Foreign key cascade delete ensures referential integrity
- Well-documented Laravel Schema Builder patterns

**Dependency Analysis:**
- None - Foundation task with no blockers
- Users table exists (required for `user_id` foreign key)
- Migration order: projects → query_results/analytics/alerts (project_id FK)

**Downstream Impact:**
- Enables: Models (TASK-34-2+), Services, API endpoints, Frontend components
- All SPEC-034 tasks depend on these 4 tables existing

**TDD Pattern:** PreTester CAN design migration tests immediately:
- Verify table creation with correct columns
- Verify index creation
- Verify foreign key constraints
- Verify rollback functionality
- No mocks needed - direct database assertions

**Execution Order:** First task in SPEC-034 pipeline

---

## Test Coverage

| Category | Tests | Status |
|----------|-------|--------|
| ai_visibility_projects (VP) | 25 | ✅ Complete |
| ai_query_results (QR) | 30 | ✅ Complete |
| ai_mention_analytics (MA) | 25 | ✅ Complete |
| ai_visibility_alerts (VA) | 25 | ✅ Complete |
| Migration Reversibility (MR) | 15 | ✅ Complete |
| Data Integrity (DI) | 10 | ✅ Complete |
| Security & Edge Cases (SE) | 15 | ✅ Complete |
| **Total** | **145** | ✅ 100% AC coverage |

### Test Files Created
- `TESTS/TEST-34-1.md` - Test documentation
- `backend/tests/Unit/Migrations/AIVisibilityTablesMigrationTest.php` - Migration tests

---

*Created by SpecSystemAnalyst on 2026-03-07*
*Tests designed by PreTester on 2026-03-07*
