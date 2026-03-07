# TEST-6-15: Webhook Retry Logic Tests

## Task Reference
- Task: [TASK-6-15](../TASKS/TASK-6-15.md)
- Spec: [SPEC-006](../SPECIFICATIONS/SPEC-006.md)

## Overview
Tests for webhook retry logic with exponential backoff, max retry limits, and worker job processing.

---

## Test Files

| File | Type | Description |
|------|------|-------------|
| `backend/tests/Unit/Services/WebhookRetryServiceTest.php` | Unit | Retry service logic |
| `backend/tests/Unit/Jobs/ProcessWebhookRetriesJobTest.php` | Unit | Queue job tests |

---

## Test Scenarios

### RL: Retry Logic Tests (WebhookRetryService)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| RL-001 | Calculate next retry time for 1st attempt | 1 minute from now |
| RL-002 | Calculate next retry time for 2nd attempt | 5 minutes from now |
| RL-003 | Calculate next retry time for 3rd attempt | 15 minutes from now |
| RL-004 | Calculate next retry time for 4th attempt | 1 hour from now |
| RL-005 | Calculate next retry time for 5th attempt | 6 hours from now |
| RL-006 | Exceed max retry attempts (6th) | Returns null, no more retries |
| RL-007 | Get retry schedule | Returns array of intervals |
| RL-008 | Should retry on 500 error | Returns true |
| RL-009 | Should retry on 502 error | Returns true |
| RL-010 | Should retry on 503 error | Returns true |
| RL-011 | Should retry on 504 error | Returns true |
| RL-012 | Should NOT retry on 400 error | Returns false |
| RL-013 | Should NOT retry on 401 error | Returns false |
| RL-014 | Should NOT retry on 403 error | Returns false |
| RL-015 | Should NOT retry on 404 error | Returns false |
| RL-016 | Should NOT retry on 422 error | Returns false |
| RL-017 | Should retry on connection timeout | Returns true |
| RL-018 | Should retry on connection refused | Returns true |
| RL-019 | Should retry on DNS resolution failure | Returns true |
| RL-020 | Get remaining retry attempts | Returns correct count |
| RL-021 | Can retry check with attempt count | Returns correct boolean |

### DS: Delivery Status Tests

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| DS-001 | Mark delivery as retrying | Sets next_retry_at, increments attempt_count |
| DS-002 | Mark delivery as failed | Sets status, clears next_retry_at |
| DS-003 | Mark delivery as delivered | Sets delivered_at, response_status |
| DS-004 | Get pending retries | Returns deliveries with next_retry_at <= now |
| DS-005 | Get delivery status (pending) | Returns 'pending' |
| DS-006 | Get delivery status (delivered) | Returns 'delivered' |
| DS-007 | Get delivery status (retrying) | Returns 'retrying' |
| DS-008 | Get delivery status (failed) | Returns 'failed' |

### WJ: Worker Job Tests (ProcessWebhookRetriesJob)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| WJ-001 | Process pending retry | Dispatches webhook, updates status |
| WJ-002 | Process multiple pending retries | All dispatched in batch |
| WJ-003 | No pending retries | Job completes without errors |
| WJ-004 | Retry succeeds | Marks as delivered |
| WJ-005 | Retry fails with 5xx | Schedules next retry |
| WJ-006 | Retry fails with 4xx | Marks as failed (no retry) |
| WJ-007 | Retry exhausts max attempts | Marks as failed |
| WJ-008 | Process retry with disabled webhook | Skips delivery |
| WJ-009 | Process retry with deleted webhook | Handles gracefully |
| WJ-010 | Batch size limit | Processes only configured batch size |

### EC: Edge Cases

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| EC-001 | Null response status | Treated as retryable |
| EC-002 | Empty response body | Logged, no error |
| EC-003 | Very large response body | Truncated, no error |
| EC-004 | Concurrent job processing | Lock prevents duplicates |
| EC-005 | Job timeout during retry | Graceful handling |
| EC-006 | Database connection lost | Job fails, retries later |
| EC-007 | Invalid next_retry_at in past | Processed immediately |
| EC-008 | Invalid next_retry_at in future | Not processed yet |

