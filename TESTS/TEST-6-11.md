# TEST-6-11: Reports API Endpoint Tests

## Task Reference
- Task: [TASK-6-11](../TASKS/TASK-6-11.md)
- Spec: [SPEC-006](../SPECIFICATIONS/SPEC-006.md)
- Feature: [FEAT-006](../FEATURES/FEAT-006.md)

---

## Overview

Tests for the REST API endpoint `POST /api/v1/reports` that triggers asynchronous report generation.

**Endpoint:** `POST /api/v1/reports`

**Authentication:** API Key (`api-key.auth` middleware)

**Permission Required:** `reports:write`

**Rate Limiting:** Per-key rate limiting (`api-key.ratelimit` middleware)

---

## Test Files

| File | Tests | Description |
|------|-------|-------------|
| `backend/tests/Feature/Api/V1/ReportsApiControllerTest.php` | 70+ | Feature tests for reports API endpoint |

---

## Test Scenarios

### Authentication Tests (AUTH-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AUTH-01 | Unauthenticated request | No Authorization header | 401 UNAUTHENTICATED |
| AUTH-02 | Invalid API key | `Bearer sk_live_invalidkey...` | 401 UNAUTHENTICATED |
| AUTH-03 | Revoked API key | Revoked key | 401 UNAUTHENTICATED |
| AUTH-04 | Expired API key | Expired key | 401 UNAUTHENTICATED |
| AUTH-05 | Valid API key with permission | Valid key + reports:write | 202 Accepted |

### Permission Tests (PERM-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PERM-01 | Missing reports:write permission | Key with reports:read only | 403 FORBIDDEN |
| PERM-02 | Key without reports permission | Key with keywords:read | 403 FORBIDDEN |
| PERM-03 | Other user's site | site_id of other user | 403 FORBIDDEN |
| PERM-04 | API key user isolation | Key from user A, site from user B | 403 FORBIDDEN |

### Validation Tests (VAL-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| VAL-01 | Missing site_id | `{ type, format }` | 422 validation error |
| VAL-02 | Missing type | `{ site_id, format }` | 422 validation error |
| VAL-03 | Missing format | `{ site_id, type }` | 422 validation error |
| VAL-04 | Invalid site_id | `site_id: 99999` | 422 validation error |
| VAL-05 | Invalid type | `type: "invalid-type"` | 422 validation error |
| VAL-06 | Invalid format | `format: "docx"` | 422 validation error |
| VAL-07 | Valid types | daily, weekly, monthly, custom, one-time | 202 Accepted |
| VAL-08 | Valid formats | pdf, csv | 202 Accepted |
| VAL-09 | Non-integer site_id | `site_id: "not-an-integer"` | 422 validation error |
| VAL-10 | Empty request body | `{}` | 422 validation error |

### Success Case Tests (SUCC-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SUCC-01 | Successful generation | Valid request | 202, `{ job_id, status: "queued" }` |
| SUCC-02 | Job ID format | Valid request | `rpt-{alphanumeric}` format |
| SUCC-03 | Report record created | Valid request | Report in DB with status=pending |
| SUCC-04 | Auto-generated name | Valid request | Name contains report type |
| SUCC-05 | CSV format | `format: "csv"` | Report created with format=csv |
| SUCC-06 | Default date range | Valid request | Date range set based on type |
| SUCC-07 | Job queued | Valid request | GenerateReportJob pushed to queue |
| SUCC-08 | API key last_used_at | Valid request | last_used_at updated |
| SUCC-09 | Unique job IDs | Multiple requests | Different job_id each time |

### Optional Parameters Tests (OPT-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| OPT-01 | Optional name | `name: "Custom Name"` | Report with custom name |
| OPT-02 | Optional date_range | `date_range: { start, end }` | Custom date range set |
| OPT-03 | Invalid date_range.start | `date_range.start: "invalid"` | 422 validation error |
| OPT-04 | Invalid date_range.end | `date_range.end: "invalid"` | 422 validation error |
| OPT-05 | End before start | `end < start` | 422 validation error |
| OPT-06 | Optional sections | `sections: ["cover", "keywords"]` | Sections set on report |
| OPT-07 | Invalid section | `sections: ["invalid"]` | 422 validation error |

### Rate Limiting Tests (RATE-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RATE-01 | Rate limit enforced | Exceed rate_limit requests | 429 Too Many Requests |
| RATE-02 | Rate limit headers | Valid request | X-RateLimit-Limit, X-RateLimit-Remaining |
| RATE-03 | Separate limits per key | Two different keys | Independent limits |

