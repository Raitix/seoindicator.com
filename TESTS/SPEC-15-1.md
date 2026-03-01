# TESTS/SPEC-15-1: Database Migration - Brand Configs Table

## Related
- Task: TASKS/TASK-15-1.md
- Specification: SPEC-015 (White-Label Customization)

## Test Status
- [x] Tests designed
- [x] Unit tests written
- [ ] Integration tests written
- [ ] E2E tests written (N/A - migration task)
- [ ] All tests passing locally
- [ ] All tests passing in production

## Overview
Tests for the `brand_configs` table migration that stores branding settings (logo, colors, footer text) for white-label customization.

## Test Files

| Test File | Location | Status |
|-----------|----------|--------|
| CreateBrandConfigsTableTest.php | `backend/tests/Feature/Database/Migrations/` | ✅ Created |

## Test Scenarios

### Schema Structure Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SS-001 | Table exists after migration | Run migration | `brand_configs` table created |
| SS-002 | Primary key column | Check `id` column | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| SS-003 | Foreign key to accounts | Check `account_id` column | BIGINT UNSIGNED NOT NULL |
| SS-004 | Logo URL column | Check `logo_url` column | VARCHAR(500) NULL |
| SS-005 | Brand name column | Check `brand_name` column | VARCHAR(100) NOT NULL DEFAULT 'SEOIndicator' |
| SS-006 | Primary color column | Check `primary_color` column | VARCHAR(7) NOT NULL DEFAULT '#3B82F6' |
| SS-007 | Secondary color column | Check `secondary_color` column | VARCHAR(7) NOT NULL DEFAULT '#1E40AF' |
| SS-008 | Favicon URL column | Check `favicon_url` column | VARCHAR(500) NULL |
| SS-009 | Login background URL column | Check `login_background_url` column | VARCHAR(500) NULL |
| SS-010 | Footer text column | Check `footer_text` column | TEXT NULL |
| SS-011 | Hide powered by column | Check `hide_powered_by` column | BOOLEAN DEFAULT FALSE |
| SS-012 | Custom email from column | Check `custom_email_from` column | VARCHAR(255) NULL |
| SS-013 | Timestamps | Check `created_at`, `updated_at` | TIMESTAMP columns exist |

### Constraint Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CT-001 | Foreign key constraint | Insert with non-existent account_id | Database rejects (SQLSTATE error) |
| CT-002 | FK cascade delete | Delete account with brand_config | Brand config cascade deleted |
| CT-003 | Unique account constraint | Insert duplicate account_id | Database rejects (duplicate key error) |
| CT-004 | Unique index exists | Check index `idx_account` | Unique index on account_id |

### Default Value Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DV-001 | Brand name default | Create record without brand_name | Default: 'SEOIndicator' |
| DV-002 | Primary color default | Create record without primary_color | Default: '#3B82F6' |
| DV-003 | Secondary color default | Create record without secondary_color | Default: '#1E40AF' |
| DV-004 | Hide powered by default | Create record without hide_powered_by | Default: FALSE |

### CRUD Operation Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CRUD-001 | Create brand config | Valid account_id, all fields | Record created successfully |
| CRUD-002 | Read brand config | Query by account_id | Returns correct brand config |
| CRUD-003 | Update brand config | Update logo_url, colors | Record updated successfully |
| CRUD-004 | Delete brand config | Delete brand config | Record deleted |
| CRUD-005 | Partial create | Only required fields | Record created with defaults |

### Edge Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | NULL logo_url | logo_url = NULL | Accepted (nullable field) |
| EC-002 | NULL favicon_url | favicon_url = NULL | Accepted (nullable field) |
| EC-003 | NULL login_background_url | login_background_url = NULL | Accepted (nullable field) |
| EC-004 | NULL footer_text | footer_text = NULL | Accepted (nullable field) |
| EC-005 | NULL custom_email_from | custom_email_from = NULL | Accepted (nullable field) |
| EC-006 | Long logo_url | 500 char URL | Accepted (max length) |
| EC-007 | Long brand_name | 100 char name | Accepted (max length) |
| EC-008 | Invalid hex color | primary_color = 'red' | Accepted (no format validation at DB level) |
| EC-009 | Empty brand_name | brand_name = '' | Accepted (NOT NULL but no min length) |

### Migration Rollback Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MR-001 | Rollback drops table | migrate:rollback | Table dropped |
| MR-002 | Re-run migration | migrate after rollback | Table recreated successfully |

### Security Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SEC-001 | SQL injection in logo_url | `'; DROP TABLE brand_configs; --` | Stored as literal string |
| SEC-002 | SQL injection in brand_name | `Robert'); DROP TABLE brand_configs; --` | Stored as literal string |
| SEC-003 | XSS payload in footer_text | `<script>alert('xss')</script>` | Stored as literal string |
| SEC-004 | HTML in footer_text | `<p>Styled footer</p>` | Stored as literal string |

## Test Data Samples

### Valid Brand Config
```json
{
  "account_id": 1,
  "logo_url": "https://example.com/logo.png",
  "brand_name": "Agency Pro",
  "primary_color": "#FF5733",
  "secondary_color": "#C70039",
  "favicon_url": "https://example.com/favicon.ico",
  "login_background_url": "https://example.com/bg.jpg",
  "footer_text": "Powered by Agency Pro",
  "hide_powered_by": false,
  "custom_email_from": "noreply@agency.pro"
}
```

### Minimal Brand Config
```json
{
  "account_id": 1
}
```
Expected defaults: brand_name='SEOIndicator', primary_color='#3B82F6', secondary_color='#1E40AF', hide_powered_by=false

## Coverage Targets
- Schema validation: 100% of columns tested
- Constraints: 100% (FK, unique, defaults)
- CRUD operations: All 5 basic operations
- Edge cases: 9 scenarios
- Security: 4 scenarios
- Rollback: 2 scenarios

## Test Execution

```bash
# Run all migration tests
php artisan test --filter=CreateBrandConfigsTableTest

# Run with coverage
php artisan test --filter=CreateBrandConfigsTableTest --coverage
```

## Dependencies
- `accounts` table (existing)
- Account model with factory

## Notes for Developer
1. Use `Schema::hasColumn()` for column existence tests
2. Use `Schema::getColumnType()` for type verification
3. Test foreign key constraints by attempting invalid inserts
4. Verify cascade delete behavior
5. Test that unique constraint prevents duplicate account_id

## Comments

### [2026-02-28 23:15] Agent: PreTester
Created test documentation and migration test file. Tests cover schema structure, constraints, CRUD operations, edge cases, and security scenarios.
