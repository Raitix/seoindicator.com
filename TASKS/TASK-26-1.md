# TASK-26-1: Create Credits and Transactions Tables Migration

## STATUS
`Passed Local Tests`

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
- [x] `id` - BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- [x] `user_id` - BIGINT UNSIGNED NOT NULL with foreign key to users table (ON DELETE CASCADE)
- [x] `balance` - DECIMAL(10, 2) NOT NULL DEFAULT 0.00
- [x] `total_purchased` - DECIMAL(10, 2) NOT NULL DEFAULT 0.00
- [x] `total_spent` - DECIMAL(10, 2) NOT NULL DEFAULT 0.00
- [x] `created_at` and `updated_at` timestamps
- [x] UNIQUE constraint on `user_id` (one credits record per user)

### credits Indexes
- [x] Unique index on `user_id`

### credit_transactions Table
- [x] `id` - BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- [x] `user_id` - BIGINT UNSIGNED NOT NULL with foreign key to users table (ON DELETE CASCADE)
- [x] `amount` - DECIMAL(10, 2) NOT NULL
- [x] `balance_after` - DECIMAL(10, 2) NOT NULL
- [x] `type` - ENUM('purchase', 'usage', 'refund', 'bonus') NOT NULL
- [x] `description` - VARCHAR(500) NOT NULL
- [x] `reference_type` - VARCHAR(50) NULL (polymorphic relation type)
- [x] `reference_id` - BIGINT UNSIGNED NULL (polymorphic relation ID)
- [x] `payment_provider` - VARCHAR(20) NULL ('stripe', 'paypal')
- [x] `payment_id` - VARCHAR(255) NULL (external payment reference)
- [x] `metadata` - JSON NULL (additional transaction data)
- [x] `created_at` - TIMESTAMP DEFAULT CURRENT_TIMESTAMP

### credit_transactions Indexes
- [x] Index on `user_id`
- [x] Index on `type`
- [x] Index on `created_at`
- [x] Composite index on `(payment_provider, payment_id)`

### Migration Files
- [x] Create migration file with both tables
- [x] Migration follows Laravel naming convention
- [x] Migration includes down() method for rollback

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

### 2026-03-14 - Developer
**Implementation Complete:**
- Migration file created: `backend/database/migrations/2026_03_14_100000_create_credits_tables.php`
- Migration run successfully against database
- All 47 unit tests passed (67 assertions)
- Committed to git: `feat(billing): create credits and credit_transactions tables migration`
- **Status changed to: To Test Locally**

**Implementation Details:**
- `credits` table: id, user_id (FK CASCADE), balance, total_purchased, total_spent, timestamps, unique(user_id)
- `credit_transactions` table: id, user_id (FK CASCADE), amount, balance_after, type (ENUM), description, reference_type, reference_id, payment_provider, payment_id, metadata (JSON), created_at
- Indexes: user_id, type, created_at, composite(payment_provider, payment_id)
- No updated_at on transactions (immutable audit trail)

### 2026-03-14 - LocalTester
**Local QA Complete:**
- **Test Results:** 47 tests passed (67 assertions) in 11.58s
- **Test File:** `backend/tests/Unit/Migrations/CreateCreditsTablesTest.php`
- **Fixed Issue:** Duplicate `negative_amount()` method in CreditTransactionTest.php renamed to `edge_case_negative_amount()`

**Test Coverage Verified:**
- ✅ credits table schema (8 tests)
- ✅ credit_transactions table schema (14 tests)
- ✅ Index verification (5 tests)
- ✅ ENUM validation (4 tests)
- ✅ DECIMAL precision (5 tests)
- ✅ Nullable fields (3 tests)
- ✅ JSON metadata (2 tests)
- ✅ Payment providers (2 tests)
- ✅ Security tests (3 tests)
- ✅ Unique constraint on user_id
- ✅ Foreign key cascade delete

**Verdict:** All tests passed. Ready for DevOps deployment.
**Status changed to:** Passed Local Tests
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
