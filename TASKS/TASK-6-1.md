# TASK-6-1: Database Schema - API Keys Table

## STATUS
`To Document`

### [2026-03-17 14:55 GMT+2] ProdTester: Production Testing Complete ✓
- **Backend Tests:** 64 tests, 124 assertions - ALL PASS (15.26s)
  - CreateApiKeysTableTest: 23 tests - Table structure, foreign keys, indexes
  - ApiKeyModelTest: 41 tests - Model behavior, scopes, helper methods
- **Production Smoke:** Homepage + Login page loaded ✓
- **Deployment Verified:** `20260317T115038Z-0a40ead0` (commit `a8277fc6672c`)
- All acceptance criteria verified:
  - Migration file created ✓
  - Table created with all columns ✓
  - All indexes created (user_id, key_prefix, revoked_at) ✓
  - Foreign key constraint working (CASCADE DELETE) ✓
  - Can insert/retrieve API keys ✓
- Approved for documentation

### [2026-03-17 14:55 GMT+2] DevOps: Deployment Complete ✓
- **Deployment ID:** 20260317T115038Z-0a40ead0
- **Commit:** ab24c302
- **Status:** success
- **Deployed at:** 2026-03-17T11:54:44 UTC
- API Keys schema and model deployed to production

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
- [x] Migration file created
- [x] Table created with all columns
- [x] All indexes created
- [x] Foreign key constraint working
- [x] Can insert/retrieve API keys

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

### [2026-02-28 08:25] PreTester: Tests Completed
- Created TEST-6-1.md with 51 test scenarios
- Created CreateApiKeysTableTest.php (24 tests) - Migration structure tests
- Created ApiKeyModelTest.php (40 tests) - Model behavior tests
- All 64 tests passing (124 assertions)
- Migration file already exists: `2026_02_27_142500_create_api_keys_table.php`
- Model already exists: `app/Models/ApiKey.php`
- Factory already exists: `ApiKeyFactory.php` with states (revoked, expired, etc.)
- Test coverage: Table structure, foreign keys, unique constraints, indexes, model behavior, edge cases, security

### [2026-03-16 19:45] Developer: Implementation Verified
- Verified migration file exists: `backend/database/migrations/2026_02_27_142500_create_api_keys_table.php`
- Verified model exists: `backend/app/Models/ApiKey.php` with all required methods
- Ran test suite: 64 tests passed (124 assertions)
- All acceptance criteria verified:
  - Migration file created ✓
  - Table created with all columns ✓
  - All indexes created (user_id, key_prefix, revoked_at) ✓
  - Foreign key constraint working (CASCADE DELETE) ✓
  - Can insert/retrieve API keys ✓
- Status: Ready for LocalTester

### [2026-03-17 11:52] LocalTester: Local Testing Complete ✓
- **CreateApiKeysTableTest**: 23 tests, 52 assertions - ALL PASS (17.51s)
  - Table existence, required columns, foreign keys
  - Unique constraints (key_hash), indexes
  - JSON permissions storage, nullable fields
  - Default rate limit, boundary values
  - Timestamps cast correctly, multiple keys per user
  - Security: SQL injection, XSS in name field
- **ApiKeyModelTest**: 41 tests, 72 assertions - ALL PASS (16.50s)
  - Model creation, mass assignment, attribute casting
  - Relationships, scopes, helper methods
  - Factory states for testing
- **Total**: 64 tests, 124 assertions - ALL PASSING
- All acceptance criteria verified ✓
- Approved for deployment

### [2026-03-17 11:51] LocalTester: Local Testing Complete ✓
- **CreateApiKeysTableTest**: 23 tests, 52 assertions - ALL PASS (17.51s)
  - Table structure verification (columns, types, defaults)
  - Foreign key constraint to users with CASCADE DELETE
  - Unique constraint on key_hash
  - Indexes exist (user_id, key_prefix, revoked_at)
  - Permissions stored as JSON, nullable
  - Rate limit defaults and boundaries
  - Timestamps cast correctly
  - Multiple keys per user supported
  - Max length validation (name, key_hash, key_prefix)
  - Security: SQL injection and XSS prevention
- **ApiKeyModelTest**: 41 tests, 72 assertions - ALL PASS (16.50s)
  - Model creation, mass assignment, attribute casting
  - Relationships (user), scopes (active, revoked, expired, byStatus)
  - Helper methods (isRevoked, isExpired, isActive, markAsUsed, revoke)
  - Factory states (active, revoked, expired, withPermissions, withRateLimit, forUser, recentlyUsed)
  - Security: key hash hidden from JSON, user deletion cascades
- **Total**: 64 tests, 124 assertions - ALL PASSING
- All acceptance criteria verified
- Approved for deployment

### [2026-03-17 12:15] DevOps: Starting Deployment
- Status changed to In Deployment
- Running deploy-prod.ps1 to deploy to production

### [2026-03-17 11:45] LocalTester: Local Testing Complete ✓
- **CreateApiKeysTableTest**: 23 tests, 52 assertions - ALL PASS (17.51s)
  - Table existence verification
  - Required columns: id, user_id, name, key_hash, key_prefix, permissions, rate_limit, last_used_at, expires_at, created_at, revoked_at
  - Foreign key constraint to users table (CASCADE DELETE working)
  - Unique constraint on key_hash
  - Indexes: idx_api_keys_user, idx_api_keys_prefix, idx_api_keys_revoked
  - JSON storage for permissions (nullable, empty array supported)
  - Default rate_limit: 60 requests/minute
  - Nullable fields: permissions, last_used_at, expires_at, revoked_at
  - Timestamp casts to Carbon
  - Multiple keys per user supported
  - Security: SQL injection and XSS prevention verified
- **Total**: 23 tests, 52 assertions - ALL PASSING
- All acceptance criteria verified
- Approved for deployment
