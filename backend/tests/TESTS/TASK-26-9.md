# TESTS/TASK-26-9.md — Stripe Payment Intent Endpoint Tests

## Task Reference
- **Task:** TASK-26-9: Create Stripe Payment Intent Endpoint
- **Specification:** SPEC-026: Billing, Credits & Model Selection
- **Feature:** FEAT-026: Billing, Credits & Model Selection
- **Status:** To Develop

---

## Overview

Test suite for `POST /api/v1/payments/stripe/intent` endpoint. This endpoint initiates a Stripe payment for credit purchase and returns a client secret for Stripe.js frontend integration.

### Key Functionality
- Create Stripe payment intent
- Validate credit packages
- Calculate credits with bonus
- Create pending transaction record
- Return client_secret for frontend

---

## Test Files

| File | Tests | Description |
|------|-------|-------------|
| `PaymentIntentControllerTest.php` | 55+ | Feature tests for payment intent endpoint |

---

## Test Scenarios

### Success Tests (PI-SUCCESS-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PI-SUCCESS-01 | Create intent returns client_secret | Valid package_id | 200, client_secret in response |
| PI-SUCCESS-02 | Response includes correct package name | Valid package | Package name matches |
| PI-SUCCESS-03 | Response includes correct amount | Package with custom price | Amount from database |
| PI-SUCCESS-04 | Credits calculated with bonus | Package with bonus | Credits = base + bonus% |
| PI-SUCCESS-05 | Credits without bonus when zero | Package with 0% bonus | Credits = base only |
| PI-SUCCESS-06 | Pending transaction created | Valid request | Transaction with status=pending |
| PI-SUCCESS-07 | Return URL stored in metadata | Valid return_url | Metadata contains return_url |

### Authentication Tests (PI-AUTH-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PI-AUTH-01 | Unauthenticated returns 401 | No auth token | 401 Unauthorized |
| PI-AUTH-02 | Authenticated user can create | Valid auth + package | 200 OK |

### Validation Tests (PI-VAL-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PI-VAL-01 | Missing package_id | Empty body | 422, validation error |
| PI-VAL-02 | Invalid package_id | Non-existent ID | 422, validation error |
| PI-VAL-03 | Non-numeric package_id | String value | 422, validation error |
| PI-VAL-04 | Inactive package | Inactive package ID | 400 Bad Request |
| PI-VAL-05 | Invalid return_url | Not a URL | 422, validation error |
| PI-VAL-06 | Valid return_url | Valid URL string | 200 OK |
| PI-VAL-07 | Optional return_url works | No return_url | 200 OK |

### Error Handling Tests (PI-ERR-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PI-ERR-01 | Stripe API error | Stripe throws exception | 500, generic message |
| PI-ERR-02 | Stripe connection error | Network failure | 500 |
| PI-ERR-03 | No transaction on Stripe error | Stripe failure | No transaction created |
| PI-ERR-04 | Error response format | Any error | Correct JSON structure |

### Security Tests (PI-SEC-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PI-SEC-01 | Amount from database, not request | Amount in request | Ignored, uses DB value |
| PI-SEC-02 | Credits calculated server-side | Credits in request | Ignored, calculated from package |
| PI-SEC-03 | Payment intent with user context | Valid auth | User ID in Stripe call |
| PI-SEC-04 | No sensitive data in response | Valid request | No stripe_customer_id exposed |
| PI-SEC-05 | No SQL injection | SQL in package_id | Safe handling |
| PI-SEC-06 | No XSS in return_url | Script tag in URL | Validation error |

### Idempotency Tests (PI-IDEMP-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PI-IDEMP-01 | Duplicate intent prevented | Same user + package | Returns existing intent |
| PI-IDEMP-02 | New intent after completion | Completed transaction | Creates new intent |
| PI-IDEMP-03 | New intent for different package | Different package ID | Creates new intent |
| PI-IDEMP-04 | New intent for different user | Different auth | Creates new intent |

### Edge Cases (PI-EDGE-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PI-EDGE-01 | Large bonus percentage | 50% bonus | Correct calculation |
| PI-EDGE-02 | Decimal credits | 10.50 credits + 10% | 11.55 credits |
| PI-EDGE-03 | Zero price package | $0.00 package | Handled correctly |
| PI-EDGE-04 | Large price package | $999.99 package | Handled correctly |
| PI-EDGE-05 | Long package name | 255 char name | Handled correctly |
| PI-EDGE-06 | Unicode in return_url | Unicode in path | Handled correctly |

### HTTP Method Tests (PI-HTTP-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PI-HTTP-01 | Only POST accepted | GET/PUT/PATCH/DELETE | 405 Method Not Allowed |

