# SPEC-004-9: Report Download Endpoint

## STATUS
`To Develop`

## PRIORITY
`Medium`

## RISK
`Low`

## Related
- Feature: [FEAT-004](../FEATURES/FEAT-004.md)
- Specification: [SPEC-004](../SPECIFICATIONS/SPEC-004.md)
- Test: TESTS/SPEC-004-9.md

## Description
Create API endpoint to download generated reports. Serves PDF and CSV files with proper headers.

## Acceptance Criteria
- [ ] GET /api/v1/reports/:reportId/download - Download report file
- [ ] Returns PDF with Content-Type: application/pdf
- [ ] Returns CSV with Content-Type: text/csv
- [ ] Proper Content-Disposition header with filename
- [ ] Authorization: user owns the site
- [ ] Returns 404 if report not found
- [ ] Returns 404 if file doesn't exist on disk
- [ ] Integration tests

## Dependencies
- TASK-4-2 (Report model)

## Effort Estimate
`Small` (1-2 hours)

## Technical Notes
- Use Laravel's response()->download() or stream()
- Include filename with date: report-2026-02-20.pdf
- Consider signed URLs for secure downloads

---

## Comments
_Agent communication log_

### [2026-02-20 10:30] Agent: SpecSystemAnalyst
Created as Phase 3 task. Simple file serving endpoint.
