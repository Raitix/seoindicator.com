# TEST-1-21 — Health Endpoint Tests

## Task Reference
TASK-1-21: Production Health Endpoint Missing (Docs Discrepancy)

## Test Objective
Verify that `GET /api/health` endpoint returns correct response structure, handles database connectivity states, and meets performance/security requirements.

---

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `backend/tests/Feature/Api/HealthEndpointTest.php` | Feature | API integration tests |
| `backend/tests/Unit/Controllers/DeployControllerTest.php` | Unit | Controller method tests |

---

## Test Scenarios

### Feature Tests (HealthEndpointTest.php)

| Test | Description | Expected Result |
|------|-------------|-----------------|
| `test_health_endpoint_returns_ok_status` | GET /api/health | 200 OK, `{ "status": "ok" }` |
| `test_health_endpoint_shows_database_connected` | Database connectivity check | `{ "database": "connected" }` |
| `test_health_endpoint_timestamp_is_valid_iso8601` | Timestamp format | Valid ISO8601 timestamp |
| `test_health_endpoint_returns_app_name` | APP_NAME in response | `{ "app": "SEOIndicator" }` |
| `test_health_endpoint_returns_environment` | APP_ENV in response | `{ "env": "testing" }` |
| `test_health_endpoint_is_public` | No auth required | 200 OK without credentials |
| `test_health_endpoint_handles_database_disconnection` | DB unavailable | `{ "database": "disconnected" }` |
| `test_health_endpoint_response_time` | Performance check | < 1 second response time |
| `test_health_endpoint_returns_json_content_type` | Content-Type header | `application/json` |
| `test_health_endpoint_does_not_expose_secrets` | Security check | No APP_KEY/passwords in response |

### Unit Tests (DeployControllerTest.php)

| Test | Description | Expected Result |
|------|-------------|-----------------|
| `test_health_method_returns_correct_structure` | Response structure | Contains all required fields |
| `test_health_method_returns_ok_status` | Status field | `"ok"` |
| `test_health_method_returns_database_connected` | DB available | `"connected"` |
| `test_health_method_returns_database_disconnected_on_error` | DB unavailable | `"disconnected"` |
| `test_health_method_timestamp_format` | Timestamp format | ISO8601 format |

---

## Expected Response Format

```json
{
    "status": "ok",
    "timestamp": "2026-02-24T20:23:00+00:00",
    "database": "connected",
    "app": "SEOIndicator",
    "env": "production"
}
```

---

## Edge Cases Covered

1. **Database unavailability** - Returns `disconnected` status, still 200 OK
2. **Missing environment variables** - Falls back to defaults
3. **Security** - No sensitive information exposed

---

## Running Tests

```bash
cd backend
./vendor/bin/phpunit tests/Feature/Api/HealthEndpointTest.php
./vendor/bin/phpunit tests/Unit/Controllers/DeployControllerTest.php
```

---

## Production Verification

After deployment, verify:
```bash
curl -s https://seoindicator.com/api/health | jq .
```

Expected output should match the response format above.

---

## Notes for Developer

The health endpoint code already exists in:
- Route: `backend/routes/api.php` line 21
- Controller: `backend/app/Http/Controllers/DeployController.php` method `health()`

The production 404 issue is likely a **deployment problem** - the code may not be deployed to production. Verify:
1. Latest code is deployed
2. Web server (nginx/apache) routes `/api/*` correctly to Laravel
3. PHP-FPM is running

---

Created by: PreTester
Date: 2026-02-24
