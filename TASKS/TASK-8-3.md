# TASK-8-3: Data Migration for Default Projects

## STATUS
`Tests Coverage In Progress`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Perform data migration to assign all existing user data to default projects. This is required to ensure backward compatibility and proper scoping of legacy data.

## Steps
1. Create a default project for each user:
```sql
INSERT INTO projects (user_id, name, domain, is_default, created_at, updated_at)
SELECT 
    id AS user_id, 
    'Default Project' AS name, 
    NULL AS domain, 
    TRUE AS is_default, 
    NOW() AS created_at, 
    NOW() AS updated_at
FROM users;
```

2. Assign existing records to default projects:
```sql
UPDATE keywords k
JOIN projects p ON p.user_id = k.user_id AND p.is_default = TRUE
SET k.project_id = p.id;

UPDATE competitors c
JOIN projects p ON p.user_id = c.user_id AND p.is_default = TRUE
SET c.project_id = p.id;

UPDATE reports r
JOIN projects p ON p.user_id = r.user_id AND p.is_default = TRUE
SET r.project_id = p.id;
```

3. Make `project_id` NOT NULL:
```sql
ALTER TABLE keywords MODIFY project_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE competitors MODIFY project_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE reports MODIFY project_id BIGINT UNSIGNED NOT NULL;
```

## Acceptance Criteria
- [ ] Default project created for every existing user.
- [ ] Keywords, competitors, reports all assigned to default projects.
- [ ] `project_id` NOT NULL constraint applied after migration.
- [ ] Migration tested in staging before production rollout.

## Dependencies
- TASK-8-1: Projects table must exist.
- TASK-8-2: Related tables (`keywords`, etc.) must have `project_id` column.

## Effort Estimate
4-6 hours.

---

## Comments
### [2026-02-26 22:30] Agent: PreTester
**Test Documentation Created:** `TESTS/TEST-8-3.md`

**Coverage Summary:**
- **79 test scenarios** across 8 categories
- Default Project Creation: 10 tests (DP-001 to DP-010)
- Keywords Assignment: 10 tests (KA-001 to KA-010)
- Competitors Assignment: 8 tests (CA-001 to CA-008)
- Reports Assignment: 8 tests (RA-001 to RA-008)
- NOT NULL Constraint: 10 tests (NN-001 to NN-010)
- Migration Safety: 10 tests (MS-001 to MS-010)
- Edge Cases: 15 tests (EC-001 to EC-015)
- Data Integrity: 8 tests (DI-001 to DI-008)

**Critical Test Scenarios:**
- DP-004: Idempotency - no duplicate default projects on re-run
- KA-006: Orphan keyword handling (user has no default project)
- NN-001 to NN-010: NOT NULL constraint enforcement and rollback
- MS-001: Transaction rollback on error
- DI-001 to DI-008: Data integrity verification post-migration

**Dependency Status:**
- TASK-8-1 (Projects Table): `To Test Prod` - deployed, table exists
- TASK-8-2 (Modify Tables): `To Write Tests` - `project_id` columns being added

**Risk Considerations:**
- Medium risk due to NOT NULL constraint on production data
- Transaction wrapping essential for atomic rollback
- Staging validation required before production rollout
- Chunked processing recommended for large datasets

**Developer Notes:**
- Migration should be wrapped in a database transaction
- Use chunking for large datasets (100 records per batch)
- Log each step with counts for audit trail
- Check for existing default projects before creating new ones
- Reports relationship goes through `site_id`, verify user match

**Status:** Changed from `To Write Tests` → `To Write Tests`

### [2026-02-26 20:35] Agent: ProjectManager
**Priority: High** (upgraded from Medium) - Critical for backward compatibility. Without this migration, existing user data won't be properly scoped to projects, breaking the multi-site feature for all existing users.

**Risk: Medium** (confirmed) - Data migration with NOT NULL constraint. Requires careful staging validation.

**Dependencies verified:**
- TASK-8-1 (Projects Table): `To Deploy` - nearly complete
- TASK-8-2 (Modify Tables): `To Write Tests` - in progress

**Status:** Changed from `To Prioritize` → `To Write Tests`. Ready for PreTester.

### [2026-02-20 17:15] Agent: SpecSystemAnalyst
Data migration task created to assign existing records to default projects for backward compatibility.