# TEST-26-2: Credit Model and Repository

## Task Reference
- **Task:** TASK-26-2
- **Feature:** FEAT-026 (Billing, Credits & Model Selection)
- **Specification:** SPEC-026
- **Status:** To Develop

---

## Overview
Tests for Credit model, CreditTransaction model, and CreditRepository. Verifies Eloquent relationships, accessors, scopes, casts, mass assignment protection, and repository methods for the credit-based billing system.

---

## Test Files

| File | Type | Tests | Assertions |
|------|------|-------|------------|
| `backend/tests/Unit/Models/CreditTest.php` | Unit | 65+ | 120+ |
| `backend/tests/Unit/Models/CreditTransactionTest.php` | Unit | 70+ | 130+ |
| `backend/tests/Unit/Repositories/CreditRepositoryTest.php` | Unit | 55+ | 100+ |
| **Total** | - | **190+** | **350+** |

---

## Credit Model Tests

### Model Instantiation Tests (CM-001 to CM-010)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CM-001 | Create with required fields | user_id only | Record created with defaults |
| CM-002 | Create with all fields | All fields populated | Record created correctly |
| CM-003 | Mass assignment protects id | Attempt to set id | id unchanged |
| CM-004 | Factory creates valid model | Factory create | Valid model instance |
| CM-005 | Factory default state | Factory create | balance=0, total_purchased=0, total_spent=0 |
| CM-006 | Factory with balance | Factory with balance | balance set correctly |
| CM-007 | Factory with purchases | Factory with total_purchased | total_purchased set correctly |
| CM-008 | Factory with spending | Factory with total_spent | total_spent set correctly |
| CM-009 | Unique user constraint | Two credits for same user | Error: duplicate key |
| CM-010 | Cascade delete on user delete | Delete user | Credit record deleted |

### Relationship Tests (CR-001 to CR-006)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CR-001 | BelongsTo User | Query relationship | Returns User model |
| CR-002 | HasMany CreditTransaction | Query relationship | Returns Collection of CreditTransaction |
| CR-003 | User relationship loaded | With('user') | User loaded without N+1 |
| CR-004 | Transactions relationship loaded | With('transactions') | Transactions loaded |
| CR-005 | Create transaction via relationship | credit->transactions()->create() | Transaction created linked to credit |
| CR-006 | Delete credit cascades to transactions | Delete credit | Transactions NOT deleted (independent) |

### Casts Tests (CC-001 to CC-008)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CC-001 | balance cast to decimal | Set balance 100.50 | Returns float 100.50 |
| CC-002 | total_purchased cast to decimal | Set total_purchased 250.00 | Returns float 250.00 |
| CC-003 | total_spent cast to decimal | Set total_spent 75.25 | Returns float 75.25 |
| CC-004 | balance precision | Set balance 123.456 | Rounded to 123.46 |
| CC-005 | Zero balance | Set balance 0.00 | Returns 0.0 |
| CC-006 | Large balance | Set balance 99999999.99 | Returns 99999999.99 |
| CC-007 | Negative balance | Set balance -50.00 | Returns -50.00 |
| CC-008 | created_at cast to datetime | Query created_at | Returns Carbon instance |

### Accessor Tests (CA-001 to CA-008)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CA-001 | Formatted balance accessor | balance 1234.50 | "$1,234.50" |
| CA-002 | Formatted balance zero | balance 0.00 | "$0.00" |
| CA-003 | Formatted balance decimal | balance 0.50 | "$0.50" |
| CA-004 | Formatted balance large | balance 1000000.00 | "$1,000,000.00" |
| CA-005 | Formatted total_purchased | total_purchased 500.00 | "$500.00" |
| CA-006 | Formatted total_spent | total_spent 250.00 | "$250.00" |
| CA-007 | Accessor exists | $credit->formatted_balance | Returns string |
| CA-008 | Accessor with negative balance | balance -10.00 | "-$10.00" |

