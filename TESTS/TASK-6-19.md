# TESTS/TASK-6-19: Unit Tests Documentation

## Overview
Comprehensive unit tests for API Access & Webhooks components.

## Status
`Tests Coverage In Progress`

## Task Reference
- Task: [TASK-6-19](../TASKS/TASK-6-19.md)
- Spec: [SPEC-006](../SPECIFICATIONS/SPEC-006.md)
- Feature: [FEAT-006](../FEATURES/FEAT-006.md)

---

## Test Files Created

### 1. Rate Limit Service Tests
**File:** `backend/tests/Unit/Services/RateLimitServiceTest.php`

#### Test Categories

##### Check Method Tests (CK)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| CK-001 | Check allows request under limit | `allowed: true, remaining: 3` |
| CK-002 | Check blocks request at limit | `allowed: false, captchaRequired: true` |
| CK-003 | Check returns correct remaining count | Remaining decreases with each request |
| CK-004 | Check returns reset timestamp | Valid future timestamp returned |
| CK-005 | Different IPs have independent counters | IP1 blocked, IP2 allowed |

##### Increment Method Tests (IN)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| IN-001 | Increment returns new count | 1 → 2 → 3 |
| IN-002 | Increment starts from zero | First call returns 1 |
| IN-003 | Increment stores count in cache | Persisted correctly |
| IN-004 | Different IPs don't interfere | Independent counters |

##### Verify CAPTCHA Tests (VC)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| VC-001 | Verify CAPTCHA resets counter | Counter reset to 0 |
| VC-002 | Verify CAPTCHA creates bypass | `hasCaptchaBypass: true` |
| VC-003 | No bypass without verification | `hasCaptchaBypass: false` |
| VC-004 | CAPTCHA allows new requests | Full limit restored |

##### Reset Method Tests (RS)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| RS-001 | Reset clears counter | Counter = 0 |
| RS-002 | Reset allows requests again | Full limit restored |
| RS-003 | Reset non-existent IP | No error, count = 0 |

##### Headers Tests (GH)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| GH-001 | Get headers returns correct format | All 3 headers present |
| GH-002 | Headers show correct limit | `X-RateLimit-Limit: 3` |
| GH-003 | Headers show correct remaining | Decrements with requests |
| GH-004 | Headers show zero at limit | `X-RateLimit-Remaining: 0` |
| GH-005 | Headers include reset timestamp | Future timestamp |

##### Client IP Tests (IP)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| IP-001 | Get IP from direct request | REMOTE_ADDR returned |
| IP-002 | Get IP from X-Forwarded-For | First IP in chain |
| IP-003 | Get IP from X-Real-IP | X-Real-IP value |
| IP-004 | Get IP from Cloudflare header | CF-Connecting-IP takes precedence |
| IP-005 | Normalize IPv4-mapped IPv6 | `::ffff:x.x.x.x` → `x.x.x.x` |
| IP-006 | Normalize IPv6 localhost | `::1` → `127.0.0.1` |
| IP-007 | Multiple X-Forwarded-For entries | First (client) IP extracted |

##### Getters Tests (GT)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| GT-001 | Get max requests | Returns 3 |
| GT-002 | Get window seconds | Returns 86400 (24h) |
| GT-003 | Get current count (new IP) | Returns 0 |
| GT-004 | Get current count (existing) | Returns actual count |

##### Edge Case Tests (EC)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| EC-001 | Empty IP string | No error, handles gracefully |
| EC-002 | IPv6 address | Normal rate limiting |
| EC-003 | Localhost IP | Normal rate limiting |
| EC-004 | Special characters in IP | Normalized/handled |
| EC-005 | Concurrent increments | Correct sequential counts |
| EC-006 | Very long IP string | No error, handled |

##### Integration Tests (IT)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| IT-001 | Full rate limit flow | Complete lifecycle works |
| IT-002 | Headers updated throughout flow | Correct at each step |
| IT-003 | Multiple IPs tracked independently | Each IP isolated |

