# TASK-26-2: Create Credit Model and Repository

## STATUS
`To Deploy`

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
Create Eloquent models and repository pattern for Credit and CreditTransaction entities with proper relationships and accessors.

## Acceptance Criteria

### Credit Model
- [ ] `app/Models/Credit.php` created
- [ ] BelongsTo relationship to User
- [ ] HasMany relationship to CreditTransaction
- [ ] Casts for DECIMAL columns (`balance`, `total_purchased`, `total_spent`)
- [ ] Accessor for formatted balance (e.g., `$12.50`)
- [ ] Method to check sufficient balance
- [ ] Protected against mass assignment

### CreditTransaction Model
- [ ] `app/Models/CreditTransaction.php` created
- [ ] BelongsTo relationship to User
- [ ] BelongsTo relationship to Credit
- [ ] Casts for DECIMAL columns (`amount`, `balance_after`)
- [ ] Cast for JSON `metadata` column
- [ ] Scope for filtering by type
- [ ] Scope for filtering by payment provider
- [ ] Protected against mass assignment

### CreditRepository
- [ ] `app/Repositories/CreditRepository.php` created
- [ ] `findOrCreateForUser(User $user): Credit` - Get or create credit record
- [ ] `getBalance(User $user): float` - Get current balance
- [ ] `getTransactions(User $user, int $perPage = 15): LengthAwarePaginator`
- [ ] Transaction-safe balance updates

### Unit Tests
- [ ] Model relationship tests
- [ ] Accessor tests
- [ ] Repository method tests
- [ ] Edge case tests (negative balance prevention)

## Dependencies
- TASK-26-1 (migrations must exist)

## Effort Estimate
`Medium` (3-4 hours)

## Notes
- Repository pattern enables easier testing and future changes
- Consider using Laravel's `lockForUpdate()` for concurrent operations
- Transactions should be created via service layer (not directly)

## Security Considerations
- Prevent direct mass assignment of balance
- Log all model changes for audit
- Validate type enum values

## Comments

### 2026-03-02 - ProjectManager
**Prioritization Complete:**
- **Priority: High** (confirmed) - Foundation task for billing system MVP
- **Risk: Medium** (confirmed) - Standard Eloquent models and repository pattern, no external APIs

**Dependency Status:**
- TASK-26-1 (Migrations): `In Development` ⏳ - Developer actively working
- TDD approach: PreTester CAN design tests using the expected model interface
- Tests can be written based on acceptance criteria before implementation completes

**Phase 1 Context:**
- TASK-26-1 (Migrations): In Development - enables this task
- TASK-26-2 (this task): Credit model and repository - depends on TASK-26-1
- TASK-26-3 (Credit packages): To Prioritize - depends on TASK-26-1
- TASK-26-4 (AI models table): To Prioritize - depends on TASK-26-1

**Test Coverage Requirements for PreTester:**
- Credit Model: Relationship tests (User, CreditTransaction), accessor tests (formatted balance), balance check method
- CreditTransaction Model: Relationship tests, scope tests (type, provider), cast tests (DECIMAL, JSON)
- CreditRepository: findOrCreateForUser, getBalance, getTransactions, transaction-safe updates
- Edge cases: Negative balance prevention, concurrent operations

**Security Notes:**
- Mass assignment protection for balance fields
- Audit logging for model changes
- Type enum validation (purchase, usage, refund, bonus)

### 2026-03-14 - PreTester
**Test Coverage Complete:**
- Test documentation created: `TESTS/TASK-26-2.md`
- Model test files created:
  - `backend/tests/Unit/Models/CreditTest.php` (65+ tests)
  - `backend/tests/Unit/Models/CreditTransactionTest.php` (70+ tests)
- Repository test file created:
  - `backend/tests/Unit/Repositories/CreditRepositoryTest.php` (55+ tests)
