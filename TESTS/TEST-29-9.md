# TEST-29-9: Backend Unit Tests

## Task Reference
- Task: [TASK-29-9](../TASKS/TASK-29-9.md)
- Specification: [SPEC-029](../SPECIFICATIONS/SPEC-029.md)
- Feature: [FEAT-029](../FEATURES/FEAT-029.md)

## Overview
Comprehensive unit tests for the export functionality backend components: `PdfToolExporter`, `XlsToolExporter`, and `ToolExportService`. These tests follow TDD principles - they are written before implementation and will pass once the dependent tasks are completed.

## Test Files

| File | Type | Tests |
|------|------|-------|
| `backend/tests/Unit/Exporters/PdfToolExporterTest.php` | Unit | 73 |
| `backend/tests/Unit/Exporters/XlsToolExporterTest.php` | Unit | 72 |
| `backend/tests/Unit/Services/Tools/ToolExportServiceTest.php` | Unit | 55 |
| **Total** | | **200** |

## Test Scenarios

### PdfToolExporter Tests (73 tests)

#### Service Instantiation (PDF-001 to PDF-002)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| PDF-001 | Service instantiated successfully | Instance of PdfToolExporter |
| PDF-002 | Service has correct constants | PAGE_WIDTH=595, PAGE_HEIGHT=842, MARGIN=40, ROWS_PER_PAGE=25 |

#### Export Method (PDF-010 to PDF-013)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| PDF-010 | export() returns valid PDF string | Starts with `%PDF-1.4`, ends with `%%EOF` |
| PDF-011 | export() with branding applies colors | Custom font in PDF |
| PDF-012 | export() without branding uses defaults | Helvetica font |
| PDF-013 | export() with empty branding uses defaults | Valid PDF |

#### Cover Page (PDF-020 to PDF-024)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| PDF-020 | Cover page has tool name | Tool name in PDF content |
| PDF-021 | Cover page has URL analyzed | Domain in PDF content |
| PDF-022 | Cover page has analysis date | Year in PDF content |
| PDF-023 | Cover page has logo placeholder | 'Logo' in PDF |
| PDF-024 | Cover page with branding logo | Company name in PDF |

#### Summary Section (PDF-030 to PDF-035)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| PDF-030 | Summary has overall score | Score value in PDF |
| PDF-031 | Summary has pass count | Pass count in PDF |
| PDF-032 | Summary has warning count | Warning count in PDF |
| PDF-033 | Summary has fail count | Fail count in PDF |
| PDF-034 | Summary with zero score | Valid PDF generated |
| PDF-035 | Summary with perfect score | 100 in PDF |

#### Indicators Table (PDF-040 to PDF-045)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| PDF-040 | Indicators table renders categories | Categories in PDF |
| PDF-041 | Indicators table renders names | Indicator names in PDF |
| PDF-042 | Indicators table renders status icons | Valid PDF |
| PDF-043 | Indicators table renders values | Values in PDF |
| PDF-044 | Indicators grouped by category | Multiple categories in PDF |
| PDF-045 | Indicators table empty state | Valid PDF generated |

#### Page Breaks (PDF-050 to PDF-052)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| PDF-050 | Page break after 25 indicators | Multiple pages |
| PDF-051 | Large indicator list spans pages | 3+ pages |
| PDF-052 | Exactly 25 indicators fit | Valid PDF |

#### Recommendations Section (PDF-060 to PDF-063)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| PDF-060 | Recommendations section renders | 'Recommendation' in PDF |
| PDF-061 | Recommendations sorted by priority | All priorities in PDF |
| PDF-062 | Recommendations with empty list | Valid PDF |
| PDF-063 | Recommendations with action items | Action text in PDF |

#### A4 Page Format (PDF-070 to PDF-071)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| PDF-070 | A4 page dimensions | MediaBox [0 0 595 842] |
| PDF-071 | Page margins applied | Margin value in PDF |

#### XSS Prevention (PDF-080 to PDF-084)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| PDF-080 | Script tags are escaped | No `<script>` in PDF |
| PDF-081 | Special characters are escaped | Valid PDF |
| PDF-082 | Backslashes are escaped | Valid PDF |
| PDF-083 | HTML entities are safe | No raw HTML in PDF |
| PDF-084 | JavaScript in URL is safe | No script in PDF |

