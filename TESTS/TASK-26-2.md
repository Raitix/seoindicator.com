# TEST-26-2: Credit Model and Repository Tests

## Task Reference
- **Task:** TASK-26-2
- **Feature:** FEAT-026 (Billing, Credits & Model Selection)
- **Specification:** SPEC-026
- **Status:** Tests Coverage In Progress

---

## Overview
Tests for Credit and CreditTransaction Eloquent models, and CreditRepository. Verifies relationships, accessors, scopes, repository methods, and business logic for the credit-based billing system.

---

## Test Files

| File | Type | Tests | Assertions |
|------|------|-------|------------|
| `backend/tests/Unit/Models/CreditTest.php` | Unit | 40+ | 100+ |
| `backend/tests/Unit/Models/CreditTransactionTest.php` | Unit | 35+ | 90+ |
| `backend/tests/Unit/Repositories/CreditRepositoryTest.php` | Unit | 30+ | 80+ |

---

## Test Scenarios

### Credit Model - Creation Tests (CM-CR)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CM-CR-01 | Create with user_id only | user_id: 1 | Record created with defaults (balance=0, total_purchased=0, total_spent=0) |
| CM-CR-02 | Create with full data | user_id, balance, totals | Record created with all values |
| CM-CR-03 | Mass assignment protection | Direct balance assignment | Fills fillable fields only |
| CM-CR-04 | Unique user constraint | Same user_id twice | Error: duplicate key |
| CM-CR-05 | Cascade delete with user | Delete user | Credit record deleted |

### Credit Model - Relationship Tests (CM-RE)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CM-RE-01 | BelongsTo User | Query user() relationship | Returns User model |
| CM-RE-02 | HasMany CreditTransaction | Query transactions() relationship | Returns Collection of CreditTransaction |
| CM-RE-03 | Eager load user | with('user') | User loaded without N+1 |
| CM-RE-04 | Eager load transactions | with('transactions') | Transactions loaded without N+1 |
| CM-RE-05 | User hasOne credit | User->credit relationship | Returns Credit model |

### Credit Model - Casts Tests (CM-CA)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CM-CA-01 | balance DECIMAL cast | balance: 123.45 | Returns float 123.45 |
| CM-CA-02 | balance precision 2 | balance: 123.456 | Stored as 123.46 (rounded) |
| CM-CA-03 | total_purchased DECIMAL cast | total_purchased: 1000.50 | Returns float 1000.50 |
| CM-CA-04 | total_spent DECIMAL cast | total_spent: 500.25 | Returns float 500.25 |
| CM-CA-05 | timestamps cast | created_at, updated_at | Returns Carbon instances |

### Credit Model - Accessor Tests (CM-AC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CM-AC-01 | formatted_balance accessor | balance: 123.45 | "$123.45" |
| CM-AC-02 | formatted_balance zero | balance: 0 | "$0.00" |
| CM-AC-03 | formatted_balance large | balance: 99999999.99 | "$99,999,999.99" |
| CM-AC-04 | formatted_balance negative | balance: -50.00 | "-$50.00" |
| CM-AC-05 | formatted_total_purchased | total_purchased: 150.00 | "$150.00" |
| CM-AC-06 | formatted_total_spent | total_spent: 75.50 | "$75.50" |

### Credit Model - Business Logic Tests (CM-BL)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CM-BL-01 | hasSufficientBalance true | balance: 100, check: 50 | true |
| CM-BL-02 | hasSufficientBalance exact | balance: 100, check: 100 | true |
| CM-BL-03 | hasSufficientBalance false | balance: 50, check: 100 | false |
| CM-BL-04 | hasSufficientBalance zero amount | balance: 0, check: 0 | true |
| CM-BL-05 | hasSufficientBalance negative check | balance: 100, check: -10 | true (negative = credit) |
| CM-BL-06 | hasSufficientBalance with negative balance | balance: -10, check: 1 | false |

### Credit Model - Edge Cases (CM-EC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CM-EC-01 | Max balance value | balance: 99999999.99 | Success |
| CM-EC-02 | Small decimal | balance: 0.01 | Success |
| CM-EC-03 | Zero balance | balance: 0.00 | Success |
| CM-EC-04 | Negative balance allowed | balance: -100.00 | Success (no DB constraint) |
| CM-EC-05 | Unicode in non-existent fields | N/A | N/A (no text fields) |

