# TEST-22-1: Database Migrations for Crawler

## Task Reference
- **Task:** TASK-22-1: Database Migrations for Crawler
- **Specification:** SPEC-022: Headless Crawler Service
- **Feature:** FEAT-022: Headless Crawler Service

---

## Overview
Tests for database migrations creating `crawl_jobs` and `crawl_pages` tables to support the headless crawler service.

---

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `tests/Feature/Migrations/CreateCrawlerTablesTest.php` | Feature | Migration structure tests |
| `tests/Unit/Migrations/CreateCrawlerTablesTest.php` | Unit | Column type validation tests |

---

## Test Scenarios

### CRAWL_JOBS TABLE

#### Table Structure Tests (CJ-TS)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CJ-TS-01 | Table exists | N/A | `crawl_jobs` table exists |
| CJ-TS-02 | All columns exist | N/A | All 15 required columns present |
| CJ-TS-03 | Primary key | N/A | `id` is BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| CJ-TS-04 | User foreign key | N/A | FK to `users.id` with CASCADE delete |
| CJ-TS-05 | Project foreign key | N/A | FK to `projects.id` with SET NULL on delete |
| CJ-TS-06 | Job ID unique | Duplicate `job_id` | QueryException thrown |
| CJ-TS-07 | URL column length | 2083 char URL | Stored successfully |
| CJ-TS-08 | URL max length | 2084 char URL | Truncated or error (DB dependent) |

#### Index Tests (CJ-IDX)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CJ-IDX-01 | user_id index exists | N/A | Index on `user_id` |
| CJ-IDX-02 | status index exists | N/A | Index on `status` |
| CJ-IDX-03 | job_id index exists | N/A | Index on `job_id` |
| CJ-IDX-04 | expires_at index exists | N/A | Index on `expires_at` |

#### Enum Tests (CJ-ENUM)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CJ-ENUM-01 | Valid status values | 'queued', 'running', 'completed', 'failed', 'cancelled' | All accepted |
| CJ-ENUM-02 | Invalid status value | 'invalid_status' | QueryException thrown |
| CJ-ENUM-03 | Default status | No status provided | 'queued' |

#### Column Constraint Tests (CJ-CC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CJ-CC-01 | user_id NOT NULL | NULL user_id | QueryException thrown |
| CJ-CC-02 | project_id NULL | NULL project_id | Accepted (nullable) |
| CJ-CC-03 | job_id NOT NULL | NULL job_id | QueryException thrown |
| CJ-CC-04 | url NOT NULL | NULL url | QueryException thrown |
| CJ-CC-05 | depth default | No depth provided | 0 |
| CJ-CC-06 | pages_crawled default | No value | 0 |
| CJ-CC-07 | total_size default | No value | 0 |
| CJ-CC-08 | options NULL | NULL options | Accepted (nullable JSON) |
| CJ-CC-09 | error NULL | NULL error | Accepted (nullable TEXT) |
| CJ-CC-10 | started_at NULL | NULL started_at | Accepted (nullable TIMESTAMP) |
| CJ-CC-11 | completed_at NULL | NULL completed_at | Accepted (nullable TIMESTAMP) |
| CJ-CC-12 | expires_at NOT NULL | NULL expires_at | QueryException thrown |

#### Foreign Key Constraint Tests (CJ-FK)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CJ-FK-01 | Valid user_id | Existing user | Job created |
| CJ-FK-02 | Invalid user_id | Non-existent user | QueryException thrown |
| CJ-FK-03 | Valid project_id | Existing project | Job created |
| CJ-FK-04 | Invalid project_id | Non-existent project | QueryException thrown |
| CJ-FK-05 | User delete cascade | Delete user | Job cascade deleted |
| CJ-FK-06 | Project delete set null | Delete project | Job's project_id = NULL |
| CJ-FK-07 | NULL project_id allowed | No project | Job created successfully |

#### JSON Column Tests (CJ-JSON)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CJ-JSON-01 | Store JSON options | `{"waitForSelector": ".content", "waitTime": 2000}` | Stored correctly |
| CJ-JSON-02 | Retrieve JSON options | N/A | Parsed as array |
| CJ-JSON-03 | NULL JSON options | NULL | Returns NULL or empty array |
| CJ-JSON-04 | Complex nested JSON | Deep nested object | Stored and retrieved correctly |

---

### CRAWL_PAGES TABLE

#### Table Structure Tests (CP-TS)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-TS-01 | Table exists | N/A | `crawl_pages` table exists |
| CP-TS-02 | All columns exist | N/A | All 13 required columns present |
| CP-TS-03 | Primary key | N/A | `id` is BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| CP-TS-04 | Crawl job foreign key | N/A | FK to `crawl_jobs.id` with CASCADE delete |
| CP-TS-05 | MEDIUMTEXT for HTML | 16MB HTML content | Stored successfully |
| CP-TS-06 | MEDIUMTEXT for text | 16MB text content | Stored successfully |

#### Index Tests (CP-IDX)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-IDX-01 | crawl_job_id index exists | N/A | Index on `crawl_job_id` |
| CP-IDX-02 | Composite index exists | N/A | Index on `(crawl_job_id, depth)` |

