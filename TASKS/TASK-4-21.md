# SPEC-004-21: Integration Tests

## STATUS
`To Prioritize`

## PRIORITY
`Medium`

## RISK
`Low`

## Related
- Feature: [FEAT-004](../FEATURES/FEAT-004.md)
- Specification: [SPEC-004](../SPECIFICATIONS/SPEC-004.md)
- Test: TESTS/SPEC-004-21.md

## Description
Create integration tests for report API endpoints and end-to-end workflows. Validates complete request/response cycles.

## Acceptance Criteria
- [ ] Integration tests for Reports CRUD API
- [ ] Integration tests for Report generation endpoint
- [ ] Integration tests for Report download endpoint
- [ ] Integration tests for Template management API
- [ ] Integration tests for Scheduled reports API
- [ ] Integration tests for Branding API
- [ ] Integration tests for Logo upload
- [ ] End-to-end test: create template → generate report → download
- [ ] Authentication and authorization tests
- [ ] All tests pass in CI pipeline

## Dependencies
- All implementation tasks (TASK-4-1 through TASK-4-19)
- TASK-4-20 (Unit Tests)

## Effort Estimate
`Medium` (4-5 hours)

## Technical Notes
- Use Laravel's Http testing methods
- Use actingAs() for authentication
- Test error responses (400, 401, 403, 404, 422, 500)

---

## Comments
_Agent communication log_

### [2026-02-20 10:30] Agent: SpecSystemAnalyst
Created as Phase 7 task. Integration tests validate complete workflows.
