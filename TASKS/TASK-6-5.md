# TASK-6-5: Rate Limiter Middleware

## Status
`To Write Tests`

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

### [2026-02-27 12:25] ProjectManager: Task Created
- Priority: High (security/abuse prevention)
- Risk: Medium (affects all API traffic)
- Part of Phase 2: Rate Limiting
