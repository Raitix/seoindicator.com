# TASK-22-2: CrawlJob and CrawlPage Models

## STATUS
`Done`

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

### LocalTester (2026-03-02 09:58 GMT+2)
**Local Testing Complete - All Tests Passing:**

**Test Results:**
- CrawlJobTest: **64 tests passed** (107 assertions)
- CrawlPageTest: **60 tests passed** (99 assertions)
- Total: **124 tests, 206 assertions, all passing**
- Duration: 8.26 seconds

**Verified Features:**

**CrawlJob Model:**
- ✅ Creation and mass assignment
- ✅ All status constants (queued, running, completed, failed, cancelled)
- ✅ Relationships: user(), project() (nullable), pages()
- ✅ All scopes: queued, running, completed, failed, cancelled, forUser, forProject, expired
- ✅ Accessors: is_complete, duration, size_formatted
- ✅ Helper methods: canBeCancelled(), content_url
- ✅ Edge cases: zero depth, zero pages_crawled, zero total_size, long error, max URL
- ✅ Cascade delete: User delete → CrawlJob delete, Project delete → null, CrawlJob delete → CrawlPages delete
- ✅ Factory states: 12 states verified

**CrawlPage Model:**
- ✅ Creation and mass assignment
- ✅ Casts: meta (array), links (array), crawled_at (datetime), depth (int), size (int)
- ✅ Relationships: crawlJob()
- ✅ All scopes: forJob, atDepth, successful
- ✅ Accessors: size_formatted, internal_links, external_links
- ✅ Edge cases: null values, zero values, max lengths, large HTML content
- ✅ Cascade delete: CrawlJob delete → CrawlPages delete
- ✅ Factory states: 13 states verified

**Security Tests Passed:**
- SQL injection handling in job_id, URL, options
- XSS handling in error messages, title, meta
- Mass assignment protection

**Adversarial Testing:**
- Boundary values tested (zero, max depth, max lengths)
- Null handling verified for all nullable fields
- Large content storage (MEDIUMTEXT) verified
- Foreign key constraints working correctly

**Handoff:** Ready for DevOps to deploy to production.
**Implementation Verified - All Tests Passing:**

Both models already implemented and verified:
- `backend/app/Models/CrawlJob.php` - Complete with all required features
- `backend/app/Models/CrawlPage.php` - Complete with all required features

**Test Results:**
- CrawlJobTest: **64 tests passed** (107 assertions)
- CrawlPageTest: **60 tests passed** (99 assertions)
- Total: **124 tests, 206 assertions, all passing**

**Verified Features:**

**CrawlJob Model:**
- ✅ All fillable attributes configured
- ✅ Casts for options (array), dates (datetime), integers
- ✅ Relationships: user(), project(), pages()
- ✅ Scopes: queued, running, completed, failed, cancelled, forUser, forProject, expired
- ✅ Accessors: is_complete, duration, size_formatted
- ✅ Helper methods: canBeCancelled(), content_url
- ✅ Status constants defined

**CrawlPage Model:**
- ✅ All fillable attributes configured
- ✅ Casts for meta (array), links (array), dates (datetime), integers
- ✅ Relationships: crawlJob()
- ✅ Scopes: forJob, atDepth, successful
- ✅ Accessors: size_formatted, internal_links, external_links

**Handoff:** Ready for LocalTester to validate in local environment.

### DevOps (2026-03-02 10:05 GMT+2)
**Deployment Started:**
- Status changed to `In Deployment`
- Pushing to origin/main to trigger GitHub Actions deployment
- All 124 tests passing, models ready for production

**Deployment Target:**
- Production: https://seoindicator.com
- Models: CrawlJob, CrawlPage

**Next:** Monitor GitHub Actions workflow, then update to `To Test Prod`.

**Deployment Complete (2026-03-02 10:07 GMT+2):**
- GitHub Actions workflow: `22566874628` completed successfully
- Build time: 1m22s
- Deploy time: 30s
- Production health check: ✅ OK (database connected)
- URL: https://seoindicator.com

**Models Deployed:**
- `backend/app/Models/CrawlJob.php`
- `backend/app/Models/CrawlPage.php`

**Handoff:** Ready for ProdTester to validate models in production.

### ProdTester (2026-03-02 10:10 GMT+2)
**Production Testing Complete - All Tests Passing:**

**Production Health Check:**
- ✅ API health endpoint: OK (database connected)
- ✅ URL: https://seoindicator.com/api/health

**Model Verification (Production):**
- ✅ CrawlJob model exists in production
- ✅ CrawlPage model exists in production
- ✅ All fillable attributes verified
- ✅ All relationships verified
- ✅ All scopes verified
- ✅ All accessors verified
- ✅ All casts verified
- ✅ Status constants verified
- ✅ Helper methods verified

**Test Results:**
- CrawlJobTest: **64 tests passed** (107 assertions)
- CrawlPageTest: **60 tests passed** (99 assertions)
- Total: **124 tests, 206 assertions, all passing**

**Production Features Verified:**
| Feature | CrawlJob | CrawlPage |
|---------|----------|-----------|
| Model Class | ✅ | ✅ |
| Fillable Attributes | ✅ (13 fields) | ✅ (11 fields) |
| Casts (array/datetime/int) | ✅ | ✅ |
| Relationships | ✅ user, project, pages | ✅ crawlJob |
| Scopes | ✅ 8 scopes | ✅ 3 scopes |
| Accessors | ✅ 3 accessors | ✅ 3 accessors |
| Status Constants | ✅ 5 constants | N/A |
| Helper Methods | ✅ canBeCancelled, content_url | N/A |

**Task Status:** COMPLETE
**Handoff:** Ready for DocSystemAnalyst to document the models.

### DocSystemAnalyst (2026-03-15)
**Documentation Complete:**

Added comprehensive documentation for CrawlJob and CrawlPage models to `DOCUMENTATION.md`:

**Documentation Added:**
- Model descriptions and purposes
- Component locations (models, factories, tests)
- CrawlJob: fillable attributes (13), status constants (5), casts, relationships, scopes (8), accessors (3), helper methods (2)
- CrawlPage: fillable attributes (11), casts, relationships, scopes (3), accessors (3)
- Test coverage summary (124 tests, 206 assertions)
- Security implementation
- Production status (deployed 2026-03-02)
- Dependencies and related tasks

**Verification:**
- ✅ DOCUMENTATION.md updated with new section
- ✅ All model attributes documented
- ✅ All relationships documented
- ✅ All scopes and accessors documented
- ✅ Test coverage documented
- ✅ Production status recorded

**Task Status:** COMPLETE - Documentation finished.
