# TEST-27-6: Request Tracking Middleware Tests

## Task Reference
- **Task:** TASK-27-6: Request Tracking Middleware
- **Specification:** SPEC-027: Admin Dashboard (Ops + Usage)
- **Feature:** FEAT-027: Admin Dashboard

## Overview

Comprehensive test suite for the Request Tracking Middleware and Service. The middleware tracks API requests for usage analytics, recording endpoint path, HTTP method, timestamp, and user ID (if authenticated). Uses lightweight Redis storage with configurable sample rates for high-traffic endpoints.

## Test Files

| File | Tests | Assertions | Description |
|------|-------|------------|-------------|
| `backend/tests/Unit/Http/Middleware/RequestTrackingMiddlewareTest.php` | 97 | 150+ | Middleware unit tests |
| `backend/tests/Unit/Services/Admin/RequestTrackingServiceTest.php` | 58 | 80+ | Service unit tests |
| **Total** | **155** | **230+** | |

---

## Test Scenarios

### Middleware Tests

#### SECTION 1: Middleware Instantiation (MI)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MI-001 | Middleware can be instantiated | RequestTrackingService mock | Instance created |
| MI-002 | Default sample rate is 1.0 | No config | getSampleRate() = 1.0 |
| MI-003 | Custom sample rate accepted | sample_rate = 0.5 | getSampleRate() = 0.5 |
| MI-004 | Dynamic sample rate | setSampleRate(0.1) | getSampleRate() = 0.1 |
| MI-005 | Sample rate clamped | setSampleRate(1.5), setSampleRate(-0.5) | Clamped to 1.0 and 0.0 |

#### SECTION 2: Basic Tracking Behavior (BT)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BT-001 | Track GET request | GET /api/v1/users | record() called with endpoint, method |
| BT-002 | Track POST request | POST /api/v1/analyses | record() called with POST |
| BT-003 | Track PUT request | PUT /api/v1/users/1 | record() called with PUT |
| BT-004 | Track DELETE request | DELETE /api/v1/users/1 | record() called with DELETE |
| BT-005 | Track PATCH request | PATCH /api/v1/users/1 | record() called with PATCH |
| BT-006 | Request passes through unchanged | Any request | Response unchanged |
| BT-007 | Timestamp recorded accurately | Any request | Timestamp between before/after |

#### SECTION 3: Authenticated User Tracking (AU)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AU-001 | Authenticated user ID tracked | User with id=123 | record() called with userId=123 |
| AU-002 | Unauthenticated request | No user | record() called with userId=null |
| AU-003 | Different users tracked separately | User id=1, User id=999 | Correct IDs passed |
| AU-004 | Admin user tracked | is_admin=true | record() called with admin's ID |

#### SECTION 4: Endpoint Normalization (EN)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EN-001 | Numeric ID normalized | /api/v1/users/123 | api/v1/users/{id} |
| EN-002 | UUID normalized | /api/v1/analyses/550e8400-e29b-... | api/v1/analyses/{id} |
| EN-003 | Multiple IDs normalized | /api/v1/sites/5/analyses/42 | api/v1/sites/{id}/analyses/{id} |
| EN-004 | Query params not in endpoint | /api/v1/users?page=1 | api/v1/users |
| EN-005 | Root endpoint handled | /api/v1/ | api/v1/ |
| EN-006 | Deep nested endpoint | /api/v1/admin/users/123/settings | api/v1/admin/users/{id}/settings |

#### SECTION 5: Sample Rate Configuration (SR)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SR-001 | Sample rate 1.0 tracks all | 10 requests at 100% | 10 records |
| SR-002 | Sample rate 0.0 tracks none | 10 requests at 0% | 0 records |
| SR-003 | Sample rate 0.5 tracks ~50% | 1000 requests at 50% | ~500 (±100) records |
| SR-004 | Per-endpoint sample rate | health=0%, users=100% | health not tracked |
| SR-005 | Multiple endpoint rates | health=0%, high-traffic=10%, normal=100% | Different counts |
| SR-006 | Health endpoints excluded | /health, /healthz, /ping | Not tracked |

