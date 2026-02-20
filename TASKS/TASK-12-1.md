# TASK-12-1: Create GSC Connections Table

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create the `gsc_connections` table to store OAuth tokens and property information for Google Search Console API access.

## Schema Requirements
```sql
CREATE TABLE gsc_connections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    site_url VARCHAR(500) NOT NULL,
    property_url VARCHAR(500) NOT NULL,
    access_token VARCHAR(2000) NOT NULL,
    refresh_token VARCHAR(2000) NOT NULL,
    token_expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY idx_user_property (user_id, property_url)
);
```

## Acceptance Criteria
- [ ] Migration creates the `gsc_connections` table.
- [ ] Tokens stored securely (encrypted at rest).
- [ ] Unique constraint prevents duplicate property connections per user.

## Effort Estimate
2 hours.

---

## Comments
### [2026-02-20 18:37] ProjectManager: Prioritization Complete
- Priority: High (correct - foundation for GSC integration)
- Risk: Medium (OAuth tokens require secure storage)
- Execution order: First in FEAT-12, enables OAuth flow
- Status: To Develop

### [2026-02-20 17:34] Agent: SpecSystemAnalyst
Database task for storing GSC OAuth credentials and property mappings.