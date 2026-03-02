# TASK-26-9: Create Stripe Payment Intent Endpoint

## STATUS
`Tests Coverage In Progress`

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
