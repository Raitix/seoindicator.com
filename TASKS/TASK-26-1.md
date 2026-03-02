# TASK-26-1: Create Credits and Transactions Tables Migration

## STATUS
`In Development`

## PRIORITY
`High`

## RISK
`Medium`

## Related Specification
SPEC-026: Billing, Credits & Model Selection

## Related Feature
FEAT-026: Billing, Credits & Model Selection

---

## Description
Create database migrations for `credits` and `credit_transactions` tables to support the credit-based billing system.

## Acceptance Criteria

### credits Table
- [ ] `id` - BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- [ ] `user_id` - BIGINT UNSIGNED NOT NULL with foreign key to users table (ON DELETE CASCADE)
- [ ] `balance` - DECIMAL(10, 2) NOT NULL DEFAULT 0.00
- [ ] `total_purchased` - DECIMAL(10, 2) NOT NULL DEFAULT 0.00
- [ ] `total_spent` - DECIMAL(10, 2) NOT NULL DEFAULT 0.00
- [ ] `created_at` and `updated_at` timestamps
- [ ] UNIQUE constraint on `user_id` (one credits record per user)

### credits Indexes
- [ ] Unique index on `user_id`

### credit_transactions Table
- [ ] `id` - BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- [ ] `user_id` - BIGINT UNSIGNED NOT NULL with foreign key to users table (ON DELETE CASCADE)
- [ ] `amount` - DECIMAL(10, 2) NOT NULL
- [ ] `balance_after` - DECIMAL(10, 2) NOT NULL
- [ ] `type` - ENUM('purchase', 'usage', 'refund', 'bonus') NOT NULL
- [ ] `description` - VARCHAR(500) NOT NULL
- [ ] `reference_type` - VARCHAR(50) NULL (polymorphic relation type)
- [ ] `reference_id` - BIGINT UNSIGNED NULL (polymorphic relation ID)
- [ ] `payment_provider` - VARCHAR(20) NULL ('stripe', 'paypal')
- [ ] `payment_id` - VARCHAR(255) NULL (external payment reference)
- [ ] `metadata` - JSON NULL (additional transaction data)
- [ ] `created_at` - TIMESTAMP DEFAULT CURRENT_TIMESTAMP

### credit_transactions Indexes
- [ ] Index on `user_id`
- [ ] Index on `type`
- [ ] Index on `created_at`
- [ ] Composite index on `(payment_provider, payment_id)`

### Migration Files
- [ ] Create migration file with both tables
- [ ] Migration follows Laravel naming convention
- [ ] Migration includes down() method for rollback

## Dependencies
- Users table (exists)

## Effort Estimate
`Medium` (2-3 hours)

## Notes
- DECIMAL(10, 2) supports up to $99,999,999.99 per user (sufficient for MVP)
- No `updated_at` on transactions table (transactions are immutable)
- Polymorphic references allow linking to analyses, refunds, etc.
- Payment provider indexing enables webhook lookup

## Security Considerations
- All monetary columns use DECIMAL for precision
- No sensitive payment data stored (only references)
- Audit trail via `credit_transactions` is immutable

## Comments

### 2026-03-02 - ProjectManager
**Prioritization Complete:**
- **Priority: High** (confirmed) - Foundation task for billing system MVP
- **Risk: Medium** (confirmed) - Non-destructive migration, adds new tables only
- **Dependencies:** Users table (exists ✅) - No blockers
- **Execution Order:** First task in Phase 1, enables TASK-26-2, 26-3, 26-4

**Phase 1 Context:**
- TASK-26-1 (this task): Migrations - foundation, no deps
- TASK-26-2: Credit model - depends on TASK-26-1
- TASK-26-3: Credit packages - depends on TASK-26-1
- TASK-26-4: AI models table - depends on TASK-26-1

**Security Notes for PreTester:**
- DECIMAL(10,2) for monetary precision
- Foreign keys with CASCADE delete for data integrity
- No sensitive payment data stored (only references)
- Transactions table is immutable (no updated_at)

**Test Coverage Requirements:**
- Migration rollback test
- Foreign key constraint test (user cascade delete)
- Unique constraint test (one credits record per user)
- Index verification (user_id, type, created_at, payment lookup)
- JSON metadata field validation

---
_Leave notes for the next agent here_

### 2026-03-02 - PreTester
**Test Coverage Complete:**
- Test documentation created: `TESTS/TASK-26-1.md`
- Migration test file created: `backend/tests/Unit/Migrations/CreateCreditsTablesTest.php`
- **45+ test scenarios covering:**
  - **credits table**: 10 tests (columns, indexes, unique constraint, FK)
  - **credit_transactions table**: 14 tests (columns, no updated_at for immutability)
  - **Index tests**: 5 tests (user_id, type, created_at, composite payment index)
  - **ENUM validation**: 5 tests (purchase, usage, refund, bonus, invalid rejected)
  - **DECIMAL precision**: 5 tests (max value, zero, small, negative)
  - **Nullable fields**: 3 tests (reference, payment, metadata)
  - **JSON metadata**: 2 tests (simple, nested)
  - **Payment providers**: 2 tests (stripe, paypal)
  - **Migration rollback**: 2 tests (drop tables, idempotent re-run)
  - **Security**: 3 tests (SQL injection, special chars, unicode)
- Tests follow existing project patterns from `CreateAlertSettingsTableTest.php`
- **Ready for Developer to implement migration**
