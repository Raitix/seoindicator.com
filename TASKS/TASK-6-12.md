# TASK-6-12: Webhooks Database Schema

## Status
`To Test Prod`

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
- [x] Migration file created
- [x] Tables created with all columns
- [x] All indexes created
- [x] Foreign keys working

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

### [2026-02-28 10:55] PreTester: Tests Completed
- Created 40 test scenarios (101 assertions) for webhooks database schema
- Test file: `backend/tests/Feature/Migrations/CreateWebhooksTablesTest.php`
- Test documentation: `TESTS/TEST-6-12.md`
- All tests passing
- Coverage: table structure, foreign keys, indexes, relationships, security
- Migration already exists, models and factories already implemented
- Ready for Developer (task is complete - migration already in place)

### [2026-02-28 10:55] Developer: Implementation Verified
- **Status:** Implementation was already complete before assignment
- **Verification:** Ran all 40 tests - passed (101 assertions, 3.00s)
- **Files verified:**
  - Migration: `2026_02_27_125500_create_webhooks_tables.php` ✅
  - Model: `Webhook.php` with relationships, scopes, and helper methods ✅
  - Model: `WebhookDelivery.php` with relationships and status helpers ✅
- **Acceptance Criteria Met:**
  - [x] Migration file created
  - [x] Tables created with all columns (webhooks, webhook_deliveries)
  - [x] All indexes created (user_id, enabled, webhook_id, next_retry_at, response_status)
  - [x] Foreign keys working (cascade delete verified)
- **Ready for:** LocalTester

### [2026-02-28 10:58 GMT+2] LocalTester: Local Testing Complete
- **Test Results:** 40/40 tests passed (101 assertions)
- **Duration:** 2.86s
- **Coverage:**
  - Webhooks table structure (18 tests)
  - Webhook deliveries table structure (14 tests)
  - Relationships (3 tests)
  - Security: SQL injection, XSS (5 tests)
- **Verification:**
  - [x] Migration file exists and runs correctly
  - [x] All columns present with correct types
  - [x] Foreign key constraints enforced (invalid user_id/webhook_id rejected)
  - [x] Cascade delete working (user → webhooks → deliveries)
  - [x] Unique constraint on secret column
  - [x] All indexes created (user_id, enabled, webhook_id, next_retry_at, response_status)
  - [x] JSON casting for events and payload columns
  - [x] Secret hidden from model serialization
- **Status:** Ready for DevOps deployment

### [2026-02-28 12:15 GMT+2] DevOps: Deployment Complete
- **Verification:** SSH to production confirmed migration already deployed
- **Migration Status:** `2026_02_27_125500_create_webhooks_tables` → [15] Ran
- **Tables Created:** `webhooks`, `webhook_deliveries`
- **Indexes:** All 5 indexes created (user_id, enabled, webhook_id, next_retry_at, response_status)
- **Foreign Keys:** Cascading delete configured and working
- **Deployment Method:** Automatic via GitHub Actions (commit 9f0321d already pushed)
- **Status:** Ready for ProdTester
