# TASK-6-5: Rate Limiter Middleware

## Status
`Testing Locally`

## Priority
`High`

## Risk
`Medium`

## Specification
- Spec: [SPEC-006](../SPECIFICATIONS/SPEC-006.md)
- Feature: [FEAT-006](../FEATURES/FEAT-006.md)

---

## Description
Implement rate limiting middleware for API endpoints.

## Requirements

### Rate Limiting Logic
- Sliding window or fixed window algorithm
- Per-API-key limiting
- Configurable limits per key
- Default: 60 requests per minute

### Headers
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1708368300
```

### Response on Limit Exceeded
```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Rate limit exceeded. Retry after 30 seconds.",
    "retry_after": 30
  }
}
```

### Storage
- Use existing SQLite for tracking
- Or in-memory cache for performance

## Acceptance Criteria
- [ ] Middleware implemented
- [ ] Per-key rate limiting
- [ ] Correct headers returned
- [ ] 429 response on exceeded
- [ ] Unit tests

## Dependencies
- TASK-6-2 (API Key model)

## Estimated Effort
Medium (2-4 hours)

---

## Comments

### [2026-02-27 14:35] Developer: Implementation Complete
- Created `ApiKeyRateLimiterService` with sliding window algorithm
  - Per-API-key rate limiting
  - Configurable limits (default: 60 req/min)
  - Atomic hit() method for check-and-increment
  - getRemaining(), getResetTime(), getRetryAfter() helpers
  - Automatic cleanup of expired entries
  - Cache-based storage with TTL
- Created `ApiKeyRateLimiter` middleware
  - Integrates with API key from auth middleware
  - Adds X-RateLimit-* headers to all responses
  - Returns 429 with SPEC-006 compliant JSON when limit exceeded
  - Includes Retry-After header and body field
- Updated tests to use actual service and middleware
  - 79 tests pass (1 unrelated failure)
- Status: To Test Locally

### [2026-02-27 12:25] ProjectManager: Task Created
- Priority: High (security/abuse prevention)
- Risk: Medium (affects all API traffic)
- Part of Phase 2: Rate Limiting

### [2026-02-27 14:22] PreTester: Test Documentation and Files Created
- Created TESTS/TASK-6-5.md with 120+ test scenarios
- Created unit test: backend/tests/Unit/Services/ApiKeyRateLimiterServiceTest.php
  - Tests for isAllowed, sliding window, remaining, reset time, count, increment, reset
  - Per-key configuration tests
  - Edge cases and security tests
- Created middleware test: backend/tests/Unit/Middleware/ApiKeyRateLimiterMiddlewareTest.php
  - Tests for middleware integration with API keys
  - Header tests (X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset)
  - 429 response format tests matching SPEC-006
  - Concurrency and integration tests
- Created feature test: backend/tests/Feature/Api/RateLimitHeadersTest.php
  - Tests for header presence on API responses
  - 429 response structure validation
- Status: To Develop
- Notes for Developer:
  1. Implement ApiKeyRateLimiterService with sliding window algorithm
  2. Use Cache facade or SQLite for storage
  3. Implement ApiKeyRateLimiter middleware that runs after auth
  4. Add headers to all API responses
  5. Return 429 with exact JSON format from SPEC-006
