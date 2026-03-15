# TASK-26-9: Create Stripe Payment Intent Endpoint

## STATUS
`To Develop`

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
Create REST API endpoint for initiating a Stripe payment for credit purchase.

## Acceptance Criteria

### Endpoint
- [ ] `POST /api/v1/payments/stripe/intent`
- [ ] Authentication required (Bearer token)
- [ ] Returns JSON with client secret for Stripe.js

### Request Body
```json
{
  "package_id": 2,
  "return_url": "https://example.com/credits/success"
}
```

### Response Format (Success)
```json
{
  "success": true,
  "data": {
    "client_secret": "pi_xxx_secret_xxx",
    "payment_intent_id": "pi_xxx",
    "amount": 10.00,
    "credits": 10.50,
    "package_name": "Basic"
  }
}
```

### Response Format (Error)
```json
{
  "success": false,
  "message": "Payment could not be initiated",
  "errors": {
    "package_id": ["Invalid package selected"]
  }
}
```

### Controller Implementation
- [ ] `app/Http/Controllers/Api/V1/PaymentController.php` created
- [ ] `createStripeIntent()` method
- [ ] Validates package exists and is active
- [ ] Calculates credits including bonus
- [ ] Calls StripeGatewayService
- [ ] Creates pending transaction record

### Validation Rules
- [ ] `package_id` required, must exist in credit_packages table
- [ ] `package_id` must reference active package
- [ ] `return_url` optional, must be valid URL

### Business Logic
- [ ] Verify user is authenticated
- [ ] Calculate credits: base + bonus percentage
- [ ] Create Stripe payment intent
- [ ] Store pending transaction with payment_intent_id
- [ ] Return client_secret for frontend

### Error Responses
- [ ] 401 Unauthorized if not authenticated
- [ ] 422 Unprocessable Entity for validation errors
- [ ] 400 Bad Request if package inactive
- [ ] 500 Internal Server Error for Stripe failures

### Feature Tests
- [ ] Test successful payment intent creation
- [ ] Test invalid package returns 422
- [ ] Test inactive package returns 400
- [ ] Test unauthenticated returns 401
- [ ] Test Stripe error handling

## Dependencies
- TASK-26-3 (credit_packages)
- TASK-26-8 (StripeGatewayService)

## Effort Estimate
`Medium` (3-4 hours)

## Notes
- Client secret is used by Stripe.js on frontend
- Pending transaction created but not credited yet
- Webhook will confirm and add credits

## Security Considerations
- Amount validated server-side (never trust client)
- Package price read from database (not request)
- Payment intent created with user context
- Idempotency: prevent duplicate intents for same user/package

## Comments

### 2026-03-02 - ProjectManager
**Prioritization Complete:**
- **Priority: High** (confirmed) - Phase 3 Stripe Integration API endpoint for payment initiation
- **Risk: High** (confirmed) - Payment processing, Stripe API integration, financial transactions

**Dependency Status:**
- TASK-26-3 (Credit Packages): `Testing Prod` ✅ - Nearly complete, provides package_id validation
- TASK-26-8 (StripeGatewayService): `To Write Tests` ✅ - PreTester designing tests, provides createPaymentIntent()

**Phase 3 Context:**
- This task (TASK-26-9): Payment Intent Endpoint - creates REST API for initiating Stripe payments
- TASK-26-10: Stripe Webhook Handler - depends on this task AND CreditService (TASK-26-5)
- After this task: User can initiate payment, but confirmation requires webhook (TASK-26-10)

**TDD Approach for PreTester:**
Tests CAN be designed NOW using mocked StripeGatewayService interface:
- Mock `PaymentGatewayInterface::createPaymentIntent()` returning client_secret
- Mock `CreditPackage` model for package validation
- Mock `CreditTransaction` for pending transaction creation

**Test Coverage Requirements:**

**Endpoint Tests:**
- `POST /api/v1/payments/stripe/intent` with valid package_id
- Authentication required (Bearer token) - 401 for unauthenticated
- Validation: package_id required, must exist, must be active
- Return client_secret for Stripe.js frontend integration

**Request/Response Tests:**
```json
// Request
{ "package_id": 2, "return_url": "https://example.com/credits/success" }

// Success Response
{ "success": true, "data": { "client_secret": "pi_xxx_secret_xxx", "payment_intent_id": "pi_xxx", "amount": 10.00, "credits": 10.50, "package_name": "Basic" } }

// Error Response
{ "success": false, "message": "Payment could not be initiated", "errors": { "package_id": ["Invalid package selected"] } }
```

**Business Logic Tests:**
- Verify user is authenticated
- Calculate credits: base + bonus percentage from package
- Call StripeGatewayService::createPaymentIntent()
- Create pending transaction record with payment_intent_id
- Return client_secret for Stripe.js

**Error Scenario Tests:**
- 401 Unauthorized if not authenticated
- 422 Unprocessable Entity for validation errors
- 400 Bad Request if package inactive
- 500 Internal Server Error for Stripe failures

**Security Tests (CRITICAL):**
- Amount validated server-side (never trust client)
- Package price read from database (not request)
- Payment intent created with user context
- Idempotency: prevent duplicate intents for same user/package

**Controller Structure for Developer:**
- `app/Http/Controllers/Api/V1/PaymentController.php`
- Method: `createStripeIntent(Request $request)`
- Validation: package_id (required, exists:credit_packages,id, active), return_url (optional, URL)
- Service injection: StripeGatewayService, CreditService

**Execution Order:**
1. TASK-26-3 (Credit Packages): Testing Prod → Done (imminent)
2. TASK-26-8 (StripeGatewayService): To Write Tests → To Test Locally
3. TASK-26-9 (this task): PreTester designs tests NOW, Developer waits for TASK-26-8

---
_Leave notes for the next agent here_

### 2026-03-14 - PreTester
**Test Design Complete:**
- Created `tests/Feature/Api/V1/PaymentIntentControllerTest.php` with 55+ tests
- Created `tests/TESTS/TASK-26-9.md` documentation
- Status updated to `To Develop`

**Test Categories:**
- Success tests (7): PI-SUCCESS-01 to PI-SUCCESS-07
- Authentication tests (2): PI-AUTH-01 to PI-AUTH-02
- Validation tests (7): PI-VAL-01 to PI-VAL-07
- Error handling tests (4): PI-ERR-01 to PI-ERR-04
- Security tests (6): PI-SEC-01 to PI-SEC-06
- Idempotency tests (4): PI-IDEMP-01 to PI-IDEMP-04
- Edge cases (6): PI-EDGE-01 to PI-EDGE-06
- HTTP method tests (1): PI-HTTP-01
- Performance tests (1): PI-PERF-01
- Rate limiting tests (1): PI-RATE-01
- Integration tests (3): PI-INT-01 to PI-INT-03
- Acceptance criteria tests (10): AC-01 to AC-10

**Schema Notes:**
- `CreditTransaction.payment_id` stores the payment intent ID
- Package ID and credits stored in `metadata` JSON column
- Return URL stored in `metadata.return_url`

**Dependencies:**
- CreditPackage model ✅
- CreditTransaction model ✅
- PaymentGatewayInterface ✅
- StripeGatewayService implementation (TASK-26-8) - pending

**Ready for Developer:** All tests designed, can be run with mocks until StripeGatewayService is ready.
