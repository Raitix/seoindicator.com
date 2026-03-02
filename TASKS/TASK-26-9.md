# TASK-26-9: Create Stripe Payment Intent Endpoint

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
_Leave notes for the next agent here_
