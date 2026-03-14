# TESTS/TASK-26-9: Stripe Payment Intent Endpoint Tests

## Task Reference
- **Task:** TASK-26-9: Create Stripe Payment Intent Endpoint
- **Specification:** SPEC-026: Billing, Credits & Model Selection
- **Feature:** FEAT-026: Billing, Credits & Model Selection

---

## Overview

Comprehensive test suite for the Stripe Payment Intent REST API endpoint. Tests cover endpoint functionality, authentication, validation, business logic, error handling, and security measures.

---

## Test Files

| File | Tests | Assertions | Purpose |
|------|-------|------------|---------|
| `backend/tests/Feature/Api/V1/PaymentControllerTest.php` | 34 | 80+ | Controller endpoint tests |
| `backend/tests/Unit/Requests/CreateStripeIntentRequestTest.php` | 22 | 40+ | Request validation tests |

---

## Test Scenarios

### Endpoint Tests (POST /api/v1/payments/stripe/intent)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PI-001 | Successful payment intent creation | Valid package_id | 200, client_secret returned |
| PI-002 | Payment intent with return_url | Valid package_id + return_url | 200, intent created |
| PI-003 | Creates pending transaction | Valid package_id | Transaction record created |
| PI-010 | Unauthenticated request | No auth token | 401 Unauthorized |
| PI-011 | Authenticated user can create intent | Valid auth + package_id | 200 OK |

### Validation Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PI-020 | Missing package_id | {} | 422, validation error |
| PI-021 | Invalid package_id | package_id: 99999 | 422, validation error |
| PI-022 | Inactive package | package_id: inactive | 400 Bad Request |
| PI-023 | Invalid return_url | return_url: "not-url" | 422, validation error |
| PI-024 | Non-numeric package_id | package_id: "abc" | 422, validation error |

### Business Logic Tests

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| PI-030 | Credits calculated with bonus | credits = base + (base * bonus_percent / 100) |
| PI-031 | Credits calculated without bonus | credits = base |
| PI-032 | Amount from package not request | Gateway receives package.price_usd |
| PI-033 | Gateway receives correct metadata | user_id, package_id in metadata |

### Error Handling Tests

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| PI-040 | Stripe API error | 500, error message |
| PI-041 | Payment timeout | 503, service unavailable |
| PI-042 | Validation error | 422, detailed errors |

### Security Tests

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| PI-050 | Amount validated server-side | Request amount ignored |
| PI-051 | Package price from database | Never trust client input |
| PI-052 | Payment intent with user context | Correct user passed to gateway |
| PI-053 | Idempotency check | Duplicate intent prevented |
| PI-054 | Cannot use another user's package | Ownership verified |

### Response Format Tests

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| PI-060 | Success response format | `{success, data: {client_secret, payment_intent_id, amount, credits, package_name}}` |
| PI-061 | Error response format | `{message, errors: {...}}` |

### Edge Case Tests

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| PI-070 | Package with maximum values | 200, handles large amounts |
| PI-071 | Package with zero bonus | credits = base amount |
| PI-072 | Multiple requests create separate intents | No idempotency key collision |
| PI-073 | Package name matches database | Correct name in response |

---

## Request Validation Tests

### package_id Validation

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| VAL-001 | Required | {} | FAIL |
| VAL-002 | Must be integer | package_id: "abc" | FAIL |
| VAL-003 | Must exist | package_id: 99999 | FAIL |
| VAL-004 | Valid existing | package_id: 1 | PASS |
| VAL-005 | Numeric string | package_id: "1" | PASS |

### return_url Validation

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| VAL-010 | Optional | {} | PASS |
| VAL-011 | Must be valid URL | return_url: "invalid" | FAIL |
| VAL-012 | HTTP URL allowed | return_url: "http://..." | PASS |
| VAL-013 | HTTPS URL allowed | return_url: "https://..." | PASS |
| VAL-014 | Max length 500 | return_url: (501 chars) | FAIL |