### Security Tests (SEC-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SEC-01 | SQL injection site_id | `site_id: "1; DROP TABLE..."` | 422 validation error |
| SEC-02 | XSS in name | `name: "<script>..."` | Name sanitized |
| SEC-03 | Mass assignment | `status: "completed"` | Ignored, status=pending |
| SEC-04 | API key not exposed | Valid request | No key in response |
| SEC-05 | Enumeration prevention | Other user's site vs non-existent | Same error type |

### Error Handling Tests (ERR-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ERR-01 | Invalid JSON body | Malformed JSON | 400 Bad Request |
| ERR-02 | Wrong HTTP method (GET) | GET /api/v1/reports | 405 Method Not Allowed |
| ERR-03 | Wrong HTTP method (PUT) | PUT /api/v1/reports | 405 Method Not Allowed |
| ERR-04 | Wrong HTTP method (PATCH) | PATCH /api/v1/reports | 405 Method Not Allowed |
| ERR-05 | Wrong HTTP method (DELETE) | DELETE /api/v1/reports | 405 Method Not Allowed |

### Edge Cases Tests (EDGE-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EDGE-01 | Large name parameter | 300+ char name | Truncated or 422 |
| EDGE-02 | Unicode in name | `名前 レポート 📊` | Preserved correctly |
| EDGE-03 | Concurrent requests | 5 simultaneous | All succeed, unique job_ids |
| EDGE-04 | Zero site_id | `site_id: 0` | 422 validation error |
| EDGE-05 | Negative site_id | `site_id: -1` | 422 validation error |
| EDGE-06 | Case sensitivity | `WEEKLY`, `PDF` | 422 validation error |

### Performance Tests (PERF-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PERF-01 | Response time | Valid request | < 500ms |
| PERF-02 | Cached auth response | Second request | < 200ms |

### Acceptance Criteria Tests (AC-*)

| ID | Acceptance Criteria | Test |
|----|---------------------|------|
| AC-01 | Endpoint working | POST returns 202 |
| AC-02 | Job queued for processing | GenerateReportJob pushed |
| AC-03 | Job ID returned | Response includes job_id |
| AC-04 | Rate limiting applied | 429 after limit exceeded |

### Response Format Tests (RESP-*)

| ID | Scenario | Expected Structure |
|----|----------|-------------------|
| RESP-01 | Success response | `{ success: true, data: { job_id, status } }` |
| RESP-02 | Error response | `{ success: false, error: { code, message } }` |
| RESP-03 | Validation error | Includes `error.details` with field errors |

---

## Request Format

```json
{
  "site_id": 1,
  "type": "weekly",
  "format": "pdf",
  "name": "Optional custom name",
  "date_range": {
    "start": "2026-01-01",
    "end": "2026-01-31"
  },
  "sections": ["cover", "summary", "keywords", "trends"]
}
```

### Required Fields
- `site_id` (integer) - ID of the site to generate report for
- `type` (string) - Report type: `daily`, `weekly`, `monthly`, `custom`, `one-time`
- `format` (string) - Output format: `pdf`, `csv`

### Optional Fields
- `name` (string, max 255) - Custom report name
- `date_range` (object) - Custom date range
  - `start` (date) - Start date
  - `end` (date) - End date (must be >= start)
- `sections` (array) - Report sections to include
  - Valid values: `cover`, `summary`, `keywords`, `changes`, `top-performers`, `needs-attention`, `trends`, `competitors`, `serp-features`, `audit`

---

## Response Format

### Success (202 Accepted)
```json
{
  "success": true,
  "data": {
    "job_id": "rpt-abc123def456ghij",
    "status": "queued"
  }
}
```

### Error (4xx/5xx)
```json
{
  "success": false,
  "error": {
    "code": "FORBIDDEN",
    "message": "You do not have permission to perform this action."
  }
}
```

### Validation Error (422)
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "site_id": ["The selected site_id is invalid."],
      "type": ["The selected type is invalid."]
    }
  }
}
```

---

## Mock Data

### Valid API Key
```php
$apiKey = ApiKey::factory()->create([
    'user_id' => $user->id,
    'permissions' => ['reports:read', 'reports:write'],
    'rate_limit' => 60,
]);
```

### Valid Request Body
```php
$requestBody = [
    'site_id' => 1,
    'type' => 'weekly',
    'format' => 'pdf',
];
```

### Expected Report Record
```php
[
    'site_id' => 1,
    'type' => 'weekly',
    'format' => 'pdf',
    'status' => 'pending',
    'name' => 'Weekly Report - 2026-03-07',
]
```

---

## Coverage Targets

| Category | Tests | Target |
|----------|-------|--------|
| Authentication | 5 | 100% |
| Permissions | 4 | 100% |
| Validation | 10 | 100% |
| Success Cases | 9 | 100% |
| Optional Params | 7 | 100% |
| Rate Limiting | 3 | 100% |
| Security | 5 | 100% |
| Error Handling | 5 | 100% |
| Edge Cases | 6 | 100% |
| Performance | 2 | 100% |
| Acceptance Criteria | 4 | 100% |
| Response Format | 3 | 100% |
| **Total** | **63+** | **100%** |

---

## Test Execution

```bash
# Run all tests for this task
php artisan test tests/Feature/Api/V1/ReportsApiControllerTest.php

