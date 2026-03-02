# TASK-26-5: Implement CreditService with Balance Management

## STATUS
`To Prioritize`

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
_Leave notes for the next agent here_
