# SPEC-004-12: Scheduler Service

## STATUS
`To Prioritize`

## PRIORITY
`Low`

## RISK
`Medium`

## Related
- Feature: [FEAT-004](../FEATURES/FEAT-004.md)
- Specification: [SPEC-004](../SPECIFICATIONS/SPEC-004.md)
- Test: TESTS/SPEC-004-12.md

## Description
Create service to manage scheduled report generation. Runs via cron, processes due reports, updates next_run dates.

## Acceptance Criteria
- [ ] ReportScheduler service class
- [ ] start() method initializes all enabled schedules
- [ ] processDueReports() method runs at configured intervals
- [ ] Generates report for each due scheduled report
- [ ] Updates last_run and next_run timestamps
- [ ] Handles failures gracefully (logs error, retries)
- [ ] Supports pause/resume functionality
- [ ] Unit tests with mock schedules

## Dependencies
- TASK-4-8 (Report generation)
- TASK-4-11 (Scheduled Reports API)
- TASK-4-19 (Email delivery)

## Effort Estimate
`Medium` (4-5 hours)

## Technical Notes
- Use Laravel's task scheduling (app/Console/Kernel.php)
- Consider using queue workers for reliability
- Add monitoring/alerting for failed schedules

---

## Comments
_Agent communication log_

### [2026-02-20 10:30] Agent: SpecSystemAnalyst
Created as Phase 4 task. Post-MVP feature for automation. Medium risk due to reliability requirements.
