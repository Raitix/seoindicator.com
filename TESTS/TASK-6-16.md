# TASK-6-16: Event Emitters Integration Tests

## Task Reference
- **Task:** [TASK-6-16](../TASKS/TASK-6-16.md)
- **Feature:** [FEAT-006](../FEATURES/FEAT-006.md) - API Access & Webhooks
- **Specification:** [SPEC-006](../SPECIFICATIONS/SPEC-006.md)

---

## Overview

Tests for integrating webhook event emitters into existing services. Event emitters are responsible for dispatching webhook events when significant actions occur in the application, such as ranking changes, alert triggers, report completion, and keyword modifications.

---

## Events to Emit

| Event | Trigger Location | Service |
|-------|------------------|---------|
| `ranking.changed` | Ranking check service | `RankCheckerService` |
| `alert.triggered` | Alert service | AlertService (to be created) |
| `report.completed` | Report generation | `ReportScheduler` / `ReportRepository` |
| `keyword.added` | Keyword creation | `KeywordRepository` / `KeywordController` |
| `keyword.removed` | Keyword deletion | `KeywordRepository` / `KeywordController` |

---

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `backend/tests/Unit/Services/EventEmitterTest.php` | Unit | Event emitter trait/class tests |
| `backend/tests/Unit/Services/RankCheckerServiceEventTest.php` | Unit | Ranking event emission tests |
| `backend/tests/Unit/Services/ReportSchedulerEventTest.php` | Unit | Report completion event tests |
| `backend/tests/Unit/Services/KeywordEventTest.php` | Unit | Keyword add/remove event tests |
| `backend/tests/Integration/EventEmitterIntegrationTest.php` | Integration | End-to-end event flow tests |
| `backend/tests/Feature/EventDispatchTest.php` | Feature | Full request-to-webhook flow |

---

## Test Scenarios

### EventEmitter Service Tests (EE)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EE-001 | Emit event with valid data | event type + payload | Event dispatched |
| EE-002 | Generate unique event ID | Any event | ID format: evt_[a-f0-9]{24} |
| EE-003 | Include timestamp in event | Any event | created_at in ISO 8601 format |
| EE-004 | Emit to multiple webhooks | Multiple matching webhooks | All webhooks receive event |
| EE-005 | Filter by event subscription | Webhook with specific events | Only subscribed webhooks receive |
| EE-006 | Skip disabled webhooks | Disabled webhook | No dispatch attempt |
| EE-007 | Handle no matching webhooks | No webhooks for event | Graceful no-op |
| EE-008 | Emit event synchronously | Default config | Event dispatched immediately |
| EE-009 | Emit event via queue | Queue config enabled | Job dispatched to queue |
| EE-010 | Include webhook secret | Any dispatch | Secret used for signature |
| EE-011 | Event payload structure | Ranking event | Correct nested data structure |
| EE-012 | Event type validation | Invalid event type | Exception or ignored |

---

### Ranking Changed Event Tests (RC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RC-001 | Emit on position improvement | Position 7 → 5 | Event with change: -2 |
| RC-002 | Emit on position decline | Position 5 → 10 | Event with change: +5 |
| RC-003 | Emit on first ranking | No previous → Position 15 | Event with old_position: null |
| RC-004 | Emit on drop from top 100 | Position 50 → null | Event with new_position: null |
| RC-005 | No emit on same position | Position 5 → 5 | No event emitted |
| RC-006 | Emit with keyword data | Any position change | keyword_id, keyword, site_id included |
| RC-007 | Emit with checked_at timestamp | Any position change | checked_at timestamp included |
| RC-008 | Emit for user's webhooks only | User A's keyword change | Only User A's webhooks notified |
| RC-009 | Emit after ranking stored | Position change | Ranking record exists before event |
| RC-010 | Handle concurrent checks | Same keyword checked twice | Correct events for each check |
| RC-011 | Emit with SERP features | Ranking with features | serp_features in payload |
| RC-012 | Handle check failure | Error during check | No event emitted (error logged) |

