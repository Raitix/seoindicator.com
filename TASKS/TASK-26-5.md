# TASK-26-5: Implement CreditService with Balance Management

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`High`

## Related Specification
SPEC-026: Billing, Credits & Model Selection

## Related Feature
FEAT-026: Billing, Credits & Model Selection

---

## Description
Implement the core CreditService that handles all credit balance operations with transaction safety and audit logging.

## Acceptance Criteria

### CreditService Methods
- [ ] `getBalance(User $user): float` - Get current credit balance
- [ ] `addCredits(User $user, float $amount, string $type, array $metadata = []): CreditTransaction` - Add credits (purchase, bonus)
- [ ] `deductCredits(User $user, float $amount, string $referenceType, int $referenceId): CreditTransaction` - Deduct credits (usage)
- [ ] `hasSufficientBalance(User $user, float $amount): bool` - Check if user can afford
- [ ] `getTransactionHistory(User $user, int $perPage = 15): LengthAwarePaginator` - Paginated history
- [ ] `refundCredits(User $user, float $amount, int $originalTransactionId, string $reason): CreditTransaction` - Refund credits

### Transaction Safety
- [ ] All balance updates wrapped in database transactions
- [ ] Use `lockForUpdate()` to prevent race conditions
- [ ] Atomic balance + transaction creation
- [ ] Idempotency support for payment webhooks

### Validation Rules
- [ ] Cannot have negative balance
- [ ] Amount must be positive
- [ ] Type must be valid enum value
- [ ] User must exist

### Error Handling
- [ ] Custom exception: `InsufficientCreditsException`
- [ ] Custom exception: `InvalidCreditAmountException`
- [ ] Log all errors with context

### Unit Tests
- [ ] Balance retrieval tests
- [ ] Add credits tests (purchase, bonus types)
- [ ] Deduct credits tests
- [ ] Insufficient balance tests
- [ ] Concurrent operation tests
- [ ] Transaction history pagination tests
- [ ] Refund flow tests

## Dependencies
- TASK-26-1 (migrations)
- TASK-26-2 (models and repository)

## Effort Estimate
`Large` (5-6 hours)

## Notes
- This is the CORE service - other payment services depend on it
- Race condition handling is CRITICAL for billing
- Consider using Laravel's `DB::transaction()` with retry logic
- All monetary operations should be logged for compliance

## Security Considerations
- Server-side balance validation only (never trust client)
- Audit trail for every balance change
- Lock records during updates
- Validate amounts are within reasonable bounds

## Comments

### 2026-03-02 - ProjectManager
**Prioritization Complete:**
- **Priority: High** (confirmed) - Core service for billing MVP, all payment services depend on CreditService
- **Risk: High** (confirmed) - Financial transactions require careful race condition handling, audit logging mandatory

**Dependency Status:**
- TASK-26-1 (Migrations): `In Development` ⏳ - Developer actively working
- TASK-26-2 (Credit Model and Repository): `Tests Coverage In Progress` ✅ - PreTester designing tests

**TDD Approach:**
- PreTester CAN design tests NOW using expected CreditService interface
- Tests can mock Credit model and CreditRepository interfaces
- Implementation waits for both dependencies to reach `To Test Locally`

**Phase 2 Context:**
- TASK-26-5 (this task): CreditService - foundation service for billing
- TASK-26-6: Credit balance API endpoint - depends on this task
- TASK-26-7: Transaction history API endpoint - depends on this task

**Test Coverage Requirements for PreTester:**
- **CreditService Methods:**
  - `getBalance(User $user): float` - balance retrieval
  - `addCredits(User $user, float $amount, string $type, array $metadata): CreditTransaction`
  - `deductCredits(User $user, float $amount, string $referenceType, int $referenceId): CreditTransaction`
  - `hasSufficientBalance(User $user, float $amount): bool`
  - `getTransactionHistory(User $user, int $perPage): LengthAwarePaginator`
  - `refundCredits(User $user, float $amount, int $originalTransactionId, string $reason): CreditTransaction`

- **Transaction Safety Tests (CRITICAL):**
  - Database transaction wrapping for all balance updates
  - `lockForUpdate()` to prevent race conditions
  - Atomic balance + transaction creation
  - Idempotency support for payment webhooks (same payment ID twice = no double-charge)

- **Error Handling Tests:**
  - `InsufficientCreditsException` - attempt to deduct more than balance
  - `InvalidCreditAmountException` - negative or zero amounts
  - Error logging with full context (user_id, amount, type, reference)

- **Concurrency Tests:**
  - Simultaneous balance operations from same user
  - Lock timeout handling
  - Retry logic for deadlocks

- **Edge Cases:**
  - User with no Credit record (create on-demand)
  - Transaction history pagination (empty, single page, multiple pages)
  - Refund with invalid original transaction ID
  - Metadata JSON field handling

**Security Notes:**
- Server-side balance validation only (never trust client)
- Audit trail for every balance change (immutable transactions)
- Lock records during updates (`lockForUpdate()`)
- Validate amounts within reasonable bounds (max purchase, max deduction)
- Custom exceptions for error handling (InsufficientCreditsException, InvalidCreditAmountException)

**Enables:**
- TASK-26-6 (Credit Balance API)
- TASK-26-7 (Transaction History API)
- TASK-26-8 (StripeGatewayService) - uses addCredits after webhook
- TASK-26-10 (Stripe Webhook Handler) - uses addCredits
- TASK-26-14 (PayPal Webhook Handler) - uses addCredits

---
_Leave notes for the next agent here_
