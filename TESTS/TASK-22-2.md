# TEST-22-2: CrawlJob and CrawlPage Models Test Documentation

## Task Reference
- **Task:** TASK-22-2
- **Specification:** SPEC-022
- **Feature:** FEAT-022

## Overview

This document describes the unit tests for the `CrawlJob` and `CrawlPage` Eloquent models used in the Headless Crawler Service. Tests cover model relationships, scopes, accessors, mass assignment protection, and edge cases.

---

## Test Files

| File | Purpose |
|------|---------|
| `tests/Unit/Models/CrawlJobTest.php` | CrawlJob model tests |
| `tests/Unit/Models/CrawlPageTest.php` | CrawlPage model tests |
| `database/factories/CrawlJobFactory.php` | CrawlJob factory |
| `database/factories/CrawlPageFactory.php` | CrawlPage factory |

---

## CrawlJob Test Scenarios

### Creation and Mass Assignment

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CJ-01 | Create with valid data | All fillable fields | Record persisted in database |
| CJ-02 | Mass assignment fillable | All fillable fields via create() | All fields correctly assigned |
| CJ-03 | Options cast to array | JSON options | Array returned |
| CJ-04 | Null options | options = null | Null returned |
| CJ-05 | Timestamp casts | datetime fields | Carbon instances |
| CJ-06 | Depth cast to integer | depth = 2 | Integer 2 |
| CJ-07 | pages_crawled cast | pages_crawled = 10 | Integer 10 |
| CJ-08 | total_size cast | total_size = 1048576 | Integer 1048576 |

### Status Constants

| ID | Scenario | Expected Value |
|----|----------|----------------|
| CJ-09 | STATUS_QUEUED | 'queued' |
| CJ-10 | STATUS_RUNNING | 'running' |
| CJ-11 | STATUS_COMPLETED | 'completed' |
| CJ-12 | STATUS_FAILED | 'failed' |
| CJ-13 | STATUS_CANCELLED | 'cancelled' |

### Relationships

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| CJ-14 | BelongsTo User | User model instance |
| CJ-15 | BelongsTo Project | Project model instance |
| CJ-16 | Nullable project | null allowed |
| CJ-17 | HasMany CrawlPages | Collection of CrawlPage |

### Scopes

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CJ-18 | scopeQueued | Mixed statuses | Only queued jobs |
| CJ-19 | scopeRunning | Mixed statuses | Only running jobs |
| CJ-20 | scopeCompleted | Mixed statuses | Only completed jobs |
| CJ-21 | scopeFailed | Mixed statuses | Only failed jobs |
| CJ-22 | scopeCancelled | Mixed statuses | Only cancelled jobs |
| CJ-23 | scopeForUser | user_id filter | Jobs for that user |
| CJ-24 | scopeForProject | project_id filter | Jobs for that project |
| CJ-25 | scopeExpired | expires_at < now | Expired jobs |
| CJ-26 | Scope chaining | Multiple scopes | Combined filter |

### Accessors

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CJ-27 | is_complete (completed) | status = completed | true |
| CJ-28 | is_complete (failed) | status = failed | true |
| CJ-29 | is_complete (cancelled) | status = cancelled | true |
| CJ-30 | is_complete (queued) | status = queued | false |
| CJ-31 | is_complete (running) | status = running | false |
| CJ-32 | duration calculation | started_at, completed_at | Seconds difference |
| CJ-33 | duration null (no started_at) | started_at = null | null |
| CJ-34 | duration null (no completed_at) | completed_at = null | null |
| CJ-35 | sizeFormatted (bytes) | total_size = 512 | "512.0 B" |
| CJ-36 | sizeFormatted (KB) | total_size = 1024 | "1.0 KB" |
| CJ-37 | sizeFormatted (MB) | total_size = 1048576 | "1.0 MB" |