# Run specific test category
php artisan test tests/Feature/Api/V1/ReportsApiControllerTest.php --filter="test_auth"

# Run with coverage
php artisan test tests/Feature/Api/V1/ReportsApiControllerTest.php --coverage
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-6-4 (API Key Management) | Done | Uses ApiKey model and authentication |
| Reports feature implementation | Done | Uses Report model |
| GenerateReportJob | Required | Job class to be created |
| api-key.auth middleware | Available | API key authentication |
| api-key.ratelimit middleware | Available | Per-key rate limiting |

---

## Notes for Developer

### Routes Required

Add to `routes/api.php` in the External REST API Routes section:

```php
Route::prefix('v1')->middleware(['api-key.auth', 'api-key.ratelimit'])->group(function () {
    // Reports API (TASK-6-11)
    Route::post('reports', [ReportsApiController::class, 'store'])
        ->middleware('api-key.auth:reports:write');
});
```

### Controller Required

Create `app/Http/Controllers/Api/V1/ReportsApiController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateReportJob;
use App\Models\Report;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReportsApiController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_id' => 'required|integer|exists:sites,id',
            'type' => 'required|in:daily,weekly,monthly,custom,one-time',
            'format' => 'required|in:pdf,csv',
            'name' => 'nullable|string|max:255',
            'date_range' => 'nullable|array',
            'date_range.start' => 'nullable|date',
            'date_range.end' => 'nullable|date|after_or_equal:date_range.start',
            'sections' => 'nullable|array',
            'sections.*' => 'string|in:cover,summary,keywords,changes,top-performers,needs-attention,trends,competitors,serp-features,audit',
        ]);

        // Verify site ownership
        $site = Site::find($validated['site_id']);
        if ($site->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this site.',
                ],
            ], 403);
        }

        // Generate job ID
        $jobId = 'rpt-' . Str::random(16);

        // Create report record
        $report = Report::create([
            'site_id' => $validated['site_id'],
            'name' => $validated['name'] ?? $this->generateName($validated['type']),
            'type' => $validated['type'],
            'format' => $validated['format'],
            'status' => 'pending',
            'date_range_start' => $validated['date_range']['start'] ?? $this->getDefaultStartDate($validated['type']),
            'date_range_end' => $validated['date_range']['end'] ?? $this->getDefaultEndDate($validated['type']),
            'sections' => $validated['sections'] ?? ['cover', 'keywords'],
        ]);

        // Dispatch job
        GenerateReportJob::dispatch($report->id, $jobId);

        return response()->json([
            'success' => true,
            'data' => [
                'job_id' => $jobId,
                'status' => 'queued',
            ],
        ], 202);
    }

    protected function generateName(string $type): string
    {
        return ucfirst($type) . ' Report - ' . now()->format('Y-m-d');
    }

    protected function getDefaultStartDate(string $type): string
    {
        return match ($type) {
            'daily' => now()->subDay()->toDateString(),
            'weekly' => now()->subWeek()->toDateString(),
            'monthly' => now()->subMonth()->toDateString(),
            default => now()->subWeek()->toDateString(),
        };
    }

    protected function getDefaultEndDate(string $type): string
    {
        return now()->toDateString();
    }
}
```

### Job Required

Create `app/Jobs/GenerateReportJob.php`:

```php
<?php

namespace App\Jobs;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $reportId,
        public string $jobId
    ) {}

    public function handle(): void
    {
        $report = Report::find($this->reportId);
        
        if (!$report) {
            return;
        }

        // Update status to generating
        $report->update(['status' => 'generating']);

        try {
            // Generate report (reuse existing ReportController logic)
            // ...
            
            $report->update([
                'status' => 'completed',
                'generated_at' => now(),
            ]);
        } catch (\Exception $e) {
            $report->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

---

## Comments

### [2026-03-07 17:20] PreTester: Test Files Created
- Created comprehensive test file with 70+ test scenarios
- Tests cover all acceptance criteria
- Tests follow existing API endpoint patterns (ApiKeyControllerTest)
- Security, rate limiting, and edge cases covered
- Documentation created at TESTS/TEST-6-11.md

**Status:** To Develop ✅

**Dependencies:**
- TASK-6-4 (API Key Management) - Done ✅
- Reports feature - Done ✅
- GenerateReportJob - To Create
- ReportsApiController - To Create
