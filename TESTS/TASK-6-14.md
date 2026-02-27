# TASK-6-14: Webhook Dispatcher Service Tests

## Task Reference
- **Task:** [TASK-6-14](../TASKS/TASK-6-14.md)
- **Feature:** [FEAT-006](../FEATURES/FEAT-006.md) - API Access & Webhooks
- **Specification:** [SPEC-006](../SPECIFICATIONS/SPEC-006.md)

---

## Overview

Tests for the `WebhookDispatcherService` responsible for delivering webhook payloads to external endpoints. This service handles HTTP POST requests, HMAC signature generation, timeout handling, and delivery logging.

---

## Service Responsibilities

| Responsibility | Description |
|----------------|-------------|
| Queue Processing | Process webhook dispatch jobs from queue |
| HTTP Delivery | Send POST requests to webhook URLs |
| HMAC Signatures | Generate secure signatures for payload verification |
| Timeout Handling | Handle connection and response timeouts |
| Delivery Logging | Record all delivery attempts and results |
| Retry Logic | Queue failed deliveries for retry (TASK-6-15) |

---

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `backend/tests/Unit/Services/WebhookDispatcherServiceTest.php` | Unit | Service method tests with mocked HTTP |
| `backend/tests/Integration/WebhookDispatcherIntegrationTest.php` | Integration | End-to-end dispatch flow tests |
| `backend/tests/Unit/Jobs/DispatchWebhookJobTest.php` | Unit | Queue job tests |

---

## Test Scenarios

### Dispatcher Service Tests (DS)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DS-001 | Dispatch successful webhook | Valid webhook + event | Returns true, delivery logged |
| DS-002 | Dispatch with HMAC signature | Any payload | Signature header present |
| DS-003 | Dispatch with timestamp header | Any payload | X-SEOIndicator-Timestamp present |
| DS-004 | Dispatch to disabled webhook | Disabled webhook | Returns false, no HTTP call |
| DS-005 | Dispatch with HTTP error | 500 response | Returns false, delivery logged |
| DS-006 | Dispatch with connection timeout | Timeout exception | Returns false, delivery logged |
| DS-007 | Dispatch with invalid URL | Malformed URL | Returns false, delivery logged |
| DS-008 | Dispatch with 4xx response | 400/404/410 response | Returns false, delivery logged |
| DS-009 | Dispatch with redirect | 301/302 response | Follows redirect (configurable) |
| DS-010 | Dispatch with large payload | 1MB+ payload | Handles appropriately |
| DS-011 | Dispatch records response body | Any delivery | Response body stored |
| DS-012 | Dispatch records response time | Any delivery | Response time logged |
| DS-013 | Dispatch increments attempt count | Retry dispatch | attempt_count incremented |
| DS-014 | Dispatch with event not subscribed | Event not in webhook.events | Skips dispatch |

### HMAC Signature Tests (HM)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| HM-001 | Generate valid HMAC signature | payload + secret | Valid sha256 HMAC |
| HM-002 | Signature format correct | Any payload | Format: sha256=<hex> |
| HM-003 | Signature varies with payload | Different payloads | Different signatures |
| HM-004 | Signature varies with secret | Same payload, different secret | Different signatures |
| HM-005 | Signature for empty payload | Empty payload | Valid HMAC of empty string |
| HM-006 | Signature for JSON payload | JSON object | Valid HMAC of JSON string |
| HM-007 | Timing-safe comparison | Verify signature | Uses timing-safe comparison |
| HM-008 | Secret is hashed before use | Stored secret | Hashed/encrypted retrieval |

### Event Payload Tests (EP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EP-001 | Ranking changed event | ranking.changed | Correct payload structure |
| EP-002 | Alert triggered event | alert.triggered | Correct payload structure |
| EP-003 | Report completed event | report.completed | Correct payload structure |
| EP-004 | Keyword added event | keyword.added | Correct payload structure |
| EP-005 | Keyword removed event | keyword.removed | Correct payload structure |
| EP-006 | Event ID generation | Any event | Unique event ID (evt_*) |
| EP-007 | Timestamp in ISO 8601 | Any event | created_at in ISO format |
| EP-008 | Event payload serialization | Complex data | Valid JSON output |

### Timeout Handling Tests (TO)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TO-001 | Default timeout applied | No timeout specified | 30s timeout used |
| TO-002 | Custom timeout respected | timeout=5s | 5s timeout used |
| TO-003 | Connection timeout | Server unreachable | Timeout exception caught |
| TO-004 | Read timeout | Slow response | Timeout exception caught |
| TO-005 | Timeout logs correctly | Timeout occurs | Delivery marked as failed |

### Delivery Logging Tests (DL)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DL-001 | Log successful delivery | 200 response | Delivery record with status |
| DL-002 | Log failed delivery | 500 response | Delivery record with status |
| DL-003 | Log response body | Response with body | Body stored in record |
| DL-004 | Log response time | Any response | response_time_ms recorded |
| DL-005 | Log attempt count | Retry attempt | attempt_count correct |
| DL-006 | Log delivered_at timestamp | Successful delivery | delivered_at set |
| DL-007 | Log next_retry_at for retry | Failed delivery | next_retry_at calculated |
| DL-008 | Log to correct webhook | Multiple webhooks | Delivery linked correctly |

