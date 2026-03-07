# TASK-6-15: Webhook Retry Logic

## Status
`To Develop`

## Priority
`Medium`

## Risk
`Low`

## Specification
- Spec: [SPEC-006](../SPECIFICATIONS/SPEC-006.md)
- Feature: [FEAT-006](../FEATURES/FEAT-006.md)

---

## Description
Implement retry logic for failed webhook deliveries.

## Requirements

### Retry Strategy
- Exponential backoff
- Max 5 retry attempts
- Retry on 5xx errors and timeouts
- No retry on 4xx errors (client errors)

### Retry Schedule
- 1st retry: 1 minute
- 2nd retry: 5 minutes
- 3rd retry: 15 minutes
- 4th retry: 1 hour
- 5th retry: 6 hours

### Worker Job
- Background worker checks for retries
- Process `next_retry_at` queue
- Update delivery status

### Delivery Status
- `pending` - Not yet delivered
- `delivered` - Success
- `failed` - All retries exhausted
- `retrying` - In retry queue

## Acceptance Criteria
- [ ] Retry logic implemented
- [ ] Exponential backoff working
- [ ] Max retry limit enforced
- [ ] Worker job processing queue
- [ ] Status updates correctly

## Dependencies
- TASK-6-14 (Webhook dispatcher)

## Estimated Effort
Medium (2-3 hours)

---

## Comments

### [2026-02-27 12:25] ProjectManager: Task Created
- Priority: Medium (reliability improvement)
- Risk: Low (background process)
- Part of Phase 4: Webhooks

### [2026-03-07 18:00 GMT+2] PreTester: Tests Ready for Developer
**Test Files Created:**
- `backend/tests/Unit/Services/WebhookRetryServiceTest.php` - 28 tests ✅ ALL PASSING
- `backend/tests/Unit/Jobs/ProcessWebhookRetriesJobTest.php` - 25 tests (15 passing, 10 pending implementation)

**Test Documentation:** `TESTS/TEST-6-15.md`

**Run Commands:**
```bash
php artisan test --filter=WebhookRetryServiceTest
php artisan test --filter=ProcessWebhookRetriesJobTest
```

**Design Issue Identified:**
The `WebhookDispatcherService::dispatch()` creates a NEW delivery record each call.
For retries, we need to UPDATE the existing delivery instead.

**Solution:** Add `redeliver(WebhookDelivery $delivery)` method to WebhookDispatcherService that:
1. Reuses the existing delivery record
2. Increments attempt_count
3. Updates response_status, response_body, delivered_at
4. Returns success/failure boolean

**Current Implementation Status:**
- ✅ `WebhookRetryService` - Fully implemented, all tests pass
- ⚠️ `ProcessWebhookRetriesJob` - Needs fix for delivery update issue
- ⚠️ `WebhookDispatcherService::redeliver()` - Not yet implemented
