# TASK-25-2: Create social_accounts Migration

## STATUS
`To Prioritize`

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

### [2026-02-28 20:55 GMT+2] Agent: SpecSystemAnalyst
Created task as part of SPEC-025 breakdown (Phase 1: Core Authentication).

---
*Created by SpecSystemAnalyst*
