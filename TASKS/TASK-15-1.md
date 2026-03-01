# TASK-15-1: Create Brand Configs Table

## STATUS
`To Develop`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Create the `brand_configs` table to store branding settings (logo, colors, footer text).

## Schema Requirements
```sql
CREATE TABLE brand_configs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT UNSIGNED NOT NULL,
    logo_url VARCHAR(500) NULL,
    brand_name VARCHAR(100) NOT NULL DEFAULT 'SEOIndicator',
    primary_color VARCHAR(7) NOT NULL DEFAULT '#3B82F6',
    secondary_color VARCHAR(7) NOT NULL DEFAULT '#1E40AF',
    favicon_url VARCHAR(500) NULL,
    login_background_url VARCHAR(500) NULL,
    footer_text TEXT NULL,
    hide_powered_by BOOLEAN DEFAULT FALSE,
    custom_email_from VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    UNIQUE KEY idx_account (account_id)
);
```

## Acceptance Criteria
- [ ] Migration creates `brand_configs` table.
- [ ] Column constraints match requirements for branding.
- [ ] Test CRUD operations.

## Effort Estimate
2 hours.

---

## Comments
### [2026-02-20 17:49] Agent: SpecSystemAnalyst
Created task for branding configuration storage.

### [2026-02-28 15:33] Agent: ProjectManager
**Prioritization Assessment:**
- **Priority: Medium** - Foundation task (database schema) enabling white-label feature
- **Risk: Low** - Non-destructive migration, standard CRUD operations
- **Feature Context:** SPEC-015 (White-Label Customization) - Low priority feature for agencies/resellers
- **Dependencies:** None - this is a foundation task
- **Execution Order:** First in SPEC-015 task chain (TASK-15-1 → TASK-15-2)
- **Handoff:** Ready for PreTester to design tests for migration and CRUD validation

### [2026-02-28 23:15] Agent: PreTester
**Test Design Started:**
- Creating test documentation and migration tests
- Tests will cover: schema validation, constraints, CRUD operations, edge cases
- Reference: TESTS/SPEC-15-1.md

### [2026-02-28 23:20] Agent: PreTester
**Test Design Complete**

Created comprehensive test suite:
- **Test File**: `backend/tests/Feature/Database/Migrations/CreateBrandConfigsTableTest.php`
- **Test Documentation**: `TESTS/SPEC-15-1.md`
- **Total Tests**: 36 test cases

**Coverage Summary:**
| Category | Tests | Description |
|----------|-------|-------------|
| Schema Structure | 13 | All columns with correct types |
| Constraints | 4 | FK, cascade delete, unique |
| Default Values | 4 | brand_name, colors, hide_powered_by |
| CRUD Operations | 5 | Create, Read, Update, Delete |
| Edge Cases | 9 | NULL handling, max lengths, invalid colors |
| Security | 4 | SQL injection, XSS, HTML |
| Rollback | 2 | Drop and recreate verification |

**Handoff to Developer**: Tests are ready. Migration should follow the schema in task description. All tests will pass once migration is created.