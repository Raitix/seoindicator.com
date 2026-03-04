# TEST-27-6: Request Tracking Middleware Tests

## Task Reference
- **Task:** TASK-27-6
- **Specification:** SPEC-027 (Admin Dashboard)
- **Feature:** FEAT-027

## Overview
Comprehensive test suite for the Request Tracking Middleware that tracks API requests for usage analytics. Tests cover middleware behavior, service layer, error handling, performance, and security.

## Test Files

| File | Type | Tests | Location |
|------|------|-------|----------|
| RequestTrackingMiddlewareTest.php | Unit | 70+ | `backend/tests/Unit/Http/Middleware/` |
| RequestTrackingMiddlewareIntegrationTest.php | Feature | 25+ | `backend/tests/Feature/Http/Middleware/` |
| RequestTrackingServiceTest.php | Unit | 45+ | `backend/tests/Unit/Services/Analytics/` |

## Test Categories

### Middleware Unit Tests

| Category | ID Range | Tests | Description |
|----------|----------|-------|-------------|
| Instantiation | RTM-INST-* | 2 | Service instantiation, container binding |
| Request Tracking | RTM-TRACK-* | 7 | Endpoint, method, user_id, timestamp tracking |
| Pass-Through | RTM-PASS-* | 3 | Request forwarding, response preservation |
| Exclusion Rules | RTM-EXCLUDE-* | 6 | Health checks, static assets, OPTIONS requests |
| Sample Rate | RTM-SAMPLE-* | 5 | Configurable sampling for high-traffic endpoints |
| Error Handling | RTM-ERROR-* | 5 | Exception handling, graceful degradation |
| Performance | RTM-PERF-* | 3 | < 5ms overhead, memory efficiency |
| Edge Cases | RTM-EDGE-* | 6 | Long URLs, Unicode, concurrent requests |
| Security | RTM-SEC-* | 5 | No sensitive data exposure, injection prevention |
| Response | RTM-RESP-* | 4 | Response preservation, status code handling |

### Feature Integration Tests

| Category | ID Range | Tests | Description |
|----------|----------|-------|-------------|
| Route Integration | RTM-ROUTE-* | 5 | GET/POST tracking, authenticated requests |
| Exclusion Integration | RTM-EXCL-INT-* | 2 | Health endpoint exclusion |
| Error Integration | RTM-ERR-INT-* | 2 | Request continuation after failures |
| Sample Rate Integration | RTM-SAMPLE-INT-* | 2 | Disabled/full tracking modes |
| Response Preservation | RTM-RESP-INT-* | 3 | Response content, status, headers |
| Multiple Requests | RTM-MULTI-* | 2 | Independent tracking per request |
| Performance Integration | RTM-PERF-INT-* | 1 | Response time validation |
| Security Integration | RTM-SEC-INT-* | 2 | Sensitive data protection |

### Service Unit Tests

| Category | ID Range | Tests | Description |
|----------|----------|-------|-------------|
| Instantiation | RTS-INST-* | 2 | Service instantiation |
| Track Method | RTS-TRACK-* | 6 | Data storage, Redis integration |
| Key Generation | RTS-KEY-* | 3 | Consistent key format, normalization |
| Queue Integration | RTS-QUEUE-* | 3 | Async processing, failure handling |
| Error Handling | RTS-ERROR-* | 3 | Redis failures, invalid data |
| Aggregation | RTS-AGG-* | 5 | Count retrieval, top endpoints |
| TTL Configuration | RTS-TTL-* | 2 | Expiry time settings |
| Performance | RTS-PERF-* | 2 | < 5ms tracking, bulk efficiency |
| Security | RTS-SEC-* | 4 | Key sanitization, injection prevention |

## Acceptance Criteria Coverage

| Criteria | Tests | Status |
|----------|-------|--------|
| Middleware class created | RTM-INST-*, RTS-INST-* | ✅ |
| Records endpoint path | RTM-TRACK-01, RTM-TRACK-07 | ✅ |
| Records HTTP method | RTM-TRACK-02, RTM-TRACK-03 | ✅ |
| Records timestamp | RTM-TRACK-06, RTS-TRACK-04 | ✅ |
| Records user_id if authenticated | RTM-TRACK-04, RTM-TRACK-05 | ✅ |
| Uses lightweight storage (Redis) | RTS-TRACK-*, RTS-KEY-* | ✅ |
| < 5ms overhead | RTM-PERF-*, RTS-PERF-* | ✅ |
| Configurable sample rate | RTM-SAMPLE-*, RTS-QUEUE-* | ✅ |
| Handles queue failures gracefully | RTM-ERROR-*, RTS-ERROR-* | ✅ |
| Unit tests verify tracking | All RTM-*, RTS-* | ✅ |
| Performance tests confirm overhead | RTM-PERF-*, RTS-PERF-* | ✅ |

## Mock Data

