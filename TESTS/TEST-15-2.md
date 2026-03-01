# TEST-15-2: Custom Domains Table

## Task Reference
- **Task:** TASK-15-2
- **Specification:** SPEC-015 (White-Label Customization)
- **Feature:** FEAT-015 (White-Label Customization)

## Overview
Tests for the `custom_domains` table migration, model, and repository. This table tracks custom domains set for white-label reselling.

## Test Files

| File | Type | Tests |
|------|------|-------|
| `backend/tests/Feature/Migrations/CreateCustomDomainsTableTest.php` | Migration | Schema, FK, indexes, constraints |
| `backend/tests/Unit/Models/CustomDomainTest.php` | Model | Attributes, relationships, scopes, edge cases |
| `backend/tests/Unit/Repositories/CustomDomainRepositoryTest.php` | Repository | CRUD, queries, edge cases |
| `backend/database/factories/CustomDomainFactory.php` | Factory | Test data generation |

## Schema Requirements

```sql
CREATE TABLE custom_domains (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT UNSIGNED NOT NULL,
    domain VARCHAR(255) NOT NULL,
    dns_verified BOOLEAN DEFAULT FALSE,
    ssl_provisioned BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_at TIMESTAMP NULL,

    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    UNIQUE KEY idx_domain (domain)
);
```

## Test Scenarios

### Migration Tests (SC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SC-001 | Migration creates custom_domains table | Run migration | Table exists |
| SC-002 | Primary key column is correct | Insert records | Auto-increment works |
| SC-003 | account_id column is correct | Insert with account_id | NOT NULL enforced |
| SC-004 | domain column is correct | Insert domain | NOT NULL enforced, max 255 chars |
| SC-005 | dns_verified column is correct | Insert record | Default FALSE |
| SC-006 | ssl_provisioned column is correct | Insert record | Default FALSE |
| SC-007 | Timestamp columns are correct | Insert record | created_at set, verified_at nullable |
| SC-008 | Domain uniqueness enforced | Insert duplicate domain | Unique constraint violation |

### Foreign Key Tests (FK)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FK-001 | Foreign key to accounts.id | Insert with valid account_id | Success |
| FK-002 | Invalid account_id rejected | Insert with non-existent account_id | FK constraint violation |
| FK-003 | Cascade delete on account deletion | Delete account | Domain records deleted |
| FK-004 | Multiple domains per account | Insert multiple domains for same account | All succeed |

### Index Tests (IX)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IX-001 | Unique index on domain | Query by domain | Uses unique index |
| IX-002 | Foreign key index on account_id | Query by account_id | Uses FK index |

### Model Tests (MD)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MD-001 | Create with all fields | Valid data | Record created |
| MD-002 | Create with minimal fields | Only required fields | Record created with defaults |
| MD-003 | Default values set correctly | Create without optional fields | dns_verified=false, ssl_provisioned=false |
| MD-004 | Boolean casts work | Set dns_verified=true | Returns boolean true |
| MD-005 | BelongsTo account relationship | Access domain->account | Returns Account model |
| MD-006 | Account hasMany domains | Access account->customDomains | Returns collection |
| MD-007 | Scope verified() | Query verified domains | Only dns_verified=true |
| MD-008 | Scope sslReady() | Query SSL provisioned | Only ssl_provisioned=true |
| MD-009 | Scope pending() | Query pending domains | dns_verified=false |
| MD-010 | Mark as verified | Call markAsVerified() | dns_verified=true, verified_at set |
| MD-011 | Mark as SSL provisioned | Call markSslProvisioned() | ssl_provisioned=true |

### Repository Tests (REP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| REP-001 | Create domain | Valid data | Domain created |
| REP-002 | Find by ID | Existing ID | Domain returned |
| REP-003 | Find by domain | Existing domain | Domain returned |
| REP-004 | Find by account | Account ID | Collection of domains |
| REP-005 | Update domain | ID + data | Domain updated |
| REP-006 | Delete domain | ID | Domain deleted |
| REP-007 | Find verified by account | Account ID | Only verified domains |
| REP-008 | Domain exists check | Domain string | Boolean |
| REP-009 | Get primary domain | Account ID | First verified domain |

