# TASK-6-2: API Key Model and Repository Tests

## Task Reference
- **Task:** [TASK-6-2](../TASKS/TASK-6-2.md)
- **Feature:** [FEAT-006](../FEATURES/FEAT-006.md) - API Access & Webhooks
- **Specification:** [SPEC-006](../SPECIFICATIONS/SPEC-006.md)

---

## Overview

Tests for the `ApiKey` model and `ApiKeyRepository` class. The model represents API key entities stored in the `api_keys` table, while the repository provides type-safe CRUD operations for database access. This is a foundation layer that will be used by the API key service and authentication middleware.

---

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `backend/tests/Unit/Models/ApiKeyTest.php` | Unit | Model tests for ApiKey entity |
| `backend/tests/Unit/Repositories/ApiKeyRepositoryTest.php` | Unit | Repository CRUD operation tests |

---

## Model: ApiKey

### Expected Model Interface

```php
/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $key_hash
 * @property string $key_prefix
 * @property array|null $permissions
 * @property int $rate_limit
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $revoked_at
 */
```

### Model Test Scenarios

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| M-001 | Create model with required fields | user_id, name, key_hash, key_prefix | Model created successfully |
| M-002 | Create model with all fields | All fields including optional | Model created with all values |
| M-003 | Create model with minimal fields | user_id, name, key_hash, key_prefix | Defaults: rate_limit=60, permissions=null |
| M-004 | Default rate_limit value | Create without rate_limit | rate_limit = 60 |
| M-005 | Permissions cast to array | permissions JSON string | Returns as array |
| M-006 | Timestamps auto-managed | Create model | created_at, updated_at set |
| M-007 | Relationship to User | $apiKey->user | Returns User model |
| M-008 | Key hash is unique | Duplicate key_hash | Database error (unique constraint) |
| M-009 | Key prefix is indexed | Query by prefix | Uses index (explain query) |
| M-010 | Revoked at is nullable | Create without revoked_at | revoked_at = null |
| M-011 | Expires at is nullable | Create without expires_at | expires_at = null |
| M-012 | Last used at is nullable | Create without last_used_at | last_used_at = null |
| M-013 | Permissions default null | Create without permissions | permissions = null |
| M-014 | Permissions as empty array | permissions = [] | Stored as [] |
| M-015 | Permissions with values | permissions = ["keywords:read", "keywords:write"] | Stored as array |
| M-016 | User cascade delete | Delete user | API key deleted (CASCADE) |
| M-017 | Model hidden fields | Model serialization | key_hash not visible |
| M-018 | isRevoked helper method | revoked_at set | isRevoked() returns true |
| M-019 | isExpired helper method | expires_at in past | isExpired() returns true |
| M-020 | isValid helper method | not revoked, not expired | isValid() returns true |
| M-021 | hasPermission helper | Check permission | hasPermission('keywords:read') returns bool |
| M-022 | touchLastUsed method | Call touchLastUsed() | last_used_at updated |
| M-023 | Generate key prefix | Generate from full key | Returns first 20 chars |
| M-024 | Key prefix length | key_prefix = "sk_live_abc123" | 20 characters |

---

## Repository: ApiKeyRepository

### Repository Methods

```php
public function create(array $data): ApiKey
public function findById(int $id): ?ApiKey
public function findByHash(string $keyHash): ?ApiKey
public function findByPrefix(string $prefix): Collection
public function findByUserId(int $userId): Collection
public function update(int $id, array $data): ?ApiKey
public function revoke(int $id): bool
public function delete(int $id): bool
```

