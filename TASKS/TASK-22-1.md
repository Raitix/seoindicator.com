# TASK-22-1: Database Migrations for Crawler

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Medium`

## Related Specification
SPEC-022: Headless Crawler Service (JS-Rendered HTML)

## Related Feature
FEAT-022: Headless Crawler Service (JS-Rendered HTML)

---

## Description
Create database migrations for `crawl_jobs` and `crawl_pages` tables to support the headless crawler service.

## Acceptance Criteria

### crawl_jobs Table
- [ ] `id` - BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- [ ] `user_id` - BIGINT UNSIGNED NOT NULL with foreign key to users table
- [ ] `project_id` - BIGINT UNSIGNED NULL with foreign key to projects table (ON DELETE SET NULL)
- [ ] `job_id` - VARCHAR(64) NOT NULL UNIQUE (unique identifier for API access)
- [ ] `url` - VARCHAR(2083) NOT NULL (max URL length per RFC 3986)
- [ ] `status` - ENUM('queued', 'running', 'completed', 'failed', 'cancelled') DEFAULT 'queued'
- [ ] `depth` - TINYINT UNSIGNED DEFAULT 0
- [ ] `options` - JSON NULL (crawl configuration)
- [ ] `pages_crawled` - INT UNSIGNED DEFAULT 0
- [ ] `total_size` - BIGINT UNSIGNED DEFAULT 0
- [ ] `error` - TEXT NULL
- [ ] `started_at` - TIMESTAMP NULL
- [ ] `completed_at` - TIMESTAMP NULL
- [ ] `expires_at` - TIMESTAMP NOT NULL (for content cleanup)
- [ ] `created_at` and `updated_at` timestamps

### crawl_jobs Indexes
- [ ] Index on `user_id`
- [ ] Index on `status`
- [ ] Index on `job_id`
- [ ] Index on `expires_at`

### crawl_pages Table
- [ ] `id` - BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- [ ] `crawl_job_id` - BIGINT UNSIGNED NOT NULL with foreign key to crawl_jobs (ON DELETE CASCADE)
- [ ] `url` - VARCHAR(2083) NOT NULL
- [ ] `depth` - TINYINT UNSIGNED NOT NULL
- [ ] `title` - VARCHAR(500) NULL
- [ ] `rendered_html` - MEDIUMTEXT NULL (up to 16MB)
- [ ] `structured_text` - MEDIUMTEXT NULL
- [ ] `meta` - JSON NULL
- [ ] `links` - JSON NULL
- [ ] `size` - INT UNSIGNED DEFAULT 0
- [ ] `crawled_at` - TIMESTAMP NULL
- [ ] `error` - TEXT NULL

### crawl_pages Indexes
- [ ] Index on `crawl_job_id`
- [ ] Composite index on `(crawl_job_id, depth)`

### Migration Files
- [ ] Create migration file with both tables
- [ ] Migration follows Laravel naming convention
- [ ] Migration includes down() method for rollback

## Dependencies
- Users table (exists)
- Projects table (exists)

## Effort Estimate
`Medium` (2-3 hours)

## Notes
- MEDIUMTEXT supports up to 16MB which is sufficient for rendered HTML
- Job expiry default: 7 days for content cleanup
- Consider adding soft deletes for job history retention

## Comments
_Leave notes for the next agent here_

### ProjectManager (2026-03-01 22:34 GMT+2)
**Prioritization Assessment:**
- **Priority:** High (confirmed) - Foundation task for SPEC-022, blocks all other crawler tasks
- **Risk:** Medium (confirmed) - Standard database migration, non-destructive (add tables only), easily reversible via rollback

**Dependency Check:**
| Dependency | Status |
|------------|--------|
| Users table | Exists ✅ |
| Projects table | Exists ✅ |

**Dependency Chain (SPEC-022 Phase 1):**
- TASK-22-1 (this task) → TASK-22-2 (Models) → TASK-22-3 (Service skeleton)
- All 20 other SPEC-022 tasks depend on this migration

**Execution Order:**
1. This task (migrations) - FIRST
2. TASK-22-2 (models) - after migrations ready
3. TASK-22-3 (service skeleton) - after models ready

**No blockers detected.** Task proceeds to PreTester for test design.

### PreTester (2026-03-01 23:58 GMT+2)
**Test Design Complete:**
- Created test documentation: `TESTS/TEST-22-1.md` (83+ test scenarios)
- Created feature test file: `tests/Feature/Migrations/CreateCrawlerTablesTest.php`
- Tests follow existing project patterns (see `CreateAnalysisJobsTablesTest.php`)

**Test Coverage:**
| Category | Tests |
|----------|-------|
| Table Structure | 14 tests |
| Indexes | 6 tests |
| Foreign Keys | 10 tests |
| Enum Validation | 3 tests |
| Column Constraints | 23 tests |
| JSON Columns | 8 tests |
| Large Content | 4 tests |
| Relationships | 6 tests |
| Security | 6 tests |
| **Total** | **80+ tests** |

**Key Design Decisions:**
- FK cascade: `user_id` → CASCADE delete (user's data cleanup)
- FK set null: `project_id` → SET NULL on delete (preserve orphaned jobs)
- MEDIUMTEXT (16MB) for `rendered_html` and `structured_text`
- Composite index on `(crawl_job_id, depth)` for efficient depth-based queries
- Job ID format: `crawl-{uuid}` stored in VARCHAR(64)

**Notes for Developer:**
1. Tests will fail until migration runs (expected TDD behavior)
2. Create migration: `php artisan make:migration create_crawler_tables`
3. Table order: `crawl_jobs` first, then `crawl_pages` (FK dependency)
4. Rollback order: Drop `crawl_pages` first, then `crawl_jobs`
5. All FK columns tested for constraint enforcement

**Handoff:** Ready for Developer to create migration file.