---

### CreditTransaction Model - Creation Tests (CT-CR)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CT-CR-01 | Create purchase transaction | type: 'purchase', amount: 50 | Record created |
| CT-CR-02 | Create usage transaction | type: 'usage', amount: -5 | Record created |
| CT-CR-03 | Create refund transaction | type: 'refund', amount: 10 | Record created |
| CT-CR-04 | Create bonus transaction | type: 'bonus', amount: 25 | Record created |
| CT-CR-05 | Cascade delete with user | Delete user | Transactions deleted |
| CT-CR-06 | No updated_at column | Create record | Only created_at set |
| CT-CR-07 | Immutable record | Update existing record | Error or blocked |

### CreditTransaction Model - Relationship Tests (CT-RE)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CT-RE-01 | BelongsTo User | Query user() relationship | Returns User model |
| CT-RE-02 | BelongsTo Credit | Query credit() relationship | Returns Credit model |
| CT-RE-03 | Eager load user | with('user') | User loaded without N+1 |
| CT-RE-04 | Eager load credit | with('credit') | Credit loaded without N+1 |
| CT-RE-05 | Multiple transactions per user | Create 5 transactions | All linked correctly |

### CreditTransaction Model - Casts Tests (CT-CA)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CT-CA-01 | amount DECIMAL cast | amount: 123.45 | Returns float 123.45 |
| CT-CA-02 | balance_after DECIMAL cast | balance_after: 100.00 | Returns float 100.00 |
| CT-CA-03 | metadata JSON cast | metadata: {"key": "value"} | Returns array |
| CT-CA-04 | metadata null | metadata: null | Returns null |
| CT-CA-05 | metadata nested | metadata: {"a": {"b": 1}} | Returns nested array |
| CT-CA-06 | created_at timestamp | created_at | Returns Carbon instance |

### CreditTransaction Model - Scope Tests (CT-SC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CT-SC-01 | scopeByType purchase | type: 'purchase' | Returns only purchase transactions |
| CT-SC-02 | scopeByType usage | type: 'usage' | Returns only usage transactions |
| CT-SC-03 | scopeByType refund | type: 'refund' | Returns only refund transactions |
| CT-SC-04 | scopeByType bonus | type: 'bonus' | Returns only bonus transactions |
| CT-SC-05 | scopeByProvider stripe | provider: 'stripe' | Returns stripe transactions |
| CT-SC-06 | scopeByProvider paypal | provider: 'paypal' | Returns paypal transactions |
| CT-SC-07 | scopeByProvider null | provider: null | Returns non-payment transactions |
| CT-SC-08 | Chained scopes | type: 'purchase', provider: 'stripe' | Returns filtered results |

### CreditTransaction Model - Validation Tests (CT-VA)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CT-VA-01 | Valid type values | 'purchase', 'usage', 'refund', 'bonus' | All accepted |
| CT-VA-02 | Invalid type value | 'invalid_type' | SQL error / rejected |
| CT-VA-03 | Description required | description: null | Error (NOT NULL) |
| CT-VA-04 | Description empty | description: '' | Error or allowed (validation) |
| CT-VA-05 | Nullable reference_type | reference_type: null | Success |
| CT-VA-06 | Nullable reference_id | reference_id: null | Success |
| CT-VA-07 | Nullable payment fields | payment_provider: null, payment_id: null | Success |

### CreditTransaction Model - Edge Cases (CT-EC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CT-EC-01 | Max amount value | amount: 99999999.99 | Success |
| CT-EC-02 | Negative amount | amount: -500.00 | Success (usage/refund) |
| CT-EC-03 | Small decimal | amount: 0.01 | Success |
| CT-EC-04 | Long description | description: 500 chars | Success |
| CT-EC-05 | Description too long | description: 501 chars | Truncated or error |
| CT-EC-06 | Long payment_id | payment_id: 255 chars | Success |
| CT-EC-07 | Unicode in description | description: "日本語の説明" | Success |
| CT-EC-08 | Special chars in payment_id | payment_id: "pi_abc-123_xyz" | Success |
| CT-EC-09 | Nested JSON metadata | metadata: {"a": {"b": {"c": 1}}} | Success |

---

