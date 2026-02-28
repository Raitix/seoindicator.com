# TEST-6-1: Database Schema - API Keys Table

## Task Reference
- Task: [TASK-6-1](../TASKS/TASK-6-1.md)
- Spec: [SPEC-006](../SPECIFICATIONS/SPEC-006.md)
- Feature: [FEAT-006](../FEATURES/FEAT-006.md)

---

## Overview
Test suite for `api_keys` database table migration. Verifies table structure, columns, constraints, indexes, and foreign key relationships.

### Table Schema
```sql
CREATE TABLE api_keys (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    key_hash VARCHAR(64) NOT NULL UNIQUE,
    key_prefix VARCHAR(20) NOT NULL,
    permissions JSON NULL,
    rate_limit INT UNSIGNED DEFAULT 60,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    revoked_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Indexes
- `idx_api_keys_user` on `user_id`
- `idx_api_keys_prefix` on `key_prefix`
- `idx_api_keys_revoked` on `revoked_at`

---

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `backend/tests/Feature/Migrations/CreateApiKeysTableTest.php` | Feature | Migration structure tests |
| `backend/tests/Unit/Models/ApiKeyModelTest.php` | Unit | Model behavior tests |

---

## Test Scenarios

### Table Structure Tests (TS-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TS-01 | Table exists | N/A | `api_keys` table exists |
| TS-02 | Primary key | N/A | `id` is BIGINT UNSIGNED AUTO_INCREMENT |
| TS-03 | All columns exist | N/A | All 12 columns present |
| TS-04 | Column types correct | N/A | Each column has correct type |
| TS-05 | Nullable columns | N/A | permissions, last_used_at, expires_at, revoked_at nullable |
| TS-06 | Non-nullable columns | N/A | user_id, name, key_hash, key_prefix NOT NULL |
| TS-07 | Default values | N/A | rate_limit defaults to 60 |

### Foreign Key Tests (FK-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FK-01 | Foreign key to users | Insert with user_id | Links to user |
| FK-02 | Cascade delete | Delete user | API keys deleted |
| FK-03 | Invalid user_id | Non-existent user_id | FK constraint error |
| FK-04 | Relationship method | $apiKey->user | Returns User model |

### Unique Constraint Tests (UC-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| UC-01 | Unique key_hash | Same key_hash twice | QueryException |
| UC-02 | Different key_hash | Different key_hash | Both inserted |
| UC-03 | key_prefix not unique | Same prefix twice | Both inserted |

### Index Tests (IX-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IX-01 | User index | Check indexes | idx on user_id |
| IX-02 | Prefix index | Check indexes | idx on key_prefix |
| IX-03 | Revoked index | Check indexes | idx on revoked_at |
| IX-04 | Unique index on key_hash | Check indexes | UNIQUE idx on key_hash |

### Model Tests (MD-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MD-01 | Create API key | Valid data | Record created |
| MD-02 | Mass assignment | Fillable fields | All fillable fields set |
| MD-03 | Hidden key_hash | JSON serialization | key_hash not exposed |
| MD-04 | Permissions cast to array | JSON permissions | Returns array |
| MD-05 | Rate limit cast to int | Integer rate_limit | Returns int |
| MD-06 | Timestamp casts | DateTime fields | Returns Carbon instances |
| MD-07 | User relationship | $apiKey->user | Returns User model |
| MD-08 | isRevoked() method | revoked_at set | Returns true |
| MD-09 | isExpired() method | expires_at in past | Returns true |
| MD-10 | isActive() method | Not revoked, not expired | Returns true |
| MD-11 | markAsUsed() method | Call method | last_used_at updated |
| MD-12 | revoke() method | Call method | revoked_at set |
| MD-13 | scopeActive | Query with scope | Only active keys |
| MD-14 | scopeRevoked | Query with scope | Only revoked keys |
| MD-15 | scopeExpired | Query with scope | Only expired keys |

### Edge Cases (EC-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-01 | Empty permissions | permissions: null | Stored as null |
| EC-02 | Empty permissions array | permissions: [] | Stored as empty array |
| EC-03 | Multiple permissions | permissions: ['a', 'b'] | Stored correctly |
| EC-04 | Rate limit boundary | rate_limit: 1 | Stored correctly |
| EC-05 | Rate limit boundary | rate_limit: 10000 | Stored correctly |
| EC-06 | Long name | 255 char name | Stored correctly |
| EC-07 | key_hash length | 64 char hash | Stored correctly |
| EC-08 | key_prefix length | 20 char prefix | Stored correctly |
| EC-09 | Null last_used_at | Never used | Stored as null |
| EC-10 | Future expires_at | Future date | Stored correctly |
| EC-11 | Past revoked_at | Past date | Stored correctly |

### Security Tests (SEC-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SEC-01 | key_hash not exposed | JSON encode | key_hash hidden |
| SEC-02 | SQL injection in name | Malicious string | Stored safely |
| SEC-03 | SQL injection in permissions | Malicious JSON | Stored safely |
| SEC-04 | XSS in name | Script tag | Stored as-is (sanitized on output) |

### Integration Tests (IT-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IT-01 | User hasMany API keys | Create multiple keys | User->apiKeys returns collection |
| IT-02 | Delete user cascades | User with API keys | All API keys deleted |
| IT-03 | Multiple API keys per user | Create 5 keys | All linked to user |

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Table Structure | 100% | 7 |
| Foreign Keys | 100% | 4 |
| Unique Constraints | 100% | 3 |
| Indexes | 100% | 4 |
| Model Behavior | 100% | 15 |
| Edge Cases | 100% | 11 |
| Security | 100% | 4 |
| Integration | 100% | 3 |
| **Total** | **100%** | **51** |

**Actual:** 64 tests, 124 assertions (all passing)

---

## Test Execution

```bash
# Run all API Keys table tests
php artisan test --filter=CreateApiKeysTableTest
php artisan test --filter=ApiKeyModelTest

