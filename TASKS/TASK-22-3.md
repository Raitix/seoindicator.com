# TASK-22-3: CrawlerService Skeleton

## STATUS
`To Prioritize`

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
Create the CrawlerService skeleton with job management methods. This establishes the service structure before implementing the full crawling logic.

## Acceptance Criteria

### Service Structure
- [ ] Service created at `app/Services/CrawlerService.php`
- [ ] Implements service interface pattern if project uses one
- [ ] Constructor injection for dependencies (CrawlJob model, logger)

### Core Methods (Skeleton)
- [ ] `createJob(int $userId, string $url, array $options = []): CrawlJob` - Create and queue a crawl job
- [ ] `getJob(string $jobId): ?CrawlJob` - Get job by job_id
- [ ] `processJob(string $jobId): void` - Main crawl execution (called by worker)
- [ ] `cancelJob(string $jobId): bool` - Cancel a running job
- [ ] `cleanup(): int` - Remove expired jobs, return count deleted

### Job Creation Logic
- [ ] Generate unique job_id (UUID or unique string)
- [ ] Validate URL format
- [ ] Set default options if not provided
- [ ] Set expiry date (default 7 days)
- [ ] Set initial status to 'queued'
- [ ] Dispatch to queue (use existing JobQueue from TASK-1-10)

### Job Retrieval Logic
- [ ] Find job by job_id
- [ ] Include page count and total size
- [ ] Return null if not found

### Job Cancellation Logic
- [ ] Only allow cancellation if status is 'queued' or 'running'
- [ ] Update status to 'cancelled'
- [ ] Record cancellation timestamp

### Cleanup Logic
- [ ] Find jobs where expires_at < now
- [ ] Delete associated crawl_pages first (cascade)
- [ ] Delete crawl_jobs
- [ ] Return count of deleted jobs

### Error Handling
- [ ] Log errors with context
- [ ] Throw specific exceptions (CrawlJobNotFoundException, CrawlJobCancellationException)
- [ ] Use try-catch in processJob to catch all exceptions

### Configuration
- [ ] Config values in config/crawler.php:
  - [ ] `max_depth` (default: 2)
  - [ ] `max_pages` (default: 50)
  - [ ] `max_content_size` (default: 3MB)
  - [ ] `page_timeout` (default: 30 seconds)
  - [ ] `job_timeout` (default: 5 minutes)
  - [ ] `retention_days` (default: 7)

### Tests
- [ ] Unit test for createJob
- [ ] Unit test for getJob
- [ ] Unit test for cancelJob
- [ ] Unit test for cleanup
- [ ] Test job_id generation uniqueness

## Dependencies
- TASK-22-1 (Database migrations)
- TASK-22-2 (Models)
- TASK-1-10 (JobQueue)

## Effort Estimate
`Medium` (3-4 hours)

## Notes
- processJob() will be implemented in later tasks (rendering, extraction)
- Consider using Laravel's queue retry and failed job handling
- Job priority should be configurable per job type

## Comments
_Leave notes for the next agent here_