#### SECTION 6: Error Handling (EH)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EH-001 | Service exception caught | Service throws Exception | Response continues normally |
| EH-002 | Tracking errors logged | Service throws Exception | Log::warning called |
| EH-003 | Redis timeout handled | RedisException | Response continues with 200 |
| EH-004 | Queue push failure handled | RuntimeException | Response continues |
| EH-005 | No throw on service unavailable | Any exception | No exception thrown |

#### SECTION 7: Performance Tests (PT)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PT-001 | Overhead < 5ms | 100 requests | Average < 5ms per request |
| PT-002 | Non-blocking response | Any request | < 100ms total |
| PT-003 | Async tracking non-blocking | record() mocked | < 10ms |
| PT-004 | Memory stable | 1000 requests | < 1MB memory growth |

#### SECTION 8: Edge Cases (EC)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Empty endpoint | / | Handled gracefully |
| EC-002 | Very long endpoint | 200+ char path | Handled gracefully |
| EC-003 | Special characters | query=test%20special | Handled gracefully |
| EC-004 | Unicode in endpoint | q=テスト | Handled gracefully |
| EC-005 | OPTIONS request | OPTIONS /api/v1/test | Tracked with OPTIONS |
| EC-006 | HEAD request | HEAD /api/v1/test | Tracked with HEAD |
| EC-007 | No path request | http://example.com | Handled gracefully |
| EC-008 | Concurrent requests | 100 concurrent | All handled safely |

#### SECTION 9: Service Integration (SI)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SI-001 | Uses injected service | Any request | service->record() called |
| SI-002 | Service receives normalized endpoint | /api/v1/users/42 | api/v1/users/{id} |
| SI-003 | Service receives correct method | GET/POST/PUT/PATCH/DELETE | Correct method passed |
| SI-004 | Service receives null for unauthenticated | No user | userId=null |
| SI-005 | Service receives user ID for authenticated | User id=999 | userId=999 |

#### SECTION 10: Configuration Tests (CT)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CT-001 | Default sample rate from config | config sample_rate=0.75 | getSampleRate()=0.75 |
| CT-002 | Excluded endpoints from config | excluded_endpoints array | Not tracked |
| CT-003 | Endpoint sample rates from config | endpoint_sample_rates array | Correct rates |
| CT-004 | Middleware can be disabled | enabled=false | No tracking |
| CT-005 | Config defaults are sensible | No config | sample_rate=1.0, enabled=true |

#### SECTION 11: Security Tests (SC)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SC-001 | No sensitive data in logs | Bearer token in header | Not logged |
| SC-002 | Malicious paths handled safely | /api/v1/../config | Safe handling |
| SC-003 | Request body not stored | POST with password | Only 4 params recorded |
| SC-004 | Headers not stored | X-API-Key header | Only 4 params recorded |
| SC-005 | SQL injection safe | id=1'; DROP TABLE | Safe handling |

#### SECTION 12: Integration Tests (IT)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IT-001 | Full request flow | Authenticated user | All data captured |
| IT-002 | Independent instances | Two middleware | Independent configs |
| IT-003 | Works in pipeline | Laravel pipeline | Handles correctly |
| IT-004 | Downstream exception | Handler throws | Tracking still happens |

#### SECTION 13: Acceptance Criteria Tests (AC)
| ID | Scenario | Acceptance Criteria |
|----|----------|---------------------|
| AC-001 | Middleware exists at correct location | Class exists at app/Http/Middleware/ |
| AC-002 | Records endpoint, method, timestamp, user_id | All four fields recorded |
| AC-003 | Uses lightweight storage | Completes under 5ms |
| AC-004 | Latency overhead < 5ms | Average overhead < 5ms |
| AC-005 | Configurable sample rate | Sample rate affects tracking |
| AC-006 | Handles queue failures gracefully | No exception, response continues |
| AC-007 | Unit tests verify tracking | Tests exist and pass |
| AC-008 | Performance tests confirm minimal overhead | PT tests pass |

