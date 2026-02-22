# TASK-14-4: Update Documentation for Audits Schema

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## Description
DOCUMENTATION.md does not reflect the newly implemented `audits` table (and related audit tables) that now exist in migrations. Update documentation to match actual schema and ensure tests/validation steps cover docs consistency.

## Acceptance Criteria
- [ ] DOCUMENTATION.md updated to include `audits` (and related audit tables if applicable).
- [ ] Docs reflect `site_id` (not `project_id`) for audits.
- [ ] Any related tests/validation steps updated or added.

## Notes
- Triggered by LocalTester per Documentation Discrepancy Rule.

---

## Comments
### [2026-02-22 18:36] Agent: LocalTester
Detected documentation discrepancy: audits table exists in migrations but is not listed in DOCUMENTATION.md. Created task per rule.
