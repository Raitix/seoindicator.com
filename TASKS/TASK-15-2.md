# TASK-15-2: Create Custom Domains Table

## STATUS
`To Prioritize`

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