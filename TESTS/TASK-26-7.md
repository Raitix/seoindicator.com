# TASK-26-7: Transaction History API Endpoint - Test Documentation

## Task Reference
- **Task:** TASK-26-7
- **Specification:** SPEC-026: Billing, Credits & Model Selection
- **Feature:** FEAT-026: Billing, Credits & Model Selection

---

## Overview

Test suite for the Transaction History API endpoint (`GET /api/v1/credits/transactions`). Tests cover authentication, pagination, filtering, validation, security, and edge cases.

---

## Test Files

| File | Location | Purpose |
|------|----------|---------|
| `TransactionHistoryControllerTest.php` | `backend/tests/Feature/Api/V1/Credits/` | Feature tests for API endpoint |
| `TransactionHistoryRequestTest.php` | `backend/tests/Unit/Http/Requests/` | Unit tests for request validation |

---

## API Specification

### Endpoint
```
GET /api/v1/credits/transactions
```

### Authentication
- **Required:** Bearer token (Sanctum)
- **Header:** `Authorization: Bearer {token}`

### Query Parameters

| Parameter | Type | Default | Max | Description |
|-----------|------|---------|-----|-------------|
| `page` | integer | 1 | - | Page number |
| `per_page` | integer | 15 | 50 | Items per page |
| `type` | string | null | - | Filter by type: purchase, usage, refund, bonus |
| `from` | date | null | - | Filter from date (YYYY-MM-DD) |
| `to` | date | null | - | Filter to date (YYYY-MM-DD) |

### Response Format

