# TASK-33-1: Add Visibility Columns to Audits Table

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Medium`

## Specification
**SPEC-33**: Public SEO Audit Reports & Embeddable Score Widget

## Description
Add visibility, public_hash, and public_hash_created_at columns to the audits table to support public audit sharing. This enables users to control whether their audits are publicly accessible.

## Acceptance Criteria
- [ ] Migration creates `visibility` ENUM column ('private', 'public') with default 'private'
- [ ] Migration creates `public_hash` VARCHAR(32) UNIQUE NULL column
- [ ] Migration creates `public_hash_created_at` TIMESTAMP NULL column
- [ ] Migration is reversible (down method removes columns)
- [ ] Audit model updated with fillable attributes
- [ ] Audit model updated with casts for visibility enum
- [ ] Index added on public_hash for fast lookups
- [ ] Existing audits remain private (default value applied)
- [ ] Unit test verifies column existence and defaults

## Dependencies
- None (foundation task)

## Effort Estimate
**2 hours** - Simple migration with model updates

## Technical Notes
- Use Laravel's enum column type for visibility
- public_hash should be nullable (only set when user publishes)
- Consider adding a generated unique hash method to Audit model
- Index public_hash for O(1) lookup performance on public pages

## Comments

### [2026-03-05 21:23 GMT+2] ProjectManager - Prioritization Assessment

**Decision:** HIGH priority confirmed ✅

**Priority Assessment:**
- **Foundation Task:** This is the first task in Phase 1 of SPEC-33, enabling all 34 downstream tasks
- **No Dependencies:** Can start immediately - no blocking tasks
- **Critical Path:** All other tasks in SPEC-33 depend on visibility columns existing
- **Effort:** 2 hours (low effort, high impact)

**Risk Assessment: MEDIUM (confirmed)**
- Non-destructive migration (adds columns only)
- Existing audits remain private (default value applied)
- Migration is reversible (down method removes columns)
- Standard Laravel migration patterns

**Key Technical Points:**
- Adds `visibility` ENUM column with default 'private'
- Adds `public_hash` VARCHAR(32) UNIQUE NULL for public URL access
- Adds `public_hash_created_at` TIMESTAMP NULL for audit trail
- Index on public_hash for O(1) lookup performance

**TDD Ready:** PreTester can design tests immediately
- Test migration creates all columns correctly
- Test default values applied to existing audits
- Test model fillable attributes and casts
- Test index exists on public_hash

**Handoff to PreTester:** Ready for test design. All acceptance criteria are testable.

### [2026-03-05 21:45 GMT+2] PreTester - Tests Coverage Complete

**Status:** Tests designed and ready for implementation ✅

**Test Files Created:**
- `backend/tests/Unit/Migrations/AddVisibilityColumnsToAuditsTableTest.php` (60+ tests)
- `backend/tests/Unit/Models/AuditVisibilityTest.php` (45+ tests)
- `backend/database/factories/AuditFactory.php` (factory with states)
- `TESTS/TEST-33-1.md` (test documentation)

**Test Categories:**
| Category | Tests | Coverage |
|----------|-------|----------|
| Migration Columns (MC) | 15 | Column structure, defaults, types |
| Migration Data (MD) | 10 | Insert, update, query operations |
| Migration Reversibility (MR) | 10 | Rollback, re-migrate cycles |
| Model Fillable/Casts (MF) | 15 | Mass assignment, type casting |
| Model Scopes/Methods (MS) | 10 | Public/private scopes, helper methods |
| Edge Cases/Security (ES) | 15 | SQL injection, XSS, unicode, boundaries |
| **Total** | **75** | **Full coverage** |

**Factory States:**
- `pending()`, `running()`, `completed()`, `failed()` - audit status states
- `public()`, `private()` - visibility states
- `withPublicHash($hash)` - custom public hash
- `withManyIssues()`, `withNoIssues()`, `withManyPages()` - data variations

**Key Test Patterns:**
- MySQL/MariaDB column inspection with Doctrine
- SQLite fallback for basic column checks
- UNIQUE constraint testing with NULL values
- Index verification via INFORMATION_SCHEMA
- Rollback/remigrate cycle testing

**TDD Pattern:** Tests will fail until:
1. Migration file created at `database/migrations/*_add_visibility_columns_to_audits_table.php`
2. Audit model updated with fillable: `visibility`, `public_hash`, `public_hash_created_at`
3. Audit model updated with casts for `visibility` (enum) and `public_hash_created_at` (datetime)

**Handoff to Developer:** Ready for implementation. Run tests with:
```bash
php artisan test --filter=AddVisibilityColumnsToAuditsTableTest
php artisan test --filter=AuditVisibilityTest
```