### Queue Job Tests (QJ)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| QJ-001 | Job processes webhook | Valid job data | Service called correctly |
| QJ-002 | Job handles failure | Service throws exception | Exception logged, job fails |
| QJ-003 | Job retries on failure | Failed job | Retries up to max_attempts |
| QJ-004 | Job with non-existent webhook | webhook_id=9999 | Job handled gracefully |
| QJ-005 | Job with deleted webhook | Deleted webhook | Job handled gracefully |
| QJ-006 | Job respects queue | High priority queue | Correct queue used |

---

### Security Tests (SC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SC-001 | Secret not logged | Any dispatch | Secret never in logs |
| SC-002 | Payload sanitization | Malicious payload | Payload not modified (raw) |
| SC-003 | HTTPS enforcement | HTTP URL | Allowed or rejected (configurable) |
| SC-004 | SSRF prevention | Internal IP URL | Blocked or logged |
| SC-005 | Signature timing attack | Verification request | Timing-safe comparison |
| SC-006 | Payload size limit | Oversized payload | Rejected or truncated |

---

### Edge Case Tests (EC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Empty webhook URL | url="" | Delivery fails gracefully |
| EC-002 | Null webhook events | events=null | No events dispatched |
| EC-003 | Empty events array | events=[] | No events dispatched |
| EC-004 | Very long URL | URL > 2048 chars | Handled appropriately |
| EC-005 | Unicode in payload | Unicode data | UTF-8 encoded correctly |
| EC-006 | Special chars in payload | HTML/JS in data | Sent as-is (receiver sanitizes) |
| EC-007 | Concurrent dispatches | Multiple simultaneous | No race conditions |
| EC-008 | Network unreachable | No network | Connection exception caught |
| EC-009 | DNS resolution failure | Invalid hostname | DNS exception caught |
| EC-010 | SSL certificate error | Invalid cert | SSL exception caught |

---

### Integration Tests (IT)

| ID | Scenario | Actions | Expected Output |
|----|----------|---------|-----------------|
| IT-001 | Full dispatch flow | Trigger event → delivery | End-to-end success |
| IT-002 | Multiple webhooks | One event, multiple webhooks | All receive payload |
| IT-003 | Event filtering | Webhook with specific events | Only subscribed events sent |
| IT-004 | Delivery retry flow | Fail → retry → success | Eventual success |
| IT-005 | Delivery history | Multiple deliveries | All logged correctly |
| IT-006 | Disabled webhook skip | Trigger event for disabled | No dispatch attempt |

---

## Event Payload Structure

### Standard Event Payload
```json
{
  "id": "evt_abc123def456",
  "type": "ranking.changed",
  "created_at": "2026-02-27T12:00:00Z",
  "data": {
    "keyword_id": 1,
    "keyword": "seo tools",
    "site_id": 1,
    "old_position": 7,
    "new_position": 5,
    "change": -2,
    "checked_at": "2026-02-27T12:00:00Z"
  }
}
```

### Security Headers
```http
X-SEOIndicator-Signature: sha256=<hmac_hash>
X-SEOIndicator-Timestamp: 1709044800
Content-Type: application/json
```

### Signature Generation
```php
$payload = json_encode($event);
$timestamp = time();
$signaturePayload = $timestamp . '.' . $payload;
$signature = 'sha256=' . hash_hmac('sha256', $signaturePayload, $secret);
```

---

## Mock HTTP Responses

### Success Response
```http
HTTP/1.1 200 OK
Content-Type: application/json

{"received": true}
```

### Error Response
```http
HTTP/1.1 500 Internal Server Error
Content-Type: application/json

{"error": "Internal server error"}
```

### Timeout Response
```
(No response - connection timeout after 30s)
```

---

## Coverage Targets

| Category | Target |
|----------|--------|
| Dispatcher Service | 100% |
| HMAC Signatures | 100% |
| Event Payloads | 100% |
| Timeout Handling | 100% |
| Delivery Logging | 100% |
| Queue Jobs | 100% |
| Security | 100% |
| Edge Cases | 100% |

---

## Test Execution

```bash
# Run all webhook dispatcher tests
php artisan test --filter=WebhookDispatcher

# Run unit tests only
php artisan test --filter=WebhookDispatcherServiceTest

# Run integration tests
php artisan test --filter=WebhookDispatcherIntegrationTest

# Run with coverage
php artisan test --filter=WebhookDispatcher --coverage
```

---

## Dependencies

- TASK-6-12 (Webhooks schema) - Required for database tables
- TASK-6-13 (Webhook Management API) - Required for webhook records

---

## Notes for Developer

1. **HTTP Client:** Use Laravel's `Http::` facade with mockable responses for testing.

2. **HMAC Security:** The signature should include timestamp to prevent replay attacks:
   ```
   signature = sha256(timestamp + '.' + payload, secret)
   ```

3. **Timeout Configuration:** Default 30 seconds, configurable per webhook or globally.

4. **Queue Worker:** The dispatcher should run via queue jobs for async processing.

5. **Retry Logic:** Basic retry is part of this task; advanced retry with backoff is TASK-6-15.

6. **Delivery Status:**
   - `delivered_at` set on successful (2xx) or failed (non-2xx) response
   - `next_retry_at` set for retries (TASK-6-15)

7. **Event Filtering:** Only dispatch to webhooks that subscribe to the event type.

---

## Comments

### [2026-02-27 12:58] PreTester: Test Documentation Created
- Created TESTS/TASK-6-14.md with comprehensive test scenarios
- 60+ test scenarios covering dispatcher, HMAC, payloads, logging, security
- Following project test documentation format
- Status: Tests Coverage In Progress