#### Branding (PDF-090 to PDF-095)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| PDF-090 | Custom primary color | Valid PDF |
| PDF-091 | Custom secondary color | Valid PDF |
| PDF-092 | Custom font | Times-Roman in PDF |
| PDF-093 | Company name | Company name in PDF |
| PDF-094 | All branding options | All values in PDF |
| PDF-095 | Invalid color fallback | Valid PDF |

#### PDF Structure (PDF-100 to PDF-105)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| PDF-100 | Valid catalog object | /Type /Catalog in PDF |
| PDF-101 | Valid pages object | /Type /Pages in PDF |
| PDF-102 | Valid font object | /Type /Font in PDF |
| PDF-103 | xref table present | 'xref' in PDF |
| PDF-104 | trailer present | 'trailer' in PDF |
| PDF-105 | Page numbering | 'Page X of Y' in PDF |

#### Edge Cases (PDF-110 to PDF-115)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| PDF-110 | Empty tool name | Valid PDF |
| PDF-111 | Very long URL | Valid PDF |
| PDF-112 | Unicode characters | Valid PDF |
| PDF-113 | Emoji in content | Valid PDF |
| PDF-114 | Failed result handled | Valid PDF with error |
| PDF-115 | Null values in indicators | Valid PDF |

#### Integration (PDF-120 to PDF-122)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| PDF-120 | Full export with all sections | All content in PDF |
| PDF-121 | Consistent output | Same PDF for same input |
| PDF-122 | Binary PDF content | String > 100 bytes |

---

### XlsToolExporter Tests (72 tests)

#### Service Instantiation (XLS-001 to XLS-002)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| XLS-001 | Service instantiated | Instance of XlsToolExporter |
| XLS-002 | Implements FromArray interface | Interface check passes |

#### Export Method (XLS-010 to XLS-011)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| XLS-010 | export() returns valid XLS binary | Starts with 'PK' (zip signature) |
| XLS-011 | export() returns non-empty binary | String > 100 bytes |

#### Headings (XLS-020 to XLS-026)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| XLS-020 | Category column present | 'Category' in headings |
| XLS-021 | Indicator column present | 'Indicator' in headings |
| XLS-022 | Value column present | 'Value' in headings |
| XLS-023 | Status column present | 'Status' in headings |
| XLS-024 | Recommendation column present | 'Recommendation' in headings |
| XLS-025 | Correct column order | [Category, Indicator, Value, Status, Recommendation] |
| XLS-026 | 5 columns total | Count = 5 |

#### Data Rows (XLS-030 to XLS-036)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| XLS-030 | Data rows match input | Correct data in array |
| XLS-031 | Multiple rows exported | Count matches indicators |
| XLS-032 | Category values preserved | Categories in array |
| XLS-033 | Status values preserved | OK, Warning, Error |
| XLS-034 | Recommendation values preserved | Text in array |
| XLS-035 | Null value as dash | '-' for null |
| XLS-036 | Empty string as dash | '-' for empty |

#### Styling (XLS-040 to XLS-042)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| XLS-040 | Header row bold | `$styles[1]['font']['bold'] === true` |
| XLS-041 | Styles method returns array | Array type |
| XLS-042 | Style applies to row 1 | Key 1 exists |

#### Empty Data (XLS-050 to XLS-052)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| XLS-050 | Empty indicators handled | Valid XLS |
| XLS-051 | Empty array returned | Count = 0 |
| XLS-052 | Missing indicators key | Count = 0 |

#### Large Datasets (XLS-060 to XLS-062)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| XLS-060 | 100 indicators | Valid XLS |
| XLS-061 | Array count matches | 50 rows |
| XLS-062 | Very long text values | Valid XLS |

#### XSS Prevention (XLS-070 to XLS-072)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| XLS-070 | Script tags sanitized | Text preserved |
| XLS-071 | HTML entities handled | Text preserved |
| XLS-072 | Special characters safe | Characters preserved |

#### Unicode (XLS-080 to XLS-081)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| XLS-080 | Unicode characters | Valid XLS |
| XLS-081 | Emoji in data | Valid XLS |

#### Integration (XLS-090 to XLS-092)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| XLS-090 | Full export workflow | Valid XLS > 1000 bytes |
| XLS-091 | Consistent output | Same array for same input |
| XLS-092 | Different tools differ | Different arrays |

