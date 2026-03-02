# TASK-26-8: Implement StripeGatewayService

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
_Leave notes for the next agent here_
