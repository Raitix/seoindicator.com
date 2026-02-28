# SPEC-029: Export Results (PDF/XLS) - Technical Specification

## STATUS
`To Break Into Tasks`

## PRIORITY
`High`

## RISK
`Medium`

## Related
- Feature: [FEAT-029](../FEATURES/FEAT-029.md)
- Depends on: SPEC-016 (Interactive SEO Tools), SPEC-004 (Reports)

---

## Overview

Enable users to export SEO analysis results from interactive tools as PDF and XLS files for reporting and client sharing. This feature bridges the gap between instant analysis and professional deliverables.

---

## Scope

### In Scope
- Export tool analysis results (Meta Tag Analyzer, Keyword Density Checker, Mobile-Friendly Checker, Social Media Preview)
- PDF export with branded summary and indicator details
- XLS export with structured indicator data table
- Export available from tool results view
- Per-tool export with consistent UX

### Out of Scope
- Batch export of multiple tool results
- Custom branding per export (uses site branding)
- Scheduled exports (use Report Scheduler instead)
- Email delivery of exports

---

## Architecture

### Component Overview

```
Tool Results UI → ExportService → FormatExporter → File Download
     ↓                                  ↓
 ToolResult DTO              PdfToolExporter / XlsToolExporter
```

### File Structure

```
backend/
├── app/
│   ├── Exporters/
│   │   ├── PdfToolExporter.php      # New - PDF for tool results
│   │   ├── XlsToolExporter.php      # New - XLS for tool results
│   │   ├── PdfReportExporter.php    # Existing - reuse patterns
│   │   └── CsvReportExporter.php    # Existing - reference
│   ├── Services/
│   │   └── Tools/
│   │       └── ToolExportService.php # New - coordinates export
│   └── Http/Controllers/Api/V1/
│       └── ToolExportController.php  # New - API endpoints
└── tests/
    └── Unit/Exporters/
        ├── PdfToolExporterTest.php
        └── XlsToolExporterTest.php

frontend/
├── components/tools/
│   ├── ExportResultsButton.tsx      # New - export trigger
│   └── ExportModal.tsx              # New - format selection
└── lib/api/tool-export.ts           # New - API helpers
```

---

## API Endpoints

### Export Tool Results
`POST /api/v1/tools/export`

**Request Body:**
```json
{
  "tool": "meta-tag-analyzer",
  "url": "https://example.com",
  "format": "pdf",
  "data": {
    "analyzedAt": "2026-02-28T12:00:00Z",
    "results": {
      "title": { "content": "...", "length": 60, "status": "ok" },
      "description": { "content": "...", "length": 155, "status": "warning" }
    }
  }
}
```

**Response (200):**
```json
{
  "success": true,
  "downloadUrl": "/api/v1/tools/export/download/{token}",
  "expiresAt": "2026-02-28T13:00:00Z"
}
```

### Download Export File
`GET /api/v1/tools/export/download/{token}`

**Response:** Binary file download (PDF or XLS)

**Error Responses:**
| Code | Condition |
|------|-----------|
| 401 | Unauthenticated request |
| 404 | Invalid or expired token |
| 422 | Invalid format or missing data |

---

## Data Structures

### ToolResult DTO (Existing - Extended)

```php
class ToolResult
{
    public string $toolName;
    public string $url;
    public Carbon $analyzedAt;
    public array $indicators;      // Structured indicator data
    public array $summary;         // High-level summary
    public array $recommendations; // Action items
}
```

### Export Data Schema

**PDF Export Structure:**
```
┌─────────────────────────────────────┐
│ Cover Page                          │
│ - Logo / Company Name               │
│ - Tool Name + URL Analyzed          │
│ - Analysis Date                     │
├─────────────────────────────────────┤
│ Summary Section                     │
│ - Overall Score                     │
│ - Pass/Fail/Warning Counts          │
│ - Quick Stats                       │
├─────────────────────────────────────┤
│ Indicators Section                  │
│ - Grouped by Category               │
│ - Status Icons                      │
│ - Values + Recommendations          │
├─────────────────────────────────────┤
│ Recommendations Section             │
│ - Prioritized Action Items          │
│ - Issue → Fix Mapping               │
└─────────────────────────────────────┘
```

**XLS Export Structure:**
| Category | Indicator | Value | Status | Recommendation |
|----------|-----------|-------|--------|----------------|
| Meta Tags | Title | "Example Page" | OK | - |
| Meta Tags | Description | "..." | Warning | Increase length to 150-160 chars |
| Content | H1 Count | 1 | OK | - |
| Content | Keyword Density | 2.5% | OK | - |

---

## Exporters

### PdfToolExporter

**Extends patterns from PdfReportExporter:**
- Reuses PDF generation infrastructure
- Supports branding (logo, colors, font)
- A4 page format with proper margins
- Automatic page breaks for long indicator lists

**Key Methods:**
```php
class PdfToolExporter
{
    public function export(ToolResult $result, ?array $branding = null): string;
    protected function renderCoverPage(ToolResult $result): array;
    protected function renderSummarySection(ToolResult $result): array;
    protected function renderIndicatorsTable(ToolResult $result): array;
    protected function renderRecommendations(ToolResult $result): array;
}
```

### XlsToolExporter

**Uses PhpSpreadsheet (Laravel Excel):**
- Single worksheet with indicator data
- Auto-sized columns
- Conditional formatting for status
- Header row with filters enabled

**Key Methods:**
```php
class XlsToolExporter
{
    public function export(ToolResult $result): string;
    protected function buildHeaders(): array;
    protected function buildRows(ToolResult $result): array;
    protected function applyFormatting(Worksheet $sheet): void;
}
```

---

## Security Considerations

