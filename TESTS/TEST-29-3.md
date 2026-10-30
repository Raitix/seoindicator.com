# TEST-29-3: ToolExportService & Controller Tests

## Task Reference
- Task: [TASK-29-3](../TASKS/TASK-29-3.md)
- Specification: [SPEC-029](../SPECIFICATIONS/SPEC-029.md)
- Feature: [FEAT-029](../FEATURES/FEAT-029.md)

## Overview
Tests for `ToolExportService` (unit tests) and `ToolExportController` (feature tests) that coordinate export generation and provide API endpoints for creating and downloading tool exports.

## Test Files

| File | Type | Tests |
|------|------|-------|
| `backend/tests/Unit/Services/Tools/ToolExportServiceTest.php` | Unit | 52 |
| `backend/tests/Feature/Api/V1/ToolExportControllerTest.php` | Feature | 67 |
| **Total** | | **119** |

## Test Scenarios

### ToolExportService Unit Tests

#### Service Instantiation (SES-001 to SES-003)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| SES-001 | Service instantiated | Instance of ToolExportService |
| SES-002 | Has PDF exporter dependency | PdfToolExporter instance |
| SES-003 | Has XLS exporter dependency | XlsToolExporter instance |

#### createExport() Method (SES-010 to SES-025)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SES-010 | Returns download URL | ToolResult | URL with /api/v1/tools/export/download/{token} |
| SES-011 | Uses PDF exporter for pdf format | format=pdf | PdfToolExporter::export() called |
| SES-012 | Uses XLS exporter for xls format | format=xls | XlsToolExporter::array() called |
| SES-013 | Stores file in temp location | ToolResult | File exists in storage |
| SES-014 | Generates download token | ToolResult | 64-char alphanumeric token |
| SES-015 | Caches token data | ToolResult | Cache::has("export:{token}") true |
| SES-016 | Throws for invalid format | format=invalid | InvalidArgumentException |
| SES-017 | Handles empty tool name | tool="" | Valid URL returned |
| SES-018 | Handles empty URL | url="" | Valid URL returned |
| SES-019 | Handles empty data | data=[] | Valid URL returned |
| SES-020 | Sets token expiry to 1 hour | ToolResult | TTL ~3600 seconds |
| SES-021 | Stores user ID in cache | userId=42 | cached['user_id'] = 42 |
| SES-022 | Stores tool name in cache | tool='keyword-density' | cached['tool'] = 'keyword-density' |

#### downloadExport() Method (SES-030 to SES-050)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SES-030 | Returns response for valid token | Valid token | Response instance |
| SES-031 | PDF has correct content type | format=pdf | Content-Type: application/pdf |
| SES-032 | XLS has correct content type | format=xls | Content-Type: application/vnd.ms-excel |
| SES-033 | Throws for invalid token | Invalid token | NotFoundHttpException |
| SES-034 | Throws for expired token | Expired token | NotFoundHttpException |
| SES-035 | Throws for wrong user | Different user_id | NotFoundHttpException |
| SES-036 | Deletes file after download | Valid token | File missing after download |
| SES-037 | Deletes token after download | Valid token | Cache::has() false |
| SES-038 | Returns disposition header | Valid token | Content-Disposition: attachment |
| SES-039 | Filename contains tool name | tool='meta-tag-analyzer' | Filename contains tool |
| SES-040 | PDF filename has .pdf extension | format=pdf | .pdf in filename |
| SES-041 | XLS filename has .xlsx extension | format=xls | .xlsx in filename |

#### Token Generation (SES-060 to SES-065)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| SES-060 | Token is 64 characters | strlen($token) === 64 |
| SES-061 | Token is alphanumeric | Matches /^[a-zA-Z0-9]+$/ |
| SES-062 | Tokens are unique | 100 unique tokens generated |

#### File Management (SES-070 to SES-075)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| SES-070 | File stored in exports directory | Path contains 'exports/' |
| SES-071 | Filename contains timestamp | Matches date pattern |
| SES-072 | Multiple exports create unique files | 2 unique file paths |

#### Security (SES-080 to SES-085)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| SES-080 | Prevents path traversal in tool name | No '../' in file path |
| SES-081 | Sanitizes URL in filename | No script tags in path |
| SES-082 | User cannot access other users exports | NotFoundHttpException |

