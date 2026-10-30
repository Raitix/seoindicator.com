# TEST-6-6: Request Logging Tests

## Overview
Comprehensive tests for API request logging functionality (TASK-6-6).

## Test Files

| File | Type | Tests |
|------|------|-------|
| `backend/tests/Unit/Models/ApiRequestLogTest.php` | Unit | 120+ |
| `backend/tests/Unit/Http/Middleware/RequestLoggingMiddlewareTest.php` | Unit | 100+ |

## Test Categories

### 1. Model Tests (ApiRequestLogTest.php)

#### Category 1: Model Instantiation & Fillables (MF-001 to MF-015)
| Test ID | Description |
|---------|-------------|
| MF-001 | Model can be instantiated |
| MF-002 | api_key_id is fillable |
| MF-003 | endpoint is fillable |
| MF-004 | method is fillable |
| MF-005 | status_code is fillable |
| MF-006 | response_time_ms is fillable |
| MF-007 | ip_address is fillable |
| MF-008 | user_agent is fillable |
| MF-009 | created_at is automatically set |
| MF-010 | All fields can be filled together |
| MF-011 | Minimal required fields |
| MF-012 | Model update with status_code |
| MF-013 | Null values are allowed for optional fields |
| MF-014 | Different HTTP methods |
| MF-015 | Different status codes |

#### Category 2: Casts (CA-001 to CA-010)
| Test ID | Description |
|---------|-------------|
| CA-001 | status_code is cast to integer |
| CA-002 | response_time_ms is cast to integer |
| CA-003 | api_key_id is cast to integer |
| CA-004 | ip_address is cast to string |
| CA-005 | user_agent is cast to string |
| CA-006 | endpoint is cast to string |
| CA-007 | method is cast to string |
| CA-008 | Timestamps are cast to datetime |
| CA-009 | Integer preservation after refresh |
| CA-010 | Zero values stored correctly |

#### Category 3: Relationship to API Key (RE-001 to RE-010)
| Test ID | Description |
|---------|-------------|
| RE-001 | apiKey relationship exists |
| RE-002 | apiKey returns BelongsTo relationship |
| RE-003 | apiKey relationship returns parent model |
| RE-004 | apiKey uses correct foreign key |
| RE-005 | Eager loading apiKey |
| RE-006 | Deleting apiKey cascades to logs |
| RE-007 | Multiple logs for same API key |
| RE-008 | Query with apiKey relationship |
| RE-009 | Log belongs to correct API key |
| RE-010 | Relationship query constraints |

#### Category 4: Scopes (SC-001 to SC-010)
| Test ID | Description |
|---------|-------------|
| SC-001 | scopeForApiKey exists |
| SC-002 | scopeForApiKey filters by api_key_id |
| SC-003 | scopeForEndpoint exists |
| SC-004 | scopeForEndpoint filters by endpoint |
| SC-005 | scopeForMethod exists |
| SC-006 | scopeForMethod filters by HTTP method |
| SC-007 | scopeSuccessful exists |
| SC-008 | scopeSuccessful filters by 2xx status codes |
| SC-009 | scopeFailed exists |
| SC-010 | scopeFailed filters by 4xx/5xx status codes |

#### Category 5: Date & Retention Queries (DR-001 to DR-010)
| Test ID | Description |
|---------|-------------|
| DR-001 | Query logs older than 30 days |
| DR-002 | Query logs within retention period |
| DR-003 | scopeWithinRetention exists |
| DR-004 | scopeWithinRetention filters correctly |
| DR-005 | scopeOlderThanRetention exists |
| DR-006 | scopeOlderThanRetention filters correctly |
| DR-007 | Date range query with from/to |
| DR-008 | Query logs by day |
| DR-009 | Delete old logs |
| DR-010 | Retention is configurable |

#### Category 6: Edge Cases & Security (ES-001 to ES-010)
| Test ID | Description |
|---------|-------------|
| ES-001 | Very long endpoint string |
| ES-002 | IPv6 address storage |
| ES-003 | Long user agent string |
| ES-004 | Unicode in user_agent |
| ES-005 | XSS in endpoint |
| ES-006 | SQL injection in ip_address |
| ES-007 | Null API key ID fails |
| ES-008 | Negative response_time_ms |
| ES-009 | Large response_time_ms |
| ES-010 | Concurrent log creation |

