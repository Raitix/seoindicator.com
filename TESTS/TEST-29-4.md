# TEST-29-4: Download Token System Tests

## Task Reference
- **Task:** [TASK-29-4](../TASKS/TASK-29-4.md)
- **Specification:** [SPEC-029](../SPECIFICATIONS/SPEC-029.md)
- **Feature:** [FEAT-029](../FEATURES/FEAT-029.md)

## Overview
Comprehensive tests for secure token-based download system with one-time use tokens, expiration, and IDOR prevention.

## Test Files

| File | Tests | Description |
|------|-------|-------------|
| `backend/tests/Unit/Services/Tools/DownloadTokenSystemTest.php` | 65 | Token system security tests |
| `backend/tests/Unit/Services/Tools/ToolExportServiceTest.php` | 35+ | Integration with export service |

## Test Scenarios

### Token Generation (TKN-001 to TKN-015)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TKN-001 | Token is exactly 64 characters | Generate token | Length = 64 |
| TKN-002 | Token contains only alphanumeric | Generate token | Matches `/^[a-zA-Z0-9]+$/` |
| TKN-003 | Cryptographically secure random | Generate 2 tokens | Tokens are unique |
| TKN-004 | Sufficient entropy | Generate 1000 tokens | All unique |
| TKN-005 | No predictable patterns | Generate 100 tokens | >90% unique prefixes |
| TKN-006 | Fast generation | Generate 100 tokens | <100ms total |

### Token Storage (TKN-020 to TKN-035)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TKN-020 | File path in metadata | Store token | `file` field present |
| TKN-021 | Format in metadata | Store token | `format` field present |
| TKN-022 | User ID in metadata | Store with user_id=42 | `user_id` = 42 |
| TKN-023 | Created timestamp | Store token | ISO8601 timestamp |
| TKN-024 | Cache key convention | Store token | Key = `export:{token}` |
| TKN-025 | Complete metadata | Store token | All 4 required fields |

### Token TTL (TKN-040 to TKN-055)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TKN-040 | Default TTL is 1 hour | Get config | 3600 seconds |
| TKN-041 | TTL configurable | Set config=7200 | Returns 7200 |
| TKN-042 | Token expires after TTL | Store with 1s TTL | Expired after 2s |
| TKN-043 | Custom TTL respected | Store with 1800s TTL | Token exists |
| TKN-044 | Zero TTL immediately invalid | Store with 0 TTL | Token invalid |
| TKN-045 | Auto-cleanup by cache TTL | Store with 1s TTL | Cache removes expired |

### Token Validation (TKN-060 to TKN-080)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TKN-060 | Valid token passes | Stored token | Token data returned |
| TKN-061 | Non-existent token fails | Fake token | Null |
| TKN-062 | Expired token fails | Expired token | Null |
| TKN-063 | Wrong user_id fails | Token owned by user 1, accessed by 999 | Validation fails |
| TKN-064 | Malformed token fails | Empty, short, special chars | Null |
| TKN-065 | All required fields checked | Valid token | All fields present |
| TKN-066 | Null cache handled | Non-existent key | Null (no error) |

### Token Invalidation (TKN-080 to TKN-095)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TKN-080 | Invalidation after download | Download then check | Token gone |
| TKN-081 | One-time use enforced | Download twice | Second fails |
| TKN-082 | Replay attack prevented | Capture & reuse token | Reuse fails |
| TKN-083 | Invalidation is atomic | Forget token | Returns true |
| TKN-084 | Non-existent invalidation | Forget fake token | Returns false |

### IDOR Prevention (TKN-100 to TKN-115)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TKN-100 | User cannot access other's token | user_id mismatch | Access denied |
| TKN-101 | Similar user IDs prevented | IDs 100 vs 10, 1000, 99 | All fail |
| TKN-102 | Null user_id check | null vs 1 | Access denied |
| TKN-103 | Type coercion prevented | String "1" vs int 1 | Strict check fails |
| TKN-104 | Enumeration attack prevented | 10 tokens, try all | Only own accessible |

### Security Edge Cases (TKN-120 to TKN-140)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TKN-120 | SQL injection in key | `'; DROP TABLE users; --` | Null (no match) |
| TKN-121 | Special characters in key | Newlines, tabs, spaces, colons | Null (no match) |
| TKN-122 | Concurrent download prevention | Simultaneous requests | Only one succeeds |
| TKN-123 | Token prediction prevented | Analyze 100 tokens | High hamming distance |
| TKN-124 | Brute force impractical | Calculate combinations | >10^100 years |
| TKN-125 | Cache key prefix prevents collision | Different prefixes | No collision |
| TKN-126 | Unicode token handling | Japanese characters | Handles gracefully |
| TKN-127 | Empty token handling | Empty string | Null (no error) |
| TKN-128 | Very long token handling | 1000 char token | Null (no error) |

