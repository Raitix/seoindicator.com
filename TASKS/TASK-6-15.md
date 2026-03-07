# TASK-6-15: Webhook Retry Logic

## Status
`Tests Coverage In Progress`

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
