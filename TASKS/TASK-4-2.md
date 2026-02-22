# SPEC-004-2: Report Model and Repository

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Low`

## Related
- Feature: [FEAT-004](../FEATURES/FEAT-004.md)
- Specification: [SPEC-004](../SPECIFICATIONS/SPEC-004.md)
- Test: TESTS/SPEC-004-2.md

## Description
Create Report model with Eloquent relationships and repository pattern for data access. Provides the core data layer for report operations.

## Acceptance Criteria
- [ ] Report model with fillable attributes: site_id, name, type, format, status, date_range_start, date_range_end, compare_range_start, compare_range_end, sections, generated_at, file_path, file_size, error
- [ ] Report belongs to Site relationship
- [ ] Report casts sections as array (JSON)
- [ ] Report has status accessor for human-readable status
- [ ] ReportRepository with methods: create, find, findBySite, findByStatus, update, delete
- [ ] Repository handles date range filtering
- [ ] Unit tests for model and repository

## Dependencies
- TASK-4-1 (reports table migration)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Follow existing repository pattern from FEAT-001/003
- Use Laravel Eloquent conventions
- Add scope methods for common queries

---

## Comments
_Agent communication log_

### [2026-02-20 10:30] Agent: SpecSystemAnalyst
Created as Phase 1 task. Provides data layer for all report operations.
