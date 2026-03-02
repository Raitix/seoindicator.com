# TESTS/TASK-27-2.md — Admin Authorization Middleware Tests

## Task Reference
- **Task:** TASK-27-2: Admin Authorization Middleware
- **Specification:** SPEC-027: Admin Dashboard (Ops + Usage)
- **Priority:** High
- **Risk:** High (Security-critical component)

---

## Overview

Feature tests for the AdminAuthorizationMiddleware that protects all admin routes. This middleware is a critical security component that enforces role-based access control for administrative functionality.

**Key Security Concerns:**
- Authentication verification (401 for unauthenticated)
- Authorization verification (403 for non-admin users)
- Audit logging of unauthorized access attempts
- Prevention of privilege escalation attacks

---

## Test Files

| File | Tests | Assertions | Status |
|------|-------|------------|--------|
| `backend/tests/Feature/Http/Middleware/AdminAuthorizationMiddlewareTest.php` | 55+ | 70+ | Ready |

---

## Test Scenarios

### AA-001: Admin User Access Granted

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AA-001-01 | Admin user access granted | Admin user, GET /admin/stats | 200 OK |
| AA-001-02 | Admin user can access protected route | Admin user, POST /admin/users/1/impersonate | Not 401/403 |
| AA-001-03 | Admin role from database | User with role='admin' | isAdmin()=true, 200 OK |

### AA-002: Super Admin Access Granted

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AA-002-01 | Super admin access granted | Super admin user | 200 OK |
| AA-002-02 | Super admin role from database | User with role='super_admin' | isAdmin()=true, isSuperAdmin()=true |
| AA-002-03 | Super admin has higher privileges | Super admin user | 200 OK on all admin routes |

### AA-003: Regular User Access Denied (403)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AA-003-01 | Regular user denied | User with role='user' | 403 Forbidden |
| AA-003-02 | JSON response format | Regular user | `{"success":false,"error":{"code":"FORBIDDEN"}}` |
| AA-003-03 | Error message contains 'admin' | Regular user | Message mentions admin access |
| AA-003-04 | Moderator role denied | User with role='moderator' | 403 Forbidden |
| AA-003-05 | Null role denied | User with role=null | 403 Forbidden |

### AA-004: Unauthenticated Request Denied (401)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AA-004-01 | Unauthenticated denied | No auth token | 401 Unauthorized |
| AA-004-02 | JSON response format | No auth token | `{"success":false,"error":{"code":"UNAUTHENTICATED"}}` |
| AA-004-03 | Error message | No auth token | Message structure correct |
| AA-004-04 | Invalid token treated as unauthenticated | Bearer invalid-token | 401 Unauthorized |
| AA-004-05 | Expired token treated as unauthenticated | Revoked token | 401 Unauthorized |

### AA-005: Access Attempt Logging

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AA-005-01 | Unauthorized access logged | Regular user | Log entry with user_id |
| AA-005-02 | Unauthenticated access logged | No auth | Log entry with ip |
| AA-005-03 | Log includes IP address | Regular user | IP in log context |
| AA-005-04 | Log includes user agent | Regular user, custom UA | user_agent in log context |
| AA-005-05 | Log includes requested URL | Regular user | URL in log context |

### AA-006: admin_activity_logs Table Integration

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AA-006-01 | Unauthorized attempt logged to DB | Regular user | Database entry created |
| AA-006-02 | Log includes target_type | Regular user | target_type='admin_route' |
| AA-006-03 | Log includes ip_address column | Regular user | ip_address not null |
| AA-006-04 | Log includes user_agent column | Regular user | user_agent contains UA |
| AA-006-05 | Admin access not logged as unauthorized | Admin user | No unauthorized_access_attempt entry |

### AA-007: Response Format

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AA-007-01 | 403 response structure | Regular user | `{success, error:{code, message}}` |
| AA-007-02 | 401 response structure | Unauthenticated | `{success, error:{code, message}}` |
| AA-007-03 | Error codes distinct | Compare 403 vs 401 | Different error codes |

### AA-008: HTTP Method Handling

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AA-008-01 | GET request blocked | Regular user, GET | 403 Forbidden |
| AA-008-02 | POST request blocked | Regular user, POST | 403 Forbidden |
| AA-008-03 | PUT request blocked | Regular user, PUT | 403 Forbidden |
| AA-008-04 | DELETE request blocked | Regular user, DELETE | 403 Forbidden |

### AA-009: Role Hierarchy

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AA-009-01 | Super admin above admin | Both roles | Both isAdmin(), only super isSuperAdmin() |
| AA-009-02 | Role case sensitive | role='Admin' | 403 Forbidden |
| AA-009-03 | Role trimmed | role=' admin ' | 403 Forbidden |

### SC-001: Security Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SC-001-01 | SQL injection in role | role="admin' OR '1'='1" | 403 Forbidden (not granted) |
| SC-001-02 | No sensitive data in response | Regular user | No passwords/tokens in response |
| SC-001-03 | XSS in error message | Regular user | No HTML tags in message |
| SC-001-04 | Rate limiting not bypassed | Multiple requests | All blocked with 403 |
| SC-001-05 | IDOR prevention | User accessing admin resource | 403 Forbidden |

