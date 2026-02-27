# TASK-6-20: Integration Tests

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
Create integration tests for API workflows.

## Requirements

### Test Scenarios

#### API Key Workflow
- Create API key
- Use key for authentication
- Revoke key
- Verify revoked key rejected

#### Keywords API Workflow
- Create keyword via API
- List keywords
- Update keyword
- Delete keyword
- Verify ranking history

#### Webhooks Workflow
- Create webhook
- Trigger event
- Verify webhook received
- Test webhook endpoint
- Delete webhook

#### Rate Limiting
- Hit rate limit
- Wait for reset
- Continue requests

### Test Structure
```
backend/tests/integration/
├── api-auth.test.ts
├── api-keywords.test.ts
├── api-rankings.test.ts
├── api-webhooks.test.ts
└── rate-limiting.test.ts
```

### Test Environment
- Separate test database
- Mock HTTP for webhook tests
- Clean state between tests

## Acceptance Criteria
- [ ] All workflow tests written
- [ ] Tests use test database
- [ ] Tests are isolated
- [ ] All tests passing

## Dependencies
- TASK-6-19 (Unit tests)

## Estimated Effort
Medium (3-4 hours)

---

## Comments

### [2026-02-27 12:25] ProjectManager: Task Created
- Priority: Medium (quality assurance)
- Risk: Low (testing only)
- Part of Phase 6: Testing
