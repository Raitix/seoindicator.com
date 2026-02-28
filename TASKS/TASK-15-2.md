# TASK-15-2: Create Custom Domains Table

## STATUS
`To Write Tests`

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