### Balance Check Method Tests (CB-001 to CB-010)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CB-001 | Has sufficient balance | balance 100, check 50 | true |
| CB-002 | Has exact balance | balance 100, check 100 | true |
| CB-003 | Insufficient balance | balance 50, check 100 | false |
| CB-004 | Zero balance check zero | balance 0, check 0 | true |
| CB-005 | Zero balance check positive | balance 0, check 1 | false |
| CB-006 | Negative balance check | balance -10, check 5 | false |
| CB-007 | Check negative amount | balance 100, check -10 | true (refund allowed) |
| CB-008 | Check decimal amount | balance 100.50, check 50.25 | true |
| CB-009 | Check with decimal precision | balance 100.00, check 99.99 | true |
| CB-010 | Check amount zero | balance 100, check 0 | true |

### Factory State Tests (CF-001 to CF-006)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CF-001 | Default state | Factory create | balance=0, totals=0 |
| CF-002 | withBalance state | Factory withBalance(100) | balance=100 |
| CF-003 | withPurchased state | Factory withPurchased(500) | total_purchased=500 |
| CF-004 | withSpent state | Factory withSpent(200) | total_spent=200 |
| CF-005 | forUser state | Factory forUser($user) | user_id set correctly |
| CF-006 | Combined states | Factory withBalance(100)->withPurchased(200) | Both values set |

### Edge Cases (CE-001 to CE-008)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CE-001 | Very large balance | balance 99999999.99 | Stored and retrieved correctly |
| CE-002 | Negative balance stored | balance -1000.00 | Stored (business logic may restrict) |
| CE-003 | Balance update | Update balance | updated_at changes |
| CE-004 | Balance string to float | Set balance "100.50" | Cast to float 100.50 |
| CE-005 | Null user_id | Create without user | Error (NOT NULL constraint) |
| CE-006 | Multiple transactions | Create 10 transactions | All linked correctly |
| CE-007 | No transactions | Query transactions | Empty collection |
| CE-008 | Refresh model | After balance update | Reflects current DB state |

### Security Tests (CS-001 to CS-005)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CS-001 | Mass assignment balance | Fill ['balance' => 1000] | balance updated (fillable) |
| CS-002 | Mass assignment protected id | Fill ['id' => 999] | id unchanged |
| CS-003 | SQL injection in accessor | N/A (numeric only) | Not applicable |
| CS-004 | Balance overflow | balance > DECIMAL max | Error or truncated |
| CS-005 | Concurrent balance updates | Two updates at once | Last write wins |

---

## CreditTransaction Model Tests

### Model Instantiation Tests (TM-001 to TM-010)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TM-001 | Create with required fields | user_id, amount, balance_after, type, description | Record created |
| TM-002 | Create with all fields | All fields populated | Record created correctly |
| TM-003 | Mass assignment protects id | Attempt to set id | id unchanged |
| TM-004 | Factory creates valid model | Factory create | Valid model instance |
| TM-005 | Factory purchase state | Factory purchase() | type='purchase', amount positive |
| TM-006 | Factory usage state | Factory usage() | type='usage', amount negative |
| TM-007 | Factory refund state | Factory refund() | type='refund', amount positive |
| TM-008 | Factory bonus state | Factory bonus() | type='bonus', amount positive |
| TM-009 | Cascade delete on user delete | Delete user | Transaction deleted |
| TM-010 | No updated_at column | Query schema | Column does not exist |

### Relationship Tests (TR-001 to TR-008)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TR-001 | BelongsTo User | Query relationship | Returns User model |
| TR-002 | BelongsTo Credit | Query relationship | Returns Credit model |
| TR-003 | User relationship loaded | With('user') | User loaded |
| TR-004 | Credit relationship loaded | With('credit') | Credit loaded |
| TR-005 | Transaction belongs to correct user | Query user_id | Matches credit's user |
| TR-006 | Transaction without credit | Create without credit_id | credit_id is null |
| TR-007 | Multiple transactions per user | Create 5 transactions | All linked to user |
| TR-008 | Transaction preserves credit reference | Delete credit | Transaction remains (no cascade) |