---

### 2. Existing Test Files (Already Comprehensive)

#### Webhook Dispatcher Service Tests
**File:** `backend/tests/Unit/Services/WebhookDispatcherServiceTest.php`

Coverage includes:
- Successful webhook dispatch
- HMAC signature generation
- Timestamp headers
- Disabled webhook handling
- HTTP error responses
- Connection timeouts
- Response body logging
- Event subscription filtering
- Secret security
- Payload size limits
- Unicode handling
- DNS resolution failures

#### Webhook Model Tests
**File:** `backend/tests/Unit/Models/WebhookTest.php`

Coverage includes:
- User relationship
- Deliveries relationship
- Secret generation
- Events casting
- Enabled casting
- Secret hiding
- isEnabled method
- subscribesTo method
- Valid events constant
- Cascade delete

#### Webhook Delivery Model Tests
**File:** `backend/tests/Unit/Models/WebhookDeliveryTest.php`

Coverage includes:
- Webhook relationship
- wasSuccessful method
- markDelivered method

#### Webhook Controller Tests
**File:** `backend/tests/Feature/Api/V1/WebhookControllerTest.php`

Coverage includes:
- List webhooks
- Create webhook
- Show webhook
- Update webhook
- Delete webhook
- Test webhook endpoint
- Authorization (unauthenticated)
- Authorization (cross-user)
- Edge cases
- Security tests
- Integration tests

---

## Test Structure

```
backend/tests/
├── Unit/
│   ├── Services/
│   │   ├── RateLimitServiceTest.php      ← NEW
│   │   └── WebhookDispatcherServiceTest.php
│   └── Models/
│       ├── WebhookTest.php
│       └── WebhookDeliveryTest.php
├── Feature/
│   └── Api/V1/
│       └── WebhookControllerTest.php
└── Integration/
    └── WebhookDispatcherIntegrationTest.php
```

---

## Running Tests

```bash
# Run all tests
cd backend && php artisan test

# Run specific test file
php artisan test tests/Unit/Services/RateLimitServiceTest.php

# Run with coverage
php artisan test --coverage

# Run specific test method
php artisan test --filter test_check_allows_request_under_limit
```

---

## Coverage Summary

| Component | Test File | Test Count | Status |
|-----------|-----------|------------|--------|
| RateLimitService | RateLimitServiceTest.php | 40+ | ✅ Created |
| WebhookDispatcherService | WebhookDispatcherServiceTest.php | 30+ | ✅ Existing |
| Webhook Model | WebhookTest.php | 12 | ✅ Existing |
| WebhookDelivery Model | WebhookDeliveryTest.php | 3+ | ✅ Existing |
| Webhook Controller | WebhookControllerTest.php | 70+ | ✅ Existing |

---

## Notes for Developer

### RateLimitService Key Behaviors
1. **Limit:** 3 requests per IP per 24 hours
2. **CAPTCHA required:** After 3 requests exhausted
3. **Bypass duration:** 1 hour after CAPTCHA verification
4. **Headers format:**
   - `X-RateLimit-Limit: 3`
   - `X-RateLimit-Remaining: 0-3`
   - `X-RateLimit-Reset: <unix_timestamp>`

### IP Normalization Rules
1. Cloudflare `CF-Connecting-IP` takes highest priority
2. `X-Forwarded-For` → first IP in chain
3. `X-Real-IP` → direct value
4. IPv4-mapped IPv6 (`::ffff:x.x.x.x`) → normalized to IPv4
5. IPv6 localhost (`::1`) → normalized to `127.0.0.1`

---

## Acceptance Criteria Progress

- [x] Rate Limiter service tests written
- [x] Webhook Dispatcher tests exist (comprehensive)
- [x] Webhook endpoint tests exist (comprehensive)
- [ ] API Key Service tests (awaiting implementation)
- [ ] API Key Endpoints tests (awaiting implementation)
- [ ] 80%+ coverage achieved (pending test run)

---

## Last Updated
2026-02-27