### Create Tests (CR)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CR-001 | Create with all required fields | user_id, name, key_hash, key_prefix | ApiKey returned with id |
| CR-002 | Create with permissions | user_id, name, key_hash, key_prefix, permissions | Permissions stored as JSON |
| CR-003 | Create with rate_limit | user_id, name, key_hash, key_prefix, rate_limit | rate_limit set to custom value |
| CR-004 | Create with expires_at | user_id, name, key_hash, key_prefix, expires_at | expires_at stored |
| CR-005 | Create with duplicate key_hash | Existing key_hash | Exception thrown (unique constraint) |
| CR-006 | Create without user_id | name, key_hash, key_prefix | Database error (NOT NULL) |
| CR-007 | Create without name | user_id, key_hash, key_prefix | Database error (NOT NULL) |
| CR-008 | Create without key_hash | user_id, name, key_prefix | Database error (NOT NULL) |
| CR-009 | Create without key_prefix | user_id, name, key_hash | Database error (NOT NULL) |
| CR-010 | Create returns model instance | Valid data | Instance of ApiKey |
| CR-011 | Create with empty name | name="" | Database error or stored (check validation) |
| CR-012 | Create with null permissions | permissions=null | Stored as NULL |
| CR-013 | Create with empty permissions array | permissions=[] | Stored as [] |
| CR-014 | Create rate_limit default | Without rate_limit | rate_limit = 60 |
| CR-015 | Create with non-existent user_id | user_id=999999 | Foreign key error (if enforced) |
| CR-016 | Create with string rate_limit | rate_limit="100" | Cast to integer or error |
| CR-017 | Create with negative rate_limit | rate_limit=-1 | Stored or validation error |
| CR-018 | Create with zero rate_limit | rate_limit=0 | Stored (means blocked) |
| CR-019 | Create with very high rate_limit | rate_limit=1000000 | Stored successfully |
| CR-020 | Create with expires_at in past | expires_at=yesterday | Stored (validation at service level) |

---

### FindById Tests (FI)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FI-001 | Find existing by ID | Valid id | ApiKey model returned |
| FI-002 | Find non-existent by ID | id=999999 | null returned |
| FI-003 | Find by ID after creation | Create then find | Same data returned |
| FI-004 | Find revoked key by ID | Revoked key id | ApiKey returned (revoked_at set) |
| FI-005 | Find expired key by ID | Expired key id | ApiKey returned (expires_at in past) |
| FI-006 | Find by ID returns full model | Valid id | All fields populated |
| FI-007 | Find by ID zero | id=0 | null returned |
| FI-008 | Find by negative ID | id=-1 | null returned |
| FI-009 | Find by ID with string | id="1" | Cast to int or error |

---

### FindByHash Tests (FH)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FH-001 | Find by valid hash | Existing key_hash | ApiKey returned |
| FH-002 | Find by non-existent hash | Invalid hash | null returned |
| FH-003 | Find by hash case-sensitive | Wrong case | null returned |
| FH-004 | Find by empty hash | hash="" | null returned |
| FH-005 | Find revoked key by hash | Revoked key hash | ApiKey returned |
| FH-006 | Find non-revoked key by hash | Active key hash | ApiKey returned |
| FH-007 | Hash lookup uses index | Valid hash | Indexed query (check explain) |
| FH-008 | Find by hash returns key data | Valid hash | Includes user_id, permissions, rate_limit |
| FH-009 | Hash with special chars | hash with special chars | Handled correctly |
| FH-010 | Hash with whitespace | hash=" abc " | Trimmed or exact match |
| FH-011 | Hash length validation | hash too short | Stored/retrieved correctly |
| FH-012 | Hash is SHA-256 format | Valid SHA-256 hash | 64 character hex string |

---

### FindByPrefix Tests (FP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FP-001 | Find by valid prefix | Existing prefix | Collection with matches |
| FP-002 | Find by non-existent prefix | Invalid prefix | Empty collection |
| FP-003 | Find by empty prefix | prefix="" | Empty collection |
| FP-004 | Multiple keys with same prefix | Duplicate prefix | Multiple results |
| FP-005 | Prefix lookup uses index | Valid prefix | Indexed query |
| FP-006 | Find by partial prefix | First 8 chars | Returns matches |
| FP-007 | Find by full prefix | Complete prefix | Exact match |
| FP-008 | Prefix case-sensitive | Wrong case | Empty collection |
| FP-009 | Prefix returns revoked keys | Revoked key prefix | Includes revoked keys |
| FP-010 | Prefix returns all statuses | Mixed revoked/active | All returned |
| FP-011 | Prefix returns collection | Valid prefix | Illuminate\Support\Collection |
| FP-012 | Prefix with special chars | prefix with special chars | Handled correctly |