### Casts Tests (TC-001 to TC-010)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-001 | amount cast to decimal | Set amount 50.25 | Returns float 50.25 |
| TC-002 | balance_after cast to decimal | Set balance_after 100.50 | Returns float 100.50 |
| TC-003 | metadata cast to array | Set metadata JSON | Returns array |
| TC-004 | metadata null | Set metadata null | Returns null |
| TC-005 | metadata empty object | Set metadata {} | Returns empty array |
| TC-006 | metadata nested | Set nested JSON | Returns nested array |
| TC-007 | metadata array | Set array | Returns array |
| TC-008 | created_at cast to datetime | Query created_at | Returns Carbon instance |
| TC-009 | Negative amount | Set amount -10.00 | Returns -10.0 |
| TC-010 | Large amount | Set amount 99999999.99 | Returns 99999999.99 |

### Scope Tests (TS-001 to TS-012)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TS-001 | Scope ofType purchase | ofType('purchase') | Only purchase transactions |
| TS-002 | Scope ofType usage | ofType('usage') | Only usage transactions |
| TS-003 | Scope ofType refund | ofType('refund') | Only refund transactions |
| TS-004 | Scope ofType bonus | ofType('bonus') | Only bonus transactions |
| TS-005 | Scope ofType invalid | ofType('invalid') | Empty result |
| TS-006 | Scope ofProvider stripe | ofProvider('stripe') | Only stripe transactions |
| TS-007 | Scope ofProvider paypal | ofProvider('paypal') | Only paypal transactions |
| TS-008 | Scope ofProvider null | ofProvider(null) | All with no provider |
| TS-009 | Scope forUser | forUser($userId) | Only user's transactions |
| TS-010 | Scope recent | recent(5) | Last 5 transactions |
| TS-011 | Scope dateRange | dateRange($start, $end) | Transactions in range |
| TS-012 | Combined scopes | ofType('purchase')->ofProvider('stripe') | Filtered correctly |

### Type ENUM Tests (TT-001 to TT-006)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TT-001 | Type purchase | type='purchase' | Stored correctly |
| TT-002 | Type usage | type='usage' | Stored correctly |
| TT-003 | Type refund | type='refund' | Stored correctly |
| TT-004 | Type bonus | type='bonus' | Stored correctly |
| TT-005 | Type invalid | type='invalid' | SQL error / rejected |
| TT-006 | Type case sensitivity | type='PURCHASE' | SQL error / rejected |

### Payment Provider Tests (TP-001 to TP-008)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TP-001 | Provider stripe | payment_provider='stripe' | Stored correctly |
| TP-002 | Provider paypal | payment_provider='paypal' | Stored correctly |
| TP-003 | Provider null | payment_provider=null | Stored correctly |
| TP-004 | Provider empty | payment_provider='' | Stored correctly |
| TP-005 | Payment ID stored | payment_id='pi_123' | Stored correctly |
| TP-006 | Payment ID null | payment_id=null | Stored correctly |
| TP-007 | Provider and ID together | Both set | Both stored |
| TP-008 | Custom provider | payment_provider='custom' | Stored correctly |

### Polymorphic Reference Tests (TPR-001 to TP-006)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TPR-001 | Valid reference_type | reference_type='App\Models\Analysis' | Stored correctly |
| TPR-002 | Valid reference_id | reference_id=123 | Stored correctly |
| TPR-003 | Both reference null | Both null | Stored correctly |
| TPR-004 | Only reference_type | reference_type only | Stored correctly |
| TPR-005 | Only reference_id | reference_id only | Stored correctly |
| TPR-006 | Long reference_type | 50+ chars | Truncated or error |

### Factory State Tests (TF-001 to TF-008)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TF-001 | Default state | Factory create | Valid transaction |
| TF-002 | Purchase state | Factory purchase() | type='purchase' |
| TF-003 | Usage state | Factory usage() | type='usage' |
| TF-004 | Refund state | Factory refund() | type='refund' |
| TF-005 | Bonus state | Factory bonus() | type='bonus' |
| TF-006 | forUser state | Factory forUser($user) | user_id set |
| TF-007 | withPayment state | Factory withPayment() | payment fields set |
| TF-008 | withReference state | Factory withReference() | reference fields set |

