# TASK-26-2: Create Credit Model and Repository

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
_Leave notes for the next agent here_
