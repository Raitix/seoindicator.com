# TEST-6-2: API Key Model and Repository Tests

## Task Reference
- Task: [TASK-6-2](../TASKS/TASK-6-2.md)
- Spec: [SPEC-006](../SPECIFICATIONS/SPEC-006.md)
- Feature: [FEAT-006](../FEATURES/FEAT-006.md)

---

## Overview

Tests for the ApiKeyRepository and ApiKey model integration. The repository provides the data access layer for API key operations including CRUD, lookups by hash/prefix, and key lifecycle management.

---

## Test Files

| File | Tests | Assertions | Description |
|------|-------|------------|-------------|
| `tests/Unit/Repositories/ApiKeyRepositoryTest.php` | 56 | 101 | Repository CRUD and query operations |

---

## Test Scenarios

### Category 1: Create Operations (CR)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CR-01 | Create with all fields | Valid data with all fields | ApiKey persisted with all values |
| CR-02 | Create with minimal fields | user_id, name, key_hash, key_prefix | ApiKey created with defaults |
| CR-03 | Create with permissions array | permissions: ['keywords:read', 'rankings:read'] | Permissions stored as JSON |
| CR-04 | Create with custom rate limit | rate_limit: 500 | Rate limit set correctly |
| CR-05 | Create with expiration | expires_at: future date | Expiration date stored |
| CR-06 | Create already revoked | revoked_at: now | Key created as revoked |

### Category 2: Find Operations (FN)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FN-01 | Find by ID - exists | Valid ID | ApiKey returned |
| FN-02 | Find by ID - not exists | Invalid ID | null returned |
| FN-03 | Find by hash - exists | Valid hash | ApiKey returned |
| FN-04 | Find by hash - not exists | Invalid hash | null returned |
| FN-05 | Find by prefix - exists | Valid prefix | ApiKey returned |
| FN-06 | Find by prefix - not exists | Invalid prefix | null returned |
| FN-07 | Find by hash is exact match | Hash of existing key | Exact match, not partial |

### Category 3: User Operations (UO)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| UO-01 | Get for user - multiple keys | User with 3 keys | Paginated list of 3 keys |
| UO-02 | Get for user - no keys | User with no keys | Empty paginated list |
| UO-03 | Get for user - with status filter active | filter: status=active | Only active keys |
| UO-04 | Get for user - with status filter revoked | filter: status=revoked | Only revoked keys |
| UO-05 | Get for user - with status filter expired | filter: status=expired | Only expired keys |
| UO-06 | Get for user - pagination | perPage: 5 | Correct pagination |
| UO-07 | Get for user - ordered by created_at desc | Multiple keys | Most recent first |
| UO-08 | Count for user | User with N keys | Returns N |
| UO-09 | Cross-user isolation | Query user A's ID from user B | No cross-user data |

### Category 4: Update Operations (UP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| UP-01 | Update name | name: 'New Name' | Name updated |
| UP-02 | Update permissions | permissions: ['new:permission'] | Permissions updated |
| UP-03 | Update rate limit | rate_limit: 1000 | Rate limit updated |
| UP-04 | Update last_used_at | last_used_at: now() | Timestamp updated |
| UP-05 | Update expires_at | expires_at: future | Expiration updated |
| UP-06 | Mark as used | markAsUsed() | last_used_at set to now |
| UP-07 | Partial update | Only name changed | Other fields preserved |
| UP-08 | Update returns fresh model | update() | Model reloaded from DB |

### Category 5: Revoke Operations (RV)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RV-01 | Revoke active key | revoke() on active key | revoked_at set |
| RV-02 | Revoke already revoked | revoke() twice | Idempotent, no error |
| RV-03 | Revoke sets timestamp | revoke() | revoked_at is now() |
| RV-04 | Revoked key is not active | isRevoked() check | Returns true |
| RV-05 | Revoke prevents API access | isValidForUse() | Returns false |

### Category 6: Delete Operations (DL)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DL-01 | Delete existing key | Valid key | true, key removed |
| DL-02 | Deleted key not found | find() after delete | null |
| DL-03 | User deletion cascades | Delete user | API keys deleted |