---

### Report Completed Event Tests (RP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RP-001 | Emit on report completion | Report generated successfully | Event dispatched |
| RP-002 | Emit with report ID | Completed report | report_id in payload |
| RP-003 | Emit with site ID | Completed report | site_id in payload |
| RP-004 | Emit with report type | Weekly report | type: "weekly" in payload |
| RP-005 | Emit with format | PDF report | format: "pdf" in payload |
| RP-006 | Emit with download URL | Completed report | download_url in payload |
| RP-007 | No emit on report failure | Report generation failed | No event emitted |
| RP-008 | Emit for scheduled report | Scheduled report completes | scheduled_report_id in payload |
| RP-009 | Emit for manual report | Manual report completes | scheduled_report_id: null |
| RP-010 | Emit after file stored | Report file saved | Event after storage complete |
| RP-011 | Emit with file size | Completed report | file_size bytes in payload |
| RP-012 | Emit with date range | Report with date range | date_range in payload |

---

### Keyword Added Event Tests (KA)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| KA-001 | Emit on keyword created | New keyword added | Event dispatched |
| KA-002 | Emit with keyword ID | Created keyword | keyword_id in payload |
| KA-003 | Emit with keyword text | Created keyword | keyword in payload |
| KA-004 | Emit with site ID | Created keyword | site_id in payload |
| KA-005 | Emit with target URL | Keyword with URL | target_url in payload |
| KA-006 | Emit with search engine | Google keyword | search_engine: "google" |
| KA-007 | Emit with location | US keyword | location: "us" |
| KA-008 | Emit with device | Desktop keyword | device: "desktop" |
| KA-009 | Emit for user's webhooks | User creates keyword | User's webhooks notified |
| KA-010 | Emit after keyword stored | Keyword created | Keyword exists before event |
| KA-011 | Emit on bulk import | 10 keywords imported | 10 events dispatched |
| KA-012 | Emit on API creation | API POST /keywords | Event dispatched |
| KA-013 | Emit on UI creation | Form submission | Event dispatched |

---

### Keyword Removed Event Tests (KR)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| KR-001 | Emit on keyword deleted | Keyword removed | Event dispatched |
| KR-002 | Emit with keyword ID | Deleted keyword | keyword_id in payload |
| KR-003 | Emit with keyword text | Deleted keyword | keyword in payload |
| KR-004 | Emit with site ID | Deleted keyword | site_id in payload |
| KR-005 | Emit before deletion | Keyword deletion | Keyword exists when event fires |
| KR-006 | Emit on soft delete | Status set to deleted | Event dispatched |
| KR-007 | Emit on hard delete | Record deleted | Event dispatched |
| KR-008 | Emit for user's webhooks | User deletes keyword | User's webhooks notified |
| KR-009 | Emit on API deletion | API DELETE /keywords/{id} | Event dispatched |
| KR-010 | Emit on UI deletion | Delete button click | Event dispatched |
| KR-011 | No emit on bulk delete failure | Error in bulk delete | No events emitted |
| KR-012 | Emit on cascade delete | Site deleted with keywords | Events for each keyword |

---

### Alert Triggered Event Tests (AT)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AT-001 | Emit on alert trigger | Alert condition met | Event dispatched |
| AT-002 | Emit with alert ID | Triggered alert | alert_id in payload |
| AT-003 | Emit with alert name | Triggered alert | alert_name in payload |
| AT-004 | Emit with keyword ID | Keyword alert | keyword_id in payload |
| AT-005 | Emit with trigger value | Position threshold | trigger_value in payload |
| AT-006 | Emit with condition type | Position change | condition: "position_change" |
| AT-007 | Emit with current value | Current position | current_value in payload |
| AT-008 | Emit with previous value | Previous position | previous_value in payload |
| AT-009 | Emit for user's webhooks | User's alert triggered | User's webhooks notified |
| AT-010 | Emit once per trigger | Same alert | One event per trigger |
| AT-011 | Handle cooldown period | Alert in cooldown | No event emitted |
| AT-012 | Emit with timestamp | Alert triggered | triggered_at in payload |

