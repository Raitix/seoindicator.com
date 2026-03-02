# TESTS/TASK-26-6.md — Credit Balance API Endpoint

## Task Reference
- **Task:** TASK-26-6
- **Specification:** SPEC-026
- **Feature:** FEAT-026
- **Agent:** PreTester

---

## Overview

Comprehensive test suite for the Credit Balance API endpoint (`GET /api/v1/credits/balance`). Tests cover authentication, authorization, response structure, error handling, edge cases, and security considerations for the credit balance retrieval functionality.

---

## Test Files

| File | Type | Tests | Purpose |
|------|------|-------|---------|
| `backend/tests/Feature/Api/V1/Credits/BalanceEndpointTest.php` | Feature | 35+ | API endpoint tests (auth, response, errors, security) |
| `backend/tests/Unit/Http/Controllers/Api/V1/CreditControllerTest.php` | Unit | 15+ | Controller unit tests |
| **Total** | | **50+** | |

---

## API Endpoint

### Get Credit Balance
`GET /api/v1/credits/balance`

**Headers:**
| Header | Value | Required |
|--------|-------|----------|
| `Authorization` | `Bearer {token}` | Yes |
| `Accept` | `application/json` | Yes |

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "balance": 100.00,
    "total_purchased": 150.00,
    "total_spent": 50.00,
    "formatted_balance": "$100.00",
    "has_sufficient_balance": true,
    "low_balance_warning": false
  }
}
```

**Error Responses:**
| Status | Condition | Response |
|--------|-----------|----------|
| 401 | Unauthenticated | `{"message": "Unauthenticated."}` |
| 500 | Server error | `{"success": false, "message": "Failed to retrieve balance"}` |

---

## Test Scenarios

### Authentication Tests (AT)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| AT-001 | Request without token | 401 Unauthorized |
| AT-002 | Request with invalid token | 401 Unauthorized |
| AT-003 | Request with expired token | 401 Unauthorized |
| AT-004 | Request with valid token | 200 OK |
| AT-005 | Request with revoked token | 401 Unauthorized |

### Response Structure Tests (RS)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| RS-001 | Response has success field | `success: true` |
| RS-002 | Response has data object | `data` object present |
| RS-003 | Data contains balance | `balance` as float |
| RS-004 | Data contains total_purchased | `total_purchased` as float |
| RS-005 | Data contains total_spent | `total_spent` as float |
| RS-006 | Data contains formatted_balance | `formatted_balance` as string with $ |
| RS-007 | Data contains has_sufficient_balance | Boolean flag |
| RS-008 | Data contains low_balance_warning | Boolean flag |
| RS-009 | Balance precision is 2 decimals | Max 2 decimal places |
| RS-010 | Response has correct Content-Type | `application/json` |

### Balance Retrieval Tests (BR)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| BR-001 | User with positive balance | Returns correct balance |
| BR-002 | User with zero balance | Returns 0.00 |
| BR-003 | User with no credit record | Creates record, returns 0.00 |
| BR-004 | User with spent credits | total_spent reflects usage |
| BR-005 | User with purchased credits | total_purchased reflects purchases |
| BR-006 | Large balance amount | Handles DECIMAL(10,2) max |
| BR-007 | Small decimal amounts | 0.01 precision maintained |
| BR-008 | Multiple users isolation | Each user sees own balance only |

### Sufficient Balance Flag Tests (SB)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| SB-001 | Balance > 0 | has_sufficient_balance: true |
| SB-002 | Balance = 0 | has_sufficient_balance: false |
| SB-003 | Balance > minimum threshold | has_sufficient_balance: true |
| SB-004 | Balance < minimum threshold | has_sufficient_balance: false |

### Low Balance Warning Tests (LB)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| LB-001 | Balance >= $5.00 | low_balance_warning: false |
| LB-002 | Balance < $5.00 | low_balance_warning: true |
| LB-003 | Balance = $0.00 | low_balance_warning: true |
| LB-004 | Balance = $4.99 | low_balance_warning: true |
| LB-005 | Balance = $5.00 | low_balance_warning: false |

### Authorization Tests (AZ)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| AZ-001 | User accesses own balance | 200 OK with correct data |
| AZ-002 | User cannot access other user's balance | Isolation enforced |
| AZ-003 | Admin user accesses balance | 200 OK with own balance |
| AZ-004 | Regular user accesses balance | 200 OK with own balance |

### Error Handling Tests (EH)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| EH-001 | CreditService throws exception | 500 error, generic message |
| EH-002 | Database connection failure | 500 error, logged |
| EH-003 | User model not found | Should not happen (auth ensures user exists) |
| EH-004 | Invalid Accept header | Still returns JSON (Laravel default) |

### Edge Case Tests (EC)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| EC-001 | Balance at DECIMAL(10,2) max | 99999999.99 handled correctly |
| EC-002 | Negative balance (edge case) | Returned as-is (data integrity issue) |
| EC-003 | Concurrent balance requests | Both return same value |
| EC-004 | Request during transaction | Returns committed balance |
| EC-005 | Unicode in user data | No impact on balance response |
| EC-006 | Very long user ID | Standard Laravel validation |

### Security Tests (SC)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| SC-001 | SQL injection in token | 401, no SQL executed |
| SC-002 | XSS in response headers | Headers sanitized |
| SC-003 | IDOR attempt (other user) | Isolation enforced |
| SC-004 | Rate limiting (if enabled) | 429 after limit exceeded |
| SC-005 | Balance data not in logs | PII not logged |

### Performance Tests (PF)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| PF-001 | Response time < 100ms | Fast balance lookup |
| PF-002 | Database query count | Single query for balance |
| PF-003 | N+1 query prevention | No N+1 issues |

### Cache Tests (CH) - Optional

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| CH-001 | Balance cached briefly | Cache hit on repeated requests |
| CH-002 | Cache invalidated on update | Fresh balance after transaction |
| CH-003 | Cache miss fallback | Database query executed |

---

## Mock Data

### User Factory

```php
// User with credit balance
$user = User::factory()->create([
    'email' => 'test@example.com',
]);

