# SPEC-001-10: Job Queue Setup

## STATUS
`To Deploy`
## PRIORITY
`High`

## RISK
`Medium`

## Description
Set up the job queue system for background rank checking. MVP uses simple SQLite-based queue; production can migrate to Redis/BullMQ.

## Acceptance Criteria
- [ ] Job queue interface defined: addJob, getJob, updateJob, getPending
- [ ] CheckJob type: id, type, keywordId/siteId, priority, status, attempts, maxAttempts, timestamps
- [ ] SQLite-based implementation for MVP (no Redis dependency)
- [ ] Job statuses: pending, running, completed, failed
- [ ] Retry logic with configurable attempts (default: 3)
- [ ] Dead letter queue for failed jobs
- [ ] Job priority support (high, normal, low)
- [ ] Unit tests for queue operations

## Dependencies
- None

## Effort Estimate
`Medium` (4-6 hours)

## Technical Notes
- SQLite queue table: id, type, payload, priority, status, attempts, created_at, started_at, completed_at, error
- For production: migrate to BullMQ + Redis
- Include job cleanup for completed jobs older than 7 days

---

## Comments
_Agent communication log_

### [2026-02-20 09:44] Agent: SpecSystemAnalyst
Created task for job queue setup. Required for background rank checking. SQLite-based for MVP simplicity.

### [2026-02-22 18:52] Agent: Developer
Implemented SQLite-backed JobQueue with interface and CheckJob DTO, added retry/dead-letter logic and cleanup, plus unit tests for queue operations. Queue uses storage/app/queue.sqlite by default. Status -> To Test Locally.

### [2026-02-22 21:31] Agent: LocalTester
- Status set to Testing Locally; ran queue tests.
- PASS: php artisan test --filter=JobQueueTest (4 tests, 23 assertions).
- NOTE (unrelated): php artisan test --filter=JobTest matched CheckKeywordJobTest and failed due to missing App\\Jobs\\CheckKeywordJob and SQLite nested transaction errors. 	ests/Unit/Services/Queue/JobTest.php not present.
- Task-specific queue tests PASS ? Status -> To Deploy.

