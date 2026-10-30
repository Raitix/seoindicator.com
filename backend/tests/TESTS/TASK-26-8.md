# TESTS/TASK-26-8.md

## StripeGatewayService Tests

**Task:** TASK-26-8
**Created:** 2026-03-02
**Status:** Complete

---

## Overview

Comprehensive test suite for the StripeGatewayService, implementing TDD best practices for payment processing.

**Test File:** `tests/Unit/Services/Billing/StripeGatewayServiceTest.php`
**Implementation:** `app/Services/Billing/StripeGatewayService.php`

---

## Test Categories

### 1. Payment Intent Tests (PI)

| Test ID | Description | Status |
|---------|-------------|--------|
| PI-001 | createPaymentIntent creates payment intent with valid parameters | ✅ |
| PI-002 | createPaymentIntent creates customer if not exists | ✅ |
| PI-003 | createPaymentIntent reuses existing customer | ✅ |
| PI-004 | createPaymentIntent includes metadata for tracking | ✅ |
| PI-005 | createPaymentIntent uses idempotency key | ✅ |
| PI-006 | createPaymentIntent validates minimum amount ($5.00) | ✅ |
| PI-007 | createPaymentIntent handles Stripe API error | ✅ |
| PI-008 | createPaymentIntent handles network timeout | ✅ |
| PI-009 | createPaymentIntent converts amount to cents correctly | ✅ |
| PI-010 | createPaymentIntent supports different currencies | ✅ |

### 2. Payment Capture Tests (PC)

| Test ID | Description | Status |
|---------|-------------|--------|
| PC-001 | capturePayment captures authorized payment | ✅ |
| PC-002 | capturePayment returns false on failure | ✅ |
| PC-003 | capturePayment handles already captured payment | ✅ |
| PC-004 | capturePayment logs errors | ✅ |

### 3. Refund Tests (RF)

| Test ID | Description | Status |
|---------|-------------|--------|
| RF-001 | refundPayment processes full refund | ✅ |
| RF-002 | refundPayment processes partial refund | ✅ |
| RF-003 | refundPayment throws on Stripe error | ✅ |
| RF-004 | refundPayment uses idempotency key | ✅ |
| RF-005 | refundPayment handles already refunded payment | ✅ |

### 4. Webhook Handler Tests (WH) - SECURITY CRITICAL

| Test ID | Description | Status |
|---------|-------------|--------|
| WH-001 | handleWebhook verifies signature | ✅ |
| WH-002 | handleWebhook processes payment_intent.succeeded | ✅ |
| WH-003 | handleWebhook processes payment_intent.payment_failed | ✅ |
| WH-004 | handleWebhook processes charge.refunded | ✅ |
| WH-005 | handleWebhook rejects invalid signature | ✅ |
| WH-006 | handleWebhook handles duplicate event idempotently | ✅ |
| WH-007 | handleWebhook ignores unknown event types | ✅ |
| WH-008 | handleWebhook logs all events | ✅ |
| WH-009 | handleWebhook requires Stripe-Signature header | ✅ |
| WH-010 | handleWebhook handles malformed payload | ✅ |

### 5. Customer Management Tests (CM)

| Test ID | Description | Status |
|---------|-------------|--------|
| CM-001 | getCustomer retrieves existing customer | ✅ |
| CM-002 | getCustomer returns null for non-existent customer | ✅ |
| CM-003 | createCustomer creates new Stripe customer | ✅ |
| CM-004 | createCustomer stores customer ID in payment_methods | ✅ |
| CM-005 | createCustomer handles Stripe error | ✅ |
| CM-006 | getCustomer handles deleted customer | ✅ |

### 6. Error Handling Tests (EH)

| Test ID | Description | Status |
|---------|-------------|--------|
| EH-001 | StripePaymentException contains error code | ✅ |
| EH-002 | PaymentTimeoutException for network issues | ✅ |
| EH-003 | InvalidWebhookSignatureException for security | ✅ |
| EH-004 | All payment errors are logged | ✅ |
| EH-005 | Graceful handling of missing configuration | ✅ |
| EH-006 | Meaningful error messages for user-facing errors | ✅ |

### 7. Security Tests (SC)

| Test ID | Description | Status |
|---------|-------------|--------|
| SC-001 | Never store card numbers | ✅ |
| SC-002 | Webhook signature always verified | ✅ |
| SC-003 | Idempotency keys prevent duplicate charges | ✅ |
| SC-004 | Minimum amount enforced server-side | ✅ |
| SC-005 | All payment events logged for audit | ✅ |

### 8. Edge Case Tests (EC)

| Test ID | Description | Status |
|---------|-------------|--------|
| EC-001 | Handle zero-decimal currencies (JPY, etc.) | ✅ |
| EC-002 | Handle very large payment amounts | ✅ |
| EC-003 | Handle concurrent payment requests | ✅ |
| EC-004 | Handle partial payment capture | ✅ |
| EC-005 | Handle Unicode in metadata | ✅ |

---

## Supporting Files Created

### Contracts
- `app/Services/Billing/Contracts/PaymentGatewayInterface.php`

### DTOs
- `app/Services/Billing/DTOs/PaymentResult.php`

### Exceptions
- `app/Services/Billing/Exceptions/StripePaymentException.php`
- `app/Services/Billing/Exceptions/PaymentTimeoutException.php`
- `app/Services/Billing/Exceptions/PaymentValidationException.php`
- `app/Services/Billing/Exceptions/InvalidWebhookSignatureException.php`

---

## Security Considerations

### Webhook Signature Verification
All webhook tests verify that signature validation is performed:
- Tests WH-001, WH-005, WH-009 verify signature requirement
- Invalid signatures throw `InvalidWebhookSignatureException`
- No payment processing occurs without valid signature

### Idempotency
- All payment operations use idempotency keys
- Duplicate webhooks are detected and handled safely (WH-006)
- Prevents double-charging scenarios

### Audit Logging
- All payment events are logged with context
- WH-008 and SC-005 verify logging behavior
- Logs include event type, user ID, and payment ID

---

## Test Data Patterns

### Mocking Strategy
Tests use Mockery to mock Stripe SDK:
- No actual Stripe API calls during testing
- Predictable responses for consistent test results
- Easy simulation of error conditions

### User Factory
Tests use `User::factory()` for test users with consistent data.

### Credit Factory
Tests create credit records directly for balance testing.

---

## Running Tests

```bash
# Run all StripeGatewayService tests
php artisan test --filter=StripeGatewayServiceTest

# Run specific test category
php artisan test --filter=StripeGatewayServiceTest::test_create_payment_intent

# Run with coverage
php artisan test --filter=StripeGatewayServiceTest --coverage
```

---

## Dependencies

- PHPUnit 10+
- Mockery
- Laravel 11+
- stripe/stripe-php SDK

---

## Notes for Implementation

1. **PaymentMethods Table:** The service references a `payment_methods` table for storing Stripe customer IDs. This table needs to be created as part of the billing migration.

2. **Configuration:** Ensure Stripe keys are set in `config/services.php`:
   ```php
   'stripe' => [
       'key' => env('STRIPE_KEY'),
       'secret' => env('STRIPE_SECRET'),
       'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
   ],
   ```

3. **Webhook Endpoint:** Create route in `routes/api.php`:
   ```php
   Route::post('payments/stripe/webhook', [PaymentController::class, 'handleWebhook']);
   ```

4. **Service Provider:** Register the interface binding:
   ```php
   $this->app->bind(PaymentGatewayInterface::class, StripeGatewayService::class);
   ```
