# TASK-25-2: Create social_accounts Migration

## STATUS
`To Document`

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
- [x] Migration creates `social_accounts` table
- [x] `id` - BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- [x] `user_id` - BIGINT UNSIGNED NOT NULL, foreign key to users
- [x] `provider` - VARCHAR(50) NOT NULL (google, github, etc.)
- [x] `provider_id` - VARCHAR(255) NOT NULL (unique ID from provider)
- [x] `provider_token` - TEXT NULL (encrypted OAuth token)
- [x] `provider_refresh_token` - TEXT NULL (encrypted refresh token)
- [x] `provider_token_expires_at` - TIMESTAMP NULL
- [x] `created_at`, `updated_at` timestamps
- [x] Foreign key cascade delete
- [x] Unique constraint on (provider, provider_id)
- [x] Index on (user_id, provider)
- [x] Migration runs successfully
- [x] Model created with encrypted casts for tokens

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

### [2026-03-01 15:00 GMT+2] Agent: ProdTester
**Production Testing Complete - PASSED**

**Production Verification Results:**

| Check | Status | Details |
|-------|--------|---------|
| Health Check | ✅ PASS | 200 OK, database connected |
| Migration Status | ✅ PASS | Batch 27, status: Ran |
| Table Structure | ✅ PASS | 9 columns, correct types |
| Primary Key | ✅ PASS | id BIGINT UNSIGNED AUTO_INCREMENT |
| Foreign Key | ✅ PASS | user_id → users.id ON DELETE CASCADE |
| Unique Constraint | ✅ PASS | (provider, provider_id) unique |
| Composite Index | ✅ PASS | (user_id, provider) index |
| Model File | ✅ PASS | 3637 bytes, deployed |
| Encrypted Casts | ✅ PASS | provider_token, provider_refresh_token |
| Hidden Attributes | ✅ PASS | Tokens hidden from serialization |
| User Relationship | ✅ PASS | socialAccounts() HasMany |
| Model Scopes | ✅ PASS | byProvider, byUser, hasToken, hasRefreshToken, tokenNotExpired |
| Helper Methods | ✅ PASS | isTokenExpired() method |

**Table Structure Verified:**
```
social_accounts
├── id              bigint(20) unsigned  PRIMARY KEY, autoincrement
├── user_id         bigint(20) unsigned  NOT NULL, FK → users.id CASCADE
├── provider        varchar(50)          NOT NULL
├── provider_id     varchar(255)         NOT NULL
├── provider_token  text                 NULL (encrypted)
├── provider_refresh_token text          NULL (encrypted)
├── provider_token_expires_at timestamp  NULL
├── created_at      timestamp            NULL
└── updated_at      timestamp            NULL

Indexes:
├── PRIMARY: id
├── UNIQUE: (provider, provider_id)
└── INDEX: (user_id, provider)
```

**Local Test Results:**
- Migration Tests: 36 passed (68 assertions)
- Model Tests: 28 passed (51 assertions)
- **Total: 64 tests, 119 assertions - ALL PASSING**

**Decision: PASS** → Ready for documentation

Status updated: `To Document`

### [2026-03-01 15:55 GMT+2] Agent: DevOps
**Deployment Complete - PASSED**

**Deployment Details:**
- GitHub Actions Run: 22543807403
- Commit: 496b0bf (includes social_accounts migration from 96833f5)
- Duration: 2m6s
- Status: SUCCESS

**Migration Executed on Production:**
```
2026_03_01_100000_create_social_accounts_table ............ 120.54ms DONE
```

**Verification:**
- Migration ran successfully on production database
- Configuration cached successfully
- Routes cached successfully
- Health endpoint returning 200

**Handoff:** ProdTester → Validate `social_accounts` table structure and model in production environment.

Status updated: `To Test Prod`

### [2026-03-01 14:43 GMT+2] Agent: LocalTester
**Local Testing Complete - PASSED**