### SC: Security Tests

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| SC-001 | Retry with invalid webhook_id | Gracefully handled |
| SC-002 | Retry without proper auth | Still processes (internal job) |
| SC-003 | Retry payload tampering | HMAC verification on dispatch |

---

## Retry Schedule Constants

```php
const RETRY_SCHEDULE = [
    1 => 1,      // 1 minute
    2 => 5,      // 5 minutes
    3 => 15,     // 15 minutes
    4 => 60,     // 1 hour
    5 => 360,    // 6 hours
];

const MAX_RETRY_ATTEMPTS = 5;
```

---

## Expected Implementation

### WebhookRetryService
```php
class WebhookRetryService
{
    public function calculateNextRetryAt(int $attemptCount): ?Carbon;
    public function shouldRetry(int $responseStatus): bool;
    public function shouldRetryOnError(?\Throwable $error): bool;
    public function getRemainingAttempts(int $attemptCount): int;
    public function canRetry(int $attemptCount): bool;
    public function getRetrySchedule(): array;
}
```

### ProcessWebhookRetriesJob
```php
class ProcessWebhookRetriesJob implements ShouldQueue
{
    public function handle(
        WebhookRetryService $retryService,
        WebhookDispatcherService $dispatcher
    ): void;
}
```

### WebhookDelivery Model Additions
```php
// Status helper
public function getStatus(): string; // pending|delivered|failed|retrying

// Scopes
public function scopePendingRetry($query);
public function scopeFailed($query);

// Retry helpers
public function markAsRetrying(Carbon $nextRetryAt): void;
public function markAsFailed(): void;
```

---

## Test Coverage Summary

| Category | Tests | Assertions |
|----------|-------|------------|
| Retry Logic (RL) | 21 | 25+ |
| Delivery Status (DS) | 8 | 12+ |
| Worker Job (WJ) | 10 | 20+ |
| Edge Cases (EC) | 8 | 10+ |
| Security (SC) | 3 | 5+ |
| **Total** | **50** | **72+** |

---

## Dependencies

- TASK-6-14: Webhook dispatcher (for actual delivery)
- TASK-6-12: Webhooks schema (database tables)

---

## Status
`Tests Coverage In Progress`

---

## Comments

### [2026-03-07 17:30 GMT+2] PreTester: Starting Test Design
- Analyzing retry requirements
- Creating test scenarios for exponential backoff
- Designing worker job tests

### [2026-03-07 17:50 GMT+2] PreTester: Test Execution Results
**WebhookRetryServiceTest:** ✅ 28 tests passing (69 assertions)
- All retry schedule calculations correct
- All HTTP status retry logic working
- All exception handling tests passing
- Integration with delivery model working

**ProcessWebhookRetriesJobTest:** ⚠️ 10 failures, 15 passes
- Failures are expected (TDD) - implementation incomplete
- Tests reveal design issue: dispatcher creates NEW delivery instead of updating EXISTING one

### [2026-03-07 17:55 GMT+2] PreTester: Design Issue Identified
**Issue:** `WebhookDispatcherService::dispatch()` creates a new `WebhookDelivery` record each call.
**Impact:** Retry job should UPDATE existing delivery, not create new one.
**Solution for Developer:**
1. Add `redeliver(WebhookDelivery $delivery)` method to WebhookDispatcherService
2. Or modify `dispatch()` to accept optional delivery parameter
3. The retry job should use this method to update the existing delivery record

### [2026-03-07 18:00 GMT+2] PreTester: Tests Fixed
- Fixed EC-001: Changed null status test to connection exception test
- Fixed SC-001: Changed invalid webhook_id test to use deleted webhook pattern

**Test Commands:**
```bash
php artisan test --filter=WebhookRetryServiceTest    # 28 tests
php artisan test --filter=ProcessWebhookRetriesJobTest  # 25 tests (10 pending implementation)
```
