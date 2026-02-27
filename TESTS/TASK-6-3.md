# TASK-6-3: API Key Generation and Validation Tests

## Task Reference
- **Task:** [TASK-6-3](../TASKS/TASK-6-3.md)
- **Feature:** [FEAT-006](../FEATURES/FEAT-006.md) - API Access & Webhooks
- **Specification:** [SPEC-006](../SPECIFICATIONS/SPEC-006.md)

---

## Overview

Tests for the `ApiKeyService` responsible for secure API key generation, hashing, and validation. This service provides the core cryptographic operations for API key management, ensuring keys are generated with sufficient entropy, hashed securely for storage, and validated using timing-safe comparisons to prevent timing attacks.

---

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `backend/tests/Unit/Services/ApiKeyServiceTest.php` | Unit | Service tests for key generation and validation |

---

## Service: ApiKeyService

### Expected Service Interface

```php
class ApiKeyService
{
    /**
     * Generate a new API key with hash and prefix.
     */
    public function generate(): array; // ['key' => string, 'hash' => string, 'prefix' => string]
    
    /**
     * Hash an API key for storage.
     */
    public function hash(string $key): string;
    
    /**
     * Validate an API key against a stored hash (timing-safe).
     */
    public function validate(string $key, string $storedHash): bool;
    
    /**
     * Extract prefix from a key for identification.
     */
    public function getPrefix(string $key): string;
    
    /**
     * Validate key format.
     */
    public function isValidFormat(string $key): bool;
}
```

---

## Key Generation Tests (KG)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| KG-001 | Generate returns array with required keys | generate() | ['key', 'hash', 'prefix'] |
| KG-002 | Generated key has correct prefix | generate() | Key starts with 'sk_live_' |
| KG-003 | Generated key is base64url encoded | generate() | Key contains only [A-Za-z0-9_-] after prefix |
| KG-004 | Generated key has sufficient length | generate() | At least 43 chars after prefix (32 bytes) |
| KG-005 | Generated hash is SHA-256 | generate() | Hash is 64 char hex string |
| KG-006 | Generated hash matches key | generate() | hash() of key equals returned hash |
| KG-007 | Generated prefix is correct length | generate() | 20 characters (sk_live_ + 12 chars) |
| KG-008 | Multiple calls generate unique keys | generate() x 2 | Keys are different |
| KG-009 | Multiple calls generate unique hashes | generate() x 2 | Hashes are different |
| KG-010 | Generated key contains sufficient entropy | generate() | 32 random bytes |
| KG-011 | Key has correct format structure | generate() | sk_live_[base64url] |
| KG-012 | Hash is lowercase hex | generate() | Hash matches /^[a-f0-9]{64}$/ |
| KG-013 | Prefix matches key start | generate() | prefix === key.substring(0, 20) |
| KG-014 | Generate is deterministic for same random | Mock random | Produces expected key |
| KG-015 | Key does not contain padding chars | generate() | No '=' in key after prefix |

---

## Hash Method Tests (HM)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| HM-001 | Hash returns SHA-256 hex string | hash(key) | 64 char lowercase hex |
| HM-002 | Hash is deterministic | hash('same') x 2 | Same result |
| HM-003 | Hash different keys produce different hashes | hash('key1'), hash('key2') | Different hashes |
| HM-004 | Hash handles empty string | hash('') | Valid SHA-256 hash |
| HM-005 | Hash handles special characters | hash('key!@#$%') | Valid SHA-256 hash |
| HM-006 | Hash handles unicode | hash('key日本語') | Valid SHA-256 hash |
| HM-007 | Hash handles very long key | hash(1000 chars) | Valid SHA-256 hash |
| HM-008 | Hash matches generate hash | generate() | hash(key) === returned hash |
| HM-009 | Hash is one-way | hash(key) | Cannot reverse to key |
| HM-010 | Hash output length is constant | hash(any input) | Always 64 chars |

---

## Validation Tests (VL)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| VL-001 | Validate correct key | validate(key, hash) | true |
| VL-002 | Validate incorrect key | validate(wrong_key, hash) | false |
| VL-003 | Validate is timing-safe | validate(key, hash) | Uses timing-safe comparison |
| VL-004 | Validate with empty key | validate('', hash) | false |
| VL-005 | Validate with empty hash | validate(key, '') | false |
| VL-006 | Validate with both empty | validate('', '') | false |
| VL-007 | Validate with null key | validate(null, hash) | false or throws |
| VL-008 | Validate with null hash | validate(key, null) | false or throws |
| VL-009 | Validate similar keys | validate('sk_live_abc', hash('sk_live_abd')) | false |
| VL-010 | Validate partial key | validate(partial_key, hash) | false |
| VL-011 | Validate key with extra chars | validate(key + 'x', hash) | false |
| VL-012 | Validate handles different hash formats | validate(key, invalid_hash) | false |
| VL-013 | Validate is case-sensitive | validate(KEY, hash) | false if case differs |
| VL-014 | Validate same execution time for match/no-match | timing test | Similar duration |
| VL-015 | Validate with whitespace | validate(' ' + key + ' ', hash) | false |