#### Category 7: Statistics & Analytics (SA-001 to SA-010)
| Test ID | Description |
|---------|-------------|
| SA-001 | Average response time by endpoint |
| SA-002 | Request count by endpoint |
| SA-003 | Error rate calculation |
| SA-004 | Slow requests identification |
| SA-005 | Requests by IP address |
| SA-006 | Method distribution |
| SA-007 | Status code distribution |
| SA-008 | Requests per API key |
| SA-009 | Hourly distribution |
| SA-010 | Performance percentile calculation |

#### Category 8: Acceptance Criteria (AC-001 to AC-006)
| Test ID | Description |
|---------|-------------|
| AC-001 | Table has all required columns |
| AC-002 | Foreign key constraint on api_key_id |
| AC-003 | Cascade delete when API key deleted |
| AC-004 | Index on api_key_id for fast queries |
| AC-005 | Index on created_at for retention queries |
| AC-006 | Model exists at correct location |

---

### 2. Middleware Tests (RequestLoggingMiddlewareTest.php)

#### Category 1: Middleware Instantiation (MI-001 to MI-010)
| Test ID | Description |
|---------|-------------|
| MI-001 | Middleware can be instantiated |
| MI-002 | Middleware has handle method |
| MI-003 | Middleware can be enabled |
| MI-004 | Middleware can be disabled |
| MI-005 | Middleware reads config for enabled state |
| MI-006 | Middleware has default retention days |
| MI-007 | Middleware can set enabled state dynamically |
| MI-008 | Middleware excludes health endpoints by default |
| MI-009 | Middleware reads excluded endpoints from config |
| MI-010 | Middleware has async logging enabled by default |

#### Category 2: Basic Logging Behavior (BL-001 to BL-015)
| Test ID | Description |
|---------|-------------|
| BL-001 | Middleware logs API request |
| BL-002 | Middleware captures response status code |
| BL-003 | Middleware captures response time |
| BL-004 | Middleware captures IP address |
| BL-005 | Middleware captures user agent |
| BL-006 | Middleware logs POST request |
| BL-007 | Middleware logs PUT request |
| BL-008 | Middleware logs DELETE request |
| BL-009 | Middleware passes request through unchanged |
| BL-010 | Middleware captures 4xx status codes |
| BL-011 | Middleware captures 5xx status codes |
| BL-012 | Middleware captures endpoint with query params |
| BL-013 | Middleware captures X-Forwarded-For header |
| BL-014 | Middleware handles missing api_key_id gracefully |
| BL-015 | Middleware logs all required fields together |

#### Category 3: Excluded Endpoints (EE-001 to EE-010)
| Test ID | Description |
|---------|-------------|
| EE-001 | Middleware excludes health endpoint |
| EE-002 | Middleware excludes health sub-endpoints |
| EE-003 | Middleware excludes configured endpoints |
| EE-004 | Middleware logs non-excluded endpoints |
| EE-005 | Excluded endpoints returns response normally |
| EE-006 | Can add custom excluded endpoint |
| EE-007 | Wildcard excluded endpoints |
| EE-008 | OPTIONS requests excluded by default |
| EE-009 | Non-API routes not logged |
| EE-010 | Empty excluded endpoints list logs all |

#### Category 4: Disabled Middleware (DM-001 to DM-010)
| Test ID | Description |
|---------|-------------|
| DM-001 | Disabled middleware does not log |
| DM-002 | Disabled middleware passes request through |
| DM-003 | Middleware can be disabled mid-request |
| DM-004 | Middleware can be re-enabled |
| DM-005 | Disabled state persists across requests |
| DM-006 | isEnabled returns correct state |
| DM-007 | Disabled middleware has no overhead |
| DM-008 | Config disabled takes precedence |
| DM-009 | Multiple middleware instances share config |
| DM-010 | Runtime disable overrides config enabled |

#### Category 5: Error Handling (EH-001 to EH-010)
| Test ID | Description |
|---------|-------------|
| EH-001 | Middleware continues if database fails |
| EH-002 | Middleware logs errors |
| EH-003 | Middleware handles exception in handler |
| EH-004 | Middleware logs failed requests |
| EH-005 | Middleware does not expose sensitive data |
| EH-006 | Middleware handles timeout gracefully |
| EH-007 | Middleware handles queue failure |
| EH-008 | Middleware does not throw on service unavailable |
| EH-009 | Middleware handles corrupt response |
| EH-010 | Middleware handles empty response |

#### Category 6: Performance Tests (PT-001 to PT-010)
| Test ID | Description |
|---------|-------------|
| PT-001 | Middleware overhead is minimal |
| PT-002 | Middleware does not block response |
| PT-003 | Async logging is non-blocking |
| PT-004 | Memory usage remains stable |
| PT-005 | Concurrent requests handled safely |
| PT-006 | Query count is optimal |
| PT-007 | Large volume logging |
| PT-008 | Response time accuracy |
| PT-009 | Batch insert performance |
| PT-010 | Middleware in pipeline |

