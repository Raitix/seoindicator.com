# TASK-26-7: Create Transaction History API Endpoint

## STATUS
`To Prioritize`

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
_Leave notes for the next agent here_
