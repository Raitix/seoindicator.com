# TEST-27-8: Admin Usage API Endpoints Tests

## Overview
Feature tests for Admin Usage API Endpoints - provides usage metrics for Admin Dashboard.

## Test File
`backend/tests/Feature/Api/V1/Admin/UsageControllerTest.php`

## Test Results
- **Total Tests:** 71
- **Assertions:** 64+
- **Status:** ⏳ Tests Ready (expect 404 until implementation)

> **TDD Note:** Tests are designed to fail with 404 until Developer creates routes and controller. This is expected TDD behavior.

## Endpoints to Implement

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/v1/admin/usage/overview` | Aggregated usage stats |
| GET | `/api/v1/admin/usage/requests` | Request counts by endpoint |
| GET | `/api/v1/admin/usage/tokens` | Token usage by model |
| GET | `/api/v1/admin/usage/trends` | Usage time series |

## Test Categories

### Overview Endpoint Tests (11 tests)
| Test ID | Test Name | Status |
|---------|-----------|--------|
| OV-001 | overview_endpoint_returns_200_for_admin | ⏳ 404 |
| OV-002 | overview_endpoint_returns_required_structure | ⏳ 404 |
| OV-003 | overview_endpoint_returns_success_true | ⏳ 404 |
| OV-004 | overview_endpoint_returns_zero_for_empty_database | ⏳ 404 |
| OV-005 | overview_endpoint_returns_401_for_unauthenticated | ⏳ 404 |
| OV-006 | overview_endpoint_returns_403_for_non_admin | ⏳ 404 |
| OV-007 | overview_endpoint_counts_analysis_jobs | ⏳ 404 |
| OV-008 | overview_endpoint_includes_analysis_history_in_total | ⏳ 404 |
| OV-009 | overview_endpoint_returns_by_status_breakdown | ⏳ 404 |
| OV-010 | overview_endpoint_returns_by_type_breakdown | ⏳ 404 |

### Requests Endpoint Tests (8 tests)
| Test ID | Test Name | Status |
|---------|-----------|--------|
| RQ-001 | requests_endpoint_returns_200_for_admin | ⏳ 404 |
| RQ-002 | requests_endpoint_returns_required_structure | ⏳ 404 |
| RQ-003 | requests_endpoint_returns_endpoints_with_counts | ⏳ 404 |
| RQ-004 | requests_endpoint_returns_empty_for_no_data | ⏳ 404 |
| RQ-005 | requests_endpoint_returns_401_for_unauthenticated | ⏳ 404 |
| RQ-006 | requests_endpoint_returns_403_for_non_admin | ⏳ 404 |
| RQ-007 | requests_endpoint_orders_by_count_desc | ⏳ 404 |

### Tokens Endpoint Tests (7 tests)
| Test ID | Test Name | Status |
|---------|-----------|--------|
| TK-001 | tokens_endpoint_returns_200_for_admin | ⏳ 404 |
| TK-002 | tokens_endpoint_returns_required_structure | ⏳ 404 |
| TK-003 | tokens_endpoint_returns_zero_when_no_token_tracking | ⏳ 404 |
| TK-004 | tokens_endpoint_returns_401_for_unauthenticated | ⏳ 404 |
| TK-005 | tokens_endpoint_returns_403_for_non_admin | ⏳ 404 |
| TK-006 | tokens_endpoint_success_flag_is_true | ⏳ 404 |

### Trends Endpoint Tests (11 tests)
| Test ID | Test Name | Status |
|---------|-----------|--------|
| TR-001 | trends_endpoint_returns_200_for_admin | ⏳ 404 |
| TR-002 | trends_endpoint_returns_required_structure | ⏳ 404 |
| TR-003 | trends_endpoint_returns_7_days_by_default | ⏳ 404 |
| TR-004 | trends_endpoint_respects_days_parameter | ⏳ 404 |
| TR-005 | trends_endpoint_returns_date_and_tokens | ⏳ 404 |
| TR-006 | trends_endpoint_uses_correct_date_format | ⏳ 404 |
| TR-007 | trends_endpoint_returns_401_for_unauthenticated | ⏳ 404 |
| TR-008 | trends_endpoint_returns_403_for_non_admin | ⏳ 404 |
| TR-009 | trends_endpoint_includes_today | ⏳ 404 |
| TR-010 | trends_endpoint_returns_chronological_order | ⏳ 404 |

### Query Parameter Tests (9 tests)
| Test ID | Test Name | Status |
|---------|-----------|--------|
| QP-001 | overview_accepts_start_date_parameter | ⏳ 404 |
| QP-002 | overview_accepts_end_date_parameter | ⏳ 404 |
| QP-003 | overview_accepts_granularity_parameter | ⏳ 404 |
| QP-004 | trends_validates_days_as_integer | ⏳ 404 |
| QP-005 | trends_validates_days_is_positive | ⏳ 404 |
| QP-006 | trends_limits_max_days | ⏳ 404 |
| QP-007 | invalid_date_format_returns_error | ⏳ 404 |
| QP-008 | granularity_accepts_valid_values | ⏳ 404 |
| QP-009 | invalid_granularity_returns_error | ⏳ 404 |

### Rate Limiting Tests (1 test)
| Test ID | Test Name | Status |
|---------|-----------|--------|
| RL-001 | endpoints_have_rate_limiting | ⏳ 404 |

### HTTP Method Tests (8 tests)
| Test ID | Test Name | Status |
|---------|-----------|--------|
| HM-001 | overview_returns_405_for_post | ⏳ 404 |
| HM-002 | overview_returns_405_for_put | ⏳ 404 |
| HM-003 | overview_returns_405_for_delete | ⏳ 404 |
| HM-004 | requests_returns_405_for_post | ⏳ 404 |
| HM-005 | tokens_returns_405_for_post | ⏳ 404 |
| HM-006 | trends_returns_405_for_post | ⏳ 404 |

### Security Tests (5 tests)
| Test ID | Test Name | Status |
|---------|-----------|--------|
| SC-001 | endpoints_do_not_expose_sensitive_user_data | ⏳ 404 |
| SC-002 | endpoints_prevent_sql_injection_in_query_params | ⏳ 404 |
| SC-003 | endpoints_prevent_xss_in_query_params | ⏳ 404 |
| SC-004 | admin_cannot_access_other_admin_tenant_data | ⏳ 404 |

### Error Handling Tests (3 tests)
| Test ID | Test Name | Status |
|---------|-----------|--------|
| EH-001 | endpoints_return_consistent_error_format | ⏳ 404 |
| EH-002 | unauthenticated_returns_proper_format | ⏳ 404 |
| EH-003 | validation_error_returns_proper_format | ⏳ 404 |

### Performance Tests (2 tests)
| Test ID | Test Name | Status |
|---------|-----------|--------|
| PF-001 | overview_responds_under_500ms | ⏳ 404 |
| PF-002 | trends_responds_under_500ms | ⏳ 404 |

### Edge Case Tests (6 tests)
| Test ID | Test Name | Status |
|---------|-----------|--------|
| EC-001 | handles_large_dataset | ⏳ 404 |
| EC-002 | handles_special_characters_in_tool_names | ⏳ 404 |
| EC-003 | handles_unicode_in_response | ⏳ 404 |
| EC-004 | handles_concurrent_requests | ⏳ 404 |
| EC-005 | handles_timezone_correctly | ⏳ 404 |

### Data Type Validation Tests (5 tests)
| Test ID | Test Name | Status |
|---------|-----------|--------|
| DT-001 | numeric_values_are_integers | ⏳ 404 |
| DT-002 | by_status_contains_all_status_keys | ⏳ 404 |
| DT-003 | by_model_is_array | ⏳ 404 |
| DT-004 | by_endpoint_is_array | ⏳ 404 |
| DT-005 | trends_is_array_of_objects | ⏳ 404 |

### Integration Tests (3 tests)
| Test ID | Test Name | Status |
|---------|-----------|--------|
| IT-001 | full_usage_flow_works | ⏳ 404 |
| IT-002 | data_is_fresh_per_request | ⏳ 404 |
| IT-003 | service_exception_returns_error | ⏳ 404 |

## Expected Response Structures

### GET /api/v1/admin/usage/overview
```json
{
  "success": true,
  "data": {
    "requests": {
      "total": 100,
      "today": 5,
      "thisWeek": 25,
      "thisMonth": 80
    },
    "tokens": {
      "total": 50000,
      "today": 2500,
      "thisWeek": 12000,
      "thisMonth": 45000,
      "byModel": {
        "gpt-4": 30000,
        "gpt-3.5-turbo": 20000
      }
    },
    "analyses": {
      "total": 150,
      "today": 8,
      "byStatus": {
        "pending": 5,
        "running": 2,
        "completed": 100,
        "failed": 10,
        "cancelled": 3
      },
      "byType": {
        "meta-tag-analyzer": 50,
        "keyword-density": 30,
        "pwa-analyzer": 20
      }
    }
  }
}
```

### GET /api/v1/admin/usage/requests
```json
{
  "success": true,
  "data": {
    "byEndpoint": {
      "/api/v1/tools/meta-tag-analyzer": 50,
      "/api/v1/tools/keyword-density": 30,
      "/api/v1/tools/pwa-analyzer": 20
    }
  }
}
```

### GET /api/v1/admin/usage/tokens
```json
{
  "success": true,
  "data": {
    "total": 50000,
    "today": 2500,
    "thisWeek": 12000,
    "thisMonth": 45000,
    "byModel": {
      "gpt-4": 30000,
      "gpt-3.5-turbo": 20000
    }
  }
}
```

### GET /api/v1/admin/usage/trends
```json
{
  "success": true,
  "data": {
    "trends": [
      { "date": "2026-02-26", "tokens": 5000 },
      { "date": "2026-02-27", "tokens": 6000 },
      { "date": "2026-02-28", "tokens": 5500 },
      { "date": "2026-03-01", "tokens": 7000 },
      { "date": "2026-03-02", "tokens": 8000 },
      { "date": "2026-03-03", "tokens": 9500 },
      { "date": "2026-03-04", "tokens": 3000 }
    ]
  }
}
```

## Query Parameters

### Overview/Requests/Tokens Endpoints
| Parameter | Type | Description |
|-----------|------|-------------|
| `start_date` | date | Filter from date (Y-m-d) |
| `end_date` | date | Filter to date (Y-m-d) |
| `granularity` | string | hourly, daily, weekly, monthly |

### Trends Endpoint
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `days` | integer | 7 | Number of days (max 90) |

## Implementation Requirements

### Routes (routes/api.php)
```php
Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('api/v1/admin/usage')
    ->group(function () {
        Route::get('/overview', [UsageController::class, 'overview']);
        Route::get('/requests', [UsageController::class, 'requests']);
        Route::get('/tokens', [UsageController::class, 'tokens']);
        Route::get('/trends', [UsageController::class, 'trends']);
    });