### Helper Methods

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CJ-38 | canBeCancelled (queued) | status = queued | true |
| CJ-39 | canBeCancelled (running) | status = running | true |
| CJ-40 | canBeCancelled (completed) | status = completed | false |
| CJ-41 | content_url accessor | job_id = 'crawl-abc123' | "/api/v1/crawler/jobs/crawl-abc123/content" |

### Edge Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CJ-42 | Unique job_id constraint | Duplicate job_id | QueryException |
| CJ-43 | Zero depth | depth = 0 | Allowed |
| CJ-44 | Maximum depth | depth = 2 | Allowed |
| CJ-45 | Empty options array | options = [] | Empty array |
| CJ-46 | Zero pages_crawled | pages_crawled = 0 | 0 |
| CJ-47 | Zero total_size | total_size = 0 | "0.0 B" |
| CJ-48 | Long error message | 1500+ chars | Stored correctly |
| CJ-49 | URL max length | 2083 chars | Stored correctly |

### Cascade Delete

| ID | Scenario | Action | Expected Result |
|----|----------|--------|-----------------|
| CJ-50 | User deleted | Delete user | CrawlJob deleted |
| CJ-51 | Project deleted | Delete project | project_id set to null |
| CJ-52 | CrawlJob deleted | Delete job | All CrawlPages deleted |

---

## CrawlPage Test Scenarios

### Creation and Mass Assignment

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-01 | Create with valid data | All fillable fields | Record persisted |
| CP-02 | Mass assignment fillable | All fillable fields via create() | All fields assigned |
| CP-03 | Meta cast to array | JSON meta | Array returned |
| CP-04 | Links cast to array | JSON links | Array returned |
| CP-05 | Null meta | meta = null | Null returned |
| CP-06 | Null links | links = null | Null returned |
| CP-07 | crawled_at cast to datetime | timestamp | Carbon instance |
| CP-08 | Depth cast to integer | depth = 2 | Integer 2 |
| CP-09 | Size cast to integer | size = 102400 | Integer 102400 |

### Relationships

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| CP-10 | BelongsTo CrawlJob | CrawlJob model instance |
| CP-11 | Relationship type | BelongsTo class |

### Scopes

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-12 | scopeForJob | crawl_job_id filter | Pages for that job |
| CP-13 | scopeAtDepth | depth filter | Pages at that depth |
| CP-14 | scopeSuccessful | Mixed pages | Only pages without errors |
| CP-15 | Scope chaining | Multiple scopes | Combined filter |

### Accessors

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-16 | sizeFormatted (bytes) | size = 512 | "512.0 B" |
| CP-17 | sizeFormatted (KB) | size = 1024 | "1.0 KB" |
| CP-18 | sizeFormatted (MB) | size = 1048576 | "1.0 MB" |
| CP-19 | internal_links | Mixed links | Only internal links |
| CP-20 | external_links | Mixed links | Only external links |
| CP-21 | internal_links (null links) | links = null | Empty array |
| CP-22 | external_links (null links) | links = null | Empty array |
| CP-23 | Links missing internal key | No 'internal' key | Treated as external |

### Edge Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-24 | Zero depth | depth = 0 | Allowed |
| CP-25 | Maximum depth | depth = 2 | Allowed |
| CP-26 | Zero size | size = 0 | "0.0 B" |
| CP-27 | Null title | title = null | Null |
| CP-28 | Null rendered_html | rendered_html = null | Null |
| CP-29 | Null structured_text | structured_text = null | Null |
| CP-30 | Null crawled_at | crawled_at = null | Null |
| CP-31 | Long title (500 chars) | 500 char string | Stored correctly |
| CP-32 | URL max length | 2083 chars | Stored correctly |
| CP-33 | Large HTML (MEDIUMTEXT) | ~1MB content | Stored correctly |
| CP-34 | Empty meta array | meta = [] | Empty array |
| CP-35 | Empty links array | links = [] | Empty array |
| CP-36 | Error message | error string | Stored correctly |
| CP-37 | Page with error | error set | No rendered_html/structured_text |

### Cascade Delete

