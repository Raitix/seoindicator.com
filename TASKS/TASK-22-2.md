# TASK-22-2: CrawlJob and CrawlPage Models

## STATUS
`To Test Locally`

## PRIORITY
`High`

## RISK
`Low`

## Related Specification
SPEC-022: Headless Crawler Service (JS-Rendered HTML)

## Related Feature
FEAT-022: Headless Crawler Service (JS-Rendered HTML)

---

## Description
Create Eloquent models for CrawlJob and CrawlPage with relationships, casts, and accessors.

## Acceptance Criteria

### CrawlJob Model
- [x] Model created at `app/Models/CrawlJob.php`
- [x] Fillable attributes: `user_id`, `project_id`, `job_id`, `url`, `status`, `depth`, `options`, `pages_crawled`, `total_size`, `error`, `started_at`, `completed_at`, `expires_at`
- [x] Casts: `options` => 'array', `started_at` => 'datetime', `completed_at` => 'datetime', `expires_at` => 'datetime'
- [x] BelongsTo relationship to User
- [x] BelongsTo relationship to Project (nullable)
- [x] HasMany relationship to CrawlPages
- [x] Status enum constants or use native PHP enum

### CrawlJob Scopes
- [x] `scopeQueued($query)` - where status = 'queued'
- [x] `scopeRunning($query)` - where status = 'running'
- [x] `scopeCompleted($query)` - where status = 'completed'
- [x] `scopeFailed($query)` - where status = 'failed'
- [x] `scopeForUser($query, $userId)` - where user_id = $userId
- [x] `scopeForProject($query, $projectId)` - where project_id = $projectId
- [x] `scopeExpired($query)` - where expires_at < now()

### CrawlJob Accessors
- [x] `getIsCompleteAttribute()` - status in ['completed', 'failed', 'cancelled']
- [x] `getDurationAttribute()` - difference between started_at and completed_at in seconds
- [x] `getSizeFormattedAttribute()` - human-readable size (KB, MB)

### CrawlPage Model
- [x] Model created at `app/Models/CrawlPage.php`
- [x] Fillable attributes: `crawl_job_id`, `url`, `depth`, `title`, `rendered_html`, `structured_text`, `meta`, `links`, `size`, `crawled_at`, `error`
- [x] Casts: `meta` => 'array', `links` => 'array', `crawled_at` => 'datetime'
- [x] BelongsTo relationship to CrawlJob

### CrawlPage Scopes
- [x] `scopeForJob($query, $jobId)` - where crawl_job_id = $jobId
- [x] `scopeAtDepth($query, $depth)` - where depth = $depth
- [x] `scopeSuccessful($query)` - where error is null

### CrawlPage Accessors
- [x] `getSizeFormattedAttribute()` - human-readable size
- [x] `getInternalLinksAttribute()` - filter links where internal = true
- [x] `getExternalLinksAttribute()` - filter links where internal = false

### Tests
- [x] Unit tests for model relationships
- [x] Unit tests for scopes
- [x] Unit tests for accessors

## Dependencies
- TASK-22-1 (Database migrations)

## Effort Estimate
`Medium` (2-3 hours)

## Notes
- Use Laravel's native enum casting if PHP 8.1+
- Consider using spatie/enum package for status enum

## Comments
_Leave notes for the next agent here_

### PreTester (2026-03-01 23:42 GMT+2)
**Test Design Complete:**
- Created comprehensive unit tests for CrawlJob model (63 test methods)
- Created comprehensive unit tests for CrawlPage model (61 test methods)
- Total: 124 tests, 206 assertions, all passing
- Created factory files with multiple states for both models

**Test Files Created:**
- `tests/Unit/Models/CrawlJobTest.php` - 63 tests
- `tests/Unit/Models/CrawlPageTest.php` - 61 tests
- `database/factories/CrawlJobFactory.php` - 12 factory states
- `database/factories/CrawlPageFactory.php` - 13 factory states
- `TESTS/TASK-22-2.md` - Test documentation

**Test Coverage:**
- Creation and mass assignment
- Status constants
- Relationships (BelongsTo, HasMany)
- Scopes (queued, running, completed, failed, cancelled, forUser, forProject, expired, forJob, atDepth, successful)
- Accessors (is_complete, duration, size_formatted, internal_links, external_links)
- Helper methods (canBeCancelled, content_url)
- Edge cases (boundaries, null values, max lengths)
- Cascade delete (foreign key constraints)
- Factory states (verification)

**Handoff:** Ready for Developer to implement any missing model features if tests reveal gaps.

### ProjectManager (2026-03-01 23:25 GMT+2)
**Prioritization Assessment:**
- **Priority:** High (confirmed) - Foundation task for SPEC-022, models required by all subsequent tasks
- **Risk:** Low (confirmed) - Standard Eloquent model implementation, no external dependencies, easily reversible

**Dependency Status:**
| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-22-1 (Migrations) | Tables Ready ✅ | Migration tables exist and work; feature tests have bugs but migration is correct |

**Dependency Chain (SPEC-022 Phase 1):**
```
TASK-22-1 (Migrations) → TASK-22-2 (Models - THIS TASK) → TASK-22-3 (Service skeleton)
```
- TASK-22-1 migration tables are functional (confirmed by LocalTester)
- Feature test bugs are in test implementation, not migration
- Models can be developed immediately

**Execution Context:**
- Creates 2 models: `CrawlJob` and `CrawlPage`
- Models use standard Laravel patterns (fillable, casts, relationships, scopes, accessors)
- Uses native PHP 8.1+ enum for status (no external package needed)
- Memory-friendly: MEDIUMTEXT columns already support up to 16MB

**Key Model Features:**
- CrawlJob: 5 scopes (queued, running, completed, failed, expired), 3 accessors (isComplete, duration, sizeFormatted)
- CrawlPage: 3 scopes (forJob, atDepth, successful), 3 accessors (sizeFormatted, internalLinks, externalLinks)
- Relationships: User/Project (BelongsTo), CrawlPages (HasMany)

**Handoff:** Ready for PreTester to design model tests.
