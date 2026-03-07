# TEST-34-1: Database Migrations for AI Visibility Tracking

## Task Reference
- **Task:** TASK-34-1
- **Specification:** SPEC-034
- **Feature:** FEAT-034 - AI Visibility Tracking (Generative Engine Optimization)

---

## Overview

Comprehensive test suite for database migrations creating four tables for AI Visibility Tracking:

1. `ai_visibility_projects` - Project configuration for brand tracking
2. `ai_query_results` - Individual AI query results and responses
3. `ai_mention_analytics` - Aggregated analytics data
4. `ai_visibility_alerts` - Alert notifications for significant changes

---

## Test Files

| File | Location | Purpose |
|------|----------|---------|
| `AIVisibilityTablesMigrationTest.php` | `backend/tests/Unit/Migrations/` | Migration structure and behavior tests |

---

## Test Scenarios

### Category 1: ai_visibility_projects Table (VP-001 to VP-025)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| VP-001 | Table exists after migration | `migrate` | Table `ai_visibility_projects` exists |
| VP-002 | Primary key column | Check `id` | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| VP-003 | user_id column | Check `user_id` | BIGINT UNSIGNED, NOT NULL, FK to users |
| VP-004 | brand_name column | Check `brand_name` | VARCHAR(255), NOT NULL |
| VP-005 | keywords column | Check `keywords` | JSON, NOT NULL |
| VP-006 | competitors column | Check `competitors` | JSON, NOT NULL |
| VP-007 | query_templates column | Check `query_templates` | JSON, nullable |
| VP-008 | tracking_frequency column | Check `tracking_frequency` | ENUM(daily, weekly, monthly), default: weekly |
| VP-009 | is_active column | Check `is_active` | BOOLEAN, default: true |
| VP-010 | last_queried_at column | Check `last_queried_at` | TIMESTAMP, nullable |
| VP-011 | Timestamps | Check `created_at`, `updated_at` | TIMESTAMP columns exist |
| VP-012 | Index on user_id, is_active | Check indexes | Composite index exists |
| VP-013 | Index on last_queried_at | Check indexes | Index exists |
| VP-014 | Foreign key to users | Check FK | FK references users.id |
| VP-015 | Cascade delete | Delete user | Project cascade deleted |
| VP-016 | Insert valid project | Valid data | Record inserted |
| VP-017 | JSON storage for keywords | JSON array | Stored and retrieved correctly |
| VP-018 | JSON storage for competitors | JSON object | Stored and retrieved correctly |
| VP-019 | ENUM validation for tracking_frequency | Valid values | All accepted |
| VP-020 | Default tracking_frequency | No value specified | Defaults to 'weekly' |
| VP-021 | Default is_active | No value specified | Defaults to true |
| VP-022 | NULL query_templates | Omit field | Stored as NULL |
| VP-023 | NULL last_queried_at | Omit field | Stored as NULL |
| VP-024 | Multiple projects per user | Insert 5 projects | All stored correctly |
| VP-025 | Large JSON data | 1000 keywords | Stored successfully |

