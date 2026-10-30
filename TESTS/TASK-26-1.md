# TEST-26-1: Credits and Transactions Tables Migration

## Task Reference
- **Task:** TASK-26-1
- **Feature:** FEAT-026 (Billing, Credits & Model Selection)
- **Specification:** SPEC-026
- **Status:** Tests Coverage In Progress

---

## Overview
Tests for the `credits` and `credit_transactions` table migrations. Verifies schema structure, column definitions, indexes, foreign key constraints, ENUM validation, DECIMAL precision, and rollback functionality for the credit-based billing system foundation.

---

## Test Files

| File | Type | Tests | Assertions |
|------|------|-------|------------|
| `backend/tests/Unit/Migrations/CreateCreditsTablesTest.php` | Integration | 45+ | 120+ |

---

## Test Scenarios

### credits Table Schema Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CS-01 | Table exists | Run migration | `credits` table created |
| CS-02 | Primary key | Inspect `id` column | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| CS-03 | user_id column | Inspect column | BIGINT UNSIGNED NOT NULL |
| CS-04 | balance column | Inspect column | DECIMAL(10, 2) NOT NULL DEFAULT 0.00 |
| CS-05 | total_purchased column | Inspect column | DECIMAL(10, 2) NOT NULL DEFAULT 0.00 |
| CS-06 | total_spent column | Inspect column | DECIMAL(10, 2) NOT NULL DEFAULT 0.00 |
| CS-07 | created_at column | Inspect column | TIMESTAMP NULLABLE |
| CS-08 | updated_at column | Inspect column | TIMESTAMP NULLABLE |

### credits Table Index Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CI-01 | Unique index on user_id | Check index | UNIQUE INDEX on user_id |
| CI-02 | Foreign key to users | Check constraint | FK to users.id ON DELETE CASCADE |

### credits Table Constraint Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CC-01 | One credits record per user | Insert two credits for same user_id | Error: duplicate key |
| CC-02 | Foreign key cascade delete | Delete user record | Related credits record deleted |
| CC-03 | Valid balance insert | Insert balance 100.50 | Success |
| CC-04 | Balance precision | Insert balance 99999999.99 | Success (max DECIMAL 10,2) |
| CC-05 | Balance precision exceeded | Insert balance 999999999.99 | Error / truncated |
| CC-06 | Negative balance allowed | Insert balance -10.00 | Success (business logic may restrict) |
| CC-07 | Balance default value | Insert without balance | Default 0.00 |
| CC-08 | total_purchased default | Insert without total_purchased | Default 0.00 |
| CC-09 | total_spent default | Insert without total_spent | Default 0.00 |

### credit_transactions Table Schema Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CT-01 | Table exists | Run migration | `credit_transactions` table created |
| CT-02 | Primary key | Inspect `id` column | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| CT-03 | user_id column | Inspect column | BIGINT UNSIGNED NOT NULL |
| CT-04 | amount column | Inspect column | DECIMAL(10, 2) NOT NULL |
| CT-05 | balance_after column | Inspect column | DECIMAL(10, 2) NOT NULL |
| CT-06 | type column | Inspect column | ENUM('purchase', 'usage', 'refund', 'bonus') NOT NULL |
| CT-07 | description column | Inspect column | VARCHAR(500) NOT NULL |
| CT-08 | reference_type column | Inspect column | VARCHAR(50) NULL |
| CT-09 | reference_id column | Inspect column | BIGINT UNSIGNED NULL |
| CT-10 | payment_provider column | Inspect column | VARCHAR(20) NULL |
| CT-11 | payment_id column | Inspect column | VARCHAR(255) NULL |
| CT-12 | metadata column | Inspect column | JSON NULL |
| CT-13 | created_at column | Inspect column | TIMESTAMP DEFAULT CURRENT_TIMESTAMP |
| CT-14 | No updated_at | Check for column | Column should NOT exist (immutable) |

### credit_transactions Table Index Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TI-01 | Index on user_id | Check index | INDEX on user_id |
| TI-02 | Index on type | Check index | INDEX on type |
| TI-03 | Index on created_at | Check index | INDEX on created_at |
| TI-04 | Composite payment index | Check index | INDEX on (payment_provider, payment_id) |