---

### Service Tests

#### SECTION 1: Service Instantiation (SI)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| SI-001 | Service instantiated | Instance created |
| SI-002 | Resolved from container | Container returns instance |
| SI-003 | Default configuration | key_prefix='request_tracking:', ttl=3600 |

#### SECTION 2: Record Method (RM)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RM-001 | Stores to Redis | Any data | Redis::rpush called |
| RM-002 | Correct key format | Any data | Key matches pattern |
| RM-003 | Endpoint stored | endpoint='api/v1/users' | JSON contains endpoint |
| RM-004 | Method stored | method='POST' | JSON contains method |
| RM-005 | user_id stored | user_id=42 | JSON contains user_id |
| RM-006 | Null user_id handled | user_id=null | JSON contains null |
| RM-007 | ISO timestamp | DateTime object | ISO format string |
| RM-008 | Returns true on success | Valid data | true |
| RM-009 | Handles Redis failure | RedisException | false |
| RM-010 | Logs error on failure | RedisException | Log::warning called |

#### SECTION 3: Increment Counter (IC)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IC-001 | Increments Redis counter | endpoint, method | Redis::incr called |
| IC-002 | Uses hourly key | Any | Key contains hour |
| IC-003 | Sets TTL on new key | New key | TTL=86400 |
| IC-004 | Groups by endpoint+method | Same endpoint, different method | Separate counters |
| IC-005 | Handles Redis failure | RedisException | false |

#### SECTION 4: Get Stats (GS)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GS-001 | Retrieves counts | Keys exist | Array of counts |
| GS-002 | Empty when no data | No keys | Empty array |
| GS-003 | Aggregates by endpoint | Multiple keys | Summed counts |
| GS-004 | Separates methods | GET and POST | Separate entries |
| GS-005 | Handles Redis failure | RedisException | Empty array |

#### SECTION 5: Get Requests by Period (RP)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RP-001 | Hourly breakdown | Date | Array by hour |
| RP-002 | Correct hour format | Hour 10 | '10:00' key |
| RP-003 | Aggregates all endpoints | Multiple keys | Summed by hour |
| RP-004 | Empty day | No keys | Empty array |

#### SECTION 6: Flush to Database (FD)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FD-001 | Moves data to DB | Queue items | DB insert called |
| FD-002 | Handles empty queue | Empty | true |
| FD-003 | Batches large datasets | 150 items | Multiple batches |
| FD-004 | Handles DB failure | DB error | false |
| FD-005 | Preserves data on failure | DB error | Queue not trimmed |

#### SECTION 7: Clear Methods (CM)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CM-001 | clearHour removes hour | date, hour | true |
| CM-002 | clearDay removes day | date | true |
| CM-003 | clear removes all | None | true |

#### SECTION 8: Edge Cases (EC)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Redis not available | RedisException | false returned |
| EC-002 | Corrupted data in queue | Invalid JSON | Continues processing |
| EC-003 | Very long endpoint | 1000 char path | Handled |
| EC-004 | Unicode in data | Japanese chars | Handled |
| EC-005 | Concurrent access | 100 concurrent calls | All processed |

#### SECTION 9: Performance Tests (PT)
| ID | Scenario | Threshold |
|----|----------|-----------|
| PT-001 | record() under 2ms | < 2ms |
| PT-002 | getEndpointStats() under 100ms | < 100ms for 100 keys |
| PT-003 | flushToDatabase() under 1s | < 1s for 1000 items |

#### SECTION 10: Acceptance Criteria (AC)
| ID | Acceptance Criteria |
|----|---------------------|
| AC-001 | Service uses lightweight storage (Redis) |
| AC-002 | Service records all required fields |
| AC-003 | Service handles failures gracefully |

---

## Mock Data Samples

