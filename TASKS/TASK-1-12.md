# SPEC-001-12: Scheduler for Automated Checks

## STATUS
`To Test Prod`
## PRIORITY
`High`

## RISK
`Low`

## Description
Implement the scheduler that automatically queues rank check jobs on a configurable schedule. Default: daily checks for all active keywords.

## Acceptance Criteria
- [ ] Scheduler runs on configurable interval (default: daily)
- [ ] Queues check jobs for all active keywords
- [ ] Respects last_checked_at - skip recently checked keywords
- [ ] Randomizes job timing to avoid API bursts
- [ ] Configurable via environment or database settings
- [ ] Manual trigger endpoint for admin/testing
- [ ] Job priority based on keyword activity (recently viewed = higher priority)
- [ ] Logs scheduler runs and job counts
- [ ] Unit tests for scheduler logic

## Dependencies
- SPEC-001-10 (job queue)
- SPEC-001-11 (rank checker worker)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Use Laravel scheduler or cron jobs
- For MVP: simple interval-based scheduling
- Consider time-of-day preferences per user (future)
- Track scheduler run history

---

## Comments
_Agent communication log_

### [2026-02-20 09:44] Agent: SpecSystemAnalyst
Created task for scheduler. Enables automated daily rank checking.

### [2026-02-22 21:28] Agent: Developer
Implemented rank check scheduler service with interval cache, randomized scheduling window, and priority based on last_viewed_at. Added manual trigger endpoint (POST /api/v1/scheduler/rank-checks/run) and artisan command (rank-checks:schedule). Added last_viewed_at tracking + migration + factory updates, queue scheduling support (scheduledFor + created_at filter), config/env settings, and unit tests for scheduler and queue scheduling. Status -> To Test Locally. Tests not run in this session.

### [2026-02-23 00:06] Agent: LocalTester
Set status to Testing Locally and ran: `php artisan test --filter RankCheckSchedulerTest`.
Result: **FAIL**. SQLite in-memory test DB errors on migration index prefix: `create index "idx_source_target" on "backlinks" (source_url(191), target_url(191))` → `SQLSTATE[HY000]: General error: 1 no such function: source_url`. This prevents scheduler unit tests from running. Please adjust migration/index for SQLite compatibility in tests or switch test DB to MySQL. Returning to Developer.

### [2026-02-23 00:28] Agent: Developer
Fixed SQLite incompatibility in backlinks migration by switching prefixed index to normal index for sqlite and guarding MySQL-only `SHOW INDEX` calls. Status → To Test Locally. Tests not run in this session.

### [2026-02-23 00:40] Agent: LocalTester
Local tests PASS. Ran `php artisan test --filter RankCheckSchedulerTest` (2 tests, 6 assertions). No other scheduler-related tests found under backend/tests. Status → To Deploy.

### [2026-02-23 00:32] Agent: DevOps
Set status to In Deployment. Starting production deploy via GitHub Actions (push to main) and post-deploy health checks.

### [2026-02-23 00:34] Agent: DevOps
Pushed main to trigger deploy. Health check OK: GET https://seoindicator.com/api/health → 200 {"status":"ok","database":"connected"}. Status → To Test Prod.
