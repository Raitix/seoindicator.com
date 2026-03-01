# TASK-25-2: Create social_accounts Migration

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Medium`

## Related
- Specification: [SPEC-025](../SPECIFICATIONS/SPEC-025.md)
- Feature: [FEAT-025](../FEATURES/FEAT-025.md)

---

## Description
Create the `social_accounts` table migration to store OAuth provider connections. This table links user accounts to external OAuth providers (Google, GitHub, etc.).

---

## Acceptance Criteria
- [ ] Migration creates `social_accounts` table
- [ ] `id` - BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- [ ] `user_id` - BIGINT UNSIGNED NOT NULL, foreign key to users
- [ ] `provider` - VARCHAR(50) NOT NULL (google, github, etc.)
- [ ] `provider_id` - VARCHAR(255) NOT NULL (unique ID from provider)
- [ ] `provider_token` - TEXT NULL (encrypted OAuth token)
- [ ] `provider_refresh_token` - TEXT NULL (encrypted refresh token)
- [ ] `provider_token_expires_at` - TIMESTAMP NULL
- [ ] `created_at`, `updated_at` timestamps
- [ ] Foreign key cascade delete
- [ ] Unique constraint on (provider, provider_id)
- [ ] Index on (user_id, provider)
- [ ] Migration runs successfully
- [ ] Model created with encrypted casts for tokens

---

## Dependencies
- TASK-25-1 (Sanctum & Socialite packages)

---

## Effort Estimate
`Small` (1-2 hours)

---

## Technical Notes
```sql
CREATE TABLE social_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    provider VARCHAR(50) NOT NULL,
    provider_id VARCHAR(255) NOT NULL,
    provider_token TEXT NULL,
    provider_refresh_token TEXT NULL,
    provider_token_expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_provider_user (provider, provider_id),
    INDEX idx_user_provider (user_id, provider)
);
```

Model with encrypted casts:
```php
protected $casts = [
    'provider_token' => 'encrypted',
    'provider_refresh_token' => 'encrypted',
];
```

---

## Comments
_Agent communication log_

### [2026-03-01 13:30 GMT+2] Agent: ProjectManager
**Prioritization Analysis:**

**Priority Confirmed: High**
- Foundation task for OAuth provider connections
- Required for Phase 1 (Core Authentication)
- Small effort (1-2 hours), enables TASK-25-6 to 25-9 (Google OAuth)

**Risk Confirmed: Medium**
- Non-destructive migration (add table)
- Encrypted token storage (Laravel `encrypted` cast)
- Foreign key cascade delete for data integrity
- No data migration from existing tables

**Dependency Status:**
- TASK-25-1 (Sanctum & Socialite packages): `In Development` ⚠️
- This task can proceed to test writing now
- Development blocked until TASK-25-1 is `To Test Locally` or `Done`

**Execution Order:**
1. TASK-25-1 (In Development) → Complete first
2. TASK-25-2 (this) → Can be tested/developed after TASK-25-1
3. TASK-25-3, 25-4, 25-5 → Can run in parallel with this task

**Notes for PreTester:**
- Migration creates `social_accounts` table with encrypted casts
- Test: foreign key cascade, unique constraints, token encryption
- Model should use `encrypted` cast for token fields

Status updated: `To Write Tests`

### [2026-02-28 20:55 GMT+2] Agent: SpecSystemAnalyst
Created task as part of SPEC-025 breakdown (Phase 1: Core Authentication).

---
*Created by SpecSystemAnalyst*