| ID | Scenario | Action | Expected Result |
|----|----------|--------|-----------------|
| CP-38 | CrawlJob deleted | Delete job | CrawlPage deleted |
| CP-39 | Multiple pages deleted | Delete job | All pages deleted |

---

## Factory States

### CrawlJobFactory

| State | Description |
|-------|-------------|
| `queued()` | Status = 'queued' |
| `running()` | Status = 'running', started_at set |
| `completed()` | Status = 'completed', started_at/completed_at set, pages_crawled > 0 |
| `failed()` | Status = 'failed', error message set |
| `cancelled()` | Status = 'cancelled' |
| `expired()` | expires_at in the past |
| `withoutProject()` | project_id = null |
| `forUser($user)` | Set specific user_id |
| `forProject($project)` | Set specific project_id |
| `withDepth($depth)` | Set specific depth |
| `withUrl($url)` | Set specific URL |
| `withJobId($jobId)` | Set specific job_id |

### CrawlPageFactory

| State | Description |
|-------|-------------|
| `successful()` | No error, crawled_at set |
| `withError($msg)` | Error message set |
| `forJob($job)` | Set specific crawl_job_id |
| `atDepth($depth)` | Set specific depth |
| `withUrl($url)` | Set specific URL |
| `withLinks($links)` | Set specific links array |
| `withMeta($meta)` | Set specific meta array |
| `withoutLinks()` | links = null |
| `withOnlyInternalLinks()` | All links have internal = true |
| `withOnlyExternalLinks()` | All links have internal = false |
| `withMixedLinks()` | Mix of internal and external links |
| `withSize($bytes)` | Set specific size |

---

## Coverage Targets

| Model | Target Coverage |
|-------|-----------------|
| CrawlJob | 95%+ |
| CrawlPage | 95%+ |

---

## Running Tests

```bash
# Run all CrawlJob tests
cd backend
php artisan test --filter CrawlJobTest

# Run all CrawlPage tests
php artisan test --filter CrawlPageTest

# Run with coverage
php artisan test --filter "CrawlJobTest|CrawlPageTest" --coverage
```

---

## Dependencies

- TASK-22-1: Database migrations (crawl_jobs, crawl_pages tables) ✅
- User model and factory
- Project model and factory

---

## Notes for Developer

1. **Models already implemented** - Tests verify existing implementation is correct
2. **Use factories** - All tests use factory states for consistent data
3. **Cascade behavior** - Verify foreign key constraints work correctly
4. **Size formatting** - Tests verify human-readable size conversion
5. **Link filtering** - Tests verify internal/external link separation
6. **Status transitions** - Ensure only valid statuses are used

---

## Comments

### PreTester (2026-03-01 23:35 GMT+2)
**Test Design Complete:**
- Created comprehensive unit tests for CrawlJob model (52 test methods)
- Created comprehensive unit tests for CrawlPage model (39 test methods)
- Created factory files with multiple states for both models
- Tests cover: relationships, scopes, accessors, edge cases, cascade delete
- All acceptance criteria from task have corresponding tests

**Factory States Created:**
- CrawlJobFactory: 12 states (queued, running, completed, failed, cancelled, expired, withoutProject, forUser, forProject, withDepth, withUrl, withJobId)
- CrawlPageFactory: 13 states (successful, withError, forJob, atDepth, withUrl, withLinks, withMeta, withoutLinks, withOnlyInternalLinks, withOnlyExternalLinks, withMixedLinks, withSize)

**Test Categories:**
- Creation and Mass Assignment
- Status Constants
- Relationships (BelongsTo, HasMany)
- Scopes (queued, running, completed, failed, cancelled, forUser, forProject, expired, forJob, atDepth, successful)
- Accessors (is_complete, duration, size_formatted, internal_links, external_links)
- Helper Methods (canBeCancelled, content_url)
- Edge Cases (boundaries, null values, max lengths)
- Cascade Delete (foreign key constraints)
- Factory States (verification)

**Handoff:** Ready for Developer to verify tests pass.