### Security Validation

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| VAL-040 | SQL injection | package_id: "1; DROP..." | FAIL |
| VAL-041 | XSS in return_url | return_url: "javascript:..." | FAIL |
| VAL-042 | File protocol | return_url: "file://..." | FAIL |

---

## Mock Data

### Valid Request Body
```json
{
  "package_id": 2,
  "return_url": "https://example.com/credits/success"
}
```

### Success Response
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

### Error Response
```json
{
  "success": false,
  "message": "Payment could not be initiated",
  "errors": {
    "package_id": ["Invalid package selected"]
  }
}
```

### CreditPackage Factory States
```php
// Basic package
['name' => 'Basic', 'credits' => 10.00, 'price_usd' => 10.00, 'bonus_percent' => 5]

// Pro package with bonus
['name' => 'Pro', 'credits' => 100.00, 'price_usd' => 90.00, 'bonus_percent' => 20]

// Inactive package
['name' => 'Legacy', 'credits' => 5.00, 'price_usd' => 5.00, 'is_active' => false]
```

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Endpoint Success | 100% | 5 tests |
| Authentication | 100% | 2 tests |
| Validation | 100% | 6 tests |
| Business Logic | 100% | 4 tests |
| Error Handling | 100% | 3 tests |
| Security | 100% | 5 tests |
| Response Format | 100% | 2 tests |
| Edge Cases | 100% | 4 tests |
| Request Validation | 100% | 22 tests |

---

## Test Execution

```bash
# Run all payment tests
cd backend
php artisan test --filter=PaymentControllerTest
php artisan test --filter=CreateStripeIntentRequestTest

# Run with coverage
php artisan test --coverage --filter=PaymentControllerTest

# Run specific test
php artisan test --filter=pi_001_successful_payment_intent_creation
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-26-3 (Credit Packages) | Testing Prod | Package model and factory |
| TASK-26-8 (StripeGatewayService) | Tests Ready | PaymentGatewayInterface |
| CreditPackage Factory | Required | For creating test packages |
| User Factory | Required | For authentication |
| Mockery | Installed | For mocking gateway |

---

## Notes for Developer

### Implementation Requirements

1. **Controller:** `app/Http/Controllers/Api/V1/PaymentController.php`
   - `createStripeIntent()` method
   - Inject `PaymentGatewayInterface`
   - Validate package exists and is active
   - Call gateway service
   - Create pending transaction

2. **Request:** `app/Http/Requests/CreateStripeIntentRequest.php`
   - Validation rules for package_id and return_url

3. **Route:** `routes/api.php`
   - `POST /api/v1/payments/stripe/intent`
   - Protected by `auth` middleware

### Security Checklist

- [ ] Amount ALWAYS from database (never from request)
- [ ] Package price read server-side
- [ ] Payment intent created with user context
- [ ] Idempotency: check for existing pending transaction
- [ ] Log all payment attempts
- [ ] Mask sensitive data in logs

### Credits Calculation

```php
$effectiveCredits = $package->credits + ($package->credits * $package->bonus_percent / 100);
```

### Error Response Codes

| Code | Scenario |
|------|----------|
| 401 | Not authenticated |
| 422 | Validation errors |
| 400 | Inactive package |
| 500 | Stripe API error |
| 503 | Payment service timeout |

---

## Comments

### 2026-03-02 - PreTester
**Tests Coverage Complete:**
- 34 feature tests for PaymentController
- 22 unit tests for CreateStripeIntentRequest
- Total: 56 tests covering all acceptance criteria

**Key Test Patterns:**
- Mock PaymentGatewayInterface for isolation
- Test credits calculation with various bonus percentages
- Security tests ensure amount never from client
- Idempotency test prevents duplicate intents

**Mock Setup:**
```php
$mock = Mockery::mock(PaymentGatewayInterface::class);
$mock->shouldReceive('createPaymentIntent')
    ->andReturn([
        'client_secret' => 'pi_test_secret',
        'payment_intent_id' => 'pi_test',
        'amount' => 1000,
        'currency' => 'usd',
    ]);
$this->app->instance(PaymentGatewayInterface::class, $mock);
```

---
_Leave notes for the next agent here_
