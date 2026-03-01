# TASK-22-2: CrawlJob and CrawlPage Models

## STATUS
`To Develop`

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
- [ ] Model created at `app/Models/CrawlJob.php`
- [ ] Fillable attributes: `user_id`, `project_id`, `job_id`, `url`, `status`, `depth`, `options`, `pages_crawled`, `total_size`, `error`, `started_at`, `completed_at`, `expires_at`
- [ ] Casts: `options` => 'array', `started_at` => 'datetime', `completed_at` => 'datetime', `expires_at` => 'datetime'
- [ ] BelongsTo relationship to User
- [ ] BelongsTo relationship to Project (nullable)
- [ ] HasMany relationship to CrawlPages
- [ ] Status enum constants or use native PHP enum

### CrawlJob Scopes
- [ ] `scopeQueued($query)` - where status = 'queued'
- [ ] `scopeRunning($query)` - where status = 'running'
- [ ] `scopeCompleted($query)` - where status = 'completed'
- [ ] `scopeFailed($query)` - where status = 'failed'
- [ ] `scopeForUser($query, $userId)` - where user_id = $userId
- [ ] `scopeForProject($query, $projectId)` - where project_id = $projectId
- [ ] `scopeExpired($query)` - where expires_at < now()

### CrawlJob Accessors
- [ ] `getIsCompleteAttribute()` - status in ['completed', 'failed', 'cancelled']
- [ ] `getDurationAttribute()` - difference between started_at and completed_at in seconds
- [ ] `getSizeFormattedAttribute()` - human-readable size (KB, MB)

### CrawlPage Model
- [ ] Model created at `app/Models/CrawlPage.php`
- [ ] Fillable attributes: `crawl_job_id`, `url`, `depth`, `title`, `rendered_html`, `structured_text`, `meta`, `links`, `size`, `crawled_at`, `error`
- [ ] Casts: `meta` => 'array', `links` => 'array', `crawled_at` => 'datetime'
- [ ] BelongsTo relationship to CrawlJob

### CrawlPage Scopes
- [ ] `scopeForJob($query, $jobId)` - where crawl_job_id = $jobId
- [ ] `scopeAtDepth($query, $depth)` - where depth = $depth
- [ ] `scopeSuccessful($query)` - where error is null

### CrawlPage Accessors
- [ ] `getSizeFormattedAttribute()` - human-readable size
- [ ] `getInternalLinksAttribute()` - filter links where internal = true
- [ ] `getExternalLinksAttribute()` - filter links where internal = false

### Tests
- [ ] Unit tests for model relationships
- [ ] Unit tests for scopes
- [ ] Unit tests for accessors

## Dependencies
- TASK-22-1 (Database migrations)

## Effort Estimate
`Medium` (2-3 hours)

## Notes
- Use Laravel's native enum casting if PHP 8.1+
- Consider using spatie/enum package for status enum

## Comments
_Leave notes for the next agent here_

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
