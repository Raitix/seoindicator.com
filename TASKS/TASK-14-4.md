# TASK-14-4: Update Documentation for Audits Schema

## STATUS
`To Develop`

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

### [2026-02-25 14:42] Agent: PreTester
**Tests Written:**
- Created `TESTS/TEST-14-4.md` - Test documentation for documentation consistency validation
- Created `backend/tests/Feature/DocumentationConsistencyTest.php` - PHPUnit tests (12 test cases)

**Test Results:**
- 3 tests pass (file exists, migrations exist, status enum documented)
- 9 tests fail (expected - DOCUMENTATION.md lacks audits documentation)

**What Developer Must Do:**
1. Update DOCUMENTATION.md to include:
   - **Audits Table**: id, site_id (FK to sites), status (pending/running/completed/failed), pages_crawled, issues_found, started_at, completed_at, timestamps
   - **Audit Issues Table**: id, audit_id (FK to audits), url, issue_type_id (FK to issue_types), severity (critical/warning/notice), description, recommendation, status (open/fixed/ignored), timestamps
   - **Issue Types Table**: id, category, name, description, severity_default (critical/warning/notice), recommendation_template, timestamps
2. Ensure `site_id` is used (NOT `project_id`) for audits foreign key
3. Run tests: `php artisan test --filter=DocumentationConsistencyTest`

**Expected Test Output After Fix:**
All 12 tests should pass.