---

### Webhook Dispatcher Integration Tests (WD)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| WD-001 | Dispatcher receives event | Event emitted | Dispatcher called |
| WD-002 | Dispatcher filters by event | event.type | Only matching webhooks |
| WD-003 | Dispatcher skips disabled | enabled=false | No HTTP call |
| WD-004 | Dispatcher uses queue | Queue enabled | Job queued |
| WD-005 | Dispatcher sends immediately | Sync mode | HTTP call immediately |
| WD-006 | Delivery logged | Successful dispatch | Delivery record created |
| WD-007 | Multiple webhooks notified | 3 webhooks for event | 3 dispatches |
| WD-008 | Partial failure handling | 1 of 3 webhooks fails | Others succeed |
| WD-009 | Event ordering preserved | Events in sequence | Dispatched in order |
| WD-010 | High volume handling | 100 events rapidly | All processed |

---

### Performance Tests (PF)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PF-001 | Event emit overhead | Single event | <5ms overhead |
| PF-002 | No blocking on dispatch | Sync dispatch | Main flow completes |
| PF-003 | Queue dispatch latency | Queued event | Job queued in <10ms |
| PF-004 | Memory usage | 1000 events | Memory stable |
| PF-005 | Database impact | Event with payload | No query degradation |
| PF-006 | Concurrent events | 10 simultaneous events | All processed correctly |
| PF-007 | Large payload handling | 10KB event payload | Processed without issue |
| PF-008 | No memory leaks | Repeated events | Memory freed |

---

### Error Handling Tests (EH)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EH-001 | Handle null keyword | keyword_id: null | Event handled gracefully |
| EH-002 | Handle missing site | site_id: 9999 | Event handled gracefully |
| EH-003 | Handle dispatcher exception | Dispatcher throws | Logged, main flow continues |
| EH-004 | Handle queue failure | Queue unavailable | Event logged, retry possible |
| EH-005 | Handle invalid payload | Malformed data | Event not dispatched |
| EH-006 | Handle webhook deleted mid-dispatch | Webhook deleted | Graceful handling |
| EH-007 | Handle database rollback | Transaction rollback | Events not dispatched |
| EH-008 | Handle partial data | Missing optional fields | Event with null values |

---

### Security Tests (SC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SC-001 | User isolation | User A's event | Only User A's webhooks |
| SC-002 | Cross-tenant protection | User A's keyword | User B's webhooks not notified |
| SC-003 | Secret not in payload | Any event | Secret never in event data |
| SC-004 | Payload not tampered | Event payload | Exact data dispatched |
| SC-005 | No sensitive data leak | User email in keyword | Email not in payload |
| SC-006 | Rate limiting respected | Rapid events | Rate limit applied |
| SC-007 | Signature verification | Any dispatch | Valid HMAC signature |
| SC-008 | Timestamp included | Any event | Timestamp for replay protection |

---

### Edge Case Tests (EC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Empty events array | No webhooks for event | Graceful no-op |
| EC-002 | Webhook with no events | webhook.events = [] | No dispatch |
| EC-003 | Keyword without site | site_id = null | Event with null site_id |
| EC-004 | Position = null | Not ranked | Event with null position |
| EC-005 | Negative position change | 10 → 5 | change: -5 |
| EC-006 | Large position change | 1 → 100 | Correct change: +99 |
| EC-007 | Unicode in keyword | "café tools" | UTF-8 encoded correctly |
| EC-008 | Very long keyword | 500 char keyword | Handled correctly |
| EC-009 | Special chars in payload | <script> in data | Sent as-is (receiver sanitizes) |
| EC-010 | Null optional fields | target_url = null | Event with null value |
| EC-011 | Concurrent modification | Update during emit | Consistent state |
| EC-012 | Transaction isolation | Event in transaction | Event dispatched after commit |