---

## Prefix Extraction Tests (PX)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PX-001 | Get prefix from valid key | getPrefix('sk_live_abc123...') | 'sk_live_abc123' (20 chars) |
| PX-002 | Get prefix from short key | getPrefix('sk_live_abc') | Returns available chars |
| PX-003 | Get prefix from key without prefix | getPrefix('invalid_key') | Returns first 20 chars |
| PX-004 | Get prefix from empty string | getPrefix('') | '' or error |
| PX-005 | Get prefix length is consistent | getPrefix(any_key) | At most 20 chars |
| PX-006 | Get prefix matches generate | generate() | prefix matches getPrefix(key) |
| PX-007 | Get prefix preserves original | getPrefix(key) | Key unchanged |
| PX-008 | Get prefix from null | getPrefix(null) | '' or throws |

---

## Format Validation Tests (FV)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FV-001 | Valid format with correct prefix | isValidFormat('sk_live_abc...') | true |
| FV-002 | Invalid format without prefix | isValidFormat('abc123') | false |
| FV-003 | Invalid format with wrong prefix | isValidFormat('pk_live_abc...') | false |
| FV-004 | Invalid format with spaces | isValidFormat('sk_live_ abc') | false |
| FV-005 | Invalid format with special chars | isValidFormat('sk_live_abc!@#') | false (if not base64url) |
| FV-006 | Empty string | isValidFormat('') | false |
| FV-007 | Null input | isValidFormat(null) | false or throws |
| FV-008 | Valid format minimum length | isValidFormat('sk_live_' + 32chars) | true |
| FV-009 | Valid format allows base64url chars | isValidFormat('sk_live_ABCdef123_-') | true |
| FV-010 | Valid format rejects base64 padding | isValidFormat('sk_live_abc=') | false |
| FV-011 | Very long key | isValidFormat(1000 chars) | true (or false if length limited) |
| FV-012 | Key with only prefix | isValidFormat('sk_live_') | false (too short) |
| FV-013 | Test key format | isValidFormat('sk_test_abc...') | Depends on implementation |
| FV-014 | Uppercase prefix rejected | isValidFormat('SK_LIVE_abc...') | false |

---

## Security Tests (SC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SC-001 | Timing attack prevention | Compare validate times | Similar for match/no-match |
| SC-002 | Hash preimage resistance | Given hash, find key | Computationally infeasible |
| SC-003 | Collision resistance | Two keys with same hash | Extremely unlikely |
| SC-004 | Entropy verification | Analyze generated keys | 256 bits of entropy |
| SC-005 | No plaintext storage | Key never stored | Only hash stored |
| SC-006 | Key shown once | generate() | Key returned once, not stored |
| SC-007 | Random generation uses CSPRNG | generate() | Uses crypto-safe random |
| SC-008 | No key in logs | generate() | Key not logged |
| SC-009 | Hash comparison uses constant time | validate() | Uses hash_equals or similar |
| SC-010 | No information leakage | validate(wrong_key) | Same error for all failures |

---

## Edge Case Tests (EC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Generate multiple keys rapidly | generate() x 1000 | All unique |
| EC-002 | Hash same key many times | hash(key) x 1000 | All identical |
| EC-003 | Validate with very long hash | validate(key, 1000 char hash) | false |
| EC-004 | Validate with hex prefix in hash | validate(key, '0x' + hash) | Depends on implementation |
| EC-005 | Key with URL-unsafe chars | Key with '+' or '/' | Should not occur (base64url) |
| EC-006 | Hash with mixed case | validate(key, 'ABCDEF...') | Should handle |
| EC-007 | Key containing null bytes | Key with \0 | Should handle |
| EC-008 | Concurrent generation | Multiple threads generate() | All unique |
| EC-009 | Memory cleanup after generate | generate() | No key in memory after return |
| EC-010 | Prefix collision probability | Generate 1M keys | Prefix collisions rare/none |

---

## Integration Tests (IT)

