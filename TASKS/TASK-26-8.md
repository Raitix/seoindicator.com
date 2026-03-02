# TASK-26-8: Implement StripeGatewayService

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
Implement the Stripe payment gateway service for creating payment intents, handling webhooks, and managing customers.

## Acceptance Criteria

### StripeGatewayService Implementation
- [ ] `app/Services/Gateway/StripeGatewayService.php` created
- [ ] Implements `PaymentGatewayInterface`
- [ ] Uses Stripe PHP SDK (`stripe/stripe-php`)

### PaymentGatewayInterface Methods
- [ ] `createPaymentIntent(User $user, float $amount, string $currency = 'USD'): array`
- [ ] `capturePayment(string $paymentIntentId): bool`
- [ ] `refundPayment(string $paymentIntentId, float $amount = null): array`
- [ ] `handleWebhook(Request $request): PaymentResult`
- [ ] `getCustomer(User $user): ?string`
- [ ] `createCustomer(User $user): string`

### Payment Intent Creation
- [ ] Create Stripe customer if not exists
- [ ] Set up payment intent with correct amount
- [ ] Include metadata for tracking (user_id, package_id)
- [ ] Return client_secret for frontend
- [ ] Use idempotency key for retry safety

### Webhook Handler
- [ ] Verify webhook signature using `STRIPE_WEBHOOK_SECRET`
- [ ] Handle `payment_intent.succeeded` event
- [ ] Handle `payment_intent.payment_failed` event
- [ ] Handle `charge.refunded` event
- [ ] Return structured PaymentResult object

### Customer Management
- [ ] Create Stripe customer with email and name
- [ ] Store Stripe customer ID in `payment_methods` table
- [ ] Retrieve existing customer for repeat purchases

### Error Handling
- [ ] Custom exception: `StripePaymentException`
- [ ] Log all Stripe API errors
- [ ] Graceful handling of network timeouts
- [ ] Return meaningful error messages

### Unit Tests
- [ ] Mock Stripe SDK for unit testing
- [ ] Test payment intent creation
- [ ] Test webhook signature verification
- [ ] Test customer creation and retrieval
- [ ] Test refund flow
- [ ] Test error scenarios

## Dependencies
- TASK-26-1 (payment_methods table)
- Stripe PHP SDK (`composer require stripe/stripe-php`)

## Effort Estimate
`Large` (5-6 hours)

## Notes
- Stripe test mode should be used during development
- Webhook secret is CRITICAL for security
- Idempotency keys prevent duplicate charges
- Consider using Stripe's metered billing for future

## Security Considerations
- NEVER store card numbers (Stripe handles this)
- ALWAYS verify webhook signatures
- Use idempotency keys for all operations
- Log all payment events for audit
- Minimum amount enforced server-side ($5)

## Environment Variables Required
```env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

## Comments

### 2026-03-02 - ProjectManager
**Prioritization Complete:**
- **Priority: High** (confirmed) - Phase 3 Stripe Integration foundation service
- **Risk: High** (confirmed) - External API (Stripe), payment processing, webhook security, PII handling

**Dependency Status:**
- TASK-26-1 (Credits/Transactions Tables): `In Development` ⏳
- TASK-26-5 (CreditService): `To Write Tests` ✅ - PreTester designing tests

**⚠️ Dependency Discrepancy Identified:**
Task file lists `TASK-26-1 (payment_methods table)` but:
- TASK-26-1 creates `credits` and `credit_transactions` tables
- `payment_methods` table is defined in SPEC-026 schema but has no explicit migration task
- **Resolution needed:** Either add `payment_methods` to TASK-26-1 migration, or create separate task
- Developer should verify migration includes `payment_methods` table or flag for SpecSystemAnalyst

**Phase 3 Context:**
- TASK-26-8 (this task): StripeGatewayService - foundation for Stripe integration
- TASK-26-9: Stripe payment intent endpoint - depends on this task
- TASK-26-10: Stripe webhook handler - depends on this task AND CreditService
- TASK-26-11: Payment methods management - depends on this task

**TDD Approach for PreTester:**
- Tests CAN be designed NOW using mocked Stripe SDK
- Mock `Stripe\Stripe` client and API responses
- Test interface methods defined in PaymentGatewayInterface

**Test Coverage Requirements:**
- **PaymentGatewayInterface Methods:**
  - `createPaymentIntent(User, amount, currency)` - returns client_secret
  - `capturePayment(paymentIntentId)` - returns bool
  - `refundPayment(paymentIntentId, amount)` - returns array
  - `handleWebhook(Request)` - returns PaymentResult
  - `getCustomer(User)` - returns Stripe customer ID or null
  - `createCustomer(User)` - returns new customer ID

- **Webhook Handler Tests (SECURITY CRITICAL):**
  - Signature verification with `STRIPE_WEBHOOK_SECRET`
  - `payment_intent.succeeded` event handling
  - `payment_intent.payment_failed` event handling
  - `charge.refunded` event handling
  - Invalid signature rejection
  - Idempotency (same event twice = no double-credit)

- **Customer Management Tests:**
  - Create customer with email/name
  - Retrieve existing customer for repeat purchases
  - Store customer ID in `payment_methods` table

- **Error Handling Tests:**
  - `StripePaymentException` custom exception
  - Network timeout handling
  - API error responses
  - Invalid amount validation (minimum $5)

**Security Notes for PreTester:**
- NEVER store card numbers (Stripe handles this)
- ALWAYS verify webhook signatures
- Use idempotency keys for all operations
- Log all payment events for audit
- Environment variables: `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`

**Environment Required:**
```env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

**Execution Order:**
1. TASK-26-1 (Migrations) → Must include `payment_methods` table
2. TASK-26-5 (CreditService) → To Test Locally
3. TASK-26-8 (this task) → PreTester designs tests, Developer implements
4. TASK-26-9 & TASK-26-10 → After this task reaches `To Test Locally`

---
_Leave notes for the next agent here_