### Cache Driver Behavior (TKN-140 to TKN-150)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TKN-140 | File cache driver | Standard operations | Works correctly |
| TKN-141 | Redis cache driver | Standard operations | Works correctly |
| TKN-142 | TTL across drivers | 1 second TTL | Expires correctly |

### Integration (TKN-160 to TKN-170)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TKN-160 | Full token lifecycle | Generate → Store → Validate → Invalidate | All steps pass |
| TKN-161 | Multiple tokens per user | Create 5 tokens | All accessible by owner |
| TKN-162 | All valid formats | pdf, xls | Both supported |
| TKN-163 | Invalid formats rejected | doc, csv, json, etc. | Not in allowed list |

## Implementation Requirements

### Token Generation
```php
$token = Str::random(64);  // Laravel's cryptographically secure random
```

### Token Storage
```php
Cache::put("export:{$token}", [
    'file' => $filePath,
    'format' => $format,
    'user_id' => auth()->id(),
    'created_at' => now()->toIso8601String(),
], now()->addSeconds(config('tools.export_token_ttl', 3600)));
```

### Token Validation
```php
$tokenData = Cache::get("export:{$token}");
if (!$tokenData || $tokenData['user_id'] !== auth()->id()) {
    abort(404, 'Invalid or expired download link');
}
```

### Token Invalidation
```php
Cache::forget("export:{$token}");
```

## Security Considerations

### IDOR Prevention
- **Strict user_id comparison:** Use `===` not `==`
- **No enumeration:** Return 404 (not 403) for wrong user to prevent enumeration
- **Type safety:** Ensure user_id is integer, reject strings

### Token Security
- **Cryptographic strength:** `Str::random()` uses `random_bytes()` (CSPRNG)
- **Sufficient entropy:** 64 alphanumeric chars = ~384 bits entropy
- **No predictable patterns:** Each token is independent

### One-Time Use
- **Atomic invalidation:** `Cache::forget()` is atomic
- **Replay attack prevention:** Token deleted immediately after first use
- **Concurrent access:** Second request finds token already deleted

### TTL Management
- **Default 1 hour:** Reasonable for most use cases
- **Configurable:** `TOOL_EXPORT_TOKEN_TTL` environment variable
- **Auto-cleanup:** Cache driver handles expired token cleanup

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Token Generation | 100% | 6 tests |
| Token Storage | 100% | 6 tests |
| Token TTL | 100% | 6 tests |
| Token Validation | 100% | 7 tests |
| Token Invalidation | 100% | 5 tests |
| IDOR Prevention | 100% | 5 tests |
| Security Edge Cases | 100% | 9 tests |
| Cache Driver | 100% | 3 tests |
| Integration | 100% | 4 tests |
| **Total** | **100%** | **51+ tests** |

## Test Execution

```bash
# Run download token system tests
php artisan test --filter=DownloadTokenSystemTest

# Run with coverage
php artisan test --filter=DownloadTokenSystemTest --coverage

# Run all ToolExportService tests
php artisan test --filter=ToolExportService
```

## Dependencies

- **TASK-29-3** (ToolExportService) - Token methods added to this service
- **Laravel Cache** - Token storage backend
- **Laravel Str** - Cryptographically secure random generation

## Notes for Developer

1. **Config file required:** Create `config/tools.php` with `export_token_ttl` key
2. **Migration not needed:** Token stored in cache, not database
3. **Cache driver:** Works with file, redis, memcached, database drivers
4. **Error handling:** Return 404 (not 403) for invalid/expired tokens to prevent enumeration
5. **Strict comparison:** Use `===` for user_id comparison to prevent type coercion attacks

## Comments
_Agent log_

### [2026-03-04 13:15 GMT+2] Agent: PreTester
**Test Design Complete**

Created comprehensive test suite for Download Token System:

**Test File Created:**
- `backend/tests/Unit/Services/Tools/DownloadTokenSystemTest.php` (65 tests)

**Test Categories:**
- Token Generation (6 tests) - Length, randomness, entropy
- Token Storage (6 tests) - Metadata structure
- Token TTL (6 tests) - Expiration, configuration
- Token Validation (7 tests) - Existence, user match
- Token Invalidation (5 tests) - One-time use
- IDOR Prevention (5 tests) - User isolation
- Security Edge Cases (9 tests) - Injection, concurrency
- Cache Driver (3 tests) - Driver compatibility
- Integration (4 tests) - Full workflows

**Key Security Tests:**
- Cryptographic randomness verification
- Brute force impracticality (10^100+ years)
- Replay attack prevention
- Concurrent download handling
- Type coercion attack prevention
- Token prediction resistance

**Implementation Notes:**
- Uses Laravel `Str::random(64)` for token generation
- Cache key format: `export:{token}`
- Metadata: file, format, user_id, created_at
- TTL configurable via `tools.export_token_ttl`
- Strict comparison (`===`) for user_id validation

Status: `To Write Tests` → `Tests Coverage In Progress`