---

### Integration Tests (IT)

| ID | Scenario | Actions | Expected Output |
|----|----------|---------|-----------------|
| IT-001 | Full ranking event flow | Check ranking → emit → dispatch | End-to-end success |
| IT-002 | Full keyword add flow | Create keyword → emit → dispatch | End-to-end success |
| IT-003 | Full keyword remove flow | Delete keyword → emit → dispatch | End-to-end success |
| IT-004 | Full report complete flow | Generate report → emit → dispatch | End-to-end success |
| IT-005 | Multiple event types | Trigger different events | Correct payloads each |
| IT-006 | Event filtering | Multiple webhooks, different events | Correct delivery |
| IT-007 | Event ordering | Rapid events | Maintained order |
| IT-008 | Queue processing | Queue events → process | All dispatched |
| IT-009 | Error recovery | Failed webhook → retry | Eventually succeeds |
| IT-010 | Delivery history | Multiple events | All logged correctly |

---

## Event Payload Structures

### ranking.changed Event
```json
{
  "id": "evt_abc123def456ghi789jkl",
  "type": "ranking.changed",
  "created_at": "2026-02-27T12:00:00Z",
  "data": {
    "keyword_id": 1,
    "keyword": "seo tools",
    "site_id": 1,
    "old_position": 7,
    "new_position": 5,
    "change": -2,
    "url_found": "https://example.com/seo-tools",
    "checked_at": "2026-02-27T12:00:00Z",
    "serp_features": {
      "featured_snippet": false,
      "local_pack": true
    }
  }
}
```

### keyword.added Event
```json
{
  "id": "evt_xyz789abc456def123ghi",
  "type": "keyword.added",
  "created_at": "2026-02-27T12:00:00Z",
  "data": {
    "keyword_id": 1,
    "keyword": "seo tools",
    "site_id": 1,
    "target_url": "https://example.com/seo-tools",
    "search_engine": "google",
    "location": "us",
    "device": "desktop",
    "status": "active"
  }
}
```

### keyword.removed Event
```json
{
  "id": "evt_qwe456rty789uio123pas",
  "type": "keyword.removed",
  "created_at": "2026-02-27T12:00:00Z",
  "data": {
    "keyword_id": 1,
    "keyword": "seo tools",
    "site_id": 1,
    "removed_at": "2026-02-27T12:00:00Z"
  }
}
```

### report.completed Event
```json
{
  "id": "evt_rep123ort456com789ple",
  "type": "report.completed",
  "created_at": "2026-02-27T12:00:00Z",
  "data": {
    "report_id": 1,
    "site_id": 1,
    "scheduled_report_id": 5,
    "type": "weekly",
    "format": "pdf",
    "download_url": "/api/v1/reports/1/download",
    "file_size": 1024000,
    "date_range": {
      "start": "2026-02-20",
      "end": "2026-02-27"
    },
    "generated_at": "2026-02-27T12:00:00Z"
  }
}
```

### alert.triggered Event
```json
{
  "id": "evt_alr123ert456trig789ger",
  "type": "alert.triggered",
  "created_at": "2026-02-27T12:00:00Z",
  "data": {
    "alert_id": 1,
    "alert_name": "Position Drop Alert",
    "keyword_id": 1,
    "keyword": "seo tools",
    "site_id": 1,
    "condition": "position_drop",
    "threshold": 5,
    "previous_value": 5,
    "current_value": 15,
    "triggered_at": "2026-02-27T12:00:00Z"
  }
}
```

---

## Implementation Pattern

