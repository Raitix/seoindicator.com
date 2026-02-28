# TASK-24-1: Database Migration - PWA Analyses Table

## STATUS
`In Development`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create database migration for the `pwa_analyses` table to store cached PWA manifest analysis results. This is the foundation for the PWA indicator feature.

## Acceptance Criteria
- [ ] Migration creates `pwa_analyses` table with all columns from SPEC-024 schema:
  - id (BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY)
  - site_id (BIGINT UNSIGNED, foreign key to sites.id)
  - has_manifest, manifest_valid (TINYINT(1))
  - manifest_url (VARCHAR(500) nullable)
  - manifest_json (JSON nullable)
  - Field presence flags: has_name, has_short_name, has_start_url, has_display, has_background_color, has_theme_color, has_description (TINYINT(1))
  - Icon counts: icons_total, icons_valid (INT UNSIGNED)
  - Icon presence flags: icons_192_present, icons_512_present, icons_180_present (TINYINT(1))
  - icon_errors (JSON nullable)
  - pwa_score (TINYINT UNSIGNED, 0-100)
  - readiness_level (ENUM: none, basic, enhanced, full)
  - analyzed_at, created_at, updated_at (TIMESTAMP)
- [ ] Foreign key constraint: site_id → sites.id with CASCADE delete
- [ ] Indexes on: (site_id, analyzed_at), pwa_score
- [ ] Migration is reversible (down method drops table)
- [ ] Migration runs successfully on local MySQL

## Dependencies
- sites table must exist
- TASK-16-1 (Tools infrastructure - URL validation)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Use Laravel migration syntax (PHP)
- JSON columns for flexible manifest and error storage
- ENUM for readiness_level with default 'none'
- TINYINT UNSIGNED for score (0-100 range)

---

## Comments
_Agent communication log_

### [2026-02-28] Agent: SpecSystemAnalyst
Created task for database migration. Foundation task for PWA Indicator - must be completed before services can store analysis results. Set priority to High due to dependency chain. Reuses URL validation infrastructure from TASK-16-1 for SSRF protection.

### [2026-02-28 20:58 GMT+2] Agent: ProjectManager
**Prioritization Review Complete**

- **Priority Confirmed:** High (foundation task, blocks entire PWA feature)
- **Risk:** Medium (non-destructive migration, adds new table)
- **Dependency Verified:** TASK-16-1 (Tools infrastructure) is Done ✅
- **Execution Order:** First task in Phase 1 - must complete before TASK-24-2 and TASK-24-3

Status updated to `To Write Tests`. Ready for PreTester to create test cases for migration validation.

### [2026-02-28 23:59 GMT+2] Agent: PreTester
**Test Coverage Complete**

Created comprehensive test suite for the `pwa_analyses` migration:

**Test Documentation:** `TESTS/SPEC-24-1.md`
**Test Implementation:** `backend/tests/Feature/Migrations/CreatePwaAnalysesTableTest.php`

**Test Categories (33 tests):**
1. **Schema Structure (11 tests):** All columns verified with correct types and defaults
2. **Foreign Key Constraints (3 tests):** FK to sites.id with CASCADE delete
3. **Indexes (3 tests):** Composite index (site_id, analyzed_at), pwa_score index
4. **Enum Validation (4 tests):** readiness_level ENUM values (none, basic, enhanced, full)
5. **Migration Rollback (2 tests):** Reversible migration verified
6. **JSON Handling (4 tests):** manifest_json and icon_errors columns
7. **Score Range Validation (3 tests):** pwa_score 0-100 range
8. **Security Tests (3 tests):** SQL injection, XSS, large JSON handling

**Notes for Developer:**
- Tests require the migration file to be created at `backend/database/migrations/`
- Tests will pass once migration is implemented with correct schema
- After migration, create `PWAAnalysis` model at `backend/app/Models/PWAAnalysis.php`
- Model should cast `manifest_json` and `icon_errors` as arrays

Status updated to `To Develop`. Ready for Developer to implement migration.