### Category 2: ai_query_results Table (QR-001 to QR-030)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| QR-001 | Table exists after migration | `migrate` | Table `ai_query_results` exists |
| QR-002 | Primary key column | Check `id` | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| QR-003 | project_id column | Check `project_id` | BIGINT UNSIGNED, NOT NULL, FK |
| QR-004 | platform column | Check `platform` | VARCHAR(50), NOT NULL |
| QR-005 | model column | Check `model` | VARCHAR(100), NOT NULL |
| QR-006 | query_text column | Check `query_text` | TEXT, NOT NULL |
| QR-007 | query_category column | Check `query_category` | VARCHAR(100), nullable |
| QR-008 | response_text column | Check `response_text` | TEXT, NOT NULL |
| QR-009 | brand_mentioned column | Check `brand_mentioned` | BOOLEAN, default: false |
| QR-010 | brand_context column | Check `brand_context` | TEXT, nullable |
| QR-011 | brand_sentiment column | Check `brand_sentiment` | ENUM(positive, neutral, negative, mixed) |
| QR-012 | competitor_mentions column | Check `competitor_mentions` | JSON, nullable |
| QR-013 | citations column | Check `citations` | JSON, nullable |
| QR-014 | query_tokens column | Check `query_tokens` | INT UNSIGNED, nullable |
| QR-015 | response_tokens column | Check `response_tokens` | INT UNSIGNED, nullable |
| QR-016 | latency_ms column | Check `latency_ms` | INT UNSIGNED, nullable |
| QR-017 | queried_at column | Check `queried_at` | TIMESTAMP, default: CURRENT_TIMESTAMP |
| QR-018 | cache_hit column | Check `cache_hit` | BOOLEAN, default: false |
| QR-019 | Index on project_id, platform | Check indexes | Composite index exists |
| QR-020 | Index on queried_at | Check indexes | Index exists |
| QR-021 | Index on brand_mentioned | Check indexes | Index exists |
| QR-022 | FULLTEXT index on response_text | Check indexes | FULLTEXT index exists |
| QR-023 | Foreign key cascade delete | Delete project | Results cascade deleted |
| QR-024 | Insert valid result | Valid data | Record inserted |
| QR-025 | JSON storage for competitor_mentions | JSON array | Stored correctly |
| QR-026 | JSON storage for citations | JSON object | Stored correctly |
| QR-027 | ENUM validation for brand_sentiment | Valid values | All accepted |
| QR-028 | Large response_text | 50KB text | Stored successfully |
| QR-029 | Full-text search on response_text | MATCH AGAINST | Search returns results |
| QR-030 | Timestamps | Check columns | created_at, updated_at exist |

### Category 3: ai_mention_analytics Table (MA-001 to MA-025)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MA-001 | Table exists after migration | `migrate` | Table `ai_mention_analytics` exists |
| MA-002 | Primary key column | Check `id` | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| MA-003 | project_id column | Check `project_id` | BIGINT UNSIGNED, NOT NULL, FK |
| MA-004 | platform column | Check `platform` | VARCHAR(50), NOT NULL |
| MA-005 | period_start column | Check `period_start` | DATE, NOT NULL |
| MA-006 | period_end column | Check `period_end` | DATE, NOT NULL |
| MA-007 | total_queries column | Check `total_queries` | INT UNSIGNED, default: 0 |
| MA-008 | brand_mentions column | Check `brand_mentions` | INT UNSIGNED, default: 0 |
| MA-009 | mention_rate column | Check `mention_rate` | DECIMAL(5,2), default: 0.00 |
| MA-010 | competitor_mentions column | Check `competitor_mentions` | JSON, nullable |
| MA-011 | share_of_voice column | Check `share_of_voice` | DECIMAL(5,2), default: 0.00 |
| MA-012 | avg_sentiment_score column | Check `avg_sentiment_score` | DECIMAL(3,2), default: 0.00 |
| MA-013 | top_query_categories column | Check `top_query_categories` | JSON, nullable |
| MA-014 | mention_trend column | Check `mention_trend` | ENUM(up, down, stable), default: stable |
| MA-015 | Timestamps | Check columns | created_at, updated_at exist |
| MA-016 | UNIQUE constraint on project_id, platform, period_start, period_end | Duplicate record | Violates unique constraint |
| MA-017 | Foreign key cascade delete | Delete project | Analytics cascade deleted |
| MA-018 | Insert valid analytics | Valid data | Record inserted |
| MA-019 | mention_rate precision | 99.99 | Stored with 2 decimal places |
| MA-020 | share_of_voice precision | 100.00 | Stored correctly |
| MA-021 | avg_sentiment_score range | 0.00 to 1.00 | Valid range |
| MA-022 | ENUM validation for mention_trend | Valid values | All accepted |
| MA-023 | Default mention_trend | No value specified | Defaults to 'stable' |
| MA-024 | JSON storage for competitor_mentions | JSON object | Stored correctly |
| MA-025 | JSON storage for top_query_categories | JSON array | Stored correctly |