### Edge Cases (TE-001 to TE-010)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TE-001 | Zero amount | amount=0.00 | Stored correctly |
| TE-002 | Negative amount | amount=-100.00 | Stored correctly |
| TE-003 | Large description | 500 chars | Stored correctly |
| TE-004 | Description exceeds max | 600 chars | Truncated or error |
| TE-005 | Unicode in description | Japanese text | Stored correctly |
| TE-006 | Special chars in payment_id | payment_id with hyphens | Stored correctly |
| TE-007 | Empty metadata | metadata={} | Empty array |
| TE-008 | Null metadata | metadata=null | Returns null |
| TE-009 | Multiple transactions same user | 10 transactions | All stored |
| TE-010 | Transaction immutability | Try to update | Error (no updated_at) |

### Security Tests (TSEC-001 to TSEC-006)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TSEC-001 | SQL injection in description | Malicious string | Sanitized / safe |
| TSEC-002 | XSS in description | Script tag | Stored as-is (output escaping) |
| TSEC-003 | SQL injection in payment_id | Malicious string | Sanitized / safe |
| TSEC-004 | Mass assignment protected fields | Fill id, user_id | Protected fields unchanged |
| TSEC-005 | Negative balance_after | balance_after=-10 | Stored (business logic may restrict) |
| TSEC-006 | Amount overflow | amount > DECIMAL max | Error or truncated |

---

## CreditRepository Tests

### Repository Instantiation Tests (RI-001 to RI-005)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RI-001 | Repository instantiable | new CreditRepository() | Instance created |
| RI-002 | Injected via container | app(CreditRepository::class) | Instance created |
| RI-003 | Interface binding | app(CreditRepositoryInterface::class) | Returns implementation |
| RI-004 | Model dependency | Inject Credit model | Works correctly |
| RI-005 | Transaction dependency | Inject CreditTransaction model | Works correctly |

### findOrCreateForUser Tests (RF-001 to RF-010)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RF-001 | Find existing credit | User with existing credit | Returns existing Credit |
| RF-002 | Create new credit | User without credit | Creates and returns new Credit |
| RF-003 | New credit defaults | New credit | balance=0, totals=0 |
| RF-004 | Same user returns same | Call twice for same user | Same Credit instance |
| RF-005 | Different users different credits | Two different users | Two different Credits |
| RF-006 | Transaction isolation | Concurrent creation | No duplicate (unique constraint) |
| RF-007 | User relationship loaded | With user | User available |
| RF-008 | Invalid user type | Null user | Exception thrown |
| RF-009 | User with ID null | User without ID | Exception thrown |
| RF-010 | Returns Credit model | Return type | Credit model instance |

### getBalance Tests (RG-001 to RG-010)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RG-001 | User with positive balance | balance=100.50 | Returns 100.50 |
| RG-002 | User with zero balance | balance=0.00 | Returns 0.00 |
| RG-003 | User with negative balance | balance=-10.00 | Returns -10.00 |
| RG-004 | User without credit | No credit record | Returns 0.00 |
| RG-005 | Creates credit if missing | No credit record | Creates credit, returns 0.00 |
| RG-006 | Returns float | Return type | float |
| RG-007 | Decimal precision | balance=123.45 | Returns 123.45 |
| RG-008 | Large balance | balance=99999999.99 | Returns correctly |
| RG-009 | Multiple calls same result | Call twice | Same value |
| RG-010 | Balance unchanged after query | Query only | Balance unchanged |

### getTransactions Tests (RGT-001 to RG-012)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RGT-001 | User with transactions | 5 transactions | LengthAwarePaginator with 5 items |
| RGT-002 | User without transactions | No transactions | Empty paginator |
| RGT-003 | User without credit | No credit record | Empty paginator |
| RGT-004 | Custom per page | perPage=10 | 10 items per page |
| RGT-005 | Default per page | No perPage | 15 items per page |
| RGT-006 | Ordered by created_at desc | Multiple transactions | Most recent first |
| RGT-007 | Filter by type | type='purchase' | Only purchases |
| RGT-008 | Filter by date range | start, end dates | Filtered results |
| RGT-009 | Paginates correctly | 50 transactions, perPage=10 | 5 pages |
| RGT-010 | Returns correct type | Return type | LengthAwarePaginator |
| RGT-011 | Loads relationships | with('credit') | Credit loaded |
| RGT-012 | User isolation | Another user's transactions | Not included |

