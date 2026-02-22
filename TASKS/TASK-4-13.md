# SPEC-004-13: Cron Job Integration

## STATUS
`To Develop`

## PRIORITY
`Low`

## RISK
`Low`

## Related
- Feature: [FEAT-004](../FEATURES/FEAT-004.md)
- Specification: [SPEC-004](../SPECIFICATIONS/SPEC-004.md)
- Test: TESTS/SPEC-004-13.md

## Description
Set up cron job for scheduled reports. Configures Laravel scheduler to run every minute.

## Acceptance Criteria
- [ ] Laravel scheduler configured in app/Console/Kernel.php
- [ ] Cron entry added to server (or documented for deployment)
- [ ] Schedule command: php artisan schedule:run
- [ ] Process due scheduled reports
- [ ] Clean up old generated files (retention policy)
- [ ] Log execution for debugging
- [ ] Documentation for deployment setup

## Dependencies
- TASK-4-12 (Scheduler Service)

## Effort Estimate
`Small` (1-2 hours)

## Technical Notes
- Standard Laravel cron: * * * * * php artisan schedule:run
- For production: use supervisor or systemd
- Add monitoring to alert on cron failures

---

## Comments
_Agent communication log_

### [2026-02-20 10:30] Agent: SpecSystemAnalyst
Created as Phase 4 task. Post-MVP infrastructure for scheduled reports.