# Run with coverage
php artisan test --filter="CreateApiKeysTableTest|ApiKeyModelTest" --coverage

# Run specific test
php artisan test --filter=test_foreign_key_cascade_delete
```

---

## Dependencies

- Users table (existing)
- User model (existing)

---

## Notes for Developer

### Factory Required
`backend/database/factories/ApiKeyFactory.php` with states:
- `active()` - Active key (not revoked, not expired)
- `revoked()` - Revoked key
- `expired()` - Expired key
- `withPermissions(array $perms)` - Custom permissions

### Model Required
`backend/app/Models/ApiKey.php` - Already exists with:
- `belongsTo(User::class)` relationship
- `$hidden = ['key_hash']` to prevent exposure
- `$casts` for permissions (array), timestamps (datetime)

### Key Implementation Details

1. **key_hash**: 64-character SHA-256 hash, NEVER store raw key
2. **key_prefix**: First 20 chars for identification in UI
3. **permissions**: JSON array of permission strings
4. **rate_limit**: Requests per minute (default: 60)
5. **Soft delete NOT used**: revoked_at field for explicit revocation

### Permission Values
From ApiKey model:
- `keywords:read`
- `keywords:write`
- `rankings:read`
- `reports:read`
- `reports:write`
- `webhooks:read`
- `webhooks:write`

---

## Comments

### [2026-02-28 08:23] PreTester: Test Documentation Created
- Created TEST-6-1.md with 51 test scenarios
- Coverage categories: Success, Auth, Authorization, Validation, Edge Cases, Security, Rate Limiting
- Test file: RankingsApiControllerTest.php (to be created)
- Status: Tests Coverage In Progress

### [2026-02-28 08:25] PreTester: Tests Completed
- Created CreateApiKeysTableTest.php (24 tests)
- Created ApiKeyModelTest.php (40 tests)
- All 64 tests passing (124 assertions)
- Migration, Model, and Factory already exist
- Status: Tests Completed
