# SPEC-004-11: Scheduled Reports API

## STATUS
`To Develop`

## PRIORITY
`Low`

## RISK
`Low`

## Related
- Feature: [FEAT-004](../FEATURES/FEAT-004.md)
- Specification: [SPEC-004](../SPECIFICATIONS/SPEC-004.md)
- Test: TESTS/SPEC-004-11.md

## Description
Create REST API endpoints for scheduled report management. Create, list, update, pause/resume scheduled reports.

## Acceptance Criteria
- [ ] GET /api/v1/sites/:siteId/scheduled-reports - List scheduled reports
- [ ] GET /api/v1/scheduled-reports/:scheduledId - Get scheduled report details
- [ ] POST /api/v1/sites/:siteId/scheduled-reports - Create scheduled report
- [ ] PUT /api/v1/scheduled-reports/:scheduledId - Update scheduled report
- [ ] DELETE /api/v1/scheduled-reports/:scheduledId - Delete scheduled report
- [ ] POST /api/v1/scheduled-reports/:scheduledId/pause - Pause schedule
- [ ] POST /api/v1/scheduled-reports/:scheduledId/resume - Resume schedule
- [ ] Request validation: frequency required, recipients must be valid emails
- [ ] Authorization: user owns the site
- [ ] Integration tests

## Dependencies
- TASK-4-1 (scheduled_reports table)
- TASK-4-3 (Template model)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Calculate next_run date on creation/update
- Validate frequency values: daily, weekly, biweekly, monthly
- Store recipients as JSON array

---

## Comments
_Agent communication log_

### [2026-02-20 17:55] ProjectManager: Prioritization Complete
- TASK-4-11 must be completed before proceeding with TASK-4-12 and TASK-4-13.
- Dependencies Verified: TASK-4-1 (foundational table) and TASK-4-3 (template repository) must be completed first.
- Priority remains Low given its Post-MVP status, though it is a prerequisite for Phase 4 scheduling.

### [2026-02-20 10:30] Agent: SpecSystemAnalyst
Created as Phase 4 task. Post-MVP feature for scheduled automation.
