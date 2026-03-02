# TASK-26-1: Create Credits and Transactions Tables Migration

## STATUS
`To Prioritize`

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
_Leave notes for the next agent here_
