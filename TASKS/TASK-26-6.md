# TASK-26-6: Create Credit Balance API Endpoint

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
Create REST API endpoint for users to retrieve their current credit balance.

## Acceptance Criteria

### Endpoint
- [ ] `GET /api/v1/credits/balance`
- [ ] Authentication required (Bearer token)
- [ ] Returns JSON response with balance data

### Response Format
```json
{
  "success": true,
  "data": {
    "balance": 25.50,
    "formatted_balance": "$25.50",
    "total_purchased": 50.00,
    "total_spent": 24.50,
    "has_low_balance": false,
    "low_balance_threshold": 2.00
  }
}
```

### Controller Implementation
- [ ] `app/Http/Controllers/Api/V1/CreditController.php` created
- [ ] `balance()` method implemented
- [ ] Uses CreditService for data retrieval
- [ ] Returns proper HTTP status codes

### Request Validation
- [ ] User must be authenticated
- [ ] User must exist in database

### Error Responses
- [ ] 401 Unauthorized if not authenticated
- [ ] 500 Internal Server Error with generic message on failures

### Feature Tests
- [ ] Test authenticated user can get balance
- [ ] Test unauthenticated user receives 401
- [ ] Test balance is correctly formatted
- [ ] Test low balance indicator works

## Dependencies
- TASK-26-5 (CreditService)
- Existing authentication middleware

## Effort Estimate
`Small` (1-2 hours)

## Notes
- This is a read-only endpoint (low risk)
- Balance is calculated fresh on each request
- Low balance threshold is configurable

## Security Considerations
- User can only see their own balance
- No sensitive data exposed beyond balance
- Rate limit to prevent abuse

## API Documentation
- OpenAPI/Swagger documentation for endpoint
- Include in API documentation file

## Comments
_Leave notes for the next agent here_
