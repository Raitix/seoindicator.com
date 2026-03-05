# TASK-26-7: Create Transaction History API Endpoint

## STATUS
`Tests Coverage In Progress`

## PRIORITY
`Medium`

## RISK
`Low`

## Related Specification
SPEC-026: Billing, Credits & Model Selection

## Related Feature
FEAT-026: Billing, Credits & Model Selection

---

## Description
Create REST API endpoint for users to view their transaction history with filtering and pagination.

## Acceptance Criteria

### Endpoint
- [ ] `GET /api/v1/credits/transactions`
- [ ] Authentication required (Bearer token)
- [ ] Returns paginated JSON response

### Query Parameters
- [ ] `page` - Page number (default: 1)
- [ ] `per_page` - Items per page (default: 15, max: 50)
- [ ] `type` - Filter by transaction type (purchase, usage, refund, bonus)
- [ ] `from` - Filter from date (YYYY-MM-DD)
- [ ] `to` - Filter to date (YYYY-MM-DD)

### Response Format
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "amount": 10.00,
      "balance_after": 35.50,
      "type": "purchase",
      "description": "Purchased $10.00 credits via Stripe",
      "payment_provider": "stripe",
      "created_at": "2026-03-02T12:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 45,
    "last_page": 3
  }
}
```

### Controller Implementation
- [ ] `transactions()` method in CreditController
- [ ] Uses CreditService for data retrieval
- [ ] Validates query parameters
- [ ] Applies filters correctly

### Validation Rules
- [ ] `per_page` must be between 1 and 50
- [ ] `type` must be valid enum value if provided
- [ ] `from` and `to` must be valid dates
- [ ] `from` must be before or equal to `to`

### Feature Tests
- [ ] Test authenticated user can list transactions
- [ ] Test pagination works correctly
- [ ] Test type filtering works
- [ ] Test date range filtering works
- [ ] Test unauthenticated user receives 401
- [ ] Test invalid parameters return validation errors

## Dependencies
- TASK-26-5 (CreditService)
- TASK-26-6 (CreditController exists)

## Effort Estimate
`Small` (2-3 hours)

## Notes
- Transaction history is read-only (immutable records)
- Consider caching for frequent requests
- May want to add export functionality later

## Security Considerations
- User can only see their own transactions
- No sensitive payment details exposed
- Rate limit to prevent abuse

## Performance Considerations
- Add database index on `created_at` for date filtering
- Consider cursor pagination for large datasets

## Comments

### 2026-03-03 - ProjectManager
**Prioritization Complete:**
- **Priority: Medium** (confirmed) - Read-only API endpoint, non-blocking for billing MVP
- **Risk: Low** (confirmed) - Read-only operation, no financial mutations, easily reversible

**Dependency Status:**
- TASK-26-5 (CreditService): `Done` ✅ - All methods available including `getTransactionHistory()`
- TASK-26-6 (CreditController): File exists but empty - PreTester should verify CreditController exists or design tests assuming it will be created

**Test Coverage Requirements for PreTester:**
1. **Endpoint Tests:**
   - `GET /api/v1/credits/transactions` returns paginated list
   - Authentication required (401 for unauthenticated)
   - User can only see their own transactions

2. **Pagination Tests:**
   - Default pagination (page=1, per_page=15)
   - Custom per_page values (1-50)
   - per_page > 50 should be capped or return validation error

3. **Filtering Tests:**
   - Filter by `type` (purchase, usage, refund, bonus)
   - Filter by `from` date
   - Filter by `to` date
   - Combined filters (type + date range)
   - Invalid `type` returns validation error
   - Invalid date format returns validation error
   - `from` > `to` returns validation error

4. **Response Format Tests:**
   - Success response includes `success`, `data`, `meta`
   - Transaction items include: id, amount, balance_after, type, description, payment_provider, created_at
   - Meta includes: current_page, per_page, total, last_page

5. **Security Tests:**
   - User A cannot see User B's transactions
   - No sensitive payment details exposed (no card numbers, etc.)
   - Rate limiting (if implemented)

**Implementation Notes:**
- CreditService already has `getTransactionHistory(User $user, int $perPage = 15): LengthAwarePaginator`
- Controller needs to add filtering logic on top of the service method
- Consider adding `created_at` index if not already present

**Enables:**
- Frontend transaction history page
- Export functionality (future enhancement)

---
_Leave notes for the next agent here_