---

### FindByUserId Tests (FU)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FU-001 | Find by valid user_id | Existing user | Collection of ApiKey |
| FU-002 | Find by non-existent user | user_id=999999 | Empty collection |
| FU-003 | User with no keys | User without keys | Empty collection |
| FU-004 | User with multiple keys | User with 5 keys | Collection with 5 items |
| FU-005 | Returns all keys for user | Mixed revoked/active | All keys returned |
| FU-006 | Ordered by created_at desc | Multiple keys | Newest first |
| FU-007 | FindByUserId uses index | Valid user_id | Indexed query |
| FU-008 | Returns collection instance | Valid user_id | Illuminate\Support\Collection |
| FU-009 | Each item is ApiKey model | Valid user_id | All items are ApiKey instances |
| FU-010 | Returns revoked keys | User with revoked keys | Includes revoked |
| FU-011 | Returns expired keys | User with expired keys | Includes expired |

---

### Update Tests (UP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| UP-001 | Update name | id, name="New Name" | Updated ApiKey returned |
| UP-002 | Update permissions | id, permissions=["admin"] | Updated ApiKey returned |
| UP-003 | Update rate_limit | id, rate_limit=120 | Updated ApiKey returned |
| UP-004 | Update last_used_at | id, last_used_at=now() | Updated ApiKey returned |
| UP-005 | Update multiple fields | id, name, permissions, rate_limit | All fields updated |
| UP-006 | Update non-existent ID | id=999999 | null returned |
| UP-007 | Update revoked_at | id, revoked_at=now() | Updated (key revoked) |
| UP-008 | Update key_hash not allowed | id, key_hash=new_hash | Error or ignored |
| UP-009 | Update user_id not allowed | id, user_id=new_user | Error or ignored |
| UP-010 | Update returns fresh model | id, name | Updated_at changed |
| UP-011 | Update with empty array | id, [] | No changes, model returned |
| UP-012 | Update permissions to null | id, permissions=null | Permissions cleared |
| UP-013 | Update permissions to empty | id, permissions=[] | Permissions empty array |
| UP-014 | Update rate_limit to zero | id, rate_limit=0 | Stored (blocked) |
| UP-015 | Update with negative rate_limit | id, rate_limit=-1 | Error or stored |
| UP-016 | Touch updated_at | Update any field | updated_at updated |

---

### Revoke Tests (RV)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RV-001 | Revoke active key | Valid id | true, revoked_at set |
| RV-002 | Revoke already revoked key | Revoked key id | true (idempotent) |
| RV-003 | Revoke non-existent key | id=999999 | false returned |
| RV-004 | Revoke sets revoked_at | Valid id | revoked_at = now() |
| RV-005 | Revoke does not delete | Valid id | Record still exists |
| RV-006 | Revoke preserves other fields | Valid id | Only revoked_at changed |
| RV-007 | Revoke prevents API access | Revoked key | findByHash still returns it |
| RV-008 | Revoke by user_id cascade | User deleted | Keys revoked (or deleted) |
| RV-009 | Revoke returns boolean | Valid id | true returned |
| RV-010 | Revoke with id zero | id=0 | false returned |
| RV-011 | Revoke with negative id | id=-1 | false returned |

---

### Delete Tests (DL)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DL-001 | Delete existing key | Valid id | true returned |
| DL-002 | Delete non-existent key | id=999999 | false returned |
| DL-003 | Delete removes from database | Valid id | findById returns null |
| DL-004 | Delete revoked key | Revoked key id | true returned |
| DL-005 | Delete with related logs | Key with logs | Cascade or error (check schema) |
| DL-006 | Delete returns boolean | Valid id | true returned |
| DL-007 | Delete by id zero | id=0 | false returned |
| DL-008 | Delete by negative id | id=-1 | false returned |
| DL-009 | Delete is permanent | Delete then find | Not found |
| DL-010 | User delete cascades | Delete user | Keys deleted (CASCADE) |

