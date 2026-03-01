# TASK-15-2: Create Custom Domains Table

## STATUS
`To Develop`

## PRIORITY
`Medium`

## RISK
`Medium`

## Description
Create the `custom_domains` table to track custom domains set for white-label reselling.

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

## Acceptance Criteria
- [ ] Migration creates `custom_domains` table.
- [ ] Domain uniqueness is enforced.
- [ ] Test DNS verification and SSL provisioning workflows.

## Effort Estimate
3 hours.

---

## Comments
### [2026-02-20 17:49] Agent: SpecSystemAnalyst
Created task for custom domain tracking.

### [2026-02-28 15:38] Agent: ProjectManager
**Prioritization Assessment:**
- **Priority: Medium** - Foundation task (database schema) for white-label feature
- **Risk: Medium** - Non-destructive migration, but includes SSL provisioning workflow considerations
- **Feature Context:** SPEC-015 (White-Label Customization) - Low priority feature for agencies/resellers
- **Dependencies:** None - independent table from TASK-15-1 (brand_configs)
- **Parallel Execution:** Can be developed in parallel with TASK-15-1 since tables are independent
- **Phase Note:** Custom domains marked as Phase 2 in SPEC-015 rollout, but task exists for implementation
- **Handoff:** Ready for PreTester to design tests for migration, uniqueness constraints, and DNS/SSL workflows

### [2026-03-01 10:55] Agent: PreTester
**Tests Complete - Ready for Developer**

Created comprehensive test suite for TASK-15-2:

**Test Files:**
1. `TESTS/TEST-15-2.md` - Test documentation with 49 test scenarios
2. `backend/tests/Feature/Migrations/CreateCustomDomainsTableTest.php` - 29 tests covering:
   - Schema structure (8 tests)
   - Foreign key constraints (4 tests)
   - Indexes (2 tests)
   - Migration rollback (2 tests)
   - Security (3 tests)
   - Edge cases (8 tests)
3. `backend/tests/Unit/Models/CustomDomainTest.php` - 25 tests covering:
   - Model creation (6 tests)
   - Relationships (3 tests)
   - Scopes (5 tests)
   - Accessor/Methods (5 tests)
   - Edge cases (4 tests)
   - Security (2 tests)
   - Workflows (4 tests)
4. `backend/tests/Unit/Repositories/CustomDomainRepositoryTest.php` - 28 tests covering:
   - CRUD operations (8 tests)
   - Query methods (9 tests)
   - Edge cases (6 tests)
   - Performance (3 tests)
   - Integration (4 tests)
5. `backend/database/factories/CustomDomainFactory.php` - Factory with 10 states:
   - Default, forAccount, verified, pending, withSsl, withoutSsl
   - fullyConfigured, withDomain, verifiedAt, unicodeDomain, subdomain, longDomain

**Key Test Coverage:**
- Domain uniqueness constraint (SC-008)
- Foreign key cascade delete (FK-003)
- DNS verification workflow (WF-001)
- SSL provisioning workflow (WF-002)
- Full domain lifecycle (WF-003)
- Security: SQL injection, XSS, path traversal

**Notes for Developer:**
- Migration should include unique index on `domain` column
- Model needs `belongsTo` relationship to Account
- Account model needs `hasMany` relationship to CustomDomain
- Repository methods needed: create, find, findByDomain, findByAccount, update, delete, findVerifiedByAccount, exists, getPrimaryDomain, findSslReadyByAccount
- Model scopes needed: verified(), sslReady(), pending(), forAccount(), byDomain()

**Status Updated:** `To Write Tests` → `To Develop`