### Category 4: ai_visibility_alerts Table (VA-001 to VA-025)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| VA-001 | Table exists after migration | `migrate` | Table `ai_visibility_alerts` exists |
| VA-002 | Primary key column | Check `id` | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| VA-003 | project_id column | Check `project_id` | BIGINT UNSIGNED, NOT NULL, FK |
| VA-004 | alert_type column | Check `alert_type` | ENUM(mention_spike, mention_drop, competitor_gain, sentiment_change), NOT NULL |
| VA-005 | threshold column | Check `threshold` | DECIMAL(5,2), NOT NULL |
| VA-006 | previous_value column | Check `previous_value` | DECIMAL(5,2), NOT NULL |
| VA-007 | current_value column | Check `current_value` | DECIMAL(5,2), NOT NULL |
| VA-008 | change_percent column | Check `change_percent` | DECIMAL(5,2), NOT NULL |
| VA-009 | is_read column | Check `is_read` | BOOLEAN, default: false |
| VA-010 | is_resolved column | Check `is_resolved` | BOOLEAN, default: false |
| VA-011 | triggered_at column | Check `triggered_at` | TIMESTAMP, default: CURRENT_TIMESTAMP |
| VA-012 | resolved_at column | Check `resolved_at` | TIMESTAMP, nullable |
| VA-013 | Timestamps | Check columns | created_at, updated_at exist |
| VA-014 | Index on project_id, is_read | Check indexes | Composite index exists |
| VA-015 | Index on triggered_at | Check indexes | Index exists |
| VA-016 | Foreign key cascade delete | Delete project | Alerts cascade deleted |
| VA-017 | Insert valid alert | Valid data | Record inserted |
| VA-018 | ENUM validation for alert_type | Valid values | All accepted |
| VA-019 | Default is_read | No value specified | Defaults to false |
| VA-020 | Default is_resolved | No value specified | Defaults to false |
| VA-021 | NULL resolved_at | Unresolved alert | resolved_at is NULL |
| VA-022 | Resolve alert | Set is_resolved=true, resolved_at | Stored correctly |
| VA-023 | Decimal precision for threshold | 999.99 | Stored with 2 decimal places |
| VA-024 | Decimal precision for change_percent | -50.00 | Negative values allowed |
| VA-025 | Multiple alerts per project | Insert 10 alerts | All stored correctly |

### Category 5: Migration Reversibility (MR-001 to MR-015)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MR-001 | Rollback drops all 4 tables | `migrate:rollback` | All tables removed |
| MR-002 | Re-migrate after rollback | `migrate` | All tables recreated |
| MR-003 | Data preserved in users on rollback | Rollback | Users table intact |
| MR-004 | Foreign keys dropped on rollback | Rollback | No FK constraints remain |
| MR-005 | Indexes dropped on rollback | Rollback | No indexes remain |
| MR-006 | Multiple rollback/remigrate cycles | 3 cycles | All tables work |
| MR-007 | Migration order correct | Check order | projects before other tables |
| MR-008 | Fresh migration works | `migrate:fresh` | All tables created |
| MR-009 | Migration file naming | Check files | Correct naming convention |
| MR-010 | Foreign key constraint enforced | Invalid FK | Exception thrown |
| MR-011 | UNIQUE constraint enforced | Duplicate analytics | Exception thrown |
| MR-012 | No data loss on upgrade | Existing data | Data preserved |
| MR-013 | Column types correct | Check types | Match specification |
| MR-014 | Migration is idempotent | Run twice | No errors |
| MR-015 | Rollback specific migration | Single rollback | Only that migration reversed |