#### Edge Cases (XLS-100 to XLS-103)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| XLS-100 | All empty fields | 1 row with empty values |
| XLS-101 | Numeric values | Values preserved |
| XLS-102 | Boolean values | 'true'/'false' strings |
| XLS-103 | Mixed status values | OK, Warning, Error, Info |

---

### ToolExportService Tests (55 tests)

#### Service Instantiation (SES-001 to SES-003)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| SES-001 | Service instantiated | Instance of ToolExportService |
| SES-002 | PDF exporter dependency | PdfToolExporter instance |
| SES-003 | XLS exporter dependency | XlsToolExporter instance |

#### createExport() Method (SES-010 to SES-025)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| SES-010 | Returns download URL | URL with /api/v1/tools/export/download/ |
| SES-011 | Uses PDF exporter for PDF | PDF export called |
| SES-012 | Uses XLS exporter for XLS | XLS export called |
| SES-013 | Stores file in temp location | File in exports/ |
| SES-014 | Generates download token | 64-char token |
| SES-015 | Caches token data | Cache entry exists |
| SES-016 | Invalid format throws exception | InvalidArgumentException |
| SES-017 | Empty tool name handled | Valid URL |
| SES-018 | Empty URL handled | Valid URL |
| SES-019 | Empty data handled | Valid URL |
| SES-020 | Token expiry 1 hour | Cache TTL ~3600 |
| SES-021 | User ID stored in cache | Correct user_id |
| SES-022 | Tool name stored in cache | Correct tool |

#### downloadExport() Method (SES-030 to SES-050)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| SES-030 | Returns response for valid token | Response instance |
| SES-031 | PDF content type correct | application/pdf |
| SES-032 | XLS content type correct | spreadsheet MIME type |
| SES-033 | Invalid token throws 404 | NotFoundHttpException |
| SES-034 | Expired token throws 404 | NotFoundHttpException |
| SES-035 | Wrong user throws 404 | NotFoundHttpException |
| SES-036 | File deleted after download | File missing |
| SES-037 | Token deleted after download | Cache missing |
| SES-038 | Disposition header present | attachment in header |
| SES-039 | Filename contains tool name | Tool name in filename |
| SES-040 | PDF has .pdf extension | .pdf in filename |
| SES-041 | XLS has .xlsx extension | .xlsx in filename |

#### Token Generation (SES-060 to SES-065)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| SES-060 | Token is 64 characters | strlen = 64 |
| SES-061 | Token is alphanumeric | Regex match |
| SES-062 | Tokens are unique | 100 unique tokens |

#### File Management (SES-070 to SES-075)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| SES-070 | File in exports directory | exports/ prefix |
| SES-071 | Filename has timestamp | Date pattern |
| SES-072 | Multiple exports unique files | 2 unique files |

#### Security (SES-080 to SES-085)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| SES-080 | Path traversal prevented | No ../ in path |
| SES-081 | URL sanitized in filename | No script tags |
| SES-082 | User isolation enforced | 404 for wrong user |

#### Edge Cases (SES-090 to SES-100)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| SES-090 | Empty indicators handled | Valid URL |
| SES-091 | Large export data | Valid URL |
| SES-092 | Unicode tool name | Valid URL |
| SES-093 | Special chars in URL | Valid URL |
| SES-094 | Concurrent exports | 10 unique URLs |

#### Integration (SES-110 to SES-115)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| SES-110 | Full export workflow | URL → download → cleanup |
| SES-111 | Different formats | Different URLs |

## Mock Data

### ToolResult for PDF/XLS Export
```php
ToolResult::ok('Meta Tag Analyzer', 'https://example.com', [
    'indicators' => [
        ['category' => 'Meta Tags', 'name' => 'Title', 'value' => 'Example Page', 'status' => 'OK', 'recommendation' => '-'],
        ['category' => 'Meta Tags', 'name' => 'Description', 'value' => 'Too short', 'status' => 'Warning', 'recommendation' => 'Increase length to 150-160 characters'],
        ['category' => 'Content', 'name' => 'H1 Count', 'value' => '1', 'status' => 'OK', 'recommendation' => '-'],
    ],
    'summary' => ['score' => 85, 'pass' => 10, 'warning' => 3, 'fail' => 2],
    'recommendations' => [
        ['priority' => 'high', 'message' => 'Add meta description', 'action' => 'Write 150-160 characters'],
    ],
]);
```