### Standard Tracking Data
```php
[
    'endpoint' => '/api/v1/analysis',
    'method' => 'GET',
    'user_id' => null, // or user ID integer
    'timestamp' => now(),
]
```

### User Tracking Data
```php
[
    'endpoint' => '/api/v1/users/123',
    'method' => 'GET',
    'user_id' => 123,
    'timestamp' => '2026-03-04T11:00:00Z',
]
```

### Excluded Paths
```php
[
    'api/health',
    'api/health/*',
    'telescope/*',
    'horizon/*',
    'css/*',
    'js/*',
    'images/*',
]
```

### Sample Rate Configuration
```php
[
    'sample_rate' => 1.0,           // 100% tracking
    'sample_rate' => 0.5,           // 50% tracking
    'sample_rate' => 0.1,           // 10% tracking
    'endpoint_sample_rates' => [
        'api/v1/high-traffic' => 0.1, // Override for specific endpoint
    ],
]
```

## Test Execution Commands

```bash
# Run all middleware tests
php artisan test tests/Unit/Http/Middleware/RequestTrackingMiddlewareTest.php
php artisan test tests/Feature/Http/Middleware/RequestTrackingMiddlewareIntegrationTest.php

# Run all service tests
php artisan test tests/Unit/Services/Analytics/RequestTrackingServiceTest.php

# Run all tests for this task
php artisan test --filter "RequestTracking"

# Run with coverage
php artisan test tests/Unit/Http/Middleware/RequestTrackingMiddlewareTest.php --coverage
```

## Expected Implementation

### Middleware Class
```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\Analytics\RequestTrackingService;

class RequestTrackingMiddleware
{
    public function __construct(
        private RequestTrackingService $trackingService
    ) {}

    public function handle(Request $request, Closure $next)
    {
        // Check exclusion rules
        if ($this->shouldExclude($request)) {
            return $next($request);
        }

        // Apply sample rate
        if (!$this->shouldSample($request)) {
            return $next($request);
        }

        // Track request (async if configured)
        try {
            $this->trackingService->track([
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'user_id' => $request->user()?->id,
                'timestamp' => now(),
            ]);
        } catch (\Throwable $e) {
            // Log error but don't affect request
            \Log::error('Request tracking failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return $next($request);
    }
}
```

### Service Class
```php
namespace App\Services\Analytics;

class RequestTrackingService
{
    public function track(array $data): void
    {
        $key = $this->generateKey($data);

        // Atomic increment in Redis
        Cache::increment($key);

        // Set TTL on new key
        if (Cache::get($key) === 1) {
            Cache::put($key, 1, config('request_tracking.ttl_seconds', 86400));
        }
    }

    public function getEndpointCount(string $endpoint, string $method): int
    {
        $key = $this->generateKey([
            'endpoint' => $endpoint,
            'method' => $method,
        ]);

        return Cache::get($key, 0);
    }
}
```

## Configuration

```php
// config/request_tracking.php
return [
    'enabled' => env('REQUEST_TRACKING_ENABLED', true),
    'sample_rate' => env('REQUEST_TRACKING_SAMPLE_RATE', 1.0),
    'use_queue' => env('REQUEST_TRACKING_USE_QUEUE', false),
    'ttl_seconds' => env('REQUEST_TRACKING_TTL', 86400),

    'excluded_paths' => [
        'api/health',
        'api/health/*',
        'telescope/*',
        'horizon/*',
    ],

    'exclude_options' => true,

    'endpoint_sample_rates' => [
        // Override for high-traffic endpoints
    ],
];
```

## Dependencies
- None (standalone infrastructure task)

## Key Patterns

### Redis Key Format
```
request_tracking:{endpoint}:{method}:{hour}
request_tracking:user:{user_id}:{hour}
```

### Atomic Operations
- Use `Cache::increment()` for atomic counters
- Set TTL on first increment
- Use Redis INCR for lightweight storage

### Graceful Degradation
- Never throw exceptions to caller
- Log errors for debugging
- Continue request processing on failure

### Performance Optimization
- Excluded paths have minimal overhead
- Sample rate reduces tracking load
- Async queue option for high-traffic scenarios

## Notes for Developer

1. **Middleware Registration:**
   ```php
   // bootstrap/app.php or Kernel.php
   ->middleware([
       RequestTrackingMiddleware::class,
   ])
   ```

2. **Redis Connection:**
   - Use Laravel's Cache facade with Redis driver
   - Ensure Redis connection is configured in `config/database.php`

3. **Queue Option:**
   - For high-traffic production, enable `use_queue` config
   - Creates jobs for async processing
   - Reduces latency impact

4. **Testing:**
   - All tests use mocks for Redis/Cache
   - Performance tests verify < 5ms overhead
   - Integration tests verify full request lifecycle

5. **Security Considerations:**
   - Never log request bodies or headers
   - Sanitize endpoints for key generation
   - Only store user_id, not other PII

---

**Created by:** PreTester
**Date:** 2026-03-04
**Status:** Tests Coverage In Progress