**Test Results:**
- Migration tests: 36 passed (68 assertions)
- Model tests: 28 passed (51 assertions)
- **Total: 64 tests, 119 assertions - ALL PASSING**

**Database Verification:**
- Migration executed successfully (`2026_03_01_100000_create_social_accounts_table`)
- Table structure verified: 9 columns with correct types
- Foreign key `social_accounts_user_id_foreign` → `users.id` ON DELETE CASCADE ✓
- Unique constraint on `(provider, provider_id)` ✓
- Composite index on `(user_id, provider)` ✓
- CHECK constraint `chk_provider_not_empty` ✓

**Adversarial Testing Performed:**
- SQL injection prevention verified (SEC-001, SEC-002)
- Token encryption at rest verified (SEC-003, SEC-004)
- Unique constraint prevents account hijacking (SEC-005)
- Cascade delete verified (FK-003)

**Decision: PASS** → Ready for deployment

Status updated: `To Deploy`

### [2026-03-01 14:35 GMT+2] Agent: Developer
**Implementation Complete**

**Migration Created:**
- `backend/database/migrations/2026_03_01_100000_create_social_accounts_table.php`

**Key Implementation Details:**
- Created migration with all required columns
- Added unique constraint on (provider, provider_id)
- Added composite index on (user_id, provider)
- Added CHECK constraint for non-empty provider (SQLite + MySQL 8.0+)
- Foreign key cascade delete on user_id

**Test Fixes Applied:**
- Fixed test helper to cast SQLite `unique` property to boolean (PHPUnit strict type check)
- SQLite returns 0/1 integers, PHPUnit's assertTrue requires boolean

**Test Results:**
- Migration tests: 36 passed (68 assertions)
- Model tests: 28 passed (51 assertions)
- All acceptance criteria verified

**Files Modified:**
- `backend/tests/Feature/Migrations/CreateSocialAccountsTableTest.php` — Fixed boolean cast for SQLite unique property

Status updated: `To Test Locally`

### [2026-03-01 14:30 GMT+2] Agent: PreTester
**Tests Complete**

**Test Documentation Created:**
- `TESTS/TEST-25-2.md` — 45 test scenarios across 10 categories

**Migration Tests Created:**
- `backend/tests/Feature/Migrations/CreateSocialAccountsTableTest.php`
- 33 tests covering:
  - Schema structure (SC-001 to SC-009)
  - Foreign key constraints (FK-001 to FK-004)
  - Unique constraints (UC-001 to UC-003)
  - Indexes (IX-001 to IX-002)
  - Migration rollback (RB-001 to RB-002)
  - Data validation (DV-001 to DV-006)
  - Edge cases (EC-001 to EC-008)
  - Security (SEC-001 to SEC-002)

**Model Tests Created:**
- `backend/tests/Unit/Models/SocialAccountTest.php`
- 25 tests covering:
  - Model creation with encrypted casts
  - User relationship (belongsTo)
  - Scopes (byProvider, byUser, hasToken, hasRefreshToken, tokenNotExpired)
  - Helper methods (isTokenExpired)
  - Security (SQL injection prevention, token encryption)

**Factory Created:**
- `backend/database/factories/SocialAccountFactory.php`
- States: google, github, facebook, linkedin, forUser, withToken, withoutToken, withRefreshToken, withoutRefreshToken, expiredToken, expiringSoon, validToken, minimal

**Model Created (for testing):**
- `backend/app/Models/SocialAccount.php`
- Encrypted casts for provider_token and provider_refresh_token
- User relationship
- Scopes: byProvider, byUser, hasToken, hasRefreshToken, tokenNotExpired
- Helper: isTokenExpired()

**User Model Updated:**
- Added `socialAccounts()` HasMany relationship

**Key Findings:**
- Encrypted casts ensure token security at rest
- Unique constraint on (provider, provider_id) prevents account hijacking
- Cascade delete on user_id ensures orphan cleanup
- Token expiration handling requires explicit check (no auto-refresh)

Status updated: `To Develop`

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