### Category 6: Data Integrity (DI-001 to DI-010)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DI-001 | Query projects by user_id | Filter by user | Correct results |
| DI-002 | Query projects by is_active | Filter active | Correct results |
| DI-003 | Query results by project_id | Filter by project | Correct results |
| DI-004 | Query results by platform | Filter by platform | Correct results |
| DI-005 | Query analytics by date range | Date filter | Correct results |
| DI-006 | Query alerts by is_read | Unread filter | Correct results |
| DI-007 | JOIN projects with results | Join query | Correct relationships |
| DI-008 | JOIN results with analytics | Join query | Correct relationships |
| DI-009 | Aggregation on mention_rate | AVG, SUM | Correct calculations |
| DI-010 | Full-text search | MATCH AGAINST | Results returned |

### Category 7: Security & Edge Cases (SE-001 to SE-015)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SE-001 | SQL injection in brand_name | Malicious input | Stored as literal text |
| SE-002 | SQL injection in JSON columns | Malicious JSON | Stored as literal text |
| SE-003 | XSS in response_text | Script tags | Stored as literal text |
| SE-004 | XSS in brand_context | Script tags | Stored as literal text |
| SE-005 | Unicode in brand_name | Chinese/emoji | Stored correctly |
| SE-006 | Unicode in response_text | Multi-language | Stored correctly |
| SE-007 | NULL in NOT NULL columns | NULL value | Exception thrown |
| SE-008 | Invalid JSON in JSON columns | Malformed JSON | Exception (MySQL) |
| SE-009 | Invalid ENUM values | Invalid status | Exception thrown |
| SE-010 | Negative values in DECIMAL | -50.00 | Stored correctly |
| SE-011 | Exceeding VARCHAR limits | 256 chars | Truncated or error |
| SE-012 | Zero values in INT columns | 0 | Stored correctly |
| SE-013 | Very large JSON | 1MB JSON | Stored successfully |
| SE-014 | Very large response_text | 1MB text | Stored successfully |
| SE-015 | Empty JSON arrays | [] | Stored correctly |

---

## Mock Data

### Sample ai_visibility_project

```json
{
  "id": 1,
  "user_id": 1,
  "brand_name": "Acme Corp",
  "keywords": ["acme", "acme corp", "acme products"],
  "competitors": [
    {"name": "Competitor A", "keywords": ["comp a"]},
    {"name": "Competitor B", "keywords": ["comp b"]}
  ],
  "query_templates": [
    "What are the best {industry} companies?",
    "Who makes the best {product}?"
  ],
  "tracking_frequency": "weekly",
  "is_active": true,
  "last_queried_at": "2026-03-07 10:00:00",
  "created_at": "2026-03-01 09:00:00",
  "updated_at": "2026-03-07 10:00:00"
}
```

### Sample ai_query_result

```json
{
  "id": 1,
  "project_id": 1,
  "platform": "chatgpt",
  "model": "gpt-4",
  "query_text": "What are the best widget companies?",
  "query_category": "industry_comparison",
  "response_text": "Some of the top widget companies include Acme Corp...",
  "brand_mentioned": true,
  "brand_context": "Listed as one of the top 5 widget manufacturers",
  "brand_sentiment": "positive",
  "competitor_mentions": [
    {"name": "Competitor A", "mentioned": true, "context": "..."}
  ],
  "citations": [
    {"title": "Widget Industry Report", "url": "https://..."}
  ],
  "query_tokens": 15,
  "response_tokens": 450,
  "latency_ms": 2300,
  "queried_at": "2026-03-07 10:00:00",
  "cache_hit": false,
  "created_at": "2026-03-07 10:00:00",
  "updated_at": "2026-03-07 10:00:00"
}
```

### Sample ai_mention_analytic

```json
{
  "id": 1,
  "project_id": 1,
  "platform": "chatgpt",
  "period_start": "2026-03-01",
  "period_end": "2026-03-07",
  "total_queries": 50,
  "brand_mentions": 35,
  "mention_rate": 70.00,
  "competitor_mentions": {
    "Competitor A": {"mentions": 42, "rate": 84.00},
    "Competitor B": {"mentions": 28, "rate": 56.00}
  },
  "share_of_voice": 33.33,
  "avg_sentiment_score": 0.72,
  "top_query_categories": ["industry_comparison", "product_review"],
  "mention_trend": "up",
  "created_at": "2026-03-07 23:00:00",
  "updated_at": "2026-03-07 23:00:00"
}
```