---

## Edge Case Tests (EC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Very long name (255 chars) | name with 255 chars | Stored successfully |
| EC-002 | Name exceeds max length | name with 256 chars | Database error or truncated |
| EC-003 | Empty name string | name="" | Database error (NOT NULL) |
| EC-004 | Name with whitespace only | name="   " | Stored or error |
| EC-005 | Name with Unicode | name="API Key 日本語 🗝️" | Stored correctly |
| EC-006 | Name with HTML | name="<script>alert(1)</script>" | Stored as literal |
| EC-007 | Name with SQL injection | name="' OR 1=1--" | Stored as literal |
| EC-008 | Key hash with invalid chars | key_hash with non-hex | Stored or error |
| EC-009 | Key hash too short | key_hash="abc" | Stored or validation error |
| EC-010 | Key prefix too short | key_prefix="sk" | Stored or validation error |
| EC-011 | Permissions with invalid format | permissions="not an array" | Error or cast to array |
| EC-012 | Permissions with deep nesting | permissions=[["nested"]] | Stored as-is or error |
| EC-013 | Permissions with special chars | permissions=["key:read-write"] | Stored correctly |
| EC-014 | Rate limit at boundary | rate_limit=PHP_INT_MAX | Stored or overflow |
| EC-015 | Expires at boundary dates | expires_at=year 9999 | Stored correctly |
| EC-016 | Expires at in past | expires_at=2000-01-01 | Stored (validation at service) |
| EC-017 | Null values for nullable fields | null for last_used_at | Stored as NULL |
| EC-018 | Concurrent updates | Two processes update same key | Last write wins or error |
| EC-019 | Race condition on revoke | Concurrent revoke calls | Idempotent result |

---

## Security Tests (SC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SC-001 | Key hash is never exposed | Model serialization | key_hash in $hidden |
| SC-002 | SQL injection in name | name with SQL | Parameterized query prevents |
| SC-003 | SQL injection in key_hash | hash with SQL | Parameterized query prevents |
| SC-004 | Mass assignment protection | Request with guarded fields | Only fillable updated |
| SC-005 | Key hash is SHA-256 | Create key | 64 char hex string |
| SC-006 | Key prefix doesn't expose full key | key_prefix | Only first 20 chars |
| SC-007 | Revoked keys can't authenticate | Revoked key lookup | Service must check revoked_at |
| SC-008 | Expired keys can't authenticate | Expired key lookup | Service must check expires_at |
| SC-009 | Permissions are validated | Invalid permission | Service must validate |
| SC-010 | User can only see own keys | findByUserId | Only returns user's keys |
| SC-011 | Key prefix for identification only | prefix lookup | Returns all matches (must filter by user) |

---

## Integration Tests (IT)

| ID | Scenario | Actions | Expected Output |
|----|----------|---------|-----------------|
| IT-001 | Full CRUD lifecycle | Create, read, update, delete | All operations succeed |
| IT-002 | Create and find by hash | Create, findByHash | Same key returned |
| IT-003 | Create and find by prefix | Create, findByPrefix | Key in results |
| IT-004 | Create and find by user | Create, findByUserId | Key in results |
| IT-005 | Revoke then find | Revoke, findById | Key exists with revoked_at |
| IT-006 | Update then find | Update name, findById | Updated name returned |
| IT-007 | Multiple keys per user | Create 10 keys | All found by findByUserId |
| IT-008 | User deletion cascade | Delete user, find keys | Keys deleted |
| IT-009 | Key hash uniqueness | Create with same hash twice | Second fails |
| IT-010 | Key prefix multiple matches | Same prefix twice | Both returned by findByPrefix |
| IT-011 | Touch last_used_at | touchLastUsed(), findById | last_used_at updated |
| IT-012 | IsRevoked check | Revoke, isRevoked() | Returns true |
| IT-013 | IsExpired check | Expire, isExpired() | Returns true |
| IT-014 | IsValid check | Active key, isValid() | Returns true |
| IT-015 | HasPermission check | Set permissions, hasPermission() | Returns correct bool |

