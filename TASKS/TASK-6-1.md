# TASK-6-1: Database Schema - API Keys Table

## Status
`Tests Coverage In Progress`

## Priority
`High`

## Risk
`Medium`

## Specification
- Spec: [SPEC-006](../SPECIFICATIONS/SPEC-006.md)
- Feature: [FEAT-006](../FEATURES/FEAT-006.md)

---

## Description
Create the `api_keys` database table for storing API key credentials.

## Requirements

### Table Structure
```sql
CREATE TABLE api_keys (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    key_hash TEXT NOT NULL UNIQUE,
    key_prefix TEXT NOT NULL,              -- First 8 chars for identification
    permissions TEXT,                       -- JSON: ["keywords:read", "keywords:write"]
    rate_limit INTEGER DEFAULT 60,         -- Requests per minute
    last_used_at DATETIME,
    expires_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    revoked_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Indexes
- `idx_api_keys_user` on `user_id`
- `idx_api_keys_prefix` on `key_prefix`
- `idx_api_keys_revoked` on `revoked_at`

## Acceptance Criteria
- [ ] Migration file created
- [ ] Table created with all columns
- [ ] All indexes created
- [ ] Foreign key constraint working
- [ ] Can insert/retrieve API keys

## Dependencies
- None (foundation task)

## Estimated Effort
Small (1-2 hours)

---

## Comments

### [2026-02-27 12:25] ProjectManager: Task Created
- Priority: High (foundation task for API access)
- Risk: Medium (new schema, non-destructive)
- Phase 1 of API Access implementation