### Category 7: Validation & Status (VS)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| VS-01 | isValidForUse - active key | Active key | true |
| VS-02 | isValidForUse - revoked key | Revoked key | false |
| VS-03 | isValidForUse - expired key | Expired key | false |
| VS-04 | isValidForUse - expiring soon | Key expires tomorrow | true (still valid) |

### Category 8: Edge Cases (EC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-01 | Empty permissions array | [] | Stored correctly |
| EC-02 | Null permissions | null | Stored as null |
| EC-03 | All permissions | All valid permissions | Stored correctly |
| EC-04 | Max rate limit | 10000 | Stored correctly |
| EC-05 | Min rate limit | 1 | Stored correctly |
| EC-06 | Very long key name | 255 chars | Stored correctly |
| EC-07 | Special chars in name | Unicode, emoji | Stored correctly |
| EC-08 | Concurrent operations | Multiple updates | Consistent state |

### Category 9: Security Tests (SC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SC-01 | Hash not exposed in JSON | toJson() | key_hash not in output |
| SC-02 | Hash lookup is exact match | Partial hash | null (not matched) |
| SC-03 | User isolation enforced | Cross-user query | No data leakage |
| SC-04 | SQL injection in hash | Malicious hash string | Safe, no injection |
| SC-05 | SQL injection in prefix | Malicious prefix | Safe, no injection |
| SC-06 | SQL injection in name | Malicious name | Safe, no injection |

---

## Mock Data

### Standard Test API Key
```php
[
    'user_id' => 1,
    'name' => 'Test API Key',
    'key_hash' => hash('sha256', 'sk_live_test_key_123'),
    'key_prefix' => 'sk_live_test_key_',
    'permissions' => ['keywords:read', 'rankings:read'],
    'rate_limit' => 60,
    'last_used_at' => null,
    'expires_at' => null,
    'revoked_at' => null,
]
```

### Test Hash Values
```php
// Test key: sk_live_abc123...
$testKeyHash = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';
$testPrefix = 'sk_live_abc123def456';
```

---

## Coverage Targets

| Component | Target | Notes |
|-----------|--------|-------|
| Repository Methods | 100% | All public methods tested |
| Model Integration | 100% | Repository correctly uses model |
| Edge Cases | 80%+ | Boundary conditions |
| Security | 100% | All security scenarios |

---

## Test Execution

```bash
# Run all ApiKey repository tests
php artisan test --filter=ApiKeyRepositoryTest

# Run with coverage
php artisan test --filter=ApiKeyRepositoryTest --coverage

# Run specific test category
php artisan test --filter="ApiKeyRepositoryTest::test_create"
```

---

## Dependencies

- TASK-6-1 (API Keys Table) - Database schema
- User model (existing)
- ApiKey model (TASK-6-1)
- ApiKeyFactory (TASK-6-1)

---

## Notes for Developer

1. **Repository Pattern**: The repository abstracts database operations. Tests should verify the repository behavior, not just the underlying Eloquent calls.

2. **Hash Security**: Never expose key_hash in responses. Tests should verify this.

3. **User Isolation**: All operations must be scoped to the user. Cross-user access should return no results.

4. **Idempotency**: revoke() should be idempotent - calling it multiple times should not cause errors.

5. **Fresh Models**: update() should return a fresh model instance from the database.

---

## Comments

### [2026-02-28 11:20] PreTester: Test Design Started
- Creating test documentation and repository tests
- Repository already implemented, need test coverage
- Focus on CRUD operations, lookups, and security

### [2026-02-28 11:35] PreTester: Tests Completed
- Created ApiKeyRepositoryTest.php with 56 tests (101 assertions)
- All tests passing
- Coverage: Create (6), Find (7), User Operations (9), Update (8), Revoke (5), Delete (3), Validation (4), Edge Cases (8), Security (6)
- Categories tested: CR, FN, UO, UP, RV, DL, VS, EC, SC
- Fixed 3 initial test failures (rate limit default, SQL injection pre-conditions)