### CreditRepository - findOrCreateForUser Tests (CR-FO)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CR-FO-01 | Create for new user | User without credit | New Credit record created |
| CR-FO-02 | Find existing | User with credit | Existing Credit returned |
| CR-FO-03 | Returns Credit instance | Any user | Returns Credit model |
| CR-FO-04 | Default values | New credit | balance=0, totals=0 |
| CR-FO-05 | Idempotent | Call twice | Same Credit returned |

### CreditRepository - getBalance Tests (CR-GB)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CR-GB-01 | Get balance existing | User with credit | Returns float balance |
| CR-GB-02 | Get balance new user | User without credit | Creates credit, returns 0.0 |
| CR-GB-03 | Balance with decimals | balance: 123.45 | Returns 123.45 |
| CR-GB-04 | Zero balance | balance: 0.00 | Returns 0.0 |
| CR-GB-05 | Negative balance | balance: -10.00 | Returns -10.0 |

### CreditRepository - getTransactions Tests (CR-GT)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CR-GT-01 | Get paginated transactions | perPage: 15 | Returns LengthAwarePaginator |
| CR-GT-02 | Empty transactions | User with no transactions | Returns empty paginator |
| CR-GT-03 | Multiple transactions | User with 20 transactions | Returns paginated (15 per page) |
| CR-GT-04 | Custom perPage | perPage: 5 | Returns 5 items per page |
| CR-GT-05 | Ordered by created_at | Multiple transactions | Newest first |
| CR-GT-06 | Filter by type | type: 'purchase' | Returns only purchases |
| CR-GT-07 | Filter by provider | provider: 'stripe' | Returns only stripe transactions |

### CreditRepository - Transaction-Safe Updates (CR-TS)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CR-TS-01 | AddCredits in transaction | amount: 50 | balance increased, transaction created |
| CR-TS-02 | DeductCredits in transaction | amount: 10 | balance decreased, transaction created |
| CR-TS-03 | Insufficient balance | deduct > balance | Exception thrown, no changes |
| CR-TS-04 | Rollback on error | Force error mid-transaction | No partial changes |
| CR-TS-05 | Concurrent updates | Two simultaneous operations | lockForUpdate prevents race |
| CR-TS-06 | Atomic balance update | Add + deduct | Both succeed or both fail |

### CreditRepository - Edge Cases (CR-EC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CR-EC-01 | Zero amount transaction | amount: 0 | No change, no transaction created |
| CR-EC-02 | Negative add (refund) | amount: -25 | balance increased (negative deduction) |
| CR-EC-03 | Very large transaction | amount: 99999999.99 | Success |
| CR-EC-04 | Precision preservation | amount: 0.01 multiple times | Balance accurate |
| CR-EC-05 | Metadata storage | metadata: {"key": "value"} | Stored and retrieved correctly |

---

## Mock Data

### Valid Credit Record
```json
{
  "id": 1,
  "user_id": 1,
  "balance": "100.50",
  "total_purchased": "150.00",
  "total_spent": "49.50",
  "created_at": "2026-03-02T12:00:00Z",
  "updated_at": "2026-03-02T12:00:00Z"
}
```

### Valid CreditTransaction - Purchase
```json
{
  "id": 1,
  "user_id": 1,
  "credit_id": 1,
  "amount": "50.00",
  "balance_after": "150.50",
  "type": "purchase",
  "description": "Purchased 50 credits via Stripe",
  "reference_type": null,
  "reference_id": null,
  "payment_provider": "stripe",
  "payment_id": "pi_1234567890",
  "metadata": {"package": "starter", "credits": 50},
  "created_at": "2026-03-02T12:00:00Z"
}
```

### Valid CreditTransaction - Usage
```json
{
  "id": 2,
  "user_id": 1,
  "credit_id": 1,
  "amount": "-5.00",
  "balance_after": "145.50",
  "type": "usage",
  "description": "Analysis job #123 execution",
  "reference_type": "App\\Models\\AnalysisJob",
  "reference_id": 123,
  "payment_provider": null,
  "payment_id": null,
  "metadata": {"model": "gpt-4", "tokens": 5000},
  "created_at": "2026-03-02T12:30:00Z"
}
```

### Valid CreditTransaction - Refund
```json
{
  "id": 3,
  "user_id": 1,
  "credit_id": 1,
  "amount": "10.00",
  "balance_after": "155.50",
  "type": "refund",
  "description": "Refund for failed analysis job #456",
  "reference_type": "App\\Models\\AnalysisJob",
  "reference_id": 456,
  "payment_provider": null,
  "payment_id": null,
  "metadata": {"reason": "timeout"},
  "created_at": "2026-03-02T13:00:00Z"
}
```

