# TEST-29-5: Rate Limiting Integration Tests

## Task Reference
- Task: [TASK-29-5](../TASKS/TASK-29-5.md)
- Specification: [SPEC-029](../SPECIFICATIONS/SPEC-029.md)
- Feature: [FEAT-029](../FEATURES/FEAT-029.md)

## Overview
Tests for rate limiting integration on the export endpoint. Validates per-user rate limiting, response headers, 429 error handling, and configuration.

## Test Files

| File | Location | Tests |
|------|----------|-------|
| RateLimitIntegrationTest.php | backend/tests/Feature/Api/V1/Tools/RateLimitIntegrationTest.php | 60+ |
| ToolsRateLimitServiceTest.php | backend/tests/Unit/Services/Tools/ToolsRateLimitServiceTest.php | 35+ (if service updates needed) |

## Test Scenarios

### RL-BASIC: Basic Rate Limiting (10 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RL-001 | First export request succeeds | Valid export request | 200 OK with download URL |
| RL-002 | Rate limit header present | Any export request | X-RateLimit-Limit: 10 |
| RL-003 | Rate limit remaining decrements | Consecutive requests | X-RateLimit-Remaining: 9, 8, 7... |
| RL-004 | Rate limit reset timestamp | Any export request | X-RateLimit-Reset: Unix timestamp within 1 hour |
| RL-005 | 10th request succeeds | 10 consecutive requests | 200 OK, remaining: 0 |
| RL-006 | 11th request blocked | 11th request within hour | 429 Too Many Requests |
| RL-007 | Rate limit error message | 11th request | "Export rate limit exceeded. Try again in X minutes." |
| RL-008 | Rate limit resets after window | Wait 1 hour after limit | New request succeeds |
| RL-009 | Unauthenticated request | No auth token | 401 Unauthorized (rate limit not checked) |
| RL-010 | Different users have separate limits | User A + User B requests | Each gets 10 exports/hour |

### RL-HEADERS: Rate Limit Headers (8 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RH-001 | X-RateLimit-Limit header | Any export request | Header present, value: 10 |
| RH-002 | X-RateLimit-Remaining header | First request | Header present, value: 9 |
| RH-003 | X-RateLimit-Reset header | Any export request | Header present, valid Unix timestamp |
| RH-004 | X-RateLimit-Reset in future | Any export request | Timestamp > current time |
| RH-005 | X-RateLimit-Reset within 1 hour | Any export request | Timestamp < current time + 3600s |
| RH-006 | Headers present on 429 | Rate limited request | All three headers present |
| RH-007 | X-RateLimit-Remaining is 0 on limit | After 10 requests | Header value: 0 |
| RH-008 | Headers on successful response | Valid request | All headers in response |

### RL-USER: User-Based Rate Limiting (6 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RU-001 | Rate limit keyed by user ID | User A requests | Key: "export:{user_id}" |
| RU-002 | Separate limits per user | User A (5 req) + User B (5 req) | Both have 5 remaining |
| RU-003 | Admin users have same limit | Admin user requests | Same 10/hour limit |
| RU-004 | Rate limit persists across sessions | Login → 5 exports → Logout → Login | 5 remaining |
| RU-005 | Anonymous users not rate limited | No auth token | 401 (not rate checked) |
| RU-006 | User ID extraction from auth | Authenticated request | auth()->id() used for key |

### RL-CONFIG: Configuration Tests (5 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RC-001 | Default export_rate_limit | No config override | 10 exports/hour |
| RC-002 | Default export_rate_window | No config override | 3600 seconds (1 hour) |
| RC-003 | Custom rate limit | config('tools.export_rate_limit', 5) | 5 exports/hour |
| RC-004 | Custom rate window | config('tools.export_rate_window', 1800) | 30-minute window |
| RC-005 | Config in tools.php | Check config file | Keys exist with defaults |

### RL-ERROR: Error Handling (6 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RE-001 | 429 response structure | Rate limited request | `{ "message": "Export rate limit exceeded..." }` |
| RE-002 | Retry time in message | Rate limited request | "Try again in X minutes" with actual minutes |
| RE-003 | Rate limit headers on error | 429 response | All X-RateLimit-* headers present |
| RE-004 | HTTP status code | Rate limited request | 429 Too Many Requests |
| RE-005 | Error response does not consume export | 11th request blocked | Still at 10/10 used |
| RE-006 | Graceful degradation | Redis unavailable | Request allowed (fail-open) |

### RL-EDGE: Edge Cases (8 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EE-001 | Rapid consecutive requests | 15 requests in 1 second | Only first 10 succeed |
| EE-002 | Concurrent requests from same user | 5 parallel requests | All count toward limit |
| EE-003 | Negative remaining prevented | Rate limited state | Remaining: 0 (not negative) |
| EE-004 | Window boundary | Request at 59:59, another at 60:01 | Second request succeeds |
| EE-005 | Large user ID | User ID: 999999999 | Rate limit works correctly |
| EE-006 | Zero rate limit config | export_rate_limit: 0 | All requests blocked |
| EE-007 | Very large rate limit | export_rate_limit: 1000 | All requests allowed |
| EE-008 | String user ID handled | Invalid auth | 401, no rate limit check |