### EventEmitter Trait/Service
```php
trait EmitsWebhookEvents
{
    protected function emitEvent(string $type, array $data, int $userId): void
    {
        $event = [
            'id' => 'evt_' . bin2hex(random_bytes(12)),
            'type' => $type,
            'created_at' => now()->toIso8601ZuluString(),
            'data' => $data,
        ];

        $webhooks = Webhook::where('user_id', $userId)
            ->where('enabled', true)
            ->whereJsonContains('events', $type)
            ->get();

        foreach ($webhooks as $webhook) {
            dispatch(new DispatchWebhookJob($webhook->id, $event));
        }
    }
}
```

### Integration in RankCheckerService
```php
public function checkAndStore(int $keywordId, ?array $competitorDomains = null): bool
{
    // ... existing ranking check logic ...
    
    // Get previous position before update
    $previousPosition = $keyword->current_position;
    
    // Store new ranking
    Ranking::create([...]);
    
    // Update keyword
    $keyword->last_checked_at = $checkedAt;
    $keyword->save();
    
    // Emit event if position changed
    if ($previousPosition !== $result['user']['position']) {
        $this->emitEvent('ranking.changed', [
            'keyword_id' => $keyword->id,
            'keyword' => $keyword->keyword,
            'site_id' => $keyword->site_id,
            'old_position' => $previousPosition,
            'new_position' => $result['user']['position'],
            'change' => ($previousPosition ?? 0) - ($result['user']['position'] ?? 0),
            'checked_at' => $checkedAt->toIso8601ZuluString(),
        ], $keyword->user_id);
    }
    
    return true;
}
```

---

## Coverage Targets

| Category | Target |
|----------|--------|
| EventEmitter Service | 100% |
| Ranking Events | 100% |
| Keyword Events | 100% |
| Report Events | 100% |
| Alert Events | 100% |
| Webhook Integration | 100% |
| Performance | 100% |
| Error Handling | 100% |
| Security | 100% |
| Edge Cases | 100% |

---

## Test Execution

```bash
# Run all event emitter tests
php artisan test --filter="Event"

# Run ranking event tests
php artisan test --filter="RankCheckerServiceEventTest"

# Run keyword event tests
php artisan test --filter="KeywordEventTest"

# Run integration tests
php artisan test --filter="EventEmitterIntegrationTest"

# Run with coverage
php artisan test --filter="Event" --coverage
```

---

## Dependencies

- TASK-6-12 (Webhooks schema) - Required for webhook tables
- TASK-6-13 (Webhook Management API) - Required for webhook records
- TASK-6-14 (Webhook Dispatcher) - Required for dispatching events
- Existing services: RankCheckerService, ReportScheduler, KeywordRepository

---

## Notes for Developer

1. **Event Emission Timing:** Emit events AFTER the main operation completes (ranking stored, keyword created, etc.) to ensure data consistency.

2. **Queue vs Sync:** Use queued dispatch for production to avoid blocking main flows. Sync dispatch acceptable for testing.

3. **Event ID Format:** Use `evt_` prefix followed by 24 hex characters for uniqueness.

4. **Position Change Calculation:**
   - Negative = improved (moved up in rankings)
   - Positive = declined (moved down in rankings)
   - Null for old_position = first ranking
   - Null for new_position = dropped out of top 100

5. **User Isolation:** Events must only be dispatched to webhooks owned by the same user who owns the resource (keyword, report, etc.).

6. **Transaction Safety:** If using database transactions, emit events AFTER the transaction commits to avoid sending events for rolled-back data.

7. **Error Handling:** Event emission failures should NOT cause the main operation to fail. Log errors and continue.

8. **Performance:** Use queue jobs for webhook dispatch to avoid blocking the main request flow.

---

## Comments

### [2026-02-27 13:10] PreTester: Test Documentation Created
- Created TESTS/TASK-6-16.md with comprehensive test scenarios
- 80+ test scenarios covering all event types, integration, security, performance
- Defined event payload structures for all 5 event types
- Provided implementation pattern examples
- Following project test documentation format
- Status: Tests Coverage In Progress