### Valid CreditTransaction - Bonus
```json
{
  "id": 4,
  "user_id": 1,
  "credit_id": 1,
  "amount": "25.00",
  "balance_after": "180.50",
  "type": "bonus",
  "description": "Welcome bonus for new user",
  "reference_type": null,
  "reference_id": null,
  "payment_provider": null,
  "payment_id": null,
  "metadata": {"campaign": "welcome_2026"},
  "created_at": "2026-03-02T14:00:00Z"
}
```

---

## Coverage Targets

| Category | Target | Description |
|----------|--------|-------------|
| Credit Model Creation | 100% | Factory, mass assignment, constraints |
| Credit Model Relationships | 100% | User, CreditTransaction |
| Credit Model Casts | 100% | DECIMAL, timestamps |
| Credit Model Accessors | 100% | formatted_balance, hasSufficientBalance |
| CreditTransaction Model Creation | 100% | All types, immutability |
| CreditTransaction Model Relationships | 100% | User, Credit |
| CreditTransaction Model Scopes | 100% | type, provider |
| CreditTransaction Model Casts | 100% | DECIMAL, JSON |
| CreditRepository findOrCreateForUser | 100% | Create, find, idempotent |
| CreditRepository getBalance | 100% | Get, create if missing |
| CreditRepository getTransactions | 100% | Pagination, filtering |
| CreditRepository Transaction Safety | 100% | Atomic updates, locking |
| Edge Cases | 90% | Boundary conditions |

---

## Test Execution

```bash
# Run Credit model tests
php artisan test --filter=CreditTest

# Run CreditTransaction model tests
php artisan test --filter=CreditTransactionTest

# Run CreditRepository tests
php artisan test --filter=CreditRepositoryTest

# Run all credit-related tests
php artisan test --filter="Credit"

# Run with coverage
php artisan test --filter="Credit" --coverage

# Run all tests
php artisan test
```

---

## Dependencies
- Laravel Eloquent ORM
- User model (exists)
- credits table migration (TASK-26-1, completed)
- credit_transactions table migration (TASK-26-1, completed)
- PHPUnit/Pest testing framework

---

## Notes for Developer

### Model Files to Create
1. `app/Models/Credit.php`
2. `app/Models/CreditTransaction.php`
3. `app/Repositories/CreditRepository.php`

### Factory Files to Create
1. `database/factories/CreditFactory.php`
2. `database/factories/CreditTransactionFactory.php`

### Key Implementation Notes

**Credit Model:**
- Use `$guarded = []` or explicit `$fillable` for mass assignment
- DECIMAL casts: `'balance' => 'decimal:2'`
- Accessor: `getFormattedBalanceAttribute()` returns "$X.XX"
- Method: `hasSufficientBalance(float $amount): bool`
- Relationship: `belongsTo(User::class)`, `hasMany(CreditTransaction::class)`

**CreditTransaction Model:**
- No `$timestamps` (only `created_at`)
- Use `$fillable` for allowed fields (NOT balance manipulation)
- Scopes: `scopeByType($query, string $type)`, `scopeByProvider($query, ?string $provider)`
- JSON cast for `metadata`
- Relationship: `belongsTo(User::class)`, `belongsTo(Credit::class)`

**CreditRepository:**
- Use `DB::transaction()` for atomic operations
- Use `lockForUpdate()` for concurrent access
- Methods:
  - `findOrCreateForUser(User $user): Credit`
  - `getBalance(User $user): float`
  - `getTransactions(User $user, int $perPage = 15): LengthAwarePaginator`
  - `addCredits(User $user, float $amount, array $data): CreditTransaction`
  - `deductCredits(User $user, float $amount, array $data): CreditTransaction`

### Security Considerations
- Balance should NOT be mass-assignable
- All balance changes go through repository methods
- Transaction audit trail is immutable
- Use database transactions for consistency

---

## Comments

### [2026-03-02] PreTester
- Test documentation created
- 100+ test scenarios covering Credit model, CreditTransaction model, and CreditRepository
- Test files to be created next
- Ready for test implementation

---
_Leave notes for the next agent here_
