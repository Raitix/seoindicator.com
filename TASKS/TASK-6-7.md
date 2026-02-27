# TASK-6-7: Rate Limit Headers

## Status
`To Write Tests`

## Priority
`Medium`

## Risk
`Low`

## Specification
- Spec: [SPEC-006](../SPECIFICATIONS/SPEC-006.md)
- Feature: [FEAT-006](../FEATURES/FEAT-006.md)

---

## Description
Implement rate limit headers in API responses.

## Requirements

### Headers
```
X-RateLimit-Limit: 60       # Max requests per window
X-RateLimit-Remaining: 45   # Requests remaining
X-RateLimit-Reset: 1708368300  # Unix timestamp when window resets
```

### Behavior
- Include in all API responses
- Update after each request
- Accurate remaining count

### Integration
- Integrate with rate limiter middleware (TASK-6-5)
- Add to response object

## Acceptance Criteria
- [ ] Headers present in responses
- [ ] Accurate limit/remaining values
- [ ] Correct reset timestamp
- [ ] Works with rate limiter

## Dependencies
- TASK-6-5 (Rate limiter middleware)

## Estimated Effort
Small (1 hour)

---

## Comments

### [2026-02-27 12:25] ProjectManager: Task Created
- Priority: Medium (UX enhancement)
- Risk: Low (headers only)
- Part of Phase 2: Rate Limiting