#### Category 7: Acceptance Criteria (AC-001 to AC-006)
| Test ID | Description |
|---------|-------------|
| AC-001 | Middleware class exists at correct location |
| AC-002 | All API requests are logged |
| AC-003 | Response time is captured |
| AC-004 | Logging is non-blocking |
| AC-005 | Health endpoints excluded |
| AC-006 | Middleware can be toggled via config |

---

## Database Schema (To Be Created)

```sql
CREATE TABLE api_request_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    api_key_id BIGINT UNSIGNED NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    status_code INT UNSIGNED NULL,
    response_time_ms INT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE CASCADE,
    INDEX idx_api_key_id (api_key_id),
    INDEX idx_created_at (created_at),
    INDEX idx_endpoint (endpoint),
    INDEX idx_status_code (status_code)
);
```

## Model Structure (To Be Implemented)

```php
// app/Models/ApiRequestLog.php

class ApiRequestLog extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'api_key_id',
        'endpoint',
        'method',
        'status_code',
        'response_time_ms',
        'ip_address',
        'user_agent',
    ];
    
    protected $casts = [
        'api_key_id' => 'integer',
        'status_code' => 'integer',
        'response_time_ms' => 'integer',
        'created_at' => 'datetime',
    ];
    
    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }
    
    // Scopes
    public function scopeForApiKey($query, $apiKeyId) { ... }
    public function scopeForEndpoint($query, $endpoint) { ... }
    public function scopeForMethod($query, $method) { ... }
    public function scopeSuccessful($query) { ... }
    public function scopeFailed($query) { ... }
    public function scopeWithinRetention($query) { ... }
    public function scopeOlderThanRetention($query) { ... }
}
```

## Middleware Structure (To Be Implemented)

```php
// app/Http/Middleware/RequestLoggingMiddleware.php

class RequestLoggingMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if disabled or excluded endpoint
        if (!$this->shouldLog($request)) {
            return $next($request);
        }
        
        $startTime = microtime(true);
        
        try {
            $response = $next($request);
        } finally {
            $this->logRequest($request, $response, $startTime);
        }
        
        return $response;
    }
    
    protected function logRequest($request, $response, $startTime): void
    {
        // Create log entry asynchronously
        dispatch(function () use ($request, $response, $startTime) {
            ApiRequestLog::create([
                'api_key_id' => $request->attributes->get('api_key_id'),
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'status_code' => $response->getStatusCode(),
                'response_time_ms' => (int) ((microtime(true) - $startTime) * 1000),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });
    }
}
```

## Configuration (To Be Implemented)

```php
// config/api-logging.php

return [
    'enabled' => env('API_LOGGING_ENABLED', true),
    'retention_days' => env('API_LOGGING_RETENTION_DAYS', 30),
    'excluded_endpoints' => [
        'api/v1/health',
        'api/v1/health/*',
    ],
];
```

## Retention Cleanup Job (Optional for MVP)

```php
// app/Console/Commands/CleanupApiRequestLogs.php

class CleanupApiRequestLogs extends Command
{
    protected $signature = 'api-logs:cleanup';
    
    public function handle()
    {
        $deleted = ApiRequestLog::olderThanRetention()->delete();
        
        $this->info("Deleted {$deleted} old API request logs.");
    }
}
```

## Running Tests

```bash
# Run all model tests
php artisan test tests/Unit/Models/ApiRequestLogTest.php

# Run all middleware tests
php artisan test tests/Unit/Http/Middleware/RequestLoggingMiddlewareTest.php

# Run with coverage
php artisan test --coverage
```

## Acceptance Criteria Mapping

| Criteria | Test IDs |
|----------|----------|
| Table created | Model Test AC-001 to AC-006 |
| Middleware implemented | Middleware Test AC-001 to AC-006 |
| All API requests logged | Middleware Test AC-002 |
| Response time captured | Middleware Test AC-003, BL-003, PT-008 |
| Non-blocking logging | Middleware Test AC-004, PT-001 to PT-004 |

---

## Status
`Tests Coverage In Progress`

## Next Steps
1. Developer creates migration for `api_request_logs` table
2. Developer creates `ApiRequestLog` model
3. Developer creates `RequestLoggingMiddleware`
4. Developer registers middleware in Kernel
5. Developer creates cleanup command (optional for MVP)
6. LocalTester runs tests

---

Created by: PreTester
Date: 2026-03-07