| ID | Scenario | Actions | Expected Output |
|----|----------|---------|-----------------|
| IT-001 | Full generation and validation cycle | generate() -> validate() | validate returns true |
| IT-002 | Generate, store hash, validate later | generate() -> store hash -> validate | true |
| IT-003 | Wrong key validation fails | generate() -> validate(wrong_key) | false |
| IT-004 | Multiple keys same user | generate() x 5 | All unique, all validatable |
| IT-005 | Key revocation (service level) | generate() -> revoke -> validate | Depends on service |
| IT-006 | Key expiration (service level) | generate() -> expire -> validate | Depends on service |
| IT-007 | Rate limit integration | generate() -> rate limit check | Service integration |
| IT-008 | Database storage flow | generate() -> save hash -> load -> validate | true |
| IT-009 | API endpoint authentication | generate() -> call API | Authenticated successfully |
| IT-010 | Multiple validations same key | validate() x 10 | Consistent results |

---

## Performance Tests (PF)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PF-001 | Generate performance | generate() x 1000 | < 100ms total |
| PF-002 | Hash performance | hash() x 10000 | < 500ms total |
| PF-003 | Validate performance | validate() x 10000 | < 500ms total |
| PF-004 | Timing-safe comparison overhead | Compare with simple compare | Slight overhead acceptable |
| PF-005 | Memory usage during generation | generate() x 1000 | No memory leak |

---

## Mock Data

### Sample Generated Key Structure

```json
{
    "key": "sk_live_ABCdefGHIjklMNOpqrSTUvwxYZ12345678901234",
    "hash": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855",
    "prefix": "sk_live_ABCdefGHIj"
}
```

### Sample Key Formats

```php
// Valid key format
'sk_live_ABCdefGHIjklMNOpqrSTUvwxYZ12345678901234'

// Test key format (if supported)
'sk_test_ABCdefGHIjklMNOpqrSTUvwxYZ12345678901234'

// Invalid key formats
'pk_live_abc123'              // Wrong prefix
'sk_live_abc'                 // Too short
'sk_live_abc+def='            // Contains base64 but not base64url
'SK_LIVE_abc123'              // Uppercase prefix
```

### Sample Hash Values

```php
// SHA-256 hash (64 hex chars)
'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855'

// Invalid hash formats
'abc'                         // Too short
'gxyz123...'                  // Non-hex chars
''                            // Empty
```

---

## Coverage Targets

| Category | Target |
|----------|--------|
| Key Generation Tests | 100% |
| Hash Method Tests | 100% |
| Validation Tests | 100% |
| Prefix Extraction Tests | 100% |
| Format Validation Tests | 100% |
| Security Tests | 100% |
| Edge Cases | 100% |
| Integration Tests | 100% |

---

## Test Execution

```bash
# Run all API key service tests
php artisan test --filter=ApiKeyServiceTest

# Run with coverage
php artisan test --filter=ApiKeyServiceTest --coverage

# Run specific test
php artisan test --filter=test_generate_returns_array_with_required_keys

# Run security tests only
php artisan test --filter=ApiKeyServiceTest --group=security
```

---

## Dependencies

- TASK-6-1 (Database schema - api_keys table) - Required for integration tests
- TASK-6-2 (Model and Repository) - Required for integration tests

---

## Notes for Developer

1. **Cryptographic Security:** Use PHP's `random_bytes()` for key generation (CSPRNG). Never use `rand()` or `mt_rand()`.

2. **Key Format:** The key must use base64url encoding (RFC 4648) - characters A-Z, a-z, 0-9, -, _. No padding (=).

3. **Hash Algorithm:** Always use SHA-256 for hashing. It's secure, fast, and produces a consistent 64-char hex output.

4. **Timing-Safe Comparison:** Use PHP's `hash_equals()` function for validation. Never use `===` directly as it's vulnerable to timing attacks.

5. **Key Length:** Minimum 32 bytes of random data. After base64url encoding, this produces at least 43 characters.

6. **Prefix Purpose:** The prefix (first 20 chars) allows identifying keys in logs and UI without exposing the full key or hash.

7. **Key Display:** Keys should only be shown ONCE when generated. After that, only the prefix and hash are available.

8. **Error Messages:** Validation failures should return the same generic error message to avoid information leakage.

9. **Integration:** The service should be stateless - no caching of keys or hashes.

10. **Testing Entropy:** In tests, you can mock `random_bytes()` to produce deterministic keys, but production must use real randomness.

---

## Comments

### [2026-02-27 13:58] PreTester: Test Documentation Created
- Created TESTS/TASK-6-3.md with comprehensive test scenarios
- 80+ test scenarios covering generation, hashing, validation, and security
- Focus on timing-safe comparison for security
- Following project test documentation format
- Status: Tests Coverage In Progress