#### Edge Cases (SES-090 to SES-100)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| SES-090 | Handles empty indicators | Valid URL returned |
| SES-091 | Handles large export data (500 indicators) | Valid URL returned |
| SES-092 | Handles Unicode tool name | Valid URL returned |
| SES-093 | Handles special characters in URL | Valid URL returned |
| SES-094 | Handles concurrent requests | 10 unique tokens |

#### Integration (SES-110 to SES-115)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| SES-110 | Full export workflow | Create → Download → Cleanup verified |
| SES-111 | Export with different formats | PDF and XLS URLs differ |

### ToolExportController Feature Tests

#### Export Endpoint - Authentication (CTR-001 to CTR-005)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| CTR-001 | Requires authentication | 401 status |
| CTR-002 | Authenticated user can export | 200 status |
| CTR-003 | Uses Sanctum middleware | 401 without token |
| CTR-004 | Token must have abilities | Works with 'read' ability |
| CTR-005 | Different users create separate exports | Different URLs |

#### Export Endpoint - Validation (CTR-010 to CTR-025)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CTR-010 | tool is required | Missing tool | 422 validation error |
| CTR-011 | tool must be string | tool=123 | 422 validation error |
| CTR-012 | url is required | Missing url | 422 validation error |
| CTR-013 | url must be valid | url='not-a-url' | 422 validation error |
| CTR-014 | format is required | Missing format | 422 validation error |
| CTR-015 | format must be pdf or xls | format='docx' | 422 validation error |
| CTR-016 | format pdf accepted | format='pdf' | 200 status |
| CTR-017 | format xls accepted | format='xls' | 200 status |
| CTR-018 | data is required | Missing data | 422 validation error |
| CTR-019 | data must be array | data='string' | 422 validation error |
| CTR-020 | data can contain indicators | indicators array | 200 status |
| CTR-021 | data can contain summary | summary object | 200 status |
| CTR-022 | data can contain recommendations | recommendations array | 200 status |

#### Export Endpoint - Response Structure (CTR-030 to CTR-035)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| CTR-030 | Response has success: true | success=true in JSON |
| CTR-031 | Response has downloadUrl | downloadUrl field present |
| CTR-032 | Response has expiresAt | expiresAt field present |
| CTR-033 | expiresAt is 1 hour from now | Within 60s tolerance |
| CTR-034 | Response structure complete | All fields present |

#### Download Endpoint - Authentication (CTR-040 to CTR-045)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| CTR-040 | Requires authentication | 401 status |
| CTR-041 | Authenticated user can download | 200 status |
| CTR-042 | Returns file response | Response::class |

#### Download Endpoint - Errors (CTR-050 to CTR-055)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| CTR-050 | Invalid token returns 404 | 404 status |
| CTR-051 | Expired token returns 404 | 404 status |
| CTR-052 | Wrong user returns 404 | 404 status |
| CTR-053 | Token can only be used once | Second request 404 |

#### Download Endpoint - Headers (CTR-060 to CTR-065)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| CTR-060 | PDF has correct content type | application/pdf |
| CTR-061 | XLS has correct content type | application/vnd.ms-excel |
| CTR-062 | Has content-disposition header | attachment in header |
| CTR-063 | Filename contains tool name | Tool name in filename |
| CTR-064 | PDF filename has .pdf extension | .pdf in filename |
| CTR-065 | XLS filename has .xlsx extension | .xlsx in filename |

#### HTTP Methods (CTR-070 to CTR-075)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| CTR-070 | Export accepts POST only | 405 for GET/PUT/PATCH/DELETE |
| CTR-071 | Download accepts GET only | 405 for POST/PUT/PATCH/DELETE |

#### Security (CTR-090 to CTR-095)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| CTR-090 | Sanitizes tool name in filename | No script tags |
| CTR-091 | Sanitizes URL in output | No XSS vectors |
| CTR-092 | No sensitive data in response | No user_id, file_path, token |
| CTR-093 | Prevents enumeration | 404 not 403 for other users |
| CTR-094 | Handles SQL injection | 200 (graceful handling) |

#### Edge Cases (CTR-100 to CTR-110)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| CTR-100 | Empty data array | 200 status |
| CTR-101 | Large indicators array (500) | 200 status |
| CTR-102 | Unicode tool name | 200 status |
| CTR-103 | Unicode URL | 200 status |
| CTR-104 | Unicode in data | 200 status |
| CTR-105 | Emoji in data | 200 status |
| CTR-106 | Special characters in URL | 200 status |
| CTR-107 | Very long tool name (500 chars) | 200 status |
| CTR-108 | Very long URL (500+ chars) | 200 status |
| CTR-109 | Null values in data | 200 status |