### Edge Case Tests (EC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Domain max length 255 | 256 char domain | Truncated or error |
| EC-002 | Domain case sensitivity | "Example.COM" vs "example.com" | Same or different (DB dependent) |
| EC-003 | Empty domain | "" | NOT NULL violation |
| EC-004 | Null verified_at | Create without verification | NULL allowed |
| EC-005 | Unicode domain | "münchen.de" | Stored correctly |
| EC-006 | IDN domain | "中国.cn" | Stored correctly |
| EC-007 | Subdomain | "sub.example.com" | Stored correctly |
| EC-008 | Multiple domains same account | 3 domains for account | All succeed |

### Security Tests (SEC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SEC-001 | SQL injection in domain | "example.com'; DROP TABLE --" | Stored as string, table intact |
| SEC-002 | XSS in domain | "<script>alert(1)</script>.com" | Stored as string, escaped on output |
| SEC-003 | Domain traversal | "../../../etc/passwd" | Stored as string (validation at app layer) |

### Workflow Tests (WF)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| WF-001 | DNS verification workflow | Create -> verify -> check | dns_verified=true, verified_at set |
| WF-002 | SSL provisioning workflow | Verify -> provision SSL -> check | ssl_provisioned=true |
| WF-003 | Full domain lifecycle | Create -> verify -> SSL -> delete | All steps succeed |
| WF-004 | Re-verification | Verify -> unverify -> re-verify | verified_at updated |

## Mock Data

### Sample Domain Data

```json
{
  "account_id": 1,
  "domain": "analytics.agency.com",
  "dns_verified": true,
  "ssl_provisioned": true,
  "verified_at": "2026-03-01T10:00:00Z"
}
```

### Factory States

```php
// Default state
CustomDomain::factory()->create();

// Verified domain
CustomDomain::factory()->verified()->create();

// With SSL
CustomDomain::factory()->withSsl()->create();

// Pending (not verified)
CustomDomain::factory()->pending()->create();

// For specific account
CustomDomain::factory()->forAccount($account)->create();
```

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Schema | 100% | 8 tests |
| Foreign Keys | 100% | 4 tests |
| Indexes | 100% | 2 tests |
| Model | 100% | 11 tests |
| Repository | 100% | 9 tests |
| Edge Cases | 100% | 8 tests |
| Security | 100% | 3 tests |
| Workflows | 100% | 4 tests |
| **Total** | **100%** | **49 tests** |

## Test Execution

```bash
# Run all tests for TASK-15-2
cd backend
php artisan test --filter "CustomDomain"

# Run specific test file
php artisan test tests/Feature/Migrations/CreateCustomDomainsTableTest.php
php artisan test tests/Unit/Models/CustomDomainTest.php
php artisan test tests/Unit/Repositories/CustomDomainRepositoryTest.php

# Run with coverage
php artisan test --filter "CustomDomain" --coverage
```

## Dependencies

- Laravel 10+
- PHPUnit 10+
- MySQL 8.0+ or SQLite for testing
- Account model and table (exists)

## Notes for Developer

1. **Migration file** should be named: `2026_03_01_XXXXXX_create_custom_domains_table.php`
2. **Model** should be at: `app/Models/CustomDomain.php`
3. **Repository** should be at: `app/Repositories/CustomDomainRepository.php`
4. **Factory** is provided in tests for test data generation
5. Domain validation (DNS, SSL) is application layer - tests verify storage only
6. Account relationship must be defined on both sides (Account hasMany CustomDomain)

---

## Comments

### [2026-03-01 10:55] Agent: PreTester
Created test documentation for TASK-15-2. Tests cover migration schema, model relationships, repository operations, and edge cases for the custom_domains table.
