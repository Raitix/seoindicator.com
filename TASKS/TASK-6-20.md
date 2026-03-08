# TASK-6-20: Integration Tests

## Status
`In Development`

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
backend/tests/Integration/Api/
├── ApiAuthWorkflowTest.php
├── ApiKeywordsWorkflowTest.php
├── ApiRankingsWorkflowTest.php
├── ApiWebhooksWorkflowTest.php
└── RateLimitingWorkflowTest.php

backend/tests/TESTS/
└── TASK-6-20.md (test documentation)
```

### Test Environment
- Separate test database
- Mock HTTP for webhook tests
- Clean state between tests

## Acceptance Criteria
- [x] All workflow tests written
- [x] Tests use test database (RefreshDatabase trait)
- [x] Tests are isolated (each test is self-contained)
- [ ] All tests passing (requires Developer to verify against actual API implementation)

## Dependencies
- TASK-6-19 (Unit tests)

## Estimated Effort
Medium (3-4 hours)

---

## Comments

### [2026-03-07 17:55] PreTester: Tests Created
Created 5 integration test files in `tests/Integration/Api/`:

1. **ApiAuthWorkflowTest.php** (26 tests)
   - Full API key lifecycle: create → use → revoke → verify rejection
   - Permission-based access control tests
   - Authentication header format validation
   - User isolation and security tests

2. **ApiKeywordsWorkflowTest.php** (27 tests)
   - Full keyword CRUD lifecycle
   - Ranking history tracking
   - SERP features integration
   - Pagination and filtering

3. **ApiRankingsWorkflowTest.php** (22 tests)
   - Rankings history workflow
   - Position change tracking
   - SERP features with rankings
   - Large dataset performance tests

4. **ApiWebhooksWorkflowTest.php** (34 tests)
   - Full webhook CRUD lifecycle
   - Webhook testing endpoint
   - Event triggering and delivery
   - HMAC signature verification

5. **RateLimitingWorkflowTest.php** (21 tests)
   - Rate limit hit and reset workflow
   - Per-key rate limits
   - Rate limit headers
   - Cross-user isolation

Also created test documentation at `tests/TESTS/TASK-6-20.md`.

**Note:** Tests are written following existing PHPUnit/Laravel patterns. Tests use:
- `RefreshDatabase` trait for clean state
- Factory methods for test data
- HTTP fake for webhook mocking
- Standard Laravel test assertions

**Next Step:** Developer needs to run tests against actual API implementation and adjust as needed based on actual endpoint behavior.

### [2026-02-27 12:25] ProjectManager: Task Created
- Priority: Medium (quality assurance)
- Risk: Low (testing only)
- Part of Phase 6: Testing
