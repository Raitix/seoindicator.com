# TASK-26-6: Create Credit Balance API Endpoint

## STATUS
`To Write Tests`

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

### 2026-03-02 - ProjectManager
**Prioritization Complete:**
- **Priority: High** (confirmed) - API endpoint essential for billing MVP user experience
- **Risk: Medium** (confirmed) - Read-only endpoint, delegates to CreditService, standard Laravel controller

**Dependency Status:**
- TASK-26-5 (CreditService): `To Write Tests` ✅ - PreTester actively designing tests
- Authentication middleware: Exists ✅ (TASK-25-3 AuthController done)

**TDD Approach:**
- PreTester CAN design tests NOW using expected CreditService interface
- Tests can mock CreditService methods (getBalance, hasSufficientBalance, getTransactionHistory)
- Implementation waits for TASK-26-5 to reach `To Test Locally`

**Phase 2 Context:**
- This is the first API endpoint that exposes credit functionality to users
- Followed by TASK-26-7 (Transaction History API) which depends on same CreditService
- Both can be developed in parallel once CreditService is ready

**Test Coverage Requirements for PreTester:**
- **Endpoint Tests:**
  - `GET /api/v1/credits/balance` - authenticated user
  - 401 Unauthorized for unauthenticated requests
  - Response structure validation (balance, formatted_balance, total_purchased, total_spent, has_low_balance, low_balance_threshold)
  - Low balance indicator (balance < $2.00 threshold)

- **Controller Tests:**
  - `CreditController::balance()` method
  - Uses CreditService for data retrieval
  - Returns proper HTTP status codes (200, 401, 500)
  - User can only see their own balance (authorization)

- **Edge Cases:**
  - User with no Credit record (should show $0.00 balance)
  - Low balance threshold configuration
  - Error handling for CreditService failures

**Security Notes:**
- User can only see their own balance (auth middleware + user context)
- No sensitive payment data exposed beyond balance
- Rate limiting to prevent abuse (add rate limit middleware)
- Generic error response on failures (no internal details leaked)

**API Documentation:**
- OpenAPI/Swagger documentation should be generated
- Include endpoint in API documentation file

### 2026-03-02 - PreTester
**Test Documentation Complete:**
- Test documentation created: `TESTS/TASK-26-6.md`
- **50+ test scenarios** across 11 categories
- Test files to create:
  - `backend/tests/Feature/Api/V1/Credits/BalanceEndpointTest.php` (35+ tests)
  - `backend/tests/Unit/Http/Controllers/Api/V1/CreditControllerTest.php` (15+ tests)

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| Authentication (AT) | 5 | Token validation, 401 responses |
| Response Structure (RS) | 10 | JSON structure, data fields |
| Balance Retrieval (BR) | 8 | Balance values, user isolation |
| Sufficient Balance Flag (SB) | 4 | has_sufficient_balance logic |
| Low Balance Warning (LB) | 5 | low_balance_warning threshold ($5.00) |
| Authorization (AZ) | 4 | User isolation, own balance only |
| Error Handling (EH) | 4 | Service errors, database failures |
| Edge Cases (EC) | 6 | Max balance, zero balance, precision |
| Security (SC) | 5 | SQL injection, XSS, IDOR, rate limiting |
| Performance (PF) | 3 | Response time, query count |
| Cache (CH) | 3 | Optional cache tests |

**Key Test Highlights:**
- ✅ Authentication via Sanctum (AT-001 to AT-005)
- ✅ Response structure matches specification (RS-001 to RS-010)
- ✅ Low balance threshold at $5.00 (LB-001 to LB-005)
- ✅ User isolation - each user sees own balance only (AZ-001 to AZ-004)
- ✅ User without Credit record gets 0.00 balance (BR-003)
- ✅ Security tests for SQL injection, XSS, IDOR (SC-001 to SC-005)

**Dependency Status:**
- TASK-26-1 (Migrations): `In Development` ⏳
- TASK-26-2 (Credit Model): `Tests Coverage In Progress` ✅
- TASK-26-5 (CreditService): `Tests Coverage In Progress` ✅

**Ready for Developer when:**
- All dependencies reach `To Test Locally` status

---
_Leave notes for the next agent here_