### Transaction-Safe Balance Updates Tests (RT-001 to RT-015)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RT-001 | Add credits successfully | amount=50.00 | balance updated, transaction created |
| RT-002 | Deduct credits successfully | amount=-25.00, sufficient balance | balance updated, transaction created |
| RT-003 | Deduct insufficient balance | amount=-100, balance=50 | Exception thrown |
| RT-004 | Zero amount transaction | amount=0 | No change, transaction logged |
| RT-005 | Decimal precision | amount=12.34 | Balance precise to 2 decimals |
| RT-006 | Transaction created | Any update | CreditTransaction record exists |
| RT-007 | Transaction type correct | purchase | type='purchase' |
| RT-008 | Transaction type correct | usage | type='usage' |
| RT-009 | Transaction type correct | refund | type='refund' |
| RT-010 | Transaction type correct | bonus | type='bonus' |
| RT-011 | Balance_after correct | After update | Matches new balance |
| RT-012 | Concurrent updates | Two simultaneous | Lock prevents race condition |
| RT-013 | Rollback on error | Exception during update | Balance unchanged |
| RT-014 | Lock for update | LockForUpdate used | Query uses lock |
| RT-015 | Database transaction | DB::transaction used | All or nothing |

### Credit Update Methods (RC-001 to RC-010)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RC-001 | addCredits method | amount, type, description | Balance increased |
| RC-002 | deductCredits method | amount, description | Balance decreased |
| RC-003 | deductCredits with reference | amount, description, reference | Transaction has reference |
| RC-004 | deductCredits insufficient | amount > balance | Exception |
| RC-005 | addCredits with payment | amount, payment fields | Transaction has payment info |
| RC-006 | refundCredits method | amount, reference | Balance increased, type='refund' |
| RC-007 | bonusCredits method | amount, description | Balance increased, type='bonus' |
| RC-008 | Description stored | Custom description | Transaction has description |
| RC-009 | Metadata stored | Custom metadata | Transaction has metadata |
| RC-010 | Balance updates total_spent | Usage transaction | total_spent updated |

### Error Handling Tests (RE-001 to RE-008)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RE-001 | Invalid amount type | amount='abc' | Exception thrown |
| RE-002 | Negative purchase amount | type='purchase', amount=-10 | Exception thrown |
| RE-003 | User not found | Invalid user_id | Exception thrown |
| RE-004 | Database error | Simulate DB error | Exception thrown |
| RE-005 | Invalid transaction type | type='invalid' | Exception thrown |
| RE-006 | Null user | user=null | Exception thrown |
| RE-007 | Zero balance deduction | amount > 0, balance=0 | Exception (insufficient) |
| RE-008 | Concurrent race condition | Two threads | Lock prevents corruption |

---

## Mock Data

### Valid Credit Record
```json
{
  "user_id": 1,
  "balance": 150.50,
  "total_purchased": 200.00,
  "total_spent": 49.50
}
```

### Valid CreditTransaction (Purchase)
```json
{
  "user_id": 1,
  "credit_id": 1,
  "amount": 50.00,
  "balance_after": 200.50,
  "type": "purchase",
  "description": "Purchased 50 credits via Stripe",
  "reference_type": null,
  "reference_id": null,
  "payment_provider": "stripe",
  "payment_id": "pi_1234567890",
  "metadata": {"package": "starter", "credits": 50}
}
```

### Valid CreditTransaction (Usage)
```json
{
  "user_id": 1,
  "credit_id": 1,
  "amount": -5.00,
  "balance_after": 195.50,
  "type": "usage",
  "description": "Analysis job #123 execution",
  "reference_type": "App\\Models\\AnalysisJob",
  "reference_id": 123,
  "payment_provider": null,
  "payment_id": null,
  "metadata": {"model": "gpt-4", "tokens": 5000}
}
```