**Success (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "amount": 10.00,
      "balance_after": 35.50,
      "type": "purchase",
      "description": "Purchased $10.00 credits via Stripe",
      "payment_provider": "stripe",
      "created_at": "2026-03-02T12:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 45,
    "last_page": 3
  }
}
```

**Error (401):**
```json
{
  "message": "Unauthenticated."
}
```

**Error (422):**
```json
{
  "message": "The selected type is invalid.",
  "errors": {
    "type": ["The selected type is invalid."]
  }
}
```

---

## Test Scenarios

### TH-AUTH: Authentication Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TH-AUTH-01 | Authenticated user can access | Valid Bearer token | 200 OK with data |
| TH-AUTH-02 | Unauthenticated user blocked | No token | 401 Unauthorized |
| TH-AUTH-03 | Invalid token rejected | Invalid token | 401 Unauthorized |
| TH-AUTH-04 | Expired token rejected | Expired token | 401 Unauthorized |

### TH-PAGINATION: Pagination Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TH-PAG-01 | Default pagination | No params | page=1, per_page=15 |
| TH-PAG-02 | Custom page | page=2 | Returns page 2 data |
| TH-PAG-03 | Custom per_page | per_page=10 | Returns 10 items |
| TH-PAG-04 | Max per_page | per_page=50 | Returns up to 50 items |
| TH-PAG-05 | per_page exceeds max | per_page=100 | Returns validation error OR caps at 50 |
| TH-PAG-06 | per_page below minimum | per_page=0 | Returns validation error |
| TH-PAG-07 | Meta contains pagination info | Any valid request | current_page, per_page, total, last_page |
| TH-PAG-08 | Empty transaction list | User with no transactions | data: [], meta.total: 0 |

### TH-TYPE: Type Filtering Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TH-TYPE-01 | Filter by purchase | type=purchase | Only purchase transactions |
| TH-TYPE-02 | Filter by usage | type=usage | Only usage transactions |
| TH-TYPE-03 | Filter by refund | type=refund | Only refund transactions |
| TH-TYPE-04 | Filter by bonus | type=bonus | Only bonus transactions |
| TH-TYPE-05 | Invalid type | type=invalid | 422 validation error |
| TH-TYPE-06 | Case sensitivity | type=PURCHASE | 422 validation error (case-sensitive) |

### TH-DATE: Date Range Filtering Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TH-DATE-01 | Filter from date | from=2026-03-01 | Transactions from 2026-03-01 onwards |
| TH-DATE-02 | Filter to date | to=2026-03-05 | Transactions up to 2026-03-05 |
| TH-DATE-03 | Date range | from=2026-03-01&to=2026-03-05 | Transactions within range |
| TH-DATE-04 | Invalid from format | from=03-01-2026 | 422 validation error |
| TH-DATE-05 | Invalid to format | to=March-5-2026 | 422 validation error |
| TH-DATE-06 | from after to | from=2026-03-10&to=2026-03-01 | 422 validation error |
| TH-DATE-07 | from equals to | from=2026-03-05&to=2026-03-05 | Transactions on that day |
| TH-DATE-08 | Future date range | from=2027-01-01 | Empty data array |
| TH-DATE-09 | ISO date format | from=2026-03-01T00:00:00Z | Accepts or strips time portion |

### TH-COMBINED: Combined Filter Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TH-COMB-01 | Type + date range | type=purchase&from=2026-03-01&to=2026-03-05 | Purchase transactions in range |
| TH-COMB-02 | All filters + pagination | type=usage&from=2026-03-01&page=2&per_page=10 | Filtered page 2 |
| TH-COMB-03 | Multiple filters no results | type=refund&from=2027-01-01 | Empty data array |

### TH-SECURITY: Security Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TH-SEC-01 | User A cannot see User B's transactions | User A token, User B's data | Only User A's data returned |
| TH-SEC-02 | No sensitive payment data exposed | Any request | No card numbers, CVV, etc. |
| TH-SEC-03 | SQL injection attempt | type=' OR '1'='1 | 422 validation error |
| TH-SEC-04 | XSS in query params | from=<script>alert(1)</script> | 422 validation error |
| TH-SEC-05 | No token in response | Any request | No API tokens exposed |

### TH-RESPONSE: Response Format Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TH-RESP-01 | Success response structure | Valid request | success: true, data: [], meta: {} |
| TH-RESP-02 | Transaction item structure | Valid request with data | id, amount, balance_after, type, description, payment_provider, created_at |
| TH-RESP-03 | Amount is numeric | Valid request | amount returned as number (not string) |
| TH-RESP-04 | created_at is ISO 8601 | Valid request | Format: YYYY-MM-DDTHH:mm:ssZ |
| TH-RESP-05 | balance_after precision | Valid request | 2 decimal places |
| TH-RESP-06 | payment_provider null handling | Non-payment transaction | payment_provider: null |

### TH-HTTP: HTTP Method Tests

| ID | Scenario | Method | Expected Output |
|----|----------|--------|-----------------|
| TH-HTTP-01 | GET allowed | GET | 200 OK |
| TH-HTTP-02 | POST not allowed | POST | 405 Method Not Allowed |
| TH-HTTP-03 | PUT not allowed | PUT | 405 Method Not Allowed |
| TH-HTTP-04 | DELETE not allowed | DELETE | 405 Method Not Allowed |
| TH-HTTP-05 | PATCH not allowed | PATCH | 405 Method Not Allowed |

### TH-EDGE: Edge Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TH-EDGE-01 | Very large page number | page=999999 | Empty data array |
| TH-EDGE-02 | Negative page | page=-1 | 422 validation error |
| TH-EDGE-03 | Non-numeric page | page=abc | 422 validation error |
| TH-EDGE-04 | Negative per_page | per_page=-5 | 422 validation error |
| TH-EDGE-05 | Non-numeric per_page | per_page=ten | 422 validation error |
| TH-EDGE-06 | Special characters in type | type=type<script> | 422 validation error |
| TH-EDGE-07 | Unicode in params | type=%E2%98%A0 | 422 validation error |
| TH-EDGE-08 | Empty string params | type=&from=&to= | Returns all (empty treated as null) |
| TH-EDGE-09 | Whitespace in params | type=%20purchase%20 | 422 validation error OR trimmed |

### TH-PERF: Performance Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TH-PERF-01 | Response time acceptable | Default request | < 500ms |
| TH-PERF-02 | Large dataset pagination | 1000+ transactions | Efficient pagination |

---

## Mock Data

### User with Transactions

```php
// User with mixed transaction types
$user = User::factory()->create();