```

### Controller (app/Http/Controllers/Api/V1/Admin/UsageController.php)
```php
<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\UsageAggregationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsageController extends Controller
{
    public function __construct(
        protected UsageAggregationService $usageService
    ) {}

    public function overview(Request $request): JsonResponse
    {
        $stats = $this->usageService->getStats();
        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    public function requests(Request $request): JsonResponse
    {
        $byEndpoint = $this->usageService->getRequestsByEndpoint();
        return response()->json([
            'success' => true,
            'data' => ['byEndpoint' => $byEndpoint],
        ]);
    }

    public function tokens(Request $request): JsonResponse
    {
        $stats = $this->usageService->getStats();
        return response()->json([
            'success' => true,
            'data' => $stats['tokens'],
        ]);
    }

    public function trends(Request $request): JsonResponse
    {
        $days = $request->validate(['days' => 'sometimes|integer|min:1|max:90'])['days'] ?? 7;
        $trends = $this->usageService->getTokenTrends($days);
        return response()->json([
            'success' => true,
            'data' => ['trends' => $trends],
        ]);
    }
}
```

### Middleware Stack
1. `auth:sanctum` - Authentication
2. `admin` (AdminAuthorizationMiddleware) - Admin check
3. `throttle:60,1` - Rate limiting (60 req/min)

## Key Patterns Used

### Factory Usage
```php
// Create admin user
User::factory()->create(['role' => 'admin']);

// Create regular user
User::factory()->create(['role' => 'user']);

// Create analysis data
AnalysisJob::factory()->count(10)->create(['status' => 'completed']);
AnalysisHistory::factory()->count(5)->create(['tool_name' => 'meta-tag-analyzer']);
```

### Sanctum Authentication
```php
// Authenticate as admin
Sanctum::actingAs($admin, ['access']);

// Unauthenticated request
$response = $this->getJson('/api/v1/admin/usage/overview');
// Expected: 401
```

### Error Response Format
```json
{
  "success": false,
  "error": {
    "code": "UNAUTHENTICATED|FORBIDDEN|VALIDATION_ERROR",
    "message": "Human readable message"
  }
}
```

## Dependencies
- TASK-27-2 (AdminAuthorizationMiddleware) - ✅ Done
- TASK-27-7 (UsageAggregationService) - ✅ Done (To Develop)
- User Factory - ✅ Available
- AnalysisJob Factory - ✅ Available
- AnalysisHistory Factory - ✅ Available

## Acceptance Criteria Mapping

| Criteria | Test Coverage |
|----------|---------------|
| Controller at `UsageController.php` | All tests assume this controller |
| GET `/usage/overview` returns aggregated stats | OV-* tests |
| GET `/usage/requests` returns request counts | RQ-* tests |
| GET `/usage/tokens` returns token usage | TK-* tests |
| GET `/usage/trends` returns time series | TR-* tests |
| Protected by AdminAuthorizationMiddleware | SC-*, 401/403 tests |
| Rate limiting: 60 requests/minute | RL-001 test |
| Query parameters: start_date, end_date, granularity | QP-* tests |
| Feature tests cover all endpoints | All 71 tests |

---
Created: 2026-03-04
Agent: PreTester