### Performance Tests (PI-PERF-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PI-PERF-01 | Response time < 500ms | Valid request | < 500ms response time |

### Rate Limiting Tests (PI-RATE-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PI-RATE-01 | Reasonable requests allowed | 5 requests | All succeed |

### Integration Tests (PI-INT-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PI-INT-01 | Full payment intent flow | Complete valid request | All steps succeed |
| PI-INT-02 | Transaction amount matches package | Custom price | Correct amount in transaction |
| PI-INT-03 | Transaction credits match effective | With bonus | Correct credits in metadata |

### Acceptance Criteria Tests (AC-*)

| ID | Scenario | Acceptance Criteria |
|----|----------|---------------------|
| AC-01 | Endpoint exists | `POST /api/v1/payments/stripe/intent` returns 200 |
| AC-02 | Authentication required | Returns 401 for unauthenticated |
| AC-03 | Returns JSON with client secret | Response includes client_secret |
| AC-04 | Validates package exists | Returns 422 for non-existent package |
| AC-05 | Validates package is active | Returns 400 for inactive package |
| AC-06 | Creates pending transaction | Transaction with status=pending |
| AC-07 | 401 for unauthenticated | Correct status code |
| AC-08 | 422 for validation errors | Correct status code |
| AC-09 | 400 for inactive package | Correct status code |
| AC-10 | 500 for Stripe failures | Correct status code |

---

## Mock Data

### Credit Package
```php
$package = [
    'id' => 1,
    'name' => 'Basic',
    'credits' => 10.00,
    'price_usd' => 10.00,
    'bonus_percent' => 5,
    'is_active' => true,
];
```

### Payment Intent Response
```php
$intentResponse = [
    'payment_intent_id' => 'pi_test123',
    'client_secret' => 'pi_test123_secret_abc',
    'amount' => 10.00,
    'currency' => 'USD',
];
```

### Expected Response
```json
{
  "success": true,
  "data": {
    "client_secret": "pi_test123_secret_abc",
    "payment_intent_id": "pi_test123",
    "amount": "10.00",
    "credits": "10.50",
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

---

## Coverage Targets

| Category | Target |
|----------|--------|
| Success Paths | 100% |
| Authentication | 100% |
| Validation | 100% |
| Error Handling | 100% |
| Security | 100% |
| Idempotency | 100% |
| Edge Cases | 100% |
| Acceptance Criteria | 100% |

---

## Test Execution

```bash
# Run all payment intent tests
php artisan test --filter=PaymentIntentControllerTest

# Run specific test category
php artisan test --filter=PI-SUCCESS
php artisan test --filter=PI-SEC
php artisan test --filter=PI-IDEMP

# Run with coverage
php artisan test --filter=PaymentIntentControllerTest --coverage
```

---

## Dependencies

### Required Models
- `User` - Authenticated user making purchase
- `CreditPackage` - Package being purchased
- `CreditTransaction` - Transaction record

### Required Services
- `PaymentGatewayInterface` - Payment gateway abstraction

### Required Routes
- `POST /api/v1/payments/stripe/intent`

---

## Notes for Developer

### Controller Implementation
- `app/Http/Controllers/Api/V1/PaymentController.php`
- Method: `createStripeIntent(Request $request)`

### Validation Rules
```php
$rules = [
    'package_id' => ['required', 'integer', 'exists:credit_packages,id'],
    'return_url' => ['nullable', 'url', 'max:2048'],
];
```

### Business Logic
1. Validate package exists and is active
2. Calculate effective credits (base + bonus)
3. Call PaymentGatewayInterface::createPaymentIntent()
4. Create pending CreditTransaction
5. Return client_secret

### Security Checklist
- [ ] Amount read from database (never trust client)
- [ ] Credits calculated server-side
- [ ] Payment intent created with user context
- [ ] Idempotency check before creating new intent
- [ ] No sensitive data in response

---

## Comments

### 2026-03-14 - PreTester
**Test Design Complete:**
- Created 55+ feature tests for payment intent endpoint
- Tests use mocked PaymentGatewayInterface
- All acceptance criteria covered
- Security tests for amount/credits validation
- Idempotency tests for duplicate prevention
- Edge cases for bonus calculation, decimal amounts

**Schema Notes:**
- CreditTransaction uses `payment_id` for payment intent ID
- Package ID and credits stored in `metadata` JSON column
- Return URL stored in `metadata.return_url`

**Dependencies:**
- CreditPackage model exists with factory ✅
- CreditTransaction model exists with factory ✅
- PaymentGatewayInterface exists ✅
- StripeGatewayService implementation pending (TASK-26-8)

---
_Leave notes for the next agent here_