// Purchase transactions
CreditTransaction::create([
    'user_id' => $user->id,
    'amount' => 10.00,
    'balance_after' => 10.00,
    'type' => 'purchase',
    'description' => 'Purchased $10.00 credits via Stripe',
    'payment_provider' => 'stripe',
    'payment_id' => 'pi_test_123',
    'created_at' => '2026-03-01 10:00:00',
]);

// Usage transaction
CreditTransaction::create([
    'user_id' => $user->id,
    'amount' => -2.50,
    'balance_after' => 7.50,
    'type' => 'usage',
    'description' => 'Analysis job #123',
    'reference_type' => 'AnalysisJob',
    'reference_id' => 123,
    'created_at' => '2026-03-02 14:30:00',
]);

// Bonus transaction
CreditTransaction::create([
    'user_id' => $user->id,
    'amount' => 5.00,
    'balance_after' => 12.50,
    'type' => 'bonus',
    'description' => 'Welcome bonus',
    'created_at' => '2026-03-03 09:00:00',
]);

// Refund transaction
CreditTransaction::create([
    'user_id' => $user->id,
    'amount' => 1.50,
    'balance_after' => 14.00,
    'type' => 'refund',
    'description' => 'Refund for failed analysis',
    'metadata' => ['original_transaction_id' => 2],
    'created_at' => '2026-03-04 16:00:00',
]);
```

### User with No Transactions

```php
$user = User::factory()->create();
// No CreditTransaction records for this user
```

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Authentication | 100% | 4 |
| Pagination | 100% | 8 |
| Type Filtering | 100% | 6 |
| Date Filtering | 100% | 9 |
| Combined Filters | 100% | 3 |
| Security | 100% | 5 |
| Response Format | 100% | 6 |
| HTTP Methods | 100% | 5 |
| Edge Cases | 100% | 9 |
| Performance | 100% | 2 |
| **Total** | **100%** | **57** |

---

## Test Execution

```bash
# Run all tests for this task
cd backend
php artisan test --filter=TransactionHistory

# Run specific test file
php artisan test tests/Feature/Api/V1/Credits/TransactionHistoryControllerTest.php

# Run with coverage
php artisan test --coverage --filter=TransactionHistory
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| CreditService | Done | getTransactionHistory() method available |
| CreditTransaction model | Done | Types, scopes defined |
| Sanctum auth | Done | Authentication middleware |
| Migrations | Done | credit_transactions table exists |

---

## Notes for Developer

1. **Controller Implementation:**
   - Create `CreditController` if not exists
   - Add `transactions()` method
   - Inject `CreditService` via constructor
   - Apply `auth:sanctum` middleware

2. **Filtering Logic:**
   - The existing `CreditService::getTransactionHistory()` returns paginated results
   - Add filtering support in controller or create a new service method
   - Use Eloquent scopes: `byType()`, `recent()`
   - Date filtering: `whereDate('created_at', '>=', $from)`

3. **Validation:**
   - Create `TransactionHistoryRequest` form request
   - Validate `type` against enum values
   - Validate date format (YYYY-MM-DD)
   - Validate `from <= to`

4. **Response Transformation:**
   - Consider using API resources for consistent formatting
   - Hide sensitive metadata from response

5. **Database Index:**
   - Ensure index on `created_at` for date filtering performance
   - Consider composite index on `(user_id, created_at)`

---

## Comments

### 2026-03-05 - PreTester
**Test Coverage Design Complete:**
- 57 test scenarios covering all acceptance criteria
- Test files to be created in `backend/tests/Feature/Api/V1/Credits/`
- Mock data patterns provided for test setup
- Validation rules documented for form request

**Implementation Notes:**
- CreditService already has `getTransactionHistory()` - needs filtering extension
- CreditTransaction model has `byType()` scope - can be used for type filtering
- No CreditController exists yet - will need to be created by Developer

---
_Leave notes for the next agent here_