---

## Mock Data

### Sample ApiKey Data

```php
[
    'user_id' => 1,
    'name' => 'Production API Key',
    'key_hash' => hash('sha256', 'sk_live_abc123def456'),
    'key_prefix' => 'sk_live_abc123',
    'permissions' => ['keywords:read', 'keywords:write', 'sites:read'],
    'rate_limit' => 300,
    'expires_at' => now()->addYear(),
]
```

### Sample Create Request

```php
[
    'user_id' => 1,
    'name' => 'New API Key',
    'key_hash' => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
    'key_prefix' => 'sk_live_test123',
]
```

### Sample ApiKey Model JSON

```json
{
    "id": 1,
    "user_id": 1,
    "name": "Production API Key",
    "key_prefix": "sk_live_abc123",
    "permissions": ["keywords:read", "keywords:write", "sites:read"],
    "rate_limit": 300,
    "last_used_at": "2026-02-27T10:00:00Z",
    "expires_at": "2027-02-27T00:00:00Z",
    "created_at": "2026-02-27T10:00:00Z",
    "updated_at": "2026-02-27T10:00:00Z",
    "revoked_at": null
}
```

---

## Coverage Targets

| Category | Target |
|----------|--------|
| Model Tests | 100% |
| Create Tests | 100% |
| FindById Tests | 100% |
| FindByHash Tests | 100% |
| FindByPrefix Tests | 100% |
| FindByUserId Tests | 100% |
| Update Tests | 100% |
| Revoke Tests | 100% |
| Delete Tests | 100% |
| Edge Cases | 100% |
| Security | 100% |
| Integration | 100% |

---

## Test Execution

```bash
# Run all API key tests
php artisan test --filter=ApiKeyTest
php artisan test --filter=ApiKeyRepositoryTest

# Run with coverage
php artisan test --filter=ApiKey --coverage

# Run specific test
php artisan test --filter=test_create_api_key_with_valid_data
```

---

## Dependencies

- TASK-6-1 (Database schema - api_keys table) - Required for database table
- User model (existing) - For relationship

---

## Notes for Developer

1. **Key Hash Security:** The `key_hash` field must be in the `$hidden` array to prevent accidental exposure in API responses or logs.

2. **Key Prefix Purpose:** The `key_prefix` (first 20 chars of key) is for identification only - it allows listing keys by a recognizable prefix without exposing the actual key or its hash.

3. **Permissions Format:** Permissions should be stored as a JSON array of strings. Recommended format: `["resource:action"]` (e.g., `"keywords:read"`).

4. **Rate Limit Default:** If not specified, rate_limit should default to 60 requests per minute.

5. **Revoked vs Deleted:** 
   - `revoke()` sets `revoked_at` timestamp (soft block, preserves history)
   - `delete()` permanently removes the record

6. **Helper Methods:** Implement on the model:
   - `isRevoked(): bool` - Check if revoked_at is set
   - `isExpired(): bool` - Check if expires_at is in the past
   - `isValid(): bool` - Check if not revoked and not expired
   - `hasPermission(string $permission): bool` - Check if permission is in array
   - `touchLastUsed(): void` - Update last_used_at to now

7. **Relationship:** Each ApiKey belongs to one User. Implement `user()` relationship.

8. **Foreign Key:** The `user_id` foreign key should cascade on delete (if user is deleted, their API keys are also deleted).

9. **Indexes:** Ensure the following indexes exist (from TASK-6-1):
   - `idx_api_keys_user` on `user_id`
   - `idx_api_keys_prefix` on `key_prefix`
   - `idx_api_keys_revoked` on `revoked_at`

10. **Type Safety:** All repository methods should have proper type hints for parameters and return types.

---

## Comments

### [2026-02-27 13:55] PreTester: Test Documentation Created
- Created TESTS/TASK-6-2.md with comprehensive test scenarios
- 120+ test scenarios covering model, repository CRUD, edge cases, and security
- Following project test documentation format
- Status: Tests Coverage In Progress