#### Column Constraint Tests (CP-CC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-CC-01 | crawl_job_id NOT NULL | NULL crawl_job_id | QueryException thrown |
| CP-CC-02 | url NOT NULL | NULL url | QueryException thrown |
| CP-CC-03 | depth NOT NULL | NULL depth | QueryException thrown |
| CP-CC-04 | title NULL | NULL title | Accepted (nullable) |
| CP-CC-05 | rendered_html NULL | NULL rendered_html | Accepted (nullable) |
| CP-CC-06 | structured_text NULL | NULL structured_text | Accepted (nullable) |
| CP-CC-07 | meta NULL | NULL meta | Accepted (nullable JSON) |
| CP-CC-08 | links NULL | NULL links | Accepted (nullable JSON) |
| CP-CC-09 | size default | No value | 0 |
| CP-CC-10 | crawled_at NULL | NULL crawled_at | Accepted (nullable TIMESTAMP) |
| CP-CC-11 | error NULL | NULL error | Accepted (nullable TEXT) |

#### Foreign Key Constraint Tests (CP-FK)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-FK-01 | Valid crawl_job_id | Existing job | Page created |
| CP-FK-02 | Invalid crawl_job_id | Non-existent job | QueryException thrown |
| CP-FK-03 | Job delete cascade | Delete job | All pages cascade deleted |

#### JSON Column Tests (CP-JSON)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-JSON-01 | Store meta JSON | `{"description": "Page desc", "robots": "index"}` | Stored correctly |
| CP-JSON-02 | Store links JSON | Array of link objects | Stored correctly |
| CP-JSON-03 | Retrieve meta JSON | N/A | Parsed as array |
| CP-JSON-04 | Retrieve links JSON | N/A | Parsed as array |

#### Large Content Tests (CP-LC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-LC-01 | Store 1MB HTML | 1MB rendered HTML | Stored successfully |
| CP-LC-02 | Store 8MB HTML | 8MB rendered HTML | Stored successfully |
| CP-LC-03 | Store 16MB HTML | 16MB rendered HTML | Stored successfully (MEDIUMTEXT limit) |
| CP-LC-04 | Store large text | 10MB structured text | Stored successfully |

---

### RELATIONSHIP TESTS (REL)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| REL-01 | Job has many pages | Create 5 pages for job | `$job->pages->count() === 5` |
| REL-02 | Page belongs to job | Access page->job | Returns parent job |
| REL-03 | Job belongs to user | Access job->user | Returns owner user |
| REL-04 | Job belongs to project | Access job->project | Returns project (or NULL) |
| REL-05 | User has many jobs | Create 3 jobs for user | `$user->crawlJobs->count() >= 3` |
| REL-06 | Project has many jobs | Create 2 jobs for project | `$project->crawlJobs->count() >= 2` |

---

### ROLLBACK TESTS (RB)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RB-01 | Migration down drops pages | Rollback | `crawl_pages` table removed |
| RB-02 | Migration down drops jobs | Rollback | `crawl_jobs` table removed |
| RB-03 | Re-run migration | Migrate again | Tables recreated correctly |

---

### SECURITY TESTS (SEC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SEC-01 | SQL injection in job_id | `'; DROP TABLE crawl_jobs; --` | Stored safely, table intact |
| SEC-02 | SQL injection in url | Malicious URL string | Stored safely |
| SEC-03 | XSS in error message | `<script>alert('xss')</script>` | Stored as-is (escaped on output) |
| SEC-04 | SQL injection in JSON options | Malicious JSON | Stored safely |
| SEC-05 | SQL injection in title | Malicious title | Stored safely |
| SEC-06 | Large URL handling | URL at max length (2083) | Stored successfully |

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Table Structure | 100% | 14 tests |
| Indexes | 100% | 6 tests |
| Foreign Keys | 100% | 10 tests |
| Enum Validation | 100% | 3 tests |
| Column Constraints | 100% | 23 tests |
| JSON Columns | 100% | 8 tests |
| Large Content | 100% | 4 tests |
| Relationships | 100% | 6 tests |
| Rollback | 100% | 3 tests |
| Security | 100% | 6 tests |
| **Total** | **100%** | **83+ tests** |

---

## Test Execution

```bash
# Run all migration tests
php artisan test --filter=CreateCrawlerTablesTest

# Run specific test method
php artisan test --filter=test_crawl_jobs_table_exists

# Run with coverage
php artisan test --filter=CreateCrawlerTablesTest --coverage
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| Users table | ✅ Exists | Required for FK |
| Projects table | ✅ Exists | Required for FK |
| Laravel migrations | ✅ Installed | Part of framework |

---

## Notes for Developer

1. **Migration File:** Create migration using `php artisan make:migration create_crawler_tables`
2. **Table Order:** Create `crawl_jobs` first, then `crawl_pages` (FK dependency)
3. **Rollback Order:** Drop `crawl_pages` first, then `crawl_jobs` (reverse order)
4. **MEDIUMTEXT:** Use `$table->mediumText('rendered_html')->nullable()` for 16MB support
5. **ENUM:** Use `$table->enum('status', [...])->default('queued')`
6. **Job ID Format:** VARCHAR(64) for `crawl-{uuid}` format
7. **URL Max Length:** VARCHAR(2083) per RFC 3986

---

## Comments

### PreTester (2026-03-01 23:58 GMT+2)
**Test Design Complete:**
- Created test documentation with 83+ test scenarios
- Created feature test file at `tests/Feature/Migrations/CreateCrawlerTablesTest.php`
- Tests follow existing project patterns (see `CreateAnalysisJobsTablesTest.php`)
- Security tests cover SQL injection and XSS scenarios
- Large content tests verify MEDIUMTEXT storage capacity

**Key Design Decisions:**
- FK cascade: user delete → job cascade (user's data cleanup)
- FK set null: project delete → job.project_id = NULL (preserve orphaned jobs)
- MEDIUMTEXT (16MB) for HTML/text storage (matches SPEC-022 requirement)
- Composite index on (crawl_job_id, depth) for efficient depth-based queries

**Handoff:** Ready for Developer to create migration file. Tests will fail until migration runs (expected TDD behavior).