### Request Tracking Data
```json
{
    "endpoint": "api/v1/users/{id}",
    "method": "GET",
    "user_id": 42,
    "timestamp": "2026-03-04T10:30:00+00:00"
}
```

### Endpoint Stats Response
```json
{
    "api/v1/users": {
        "GET": 1250,
        "POST": 340
    },
    "api/v1/analyses": {
        "GET": 890,
        "POST": 156
    }
}
```

---

## Coverage Targets

| Category | Target | Description |
|----------|--------|-------------|
| Middleware instantiation | 100% | All constructor paths |
| Tracking behavior | 100% | All HTTP methods, user states |
| Error handling | 100% | All exception types |
| Performance | 100% | All performance thresholds |
| Edge cases | 100% | All boundary conditions |
| Security | 100% | All sensitive data checks |

---

## Test Execution Commands

```bash
# Run all Request Tracking tests
php artisan test --filter=RequestTracking

# Run middleware tests only
php artisan test tests/Unit/Http/Middleware/RequestTrackingMiddlewareTest.php

# Run service tests only
php artisan test tests/Unit/Services/Admin/RequestTrackingServiceTest.php

# Run with coverage
php artisan test --filter=RequestTracking --coverage
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| PHPUnit | Available | Laravel built-in |
| Mockery | Available | Laravel built-in |
| Redis facade | Available | Laravel Redis |
| RequestTrackingService | To Develop | Service for middleware |

---

## Notes for Developer

### Implementation Requirements

1. **Middleware Class:**
   - Location: `app/Http/Middleware/RequestTrackingMiddleware.php`
   - Constructor: Accept RequestTrackingService, optional sample_rate
   - Methods: handle(), getSampleRate(), setSampleRate(), getEndpointSampleRate(), setEndpointSampleRate(), isEnabled()
   - Endpoint normalization: Replace numeric IDs and UUIDs with `{id}`

2. **Service Class:**
   - Location: `app/Services/Admin/RequestTrackingService.php`
   - Methods: record(), incrementEndpoint(), getEndpointStats(), getRequestsByPeriod(), flushToDatabase(), clearHour(), clearDay(), clear()
   - Storage: Redis for real-time tracking, optional DB for persistence

3. **Configuration:**
   ```php
   // config/request-tracking.php
   return [
       'enabled' => env('REQUEST_TRACKING_ENABLED', true),
       'sample_rate' => env('REQUEST_TRACKING_SAMPLE_RATE', 1.0),
       'excluded_endpoints' => [
           'api/v1/health',
           'api/v1/health/*',
           'health',
           'healthz',
           'ping',
       ],
       'endpoint_sample_rates' => [
           // Override sample rates for specific endpoints
       ],
   ];
   ```

4. **Database Table (optional for aggregation):**
   ```sql
   CREATE TABLE request_logs (
       id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
       endpoint VARCHAR(255) NOT NULL,
       method VARCHAR(10) NOT NULL,
       user_id BIGINT UNSIGNED NULL,
       timestamp DATETIME NOT NULL,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       
       INDEX idx_endpoint_method (endpoint, method),
       INDEX idx_timestamp (timestamp),
       INDEX idx_user_id (user_id)
   );
   ```

5. **Registration in Kernel:**
   ```php
   // app/Http/Kernel.php
   protected $middlewareGroups = [
       'api' => [
           // ...
           \App\Http\Middleware\RequestTrackingMiddleware::class,
       ],
   ];
   ```

### Key Patterns

- **Non-blocking:** Use async queue for write operations if possible
- **Atomic counters:** Use Redis INCR for request counting
- **Graceful degradation:** Never throw exceptions, always continue
- **Logging:** Log warnings on failures, not errors

---

## Comments

### 2026-03-04 - PreTester
- Created 155 tests (97 middleware + 58 service)
- Tests cover all acceptance criteria
- Performance tests verify < 5ms overhead
- Security tests verify no sensitive data exposure
- Edge cases cover concurrent access, unicode, long paths
- Ready for Developer implementation

---

*Created by PreTester from TASK-27-6*
