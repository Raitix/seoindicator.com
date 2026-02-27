# TASK-6-14: Webhook Dispatcher Service

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
Implement the webhook dispatcher service for sending webhook payloads.

## Requirements

### Dispatcher Service
- Queue-based dispatch
- HTTP POST to webhook URL
- HMAC signature for security
- Timeout handling

### Event Payload
```json
{
  "id": "evt_abc123",
  "type": "ranking.changed",
  "created_at": "2026-02-19T17:00:00Z",
  "data": {
    "keyword_id": 1,
    "keyword": "seo tools",
    "old_position": 7,
    "new_position": 5,
    "change": -2
  }
}
```

### Security Headers
```
X-SEOIndicator-Signature: sha256=<hmac_hash>
X-SEOIndicator-Timestamp: <unix_timestamp>
```

### Delivery Tracking
- Log all delivery attempts
- Record response status
- Store response body for debugging

## Acceptance Criteria
- [ ] Dispatcher service implemented
- [ ] HMAC signatures working
- [ ] Delivery logging
- [ ] Timeout handling
- [ ] Unit tests

## Dependencies
- TASK-6-12 (Webhooks schema)
- TASK-6-13 (Webhook management)

## Estimated Effort
Medium (3-4 hours)

---

## Comments

### [2026-02-27 12:25] ProjectManager: Task Created
- Priority: High (MVP core feature)
- Risk: Medium (external HTTP calls)
- Part of Phase 4: Webhooks
