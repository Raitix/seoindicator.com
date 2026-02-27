# TASK-6-13: Webhook Management API

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
Implement API endpoints for managing webhooks.

## Requirements

### Endpoints
- `GET /api/v1/webhooks` - List webhooks
- `POST /api/v1/webhooks` - Create webhook
- `GET /api/v1/webhooks/:id` - Get webhook
- `PUT /api/v1/webhooks/:id` - Update webhook
- `DELETE /api/v1/webhooks/:id` - Delete webhook
- `POST /api/v1/webhooks/:id/test` - Test webhook

### Create Request
```json
{
  "name": "Slack Integration",
  "url": "https://hooks.slack.com/services/...",
  "events": ["ranking.changed", "alert.triggered"]
}
```

### Create Response
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Slack Integration",
    "secret": "whsec_abc123..."  // Show once
  }
}
```

### Test Endpoint
- Send test payload to webhook URL
- Return delivery status

## Acceptance Criteria
- [ ] All CRUD endpoints working
- [ ] Secret generated and shown once
- [ ] Test endpoint working
- [ ] Input validation
- [ ] Rate limiting applied

## Dependencies
- TASK-6-12 (Webhooks schema)

## Estimated Effort
Medium (3-4 hours)

---

## Comments

### [2026-02-27 12:25] ProjectManager: Task Created
- Priority: High (MVP feature)
- Risk: Medium (new endpoints, secret handling)
- Part of Phase 4: Webhooks
