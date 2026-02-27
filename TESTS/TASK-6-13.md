# TASK-6-13: Webhook Management API Tests

## Task Reference
- **Task:** [TASK-6-13](../TASKS/TASK-6-13.md)
- **Feature:** [FEAT-006](../FEATURES/FEAT-006.md) - API Access & Webhooks
- **Specification:** [SPEC-006](../SPECIFICATIONS/SPEC-006.md)

---

## Overview

Tests for the `WebhookController` API endpoints handling CRUD operations for webhook management. This controller provides the REST API layer for creating, managing, and testing webhooks that notify external systems of SEO events.

---

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/webhooks` | List all webhooks for authenticated user |
| POST | `/api/v1/webhooks` | Create a new webhook |
| GET | `/api/v1/webhooks/{id}` | Get webhook details |
| PUT | `/api/v1/webhooks/{id}` | Update webhook |
| DELETE | `/api/v1/webhooks/{id}` | Delete webhook |
| POST | `/api/v1/webhooks/{id}/test` | Test webhook delivery |

---

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `backend/tests/Feature/Api/V1/WebhookControllerTest.php` | Feature | Controller endpoint tests with authorization |
| `backend/tests/Unit/Models/WebhookTest.php` | Unit | Model tests for Webhook entity |
| `backend/tests/Unit/Models/WebhookDeliveryTest.php` | Unit | Model tests for WebhookDelivery entity |

---

## Test Scenarios

### Index (List) Tests (IX)

| ID | Scenario | Request | Expected Output |
|----|----------|---------|-----------------|
| IX-001 | List webhooks for user | GET /webhooks | 200, array of webhooks |
| IX-002 | List empty for user with no webhooks | GET /webhooks | 200, empty array |
| IX-003 | List only returns user's webhooks | GET /webhooks | Only user's webhooks |
| IX-004 | List ordered by created_at desc | GET /webhooks | Newest first |
| IX-005 | List includes all fields | GET /webhooks | id, name, url, events, enabled, created_at |
| IX-006 | List excludes secret field | GET /webhooks | No secret in response |
| IX-007 | List with multiple webhooks | GET /webhooks | All webhooks returned |

### Store (Create) Tests (ST)

| ID | Scenario | Request | Expected Output |
|----|----------|---------|-----------------|
| ST-001 | Create webhook with valid data | POST with name, url, events | 201, webhook created |
| ST-002 | Create returns secret once | POST valid data | 201, secret visible |
| ST-003 | Create with minimal fields | POST with name, url, events | 201, defaults applied |
| ST-004 | Create without name | POST without name | 422, validation error |
| ST-005 | Create without url | POST without url | 422, validation error |
| ST-006 | Create without events | POST without events | 422, validation error |
| ST-007 | Create with invalid url format | POST with bad url | 422, validation error |
| ST-008 | Create with non-https url | POST with http url | 422 or 201 (configurable) |
| ST-009 | Create with invalid event type | POST with bad event | 422, validation error |
| ST-010 | Create with empty events array | POST with [] events | 422, validation error |
| ST-011 | Create with name > 255 chars | POST with long name | 422, validation error |
| ST-012 | Create with url > 2048 chars | POST with long url | 422, validation error |
| ST-013 | Create generates unique secret | POST valid data | Secret is cryptographically secure |
| ST-014 | Create with multiple events | POST with multiple events | 201, all events stored |
| ST-015 | Create enabled by default | POST valid data | enabled = true |

### Show (Single) Tests (SH)

| ID | Scenario | Request | Expected Output |
|----|----------|---------|-----------------|
| SH-001 | Show existing webhook | GET /webhooks/{id} | 200, webhook data |
| SH-002 | Show non-existent webhook | GET /webhooks/9999 | 404 Not Found |
| SH-003 | Show excludes secret | GET /webhooks/{id} | No secret in response |
| SH-004 | Show includes all public fields | GET /webhooks/{id} | id, name, url, events, enabled, created_at |
| SH-005 | Show includes delivery stats | GET /webhooks/{id} | Optional: last_delivery, success_rate |

### Update Tests (UP)

| ID | Scenario | Request | Expected Output |
|----|----------|---------|-----------------|
| UP-001 | Update name | PUT with new name | 200, updated name |
| UP-002 | Update url | PUT with new url | 200, updated url |
| UP-003 | Update events | PUT with new events | 200, updated events |
| UP-004 | Update enabled status | PUT with enabled=false | 200, disabled |
| UP-005 | Update all fields | PUT with all fields | 200, all updated |
| UP-006 | Update non-existent webhook | PUT /webhooks/9999 | 404 Not Found |
| UP-007 | Update with invalid url | PUT with bad url | 422, validation error |
| UP-008 | Update with invalid event | PUT with bad event | 422, validation error |
| UP-009 | Partial update (only name) | PUT with name only | 200, other fields unchanged |
| UP-010 | Update does not change secret | PUT any | Secret remains same |

### Destroy (Delete) Tests (DE)

| ID | Scenario | Request | Expected Output |
|----|----------|---------|-----------------|
| DE-001 | Delete existing webhook | DELETE /webhooks/{id} | 204 No Content |
| DE-002 | Delete non-existent webhook | DELETE /webhooks/9999 | 404 Not Found |
| DE-003 | Delete removes from database | DELETE /webhooks/{id} | Record not found after |
| DE-004 | Delete cascades to deliveries | DELETE /webhooks/{id} | Deliveries removed |
| DE-005 | Delete returns no content | DELETE /webhooks/{id} | Empty response body |

### Test Webhook Endpoint Tests (TW)

| ID | Scenario | Request | Expected Output |
|----|----------|---------|-----------------|
| TW-001 | Test webhook success | POST /webhooks/{id}/test | 200, delivered=true |
| TW-002 | Test webhook failure | POST /webhooks/{id}/test (bad url) | 200, delivered=false |
| TW-003 | Test returns response status | POST /webhooks/{id}/test | response_status included |
| TW-004 | Test returns response time | POST /webhooks/{id}/test | response_time_ms included |
| TW-005 | Test non-existent webhook | POST /webhooks/9999/test | 404 Not Found |
| TW-006 | Test creates delivery record | POST /webhooks/{id}/test | Delivery logged |
| TW-007 | Test with disabled webhook | POST /webhooks/{id}/test | 200, test still runs |
| TW-008 | Test timeout handling | POST /webhooks/{id}/test (slow) | 200, timeout reported |

---

### Authorization Tests (AZ) - Unauthenticated

| ID | Scenario | Request | Expected Output |
|----|----------|---------|-----------------|
| AZ-001 | Unauthenticated list | GET /webhooks | 401 Unauthorized |
| AZ-002 | Unauthenticated create | POST /webhooks | 401 Unauthorized |
| AZ-003 | Unauthenticated show | GET /webhooks/{id} | 401 Unauthorized |
| AZ-004 | Unauthenticated update | PUT /webhooks/{id} | 401 Unauthorized |
| AZ-005 | Unauthenticated delete | DELETE /webhooks/{id} | 401 Unauthorized |
| AZ-006 | Unauthenticated test | POST /webhooks/{id}/test | 401 Unauthorized |

---

### Authorization Tests (AZ) - Cross-User Access

| ID | Scenario | Request | Expected Output |
|----|----------|---------|-----------------|
| AZ-010 | List other user's webhooks | GET as user B | Only user B's webhooks |
| AZ-011 | Show other user's webhook | GET as user B on user A's webhook | 404 Not Found |
| AZ-012 | Update other user's webhook | PUT as user B on user A's webhook | 404 Not Found |
| AZ-013 | Delete other user's webhook | DELETE as user B on user A's webhook | 404 Not Found |
| AZ-014 | Test other user's webhook | POST as user B on user A's webhook/test | 404 Not Found |

---

### Authorization Tests (AZ) - Owner Access

| ID | Scenario | Request | Expected Output |
|----|----------|---------|-----------------|
| AZ-020 | Owner can list | GET as owner | 200 OK |
| AZ-021 | Owner can create | POST as owner | 201 Created |
| AZ-022 | Owner can show | GET as owner | 200 OK |
| AZ-023 | Owner can update | PUT as owner | 200 OK |
| AZ-024 | Owner can delete | DELETE as owner | 204 No Content |
| AZ-025 | Owner can test | POST /test as owner | 200 OK |

---

### Edge Case Tests (EC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Empty name string | name="" | 422, validation error |
| EC-002 | Whitespace-only name | name="   " | 422 or trimmed |
| EC-003 | URL with port | url="https://example.com:8080/hook" | 201, allowed |
| EC-004 | URL with query params | url="https://example.com/hook?token=abc" | 201, allowed |
| EC-005 | URL with basic auth | url="https://user:pass@example.com/hook" | 201, allowed |
| EC-006 | Unicode in name | name="Slack 연동 🚀" | 201, saved correctly |
| EC-007 | HTML in name | name="<script>alert(1)</script>" | 201, sanitized or stored |
| EC-008 | SQL injection in name | name="' OR 1=1--" | 201, stored as literal |
| EC-009 | Very long name (255 chars) | name with 255 chars | 201, saved |
| EC-010 | Name exceeds max (256 chars) | name with 256 chars | 422, validation error |
| EC-011 | Empty url string | url="" | 422, validation error |
| EC-012 | Invalid URL protocol | url="ftp://example.com" | 422, validation error |
| EC-013 | Duplicate webhook URL for same user | Same URL twice | 201 or 409 (configurable) |
| EC-014 | Events as object instead of array | events={} | 422, validation error |
| EC-015 | Null events | events=null | 422, validation error |

---

### Integration Tests (IT)

| ID | Scenario | Actions | Expected Output |
|----|----------|---------|-----------------|
| IT-001 | Full CRUD lifecycle | Create, read, update, delete | All operations succeed |
| IT-002 | Multiple webhooks for user | Create 10 webhooks | All listed correctly |
| IT-003 | Create and test webhook | Create, test | Test returns success |
| IT-004 | Update reflected in show | Update, show | Updated values returned |
| IT-005 | Delete removes from list | Create, delete, list | Not in list |
| IT-006 | Secret only shown on create | Create, show, list | Secret only in create response |
| IT-007 | Delivery logged on test | Test webhook | Delivery record exists |
| IT-008 | Disabled webhook excluded from dispatch | Create disabled, trigger event | No delivery attempt |

---

### Security Tests (SC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SC-001 | XSS in name | name="<script>alert(1)</script>" | Stored safely, not executed |
| SC-002 | XSS in url | url="javascript:alert(1)" | 422, rejected |
| SC-003 | SSRF with localhost | url="http://localhost/admin" | 422, rejected or allowed |
| SC-004 | SSRF with internal IP | url="http://192.168.1.1/" | 422, rejected or allowed |
| SC-005 | SSRF with metadata endpoint | url="http://169.254.169.254/" | 422, rejected |
| SC-006 | Secret is hashed/encrypted | Check database | Secret not plaintext |
| SC-007 | Secret is cryptographically random | Check secret format | Min 32 chars, random |
| SC-008 | URL validation prevents injection | url with special chars | Validated correctly |

---

## Valid Event Types

| Event | Description |
|-------|-------------|
| `ranking.changed` | Keyword ranking position changed |
| `alert.triggered` | Alert condition met |
| `report.completed` | Report generation finished |
| `keyword.added` | New keyword added to tracking |
| `keyword.removed` | Keyword removed from tracking |

---

## Mock Data

### Sample Create Request

```json
{
  "name": "Slack Integration",
  "url": "https://hooks.slack.com/services/T00/B00/XXX",
  "events": ["ranking.changed", "alert.triggered"]
}
```

### Sample Create Response (201)

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Slack Integration",
    "url": "https://hooks.slack.com/services/T00/B00/XXX",
    "events": ["ranking.changed", "alert.triggered"],
    "enabled": true,
    "secret": "whsec_abc123def456ghi789...",
    "created_at": "2026-02-27T12:00:00Z"
  }
}
```