### credit_transactions Table Constraint Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-01 | Foreign key to users | Check constraint | FK to users.id ON DELETE CASCADE |
| TC-02 | Cascade delete user | Delete user record | Related transactions deleted |
| TC-03 | Valid type values | Insert type 'purchase' | Success |
| TC-04 | Valid type values | Insert type 'usage' | Success |
| TC-05 | Valid type values | Insert type 'refund' | Success |
| TC-06 | Valid type values | Insert type 'bonus' | Success |
| TC-07 | Invalid type value | Insert type 'invalid' | SQL error / rejected |
| TC-08 | Amount precision | Insert amount 99999999.99 | Success (max DECIMAL 10,2) |
| TC-09 | Negative amount allowed | Insert amount -50.00 | Success (refunds, usage) |
| TC-10 | Description required | Insert without description | Error / rejected |
| CT-11 | Nullable reference fields | Insert without reference_type/id | Success |
| CT-12 | Nullable payment fields | Insert without payment_provider/id | Success |
| CT-13 | Nullable metadata | Insert without metadata | Success |

### Migration Rollback Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RB-01 | Rollback migration | `php artisan migrate:rollback --step=1` | Both tables dropped |
| RB-02 | Re-run migration | `php artisan migrate` | Both tables recreated |
| RB-03 | Idempotent re-run | Run migration twice | No error, tables exist |

### DECIMAL Precision Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DP-01 | Max value balance | balance: 99999999.99 | Success (8 digits before decimal) |
| DP-02 | Precision 2 decimals | balance: 123.456 | Rounded to 123.46 or error |
| DP-03 | Zero balance | balance: 0.00 | Success |
| DP-04 | Small decimal | balance: 0.01 | Success |
| DP-05 | Negative balance | balance: -100.00 | Success (business logic may restrict) |
| DP-06 | Amount precision | amount: 12345.67 | Success |

### Polymorphic Reference Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PR-01 | Valid reference_type | reference_type: 'App\Models\Analysis' | Success |
| PR-02 | Valid reference_id | reference_id: 123 | Success |
| PR-03 | Both reference fields null | reference_type: null, reference_id: null | Success |
| PR-04 | Only reference_type | reference_type: 'Analysis', reference_id: null | Success (partial allowed) |
| PR-05 | Only reference_id | reference_type: null, reference_id: 123 | Success (partial allowed) |

### Payment Provider Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PP-01 | Valid provider stripe | payment_provider: 'stripe' | Success |
| PP-02 | Valid provider paypal | payment_provider: 'paypal' | Success |
| PP-03 | Custom provider | payment_provider: 'custom' | Success (not restricted by enum) |
| PP-04 | Payment ID stored | payment_id: 'pi_123abc' | Success |
| PP-05 | Both payment fields null | payment_provider: null, payment_id: null | Success |

### JSON Metadata Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JM-01 | Valid JSON object | metadata: {"key": "value"} | Success |
| JM-02 | Empty JSON object | metadata: {} | Success |
| JM-03 | Null metadata | metadata: null | Success |
| JM-04 | Nested JSON | metadata: {"level1": {"level2": "value"}} | Success |
| JM-05 | JSON array | metadata: [{"item": 1}] | Success |
| JM-06 | Invalid JSON | metadata: "not json" | Error / rejected (if column validates) |

### Security Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SC-01 | SQL injection in description | description: `'; DROP TABLE credit_transactions;--` | Sanitized / safe |
| SC-02 | SQL injection in payment_id | payment_id: malicious string | Sanitized / safe |
| SC-03 | XSS in description | description: `<script>alert(1)</script>` | Stored as-is (output escaping required) |
| SC-04 | Long description | description: 600 chars | Truncated or error (max 500) |
| SC-05 | Long payment_id | payment_id: 300 chars | Truncated or error (max 255) |
| SC-06 | Long reference_type | reference_type: 100 chars | Truncated or error (max 50) |

### Edge Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-01 | Multiple transactions per user | Insert 5 transactions for same user | All success |
| EC-02 | Concurrent transaction insert | Simultaneous inserts | Both success (no race condition on balance) |
| EC-03 | Unicode in description | description: "日本語の説明" | Success |
| EC-04 | Special chars in payment_id | payment_id: "pi_abc-123_xyz" | Success |
| EC-05 | Empty description | description: "" | Error (NOT NULL constraint) |
| EC-06 | Whitespace description | description: "   " | Success (validation may trim) |