| Risk | Mitigation |
|------|------------|
| IDOR | Download tokens are one-time use, expire in 1 hour |
| File Injection | All data sanitized before export generation |
| XSS in PDF | Text properly escaped in PDF generation |
| Large Exports | Max 500 indicators per export; pagination for larger datasets |
| Rate Limiting | 10 exports per hour per user (uses existing ToolsRateLimitService) |

### Token-Based Download

```php
// Generate secure download token
$token = Str::random(64);
Cache::put("export:{$token}", [
    'file' => $filePath,
    'format' => $format,
    'user_id' => auth()->id(),
], now()->addHour());

// Token invalidated after download
Cache::forget("export:{$token}");
```

---

## Dependencies

### Backend
- **PhpSpreadsheet** (via `maatwebsite/excel` package) - XLS generation
- **Existing PDF Infrastructure** - PdfReportExporter patterns
- **Laravel Storage** - Temporary file management

### Frontend
- **Existing Tool Components** - Integration point
- **React Hook Form** - Export modal form

### Environment
```
TOOL_EXPORT_MAX_INDICATORS=500
TOOL_EXPORT_TOKEN_TTL=3600
TOOL_EXPORT_RATE_LIMIT=10
```

---

## Task Breakdown

### Phase 1: Backend Export Infrastructure (3 tasks)
| Task ID | Name | Priority | Effort |
|---------|------|----------|--------|
| TASK-29-1 | PdfToolExporter Implementation | High | 4h |
| TASK-29-2 | XlsToolExporter Implementation | High | 4h |
| TASK-29-3 | ToolExportService & Controller | High | 3h |

### Phase 2: API & Security (2 tasks)
| Task ID | Name | Priority | Effort |
|---------|------|----------|--------|
| TASK-29-4 | Download Token System | High | 2h |
| TASK-29-5 | Rate Limiting Integration | Medium | 1h |

### Phase 3: Frontend Integration (3 tasks)
| Task ID | Name | Priority | Effort |
|---------|------|----------|--------|
| TASK-29-6 | ExportResultsButton Component | High | 2h |
| TASK-29-7 | ExportModal Component | Medium | 2h |
| TASK-29-8 | Tool Page Integration | High | 2h |

### Phase 4: Testing & Polish (2 tasks)
| Task ID | Name | Priority | Effort |
|---------|------|----------|--------|
| TASK-29-9 | Backend Unit Tests | High | 3h |
| TASK-29-10 | Frontend Component Tests | Medium | 2h |

**Total: 10 tasks, ~25 hours**

---

## Technical Implementation Details

### PdfToolExporter Implementation

```php
<?php

namespace App\Exporters;

use App\Services\Tools\ToolResult;
use Carbon\Carbon;

class PdfToolExporter
{
    private const PAGE_WIDTH = 595;  // A4
    private const PAGE_HEIGHT = 842;
    private const MARGIN = 40;

    public function export(ToolResult $result, ?array $branding = null): string
    {
        $pages = [];
        
        // Cover page
        $pages[] = $this->renderCoverPage($result, $branding);
        
        // Summary
        $pages[] = $this->renderSummary($result);
        
        // Indicators (may span multiple pages)
        $pages = array_merge($pages, $this->renderIndicators($result));
        
        // Recommendations
        $pages[] = $this->renderRecommendations($result);
        
        return $this->assemblePdf($pages, $branding);
    }
    
    protected function renderIndicators(ToolResult $result): array
    {
        $pages = [];
        $indicators = $result->indicators;
        $rowsPerPage = 25;
        
        foreach (array_chunk($indicators, $rowsPerPage) as $chunk) {
            $pages[] = $this->renderIndicatorTable($chunk);
        }
        
        return $pages;
    }
}
```

### XlsToolExporter Implementation

```php
<?php

namespace App\Exporters;

use App\Services\Tools\ToolResult;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;

class XlsToolExporter implements FromArray, WithHeadings, WithStyles
{
    private ToolResult $result;
    
    public function __construct(ToolResult $result)
    {
        $this->result = $result;
    }
    
    public function array(): array
    {
        $rows = [];
        
        foreach ($this->result->indicators as $indicator) {
            $rows[] = [
                $indicator['category'] ?? 'General',
                $indicator['name'],
                $indicator['value'] ?? '-',
                $indicator['status'],
                $indicator['recommendation'] ?? '-',
            ];
        }
        
        return $rows;
    }
    
    public function headings(): array
    {
        return ['Category', 'Indicator', 'Value', 'Status', 'Recommendation'];
    }
    
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
```

### ToolExportController

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Tools\ToolExportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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

---

## Acceptance Criteria Mapping

| Criteria | Implementation |
|----------|---------------|
| PDF export of summary + indicators | PdfToolExporter generates multi-section PDF |
| XLS export of indicator table | XlsToolExporter generates spreadsheet with columns |
| Export available from results view | ExportResultsButton in tool results UI |

---

## Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Large exports causing memory issues | Medium | Medium | Limit indicators, use streaming for PDF |
| XSS in user-provided URLs | Low | High | URL validation + output escaping |
| Download link sharing | Medium | Low | Token-based, user-validated downloads |

---

## Comments
_Agent log_

### [2026-02-28 11:45 GMT+2] Agent: Architect
Created SPEC-029. Key decisions:
1. Reuse PDF infrastructure from PdfReportExporter for consistency
2. Use PhpSpreadsheet (Laravel Excel) for XLS - industry standard
3. Token-based download for security (one-time use, 1-hour expiry)
4. Rate limiting integrated with existing ToolsRateLimitService
5. 10 tasks across 4 phases for systematic implementation

Ready for SpecSystemAnalyst breakdown.