#### Performance (CTR-120 to CTR-122)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| CTR-120 | Export responds within 500ms | Duration < 500ms |
| CTR-121 | Download responds within 500ms | Duration < 500ms |

#### Integration (CTR-130 to CTR-135)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| CTR-130 | Full export/download workflow | Create → Download → Token consumed |
| CTR-131 | Multiple exports for same user | Different URLs |
| CTR-132 | Different formats create different files | PDF/XLS content types differ |
| CTR-133 | Export with full ToolResult | 200 with all data |

#### Error Format (CTR-140 to CTR-142)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| CTR-140 | Validation error format | message + errors structure |
| CTR-141 | Not found error format | message field present |
| CTR-142 | Unauthorized error format | message field present |

## Mock Data

### Export Request Payload
```json
{
    "tool": "meta-tag-analyzer",
    "url": "https://example.com",
    "format": "pdf",
    "data": {
        "toolName": "Meta Tag Analyzer",
        "url": "https://example.com",
        "analyzedAt": "2026-03-04T12:00:00Z",
        "indicators": [
            {
                "category": "Meta Tags",
                "name": "Title",
                "value": "Example Page - Welcome",
                "status": "OK",
                "recommendation": ""
            },
            {
                "category": "Meta Tags",
                "name": "Description",
                "value": "Too short at 50 characters",
                "status": "Warning",
                "recommendation": "Increase length to 150-160 characters"
            }
        ],
        "summary": {
            "score": 85,
            "pass": 10,
            "warning": 3,
            "fail": 2
        },
        "recommendations": [
            {
                "priority": "high",
                "message": "Add meta description",
                "action": "Write 150-160 characters"
            }
        ]
    }
}
```

### Export Response
```json
{
    "success": true,
    "downloadUrl": "/api/v1/tools/export/download/abc123...xyz",
    "expiresAt": "2026-03-04T13:00:00Z"
}
```

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Service Instantiation | 100% | 3 |
| createExport() | 100% | 13 |
| downloadExport() | 100% | 12 |
| Token Generation | 100% | 3 |
| File Management | 100% | 3 |
| Security | 100% | 3 |
| Edge Cases | 100% | 5 |
| Integration (Service) | 100% | 2 |
| Export Auth | 100% | 5 |
| Export Validation | 100% | 13 |
| Export Response | 100% | 5 |
| Download Auth | 100% | 3 |
| Download Errors | 100% | 4 |
| Download Headers | 100% | 6 |
| HTTP Methods | 100% | 2 |
| Rate Limiting | 100% | 1 |
| Controller Security | 100% | 5 |
| Controller Edge Cases | 100% | 10 |
| Controller Performance | 100% | 2 |
| Controller Integration | 100% | 4 |
| Error Format | 100% | 3 |
| **Total** | **100%** | **119** |

## Test Execution

```bash
# Run all tests
cd backend
php artisan test --filter ToolExport

# Run service tests only
php artisan test --filter ToolExportServiceTest

# Run controller tests only
php artisan test --filter ToolExportControllerTest

# Run with coverage
php artisan test --filter ToolExport --coverage
```

## Dependencies

- `App\Services\Tools\ToolExportService` - Service under test
- `App\Http\Controllers\Api\V1\ToolExportController` - Controller under test
- `App\Exporters\PdfToolExporter` - Mocked for service tests
- `App\Exporters\XlsToolExporter` - Mocked for service tests
- `App\Services\Tools\ToolResult` - DTO for tool results
- Laravel Sanctum - Authentication
- PHPUnit - Test framework

## Notes for Developer