### Export Request Payload
```json
{
  "tool": "meta-tag-analyzer",
  "url": "https://example.com",
  "format": "pdf",
  "data": {
    "analyzedAt": "2026-03-04T12:00:00Z",
    "results": {
      "title": { "content": "...", "length": 60, "status": "ok" },
      "description": { "content": "...", "length": 155, "status": "warning" }
    }
  }
}
```

## Coverage Targets

| Component | Target | Tests | Status |
|-----------|--------|-------|--------|
| PdfToolExporter | 100% | 73 | Written |
| XlsToolExporter | 100% | 72 | Written |
| ToolExportService | 100% | 55 | Written |
| **Total** | **100%** | **200** | Written |

## Test Execution

```bash
# Run all export tests
cd backend
php artisan test --filter="PdfToolExporterTest|XlsToolExporterTest|ToolExportServiceTest"

# Run individual test file
php artisan test --filter PdfToolExporterTest
php artisan test --filter XlsToolExporterTest
php artisan test --filter ToolExportServiceTest

# Run with coverage
php artisan test --filter="PdfToolExporterTest|XlsToolExporterTest|ToolExportServiceTest" --coverage
```

## Dependencies

### Implementation Dependencies (Blocked)
| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-29-1 (PdfToolExporter) | To Develop | Required for tests to pass |
| TASK-29-2 (XlsToolExporter) | To Develop | Required for tests to pass |
| TASK-29-3 (ToolExportService) | To Develop | Required for tests to pass |
| TASK-29-4 (Download Token System) | To Develop | Required for service tests |

### Test Dependencies (Available)
- PHPUnit - Test framework
- Laravel test infrastructure
- Mockery - Mocking library
- RefreshDatabase trait
- Storage facade (fake)
- Cache facade

## Notes for Developer

### TDD Approach
Tests are written before implementation. All tests currently fail because the classes don't exist yet. This is expected. Implementation tasks TASK-29-1, TASK-29-2, TASK-29-3 will make these tests pass.

### Expected Class Structure

#### PdfToolExporter
```php
class PdfToolExporter
{
    public const PAGE_WIDTH = 595;
    public const PAGE_HEIGHT = 842;
    public const MARGIN = 40;
    public const ROWS_PER_PAGE = 25;
    
    public function export(ToolResult $result, ?array $branding = null): string;
}
```

#### XlsToolExporter
```php
class XlsToolExporter implements FromArray, WithHeadings, WithStyles
{
    public function export(ToolResult $result): string;
    public function array(): array;
    public function headings(): array;
    public function styles(Worksheet $sheet): array;
}
```

#### ToolExportService
```php
class ToolExportService
{
    public function createExport(string $tool, string $url, string $format, array $data, int $userId): string;
    public function downloadExport(string $token, int $userId): Response;
    private function generateToken(): string;
}
```

### Security Considerations
- XSS prevention in PDF content
- Formula injection prevention in XLS
- Path traversal prevention in filenames
- User isolation in download tokens
- Token invalidation after download

## Acceptance Criteria Mapping

| Criteria | Test Coverage |
|----------|---------------|
| PdfToolExporter tests | 73 tests covering all criteria |
| XlsToolExporter tests | 72 tests covering all criteria |
| ToolExportService tests | 55 tests covering all criteria |
| All tests pass | Blocked by TASK-29-1, 29-2, 29-3, 29-4 |

## Comments
_Agent log_

### [2026-03-04 14:30 GMT+2] Agent: PreTester
**Tests Coverage Complete**

Created comprehensive test documentation for TASK-29-9:
- 73 PdfToolExporter tests (PDF generation, pagination, XSS prevention, branding)
- 72 XlsToolExporter tests (headings, data rows, styling, edge cases)
- 55 ToolExportService tests (export creation, token management, security)

**Test Status:**
- All test files exist and are well-structured
- Tests follow TDD pattern - will pass once implementations complete
- Dependencies: TASK-29-1, TASK-29-2, TASK-29-3, TASK-29-4 must be implemented

**Key Patterns:**
- ToolResult DTO for data transfer
- Mockery for mocking exporters in service tests
- Storage::fake() for file testing
- Cache facade for token testing

Status: `To Write Tests` → `To Develop`

Ready for Developer implementation of dependencies.
