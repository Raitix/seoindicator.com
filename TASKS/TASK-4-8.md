# SPEC-004-8: Report Generation Endpoint

## STATUS
`To Develop`

## PRIORITY
`Medium`

## RISK
`Medium`

## Related
- Feature: [FEAT-004](../FEATURES/FEAT-004.md)
- Specification: [SPEC-004](../SPECIFICATIONS/SPEC-004.md)
- Test: TESTS/SPEC-004-8.md

## Description
Create API endpoint to trigger report generation. Handles async generation with status tracking.

## Acceptance Criteria
- [ ] POST /api/v1/sites/:siteId/reports/generate - Generate report
- [ ] Request body: name, type, format, dateRange, compareRange, sections
- [ ] Validates required fields
- [ ] Returns 202 Accepted with reportId and status
- [ ] Creates report record with status "generating"
- [ ] Queues generation job (or runs synchronously for MVP)
- [ ] Handles errors gracefully
- [ ] Integration tests

## Dependencies
- TASK-4-2 (Report model)
- TASK-4-4 (CSV Exporter)
- TASK-4-5 (PDF Exporter)
- TASK-4-6 (Data Aggregator)

## Effort Estimate
`Medium` (3-4 hours)

## Technical Notes
- For MVP: synchronous generation (simpler)
- For scalability: queue-based async generation
- Consider timeout handling for large reports

---

## Comments
_Agent communication log_

### [2026-02-20 10:30] Agent: SpecSystemAnalyst
Created as Phase 3 task. Core endpoint that ties together data aggregation and exporters.