### Sample ai_visibility_alert

```json
{
  "id": 1,
  "project_id": 1,
  "alert_type": "mention_spike",
  "threshold": 10.00,
  "previous_value": 35.00,
  "current_value": 52.00,
  "change_percent": 48.57,
  "is_read": false,
  "is_resolved": false,
  "triggered_at": "2026-03-07 23:00:00",
  "resolved_at": null,
  "created_at": "2026-03-07 23:00:00",
  "updated_at": "2026-03-07 23:00:00"
}
```

---

## Coverage Targets

| Category | Tests | Target |
|----------|-------|--------|
| ai_visibility_projects | 25 | 100% column/feature coverage |
| ai_query_results | 30 | 100% column/feature coverage |
| ai_mention_analytics | 25 | 100% column/feature coverage |
| ai_visibility_alerts | 25 | 100% column/feature coverage |
| Migration Reversibility | 15 | Rollback/remigrate scenarios |
| Data Integrity | 10 | Relationship/join tests |
| Security & Edge Cases | 15 | Injection, XSS, boundaries |
| **Total** | **145** | **100% acceptance criteria coverage** |

---

## Test Execution

```bash
# Run all migration tests
cd backend
php artisan test --filter=AIVisibilityTablesMigrationTest

# Run with coverage
php artisan test --filter=AIVisibilityTablesMigrationTest --coverage

# Run specific category
php artisan test --filter=test_vp001_table_exists_after_migration
```

---

## Dependencies

- MySQL/MariaDB database (or SQLite for CI)
- Laravel migration system
- Users table (for foreign key)

---

## Notes for Developer

1. **Migration Order**: Ensure migrations run in correct order:
   - `ai_visibility_projects` first (no project_id FK dependencies)
   - Other 3 tables after (all depend on projects)

2. **JSON Columns**: Use Laravel's `$table->json()` for:
   - `keywords`, `competitors`, `query_templates`
   - `competitor_mentions`, `citations`
   - `top_query_categories`

3. **FULLTEXT Index**: Requires MyISAM or InnoDB (MySQL 5.6+):
   - Use `$table->fullText('response_text')`

4. **ENUM Columns**: Define all allowed values:
   - `tracking_frequency`: daily, weekly, monthly
   - `brand_sentiment`: positive, neutral, negative, mixed
   - `mention_trend`: up, down, stable
   - `alert_type`: mention_spike, mention_drop, competitor_gain, sentiment_change

5. **Foreign Keys**: Use cascade delete for:
   - All project_id foreign keys
   - user_id foreign key in projects

6. **Performance Indexes**: Create composite indexes for common queries:
   - `(user_id, is_active)` on projects
   - `(project_id, platform)` on results
   - `(project_id, is_read)` on alerts

---

## Comments

### PreTester Test Design (2026-03-07)

**Test Approach:**
- Direct database assertions using Laravel's Schema and DB facades
- No mocks needed - migrations tested against actual database
- Foreign key constraints verified with cascade delete tests
- JSON storage validated with complex nested structures
- FULLTEXT index tested with actual search queries

**Acceptance Criteria Mapping:**
- All AC items covered by specific test IDs
- Each column has at least 2 tests (existence + type/nullability)
- Indexes verified with information_schema queries
- Rollback tested for all 4 migrations

**Edge Cases Identified:**
- NULL values in NOT NULL columns
- Invalid ENUM values
- Invalid JSON format
- Duplicate entries for UNIQUE constraint
- Cascade delete behavior
- FULLTEXT search functionality
- Large data handling (1MB+ JSON/text)

---

*Created by PreTester on 2026-03-07*