// Credit record
$credit = Credit::create([
    'user_id' => $user->id,
    'balance' => 100.00,
    'total_purchased' => 150.00,
    'total_spent' => 50.00,
]);
```

### Authentication Helper

```php
// Acting as user
$this->actingAs($user, 'sanctum');

// Or with token
$token = $user->createToken('test-token')->plainTextToken;
$response = $this->withHeader('Authorization', "Bearer {$token}")
    ->getJson('/api/v1/credits/balance');
```

### Credit Scenarios

```php
// Zero balance user
$zeroUser = User::factory()->create();
Credit::create(['user_id' => $zeroUser->id, 'balance' => 0.00]);

// Low balance user
$lowUser = User::factory()->create();
Credit::create(['user_id' => $lowUser->id, 'balance' => 2.50]);

// High balance user
$highUser = User::factory()->create();
Credit::create(['user_id' => $highUser->id, 'balance' => 1000.00]);

// User without credit record
$newUser = User::factory()->create();
// No credit record created - should auto-create on first request
```

---

## Coverage Targets

| Component | Target | Status |
|-----------|--------|--------|
| Authentication | 100% | ⏳ |
| Response Structure | 100% | ⏳ |
| Balance Retrieval | 100% | ⏳ |
| Error Handling | 100% | ⏳ |
| Security | 100% | ⏳ |
| Edge Cases | 90% | ⏳ |
| **Overall** | **95%** | ⏳ |

---

## Test Execution

```bash
# Run Balance Endpoint tests
cd backend
php artisan test --filter=BalanceEndpointTest

# Run with coverage
php artisan test --coverage --filter=BalanceEndpointTest

# Run specific test category
php artisan test --filter="test_authentication"
php artisan test --filter="test_response_structure"
php artisan test --filter="test_balance_retrieval"
php artisan test --filter="test_security"

# Run all credit-related tests
php artisan test --filter=Credit
```

---

## Dependencies

### Required (Must exist before implementation)
- **TASK-26-1:** Migrations for `credits` table
- **TASK-26-2:** Credit model and repository
- **TASK-26-5:** CreditService with `getBalance()` method

### Route Definition

```php
// routes/api.php
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/credits/balance', [CreditController::class, 'balance']);
});
```

### Controller Interface

```php
class CreditController extends Controller
{
    public function __construct(
        private CreditService $creditService
    ) {}

    public function balance(Request $request): JsonResponse
    {
        $user = $request->user();
        $balance = $this->creditService->getBalance($user);
        $credit = $user->credit;

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $balance,
                'total_purchased' => $credit->total_purchased ?? 0,
                'total_spent' => $credit->total_spent ?? 0,
                'formatted_balance' => '$' . number_format($balance, 2),
                'has_sufficient_balance' => $balance > 0,
                'low_balance_warning' => $balance < 5.00,
            ],
        ]);
    }
}
```

---

## Notes for Developer

### Implementation Priority

1. **Route Registration:**
   - Register in `routes/api.php` under `auth:sanctum` middleware
   - Use `api/v1` prefix for versioning

2. **Controller Structure:**
   - Inject `CreditService` via constructor
   - Use `auth()->user()` or `$request->user()` for current user
   - Return JSON response with consistent structure

3. **Response Format:**
   - Always include `success` boolean at top level
   - Wrap data in `data` object
   - Use consistent key naming (snake_case for JSON)

4. **Error Handling:**
   - Wrap in try-catch for unexpected errors
   - Log errors with context (user_id)
   - Return generic error message to client (no stack traces)

### Balance Thresholds

| Threshold | Value | Purpose |
|-----------|-------|---------|
| Low Balance Warning | $5.00 | Prompt user to add credits |
| Minimum Balance | $0.01 | Minimum actionable balance |
| Maximum Balance | $99,999,999.99 | DECIMAL(10,2) limit |

### Response Performance

- Single database query per request
- Consider brief caching (60 seconds) for balance
- Cache must invalidate on any credit transaction

### Security Considerations

- Never log full balance values with user identifiers
- Validate user authentication on every request
- Rate limiting: 60 requests per minute (standard API limit)

---

## Comments

### 2026-03-02 - PreTester
**Test Suite Created**

- Test documentation file created: `TESTS/TASK-26-6.md`
- 50+ test scenarios across 11 categories
- Coverage includes authentication, response structure, balance retrieval, security, edge cases

**Key Test Categories:**
| Category | Tests | Critical |
|----------|-------|----------|
| Authentication | 5 | ✅ |
| Response Structure | 10 | ✅ |
| Balance Retrieval | 8 | ✅ |
| Sufficient Balance Flag | 4 | ✅ |
| Low Balance Warning | 5 | ✅ |
| Authorization | 4 | ✅ |
| Error Handling | 4 | ✅ |
| Edge Cases | 6 | ✅ |
| Security | 5 | ✅ |
| Performance | 3 | ✅ |
| Cache | 3 | Optional |

**Dependency Status:**
- TASK-26-1 (Migrations): `In Development` ⏳
- TASK-26-2 (Credit Model): `Tests Coverage In Progress` ✅
- TASK-26-5 (CreditService): `Tests Coverage In Progress` ✅

**Ready for Developer when:**
- All dependencies reach `To Test Locally` status

---
_Updated by PreTester on 2026-03-02_
