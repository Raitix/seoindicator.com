# SPEC-001-12: Scheduler for Automated Checks

## STATUS
`To Test Locally`
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