### Sample List Response (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Slack Integration",
      "url": "https://hooks.slack.com/services/T00/B00/XXX",
      "events": ["ranking.changed", "alert.triggered"],
      "enabled": true,
      "created_at": "2026-02-27T12:00:00Z"
    }
  ]
}
```

### Sample Test Response (200)

```json
{
  "success": true,
  "data": {
    "delivered": true,
    "response_status": 200,
    "response_time_ms": 150
  }
}
```

### Sample Validation Error (422)

```json
{
  "message": "The url field must be a valid URL.",
  "errors": {
    "url": ["The url field must be a valid URL."]
  }
}
```

---

## Coverage Targets

| Category | Target |
|----------|--------|
| Index (List) | 100% |
| Store (Create) | 100% |
| Show (Read) | 100% |
| Update | 100% |
| Destroy (Delete) | 100% |
| Test Endpoint | 100% |
| Authorization | 100% |
| Edge Cases | 100% |
| Security | 100% |

---

## Test Execution

```bash
# Run all webhook tests
php artisan test --filter=WebhookControllerTest

# Run with coverage
php artisan test --filter=WebhookControllerTest --coverage

# Run specific test
php artisan test --filter=test_create_webhook_with_valid_data
```

---

## Dependencies

- TASK-6-12 (Webhooks schema) - Required for database tables
- User authentication system (existing)

---

## Notes for Developer

1. **Secret Handling:** The `secret` field must only be returned once during creation. Store hashed or encrypted.

2. **URL Validation:** Accept only HTTP/HTTPS URLs. Consider SSRF protection for internal URLs.

3. **Events Validation:** Validate against the allowed event types list. Store as JSON array.

4. **Rate Limiting:** Consider rate limiting webhook creation and test endpoints.

5. **Cascade Delete:** When deleting a webhook, all related `webhook_deliveries` should be deleted.

6. **Test Endpoint:** The test endpoint should send a test payload and record the delivery, but should NOT trigger actual event processing.

---

## Comments

### [2026-02-27 12:55] PreTester: Test Documentation Created
- Created TESTS/TASK-6-13.md with comprehensive test scenarios
- 79 test scenarios covering all CRUD operations, authorization, edge cases, and security
- Following project test documentation format
- Status: Tests Coverage In Progress