- Factory files created:
  - `backend/database/factories/CreditFactory.php` (with states: withBalance, withPurchased, withSpent, forUser, withFunds, empty, overdrawn)
  - `backend/database/factories/CreditTransactionFactory.php` (with states: purchase, usage, refund, bonus, forUser, forCredit, withPayment, withReference, withMetadata)

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| Credit Model | 65+ | Instantiation, relationships, casts, accessors, balance check, factory states |
| CreditTransaction Model | 70+ | Instantiation, relationships, casts, scopes, ENUM types, payment providers, polymorphic references |
| CreditRepository | 55+ | findOrCreateForUser, getBalance, getTransactions, transaction-safe updates, error handling |
| **Total** | **190+** | Full coverage for all acceptance criteria |

**Key Test Scenarios:**
- Unique user constraint for credits (one credit per user)
- Transaction immutability (no updated_at column)
- Transaction-safe balance updates with lockForUpdate()
- Concurrent race condition handling
- All 4 transaction types: purchase, usage, refund, bonus
- All payment providers: stripe, paypal, custom
- Polymorphic references for linking transactions to analyses

**Ready for Developer to implement:**
- `app/Models/Credit.php`
- `app/Models/CreditTransaction.php`
- `app/Repositories/CreditRepository.php`
- `app/Repositories/Contracts/CreditRepositoryInterface.php` (optional)

### 2026-03-14 - PreTester (verification session)
**Test Coverage Verified:**
- All test files confirmed to exist:
  - `backend/tests/Unit/Models/CreditTest.php` ✓
  - `backend/tests/Unit/Models/CreditTransactionTest.php` ✓
  - `backend/tests/Unit/Repositories/CreditRepositoryTest.php` ✓
  - `backend/database/factories/CreditFactory.php` ✓
  - `backend/database/factories/CreditTransactionFactory.php` ✓
- Test documentation `TESTS/TASK-26-2.md` complete with 190+ test scenarios
- Status transitioned to `To Develop` for Developer implementation

### 2026-03-14 - LocalTester
**Local Testing Complete - ALL TESTS PASSED**

| Test File | Tests | Passed | Skipped | Assertions |
|-----------|-------|--------|---------|------------|
| CreditTest | 62 | 61 | 1 | 86 |
| CreditTransactionTest | 78 | 75 | 3 | 137 |
| CreditRepositoryTest | 61 | 60 | 1 | 103 |
| **Total** | **201** | **196** | **5** | **326** |

**Skipped Tests (Expected):**
- CreditTest: `balance overflow` - SQLite does not enforce DECIMAL constraints
- CreditTransactionTest: `long reference type truncated`, `description exceeds max`, `amount overflow` - SQLite does not enforce VARCHAR/DECIMAL constraints
- CreditRepositoryTest: `interface binding` - CreditRepositoryInterface not defined (optional, implementation exists)

**Duration:** 45 seconds total

**Implementation Verified:**
- Credit model: Relationships, casts, accessors, balance check method ✓
- CreditTransaction model: Relationships, casts, scopes, type enum validation ✓
- CreditRepository: findOrCreateForUser, getBalance, getTransactions, addCredits, deductCredits, hasSufficientBalance ✓
- Factory states: All states (withBalance, withPurchased, purchase, usage, refund, bonus, etc.) working ✓
- Transaction safety: lockForUpdate() used, DB::transaction() wraps operations ✓
- Security: Mass assignment protection verified ✓

**Ready for DevOps deployment.**

### 2026-03-14 - DevOps
**Deployment Started:**
- Status changed to `In Deployment`
- Running local deployment flow: `scripts/deploy-prod.ps1`
- Implementation commit: fd565227 (TASK-26-2: Local testing complete)

### 2026-03-14 - DevOps (resumed)
**Deployment Resumed:**
- Previous deployment session was interrupted
- Pushing commits to origin/main and running deployment
- Implementation includes: Credit model, CreditTransaction model, CreditRepository
