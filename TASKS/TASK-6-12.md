# TASK-6-12: Webhooks Database Schema

## Status
`Tests Coverage In Progress`

## Priority
`High`

## Risk
`Medium`

## Specification
- Spec: [SPEC-006](../SPECIFICATIONS/SPEC-006.md)
- Feature: [FEAT-006](../FEATURES/FEAT-006.md)

---

## Description
Create database tables for webhooks and delivery tracking.

## Requirements

### Webhooks Table
```sql
CREATE TABLE webhooks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    url TEXT NOT NULL,
    secret TEXT NOT NULL,
    events TEXT NOT NULL,  -- JSON array
    enabled INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Webhook Deliveries Table
```sql
CREATE TABLE webhook_deliveries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    webhook_id INTEGER NOT NULL,
    event_type TEXT NOT NULL,
    payload TEXT NOT NULL,
    response_status INTEGER,
    response_body TEXT,
    attempt_count INTEGER DEFAULT 1,
    delivered_at DATETIME,
    next_retry_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (webhook_id) REFERENCES webhooks(id) ON DELETE CASCADE
);
```

### Indexes
- `idx_webhooks_user` on `user_id`
- `idx_webhooks_enabled` on `enabled`
- `idx_webhook_deliveries_webhook` on `webhook_id`
- `idx_webhook_deliveries_retry` on `next_retry_at`

## Acceptance Criteria
- [ ] Migration file created
- [ ] Tables created with all columns
- [ ] All indexes created
- [ ] Foreign keys working

## Dependencies
- None (foundation task)

## Estimated Effort
Small (1-2 hours)

---

## Comments

### [2026-02-27 12:25] ProjectManager: Task Created
- Priority: High (foundation for webhooks)
- Risk: Medium (new schema)
- Part of Phase 4: Webhooks