### Valid CreditTransaction (Refund)
```json
{
  "user_id": 1,
  "credit_id": 1,
  "amount": 10.00,
  "balance_after": 205.50,
  "type": "refund",
  "description": "Refund for failed analysis job #456",
  "reference_type": "App\\Models\\AnalysisJob",
  "reference_id": 456,
  "payment_provider": null,
  "payment_id": null,
  "metadata": {"reason": "timeout"}
}
```

### Valid CreditTransaction (Bonus)
```json
{
  "user_id": 1,
  "credit_id": 1,
  "amount": 25.00,
  "balance_after": 230.50,
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
| Credit Model | 100% | All methods, relationships, accessors |
| CreditTransaction Model | 100% | All methods, relationships, scopes |
| CreditRepository | 100% | All public methods |
| Relationships | 100% | All relationships tested |
| Casts | 100% | All casts verified |
| Scopes | 100% | All scopes tested |
| Edge Cases | 90% | Boundary conditions |
| Security | 100% | Mass assignment, SQL injection |
| Concurrency | 90% | Race conditions, locks |

---

## Test Execution

```bash
# Run Credit model tests
php artisan test --filter=CreditTest

# Run CreditTransaction model tests
php artisan test --filter=CreditTransactionTest

# Run CreditRepository tests
php artisan test --filter=CreditRepositoryTest

# Run all TASK-26-2 tests
php artisan test --filter="CreditTest|CreditTransactionTest|CreditRepositoryTest"

# Run with coverage
php artisan test --filter="Credit" --coverage
```

---

## Dependencies
- TASK-26-1: Credits and Transactions Tables Migration (must be complete)
- User model (exists)
- Laravel Eloquent ORM
- Database transactions support

---

## Notes for Developer

### Credit Model Implementation
```php
// app/Models/Credit.php
class Credit extends Model
{
    protected $fillable = ['user_id', 'balance', 'total_purchased', 'total_spent'];
    
    protected $casts = [
        'balance' => 'decimal:2',
        'total_purchased' => 'decimal:2',
        'total_spent' => 'decimal:2',
    ];
    
    public function user(): BelongsTo { ... }
    public function transactions(): HasMany { ... }
    
    public function getFormattedBalanceAttribute(): string { ... }
    public function hasSufficientBalance(float $amount): bool { ... }
}
```

### CreditTransaction Model Implementation
```php
// app/Models/CreditTransaction.php
class CreditTransaction extends Model
{
    const UPDATED_AT = null; // Immutable
    
    protected $fillable = [
        'user_id', 'credit_id', 'amount', 'balance_after', 'type',
        'description', 'reference_type', 'reference_id',
        'payment_provider', 'payment_id', 'metadata'
    ];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];
    
    public function user(): BelongsTo { ... }
    public function credit(): BelongsTo { ... }
    
    public function scopeOfType($query, string $type) { ... }
    public function scopeOfProvider($query, ?string $provider) { ... }
}
```

### CreditRepository Implementation
```php
// app/Repositories/CreditRepository.php
class CreditRepository implements CreditRepositoryInterface
{
    public function findOrCreateForUser(User $user): Credit { ... }
    public function getBalance(User $user): float { ... }
    public function getTransactions(User $user, int $perPage = 15): LengthAwarePaginator { ... }
    public function addCredits(User $user, float $amount, string $type, string $description, array $options = []): Credit { ... }
    public function deductCredits(User $user, float $amount, string $description, ?array $reference = null): Credit { ... }
}
```

### Concurrency Protection
- Use `lockForUpdate()` when checking/updating balance
- Wrap balance updates in `DB::transaction()`
- Create transaction record atomically with balance update

### Performance Considerations
- Index on user_id for fast lookups
- Index on type for filtering
- Index on created_at for ordering
- Composite index on (payment_provider, payment_id) for webhook lookups

---

## Comments

### [2026-03-14] PreTester
- Test documentation created
- 190+ test scenarios covering models, repository, relationships, scopes
- Factory states defined for all transaction types
- Concurrency tests included for balance updates
- Ready for test implementation

---
_Leave notes for the next agent here_