### EC-001: Edge Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001-01 | Empty role denied | role='' | 403 Forbidden |
| EC-001-02 | Numeric role denied | role='1' | 403 Forbidden |
| EC-001-03 | Unicode role denied | role='αdmin' | 403 Forbidden |
| EC-001-04 | Very long role denied | role=1000 chars | 403 Forbidden |
| EC-001-05 | Deleted user denied | Deleted user token | 401 Unauthorized |
| EC-001-06 | Unverified email admin access | Unverified admin | 200 OK (verification separate) |

### EC-002: Concurrent Sessions

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-002-01 | Multiple tokens same admin | Two tokens | Both work |
| EC-002-02 | Token revoked mid-session | Revoke after first request | First 200, second 401 |

---

## Mock Data

### User Factory States

```php
// Admin user
User::factory()->admin()->create();

// Super admin user
User::factory()->superAdmin()->create();

// Regular user
User::factory()->create(['role' => 'user']);

// Unverified admin
User::factory()->unverified()->admin()->create();
```

### Admin Activity Log Structure

```php
[
    'admin_user_id' => $user->id,
    'action' => 'unauthorized_access_attempt',
    'target_type' => 'admin_route',
    'target_id' => null,
    'metadata' => json_encode(['url' => '/api/v1/admin/stats']),
    'ip_address' => '127.0.0.1',
    'user_agent' => 'TestAgent/1.0',
    'created_at' => now(),
]
```

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Admin Access Granted | 100% | 5 |
| Super Admin Access | 100% | 3 |
| Regular User Denied (403) | 100% | 5 |
| Unauthenticated Denied (401) | 100% | 5 |
| Access Logging | 100% | 5 |
| DB Logging Integration | 100% | 5 |
| Response Format | 100% | 3 |
| HTTP Methods | 100% | 4 |
| Role Hierarchy | 100% | 3 |
| Security | 100% | 5 |
| Edge Cases | 100% | 6 |
| Concurrent Sessions | 100% | 2 |
| **Total** | **100%** | **51+** |

---

## Test Execution

```bash
# Run all middleware tests
cd backend
php artisan test --filter=AdminAuthorizationMiddlewareTest

# Run with verbose output
php artisan test --filter=AdminAuthorizationMiddlewareTest -v

# Run specific test category
php artisan test --filter="aa_001"
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-27-1 (admin_activity_logs table) | Testing Locally | Tests skip DB logging if table missing |
| User model with isAdmin() | ✅ Exists | `app/Models/User.php` |
| UserFactory admin() state | ✅ Exists | `database/factories/UserFactory.php` |
| Laravel Sanctum | ✅ Installed | Token-based authentication |

---

## Notes for Developer

### Middleware Implementation Requirements

1. **Create Middleware:**
   - Path: `app/Http/Middleware/AdminAuthorizationMiddleware.php`
   - Handle method should check `$user->isAdmin()`

2. **Register in Kernel.php:**
   ```php
   protected $middlewareAliases = [
       'admin' => \App\Http\Middleware\AdminAuthorizationMiddleware::class,
   ];
   ```

3. **Apply to Routes:**
   ```php
   Route::middleware(['auth:sanctum', 'admin'])->group(function () {
       // Admin routes
   });
   ```

4. **Response Format:**
   - 401: `{"success":false,"error":{"code":"UNAUTHENTICATED","message":"..."}}`
   - 403: `{"success":false,"error":{"code":"FORBIDDEN","message":"..."}}`

5. **Logging Requirements:**
   - Log unauthorized attempts to `admin_activity_logs` table
   - Include: user_id, action, ip_address, user_agent, url

6. **Role Checks:**
   - `isAdmin()` returns true for 'admin' or 'super_admin'
   - Case-sensitive comparison
   - No trimming of role values

### Test Skips

- Tests in AA-006 skip if `admin_activity_logs` table doesn't exist
- This allows running tests before TASK-27-1 migration is complete

---

## Comments

### 2026-03-02 - PreTester
**Test Design Complete**

Created 55+ feature tests covering:
- Admin/super admin access granted
- Regular user denied (403)
- Unauthenticated denied (401)
- Access attempt logging (file + DB)
- Security tests (SQL injection, XSS, IDOR)
- Edge cases (unicode roles, concurrent sessions)

**Key Design Decisions:**
- Tests skip DB logging if admin_activity_logs table missing
- Uses existing UserFactory admin() and superAdmin() states
- Tests both Sanctum token and session auth
- Covers all HTTP methods (GET, POST, PUT, DELETE)

**Handoff:** Ready for Developer to implement middleware. Run tests with:
```bash
cd backend
php artisan test --filter=AdminAuthorizationMiddlewareTest
```

---
*Created by PreTester agent on 2026-03-02*