### Model Integration Tests (Future)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MI-01 | Create credits record | Insert valid data | Record created with ID |
| MI-02 | Create transaction record | Insert valid data | Record created with ID |
| MI-03 | User hasOne credits | Query relationship | Returns credits record |
| MI-04 | User hasMany transactions | Query relationship | Returns transaction collection |
| MI-05 | Transaction belongsTo user | Query relationship | Returns user record |

---

## Mock Data

### Valid Credits Record
```json
{
  "user_id": 1,
  "balance": 100.50,
  "total_purchased": 150.00,
  "total_spent": 49.50
}
```

### Valid Credit Transaction Record (Purchase)
```json
{
  "user_id": 1,
  "amount": 50.00,
  "balance_after": 150.50,
  "type": "purchase",
  "description": "Purchased 50 credits via Stripe",
  "reference_type": null,
  "reference_id": null,
  "payment_provider": "stripe",
  "payment_id": "pi_1234567890",
  "metadata": {"package": "starter", "credits": 50}
}
```

### Valid Credit Transaction Record (Usage)
```json
{
  "user_id": 1,
  "amount": -5.00,
  "balance_after": 145.50,
  "type": "usage",
  "description": "Analysis job #123 execution",
  "reference_type": "App\\Models\\AnalysisJob",
  "reference_id": 123,
  "payment_provider": null,
  "payment_id": null,
  "metadata": {"model": "gpt-4", "tokens": 5000}
}
```

### Valid Credit Transaction Record (Refund)
```json
{
  "user_id": 1,
  "amount": 10.00,
  "balance_after": 155.50,
  "type": "refund",
  "description": "Refund for failed analysis job #456",
  "reference_type": "App\\Models\\AnalysisJob",
  "reference_id": 456,
  "payment_provider": null,
  "payment_id": null,
  "metadata": {"reason": "timeout"}
}
```

### Valid Credit Transaction Record (Bonus)
```json
{
  "user_id": 1,
  "amount": 25.00,
  "balance_after": 180.50,
  "type": "bonus",
  "description": "Welcome bonus for new user",
  "reference_type": null,
  "reference_id": null,
  "payment_provider": null,
  "payment_id": null,
  "metadata": {"campaign": "welcome_2026"}
}
```

---

## Coverage Targets

| Category | Target | Description |
|----------|--------|-------------|
| credits Schema | 100% | All columns, types, defaults |
| credit_transactions Schema | 100% | All columns, types, defaults |
| Indexes | 100% | All indexes on both tables |
| Foreign Keys | 100% | FK constraints and cascade |
| ENUM Values | 100% | All type values |
| DECIMAL Precision | 100% | Max values, precision, negatives |
| Rollback | 100% | Migration rollback works |
| Security | 100% | SQL injection, XSS, limits |
| Edge Cases | 90% | Boundary conditions |

---

## Test Execution

```bash
# Run specific test file
php artisan test --filter=CreateCreditsTablesTest

# Run with coverage
php artisan test --filter=CreateCreditsTablesTest --coverage

# Run all migration tests
php artisan test --filter=Migrations

# Run fresh migrations
php artisan migrate:fresh && php artisan test --filter=CreateCreditsTablesTest
```

---

## Dependencies
- Laravel migrations system
- users table (for FK constraints)
- MySQL/SQLite database connection

---

## Notes for Developer

### Migration Naming Convention
- File: `YYYY_MM_DD_HHMMSS_create_credits_tables.php`
- Use Laravel's `Schema::create()` for both tables

### credits Table Design Notes
- One record per user (enforced by unique index on user_id)
- DECIMAL(10, 2) supports up to $99,999,999.99 (sufficient for MVP)
- Balance can be negative (business logic may restrict)
- `updated_at` IS included (balance changes over time)

### credit_transactions Table Design Notes
- Immutable records (no `updated_at` column)
- Polymorphic references for linking to analyses, refunds, etc.
- Payment provider indexing for webhook lookups
- JSON metadata for flexible additional data

### Performance Considerations
- Index on user_id for fast user transaction lookups
- Index on type for filtering by transaction type
- Index on created_at for chronological queries
- Composite index on (payment_provider, payment_id) for webhook lookups

### Security Considerations
- DECIMAL prevents floating-point precision issues
- No sensitive payment data stored (only references)
- Audit trail via transactions is immutable
- Foreign keys with CASCADE ensure data integrity

---

## Comments

### [2026-03-02] PreTester
- Test documentation created
- 45+ test scenarios covering schema, indexes, constraints, security
- Ready for test implementation

---
_Leave notes for the next agent here_