### RL-SECURITY: Security Tests (5 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RS-001 | Rate limit bypass attempt | Different format param | Still rate limited |
| RS-002 | Header injection prevention | Malicious header values | Headers sanitized |
| RS-003 | User ID spoofing | Modified token | Original user ID used |
| RS-004 | Race condition prevention | Concurrent limit checks | No double-spend |
| RS-005 | Rate limit DoS protection | Malicious user hits limit | Legitimate users unaffected |

### RL-PERFORMANCE: Performance Tests (4 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RP-001 | Rate limit check overhead | Single request | < 10ms added latency |
| RP-002 | Redis connection pooling | 100 requests | No connection exhaustion |
| RP-003 | Memory usage | 1000 requests | Memory stable (< 5MB growth) |
| RP-004 | Rate limit key efficiency | 100 users | O(1) lookup per request |

## Mock Data

### Valid Export Request
```php
$validRequest = [
    'tool' => 'meta-tag-analyzer',
    'url' => 'https://example.com',
    'format' => 'pdf',
    'data' => [
        'analyzedAt' => '2026-03-05T12:00:00Z',
        'results' => [
            'title' => ['content' => 'Example', 'length' => 60, 'status' => 'ok'],
        ],
    ],
];
```

### Rate Limit Response (429)
```php
$rateLimitResponse = [
    'message' => 'Export rate limit exceeded. Try again in 45 minutes.',
];
```

### Expected Headers
```php
$headers = [
    'X-RateLimit-Limit' => 10,
    'X-RateLimit-Remaining' => 5,
    'X-RateLimit-Reset' => 1709650800, // Unix timestamp
];
```

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Basic Rate Limiting | 100% | 10 |
| Headers | 100% | 8 |
| User-Based Limiting | 100% | 6 |
| Configuration | 100% | 5 |
| Error Handling | 100% | 6 |
| Edge Cases | 100% | 8 |
| Security | 100% | 5 |
| Performance | 100% | 4 |
| **Total** | **100%** | **52** |

## Test Execution

```bash
# Run all rate limit tests
php artisan test --filter=RateLimitIntegration

# Run specific test file
php artisan test tests/Feature/Api/V1/Tools/RateLimitIntegrationTest.php

# Run with coverage
php artisan test --coverage tests/Feature/Api/V1/Tools/RateLimitIntegrationTest.php
```

## Dependencies
- TASK-29-3 (ToolExportController) - Provides integration point
- ToolsRateLimitService - Existing service for rate limiting

## Notes for Developer

### Implementation Pattern
```php
// In ToolExportController
public function export(Request $request): JsonResponse
{
    // Check rate limit FIRST
    $remaining = $this->rateLimiter->attempt(
        key: "export:" . auth()->id(),
        maxAttempts: config('tools.export_rate_limit', 10),
        decaySeconds: config('tools.export_rate_window', 3600)
    );
    
    if ($remaining === false) {
        return response()->json([
            'message' => 'Export rate limit exceeded. Try again in ' . 
                $this->rateLimiter->availableIn("export:" . auth()->id()) . ' minutes.',
        ], 429)->withHeaders([
            'X-RateLimit-Limit' => config('tools.export_rate_limit', 10),
            'X-RateLimit-Remaining' => 0,
            'X-RateLimit-Reset' => $this->rateLimiter->availableAt("export:" . auth()->id()),
        ]);
    }
    
    // ... export logic
    
    return response()->json([
        'success' => true,
        'downloadUrl' => $downloadUrl,
    ])->withHeaders([
        'X-RateLimit-Limit' => config('tools.export_rate_limit', 10),
        'X-RateLimit-Remaining' => $remaining,
        'X-RateLimit-Reset' => $this->rateLimiter->availableAt("export:" . auth()->id()),
    ]);
}
```

### Key Service Methods
- `attempt(key, maxAttempts, decaySeconds)` - Check and decrement limit
- `availableIn(key)` - Seconds until limit resets
- `availableAt(key)` - Unix timestamp when limit resets

### Configuration
```php
// config/tools.php
return [
    'export_rate_limit' => env('TOOL_EXPORT_RATE_LIMIT', 10),
    'export_rate_window' => env('TOOL_EXPORT_RATE_WINDOW', 3600),
];
```

## Comments
_Agent log_

### [2026-03-05 20:30 GMT+2] Agent: PreTester
Created TEST-29-5.md with 52 test scenarios covering:
- Basic rate limiting (10 tests)
- Rate limit headers (8 tests)
- User-based limiting (6 tests)
- Configuration (5 tests)
- Error handling (6 tests)
- Edge cases (8 tests)
- Security (5 tests)
- Performance (4 tests)

Tests are ready for TDD implementation. Developer should implement ToolExportController rate limiting logic.