### Expected Service Structure
```php
<?php

namespace App\Services\Tools;

use App\Exporters\PdfToolExporter;
use App\Exporters\XlsToolExporter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ToolExportService
{
    public function __construct(
        private PdfToolExporter $pdfExporter,
        private XlsToolExporter $xlsExporter
    ) {}
    
    public function createExport(
        string $tool,
        string $url,
        string $format,
        array $data,
        int $userId
    ): string {
        // 1. Validate format
        // 2. Build ToolResult DTO
        // 3. Generate file using appropriate exporter
        // 4. Store in temp location
        // 5. Generate download token
        // 6. Cache token data
        // 7. Return download URL
    }
    
    public function downloadExport(string $token, int $userId): Response
    {
        // 1. Validate token and ownership
        // 2. Stream file to response
        // 3. Delete file and token
    }
    
    public function getPdfExporter(): PdfToolExporter
    {
        return $this->pdfExporter;
    }
    
    public function getXlsExporter(): XlsToolExporter
    {
        return $this->xlsExporter;
    }
    
    protected function generateToken(): string
    {
        return Str::random(64);
    }
}
```

### Expected Controller Structure
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Tools\ToolExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ToolExportController extends Controller
{
    public function __construct(
        private ToolExportService $exportService
    ) {}
    
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'tool' => 'required|string',
            'url' => 'required|url',
            'format' => 'required|in:pdf,xls',
            'data' => 'required|array',
        ]);
        
        $downloadUrl = $this->exportService->createExport(
            tool: $request->tool,
            url: $request->url,
            format: $request->format,
            data: $request->data,
            userId: auth()->id()
        );
        
        return response()->json([
            'success' => true,
            'downloadUrl' => $downloadUrl,
            'expiresAt' => now()->addHour()->toIso8601String(),
        ]);
    }
    
    public function download(string $token): mixed
    {
        return $this->exportService->downloadExport($token, auth()->id());
    }
}
```

### Key Implementation Points

1. **Token System**
   - 64-character alphanumeric tokens
   - Stored in cache with 1-hour expiry
   - One-time use (deleted after download)
   - User isolation (user_id check)

2. **File Storage**
   - Store in `storage/app/exports/`
   - Filename includes timestamp and tool name
   - Delete after successful download

3. **Format Routing**
   - `pdf` → PdfToolExporter::export()
   - `xls` → XlsToolExporter (Laravel Excel)
   - Invalid format → InvalidArgumentException

4. **Response Headers**
   - PDF: `Content-Type: application/pdf`
   - XLS: `Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`
   - Both: `Content-Disposition: attachment; filename="..."`

5. **Security**
   - Sanitize tool names and URLs in filenames
   - Prevent path traversal (`../`)
   - No sensitive data in API responses
   - User ownership validation on download

### Routes to Add
```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/tools/export', [ToolExportController::class, 'export']);
    Route::get('/tools/export/download/{token}', [ToolExportController::class, 'download']);
});
```

### Error Responses
| Code | Condition | Message |
|------|-----------|---------|
| 401 | Unauthenticated | "Unauthenticated." |
| 404 | Invalid/expired token | "Export not found." |
| 422 | Invalid format | Validation error with format field |

## Comments
_Agent log_

### [2026-03-04 13:10 GMT+2] Agent: PreTester
**Tests Coverage Complete**

Created 119 tests covering:
- **ToolExportService (52 unit tests):**
  - Service instantiation and dependencies
  - createExport() method (format routing, token generation, file storage)
  - downloadExport() method (validation, streaming, cleanup)
  - Token generation (64 chars, alphanumeric, unique)
  - File management (storage, naming, cleanup)
  - Security (path traversal, XSS, user isolation)
  - Edge cases (unicode, large data, concurrent requests)
  - Integration (full workflow)

- **ToolExportController (67 feature tests):**
  - Authentication (Sanctum, token abilities)
  - Request validation (tool, url, format, data)
  - Response structure (success, downloadUrl, expiresAt)
  - Download authentication
  - Download error handling (invalid/expired/wrong user)
  - Response headers (Content-Type, Content-Disposition)
  - HTTP methods (POST for export, GET for download)
  - Security (XSS, enumeration, SQL injection)
  - Edge cases (unicode, emoji, large data, nulls)
  - Performance (<500ms)
  - Integration (full workflows)
  - Error format consistency

**Key Test Patterns:**
- Mockery for PdfToolExporter and XlsToolExporter
- Sanctum::actingAs() for authentication
- Storage::fake() for file operations
- Cache assertions for token validation

**Dependencies:**
- TASK-29-1 (PdfToolExporter) - Mocked in tests
- TASK-29-2 (XlsToolExporter) - Mocked in tests
- Laravel Sanctum - Authentication
- Laravel Storage - File management

Status: `To Write Tests` → `To Develop`

Ready for Developer implementation.
