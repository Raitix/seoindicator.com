# TASK-29-9: Backend Unit Tests

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Low`

## Specification
[SPEC-029](../SPECIFICATIONS/SPEC-029.md) - Export Results (PDF/XLS)

## Description
Create comprehensive unit tests for PdfToolExporter, XlsToolExporter, and ToolExportService.

## Dependencies
- TASK-29-1 (PdfToolExporter)
- TASK-29-2 (XlsToolExporter)
- TASK-29-3 (ToolExportService)
- TASK-29-4 (Download Token System)

## Acceptance Criteria
- [ ] Test files created:
  - `tests/Unit/Exporters/PdfToolExporterTest.php`
  - `tests/Unit/Exporters/XlsToolExporterTest.php`
  - `tests/Unit/Services/Tools/ToolExportServiceTest.php`
- [ ] PdfToolExporter tests:
  - Generates valid PDF binary
  - Cover page contains tool name and URL
  - Summary section renders correctly
  - Indicators table handles pagination (25+ items)
  - Recommendations section renders
  - Branding options applied
  - XSS prevention (malicious content escaped)
- [ ] XlsToolExporter tests:
  - Generates valid XLS binary
  - Headers match expected columns
  - Data rows match input data
  - Styling applied to header row
  - Handles empty data gracefully
- [ ] ToolExportService tests:
  - Creates export and returns download URL
  - Token validation succeeds for valid token
  - Token validation fails for invalid/expired token
  - User ID mismatch rejected (IDOR prevention)
  - Token invalidated after download
  - Rate limiting enforced
- [ ] All tests pass with `php artisan test`

## Technical Details
```php
class PdfToolExporterTest extends TestCase
{
    public function test_generates_valid_pdf_binary()
    {
        $result = $this->createMockToolResult();
        $exporter = new PdfToolExporter();
        
        $pdf = $exporter->export($result);
        
        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF', $pdf);
    }
    
    public function test_cover_page_contains_tool_name_and_url()
    {
        $result = new ToolResult(
            toolName: 'Meta Tag Analyzer',
            url: 'https://example.com',
            analyzedAt: now(),
            indicators: [],
            summary: [],
            recommendations: []
        );
        
        $exporter = new PdfToolExporter();
        $pdf = $exporter->export($result);
        
        // Parse PDF and verify content contains tool name and URL
        $this->assertStringContainsString('Meta Tag Analyzer', $pdf);
        $this->assertStringContainsString('example.com', $pdf);
    }
}
```

## Files to Create/Modify
- `backend/tests/Unit/Exporters/PdfToolExporterTest.php` (new)
- `backend/tests/Unit/Exporters/XlsToolExporterTest.php` (new)
- `backend/tests/Unit/Services/Tools/ToolExportServiceTest.php` (new)

## Effort Estimate
3 hours

## Comments
_Agent log_

### ProjectManager Prioritization (2026-03-04)

**Priority: HIGH (confirmed)**
- Testing task for export feature MVP - essential for code quality and reliability
- Unit tests validate core export functionality before deployment
- Low risk category (testing task, no production impact)

**Risk: LOW (confirmed)**
- Testing task only - no production code changes
- Standard PHPUnit test patterns
- No external API dependencies

**Dependency Analysis:**
| Dependency | Status | Implementation Status |
|------------|--------|----------------------|
| TASK-29-1 (PdfToolExporter) | To Develop | NOT IMPLEMENTED |
| TASK-29-2 (XlsToolExporter) | To Develop | NOT IMPLEMENTED |
| TASK-29-3 (ToolExportService) | To Develop | NOT IMPLEMENTED |
| TASK-29-4 (Download Token System) | To Develop | NOT IMPLEMENTED |

**TDD Approach:**
- PreTester CAN design tests NOW with mocked exporter interfaces
- Test design does NOT require implementations to exist
- Tests define expected behavior (contract-first approach)
- Implementation waits for all dependencies to reach `To Test Locally`

**Test Coverage Scope:**
1. **PdfToolExporterTest** (8+ test cases)
   - Valid PDF binary generation
   - Cover page content verification
   - Summary section rendering
   - Indicators table pagination (25+ items)
   - Recommendations section
   - Branding options
   - XSS prevention

2. **XlsToolExporterTest** (5+ test cases)
   - Valid XLS binary generation
   - Header row verification
   - Data row accuracy
   - Styling application
   - Empty data handling

3. **ToolExportServiceTest** (6+ test cases)
   - Export creation with URL generation
   - Token validation (valid/expired/invalid)
   - User ID mismatch rejection (IDOR prevention)
   - Token invalidation after download
   - Rate limiting enforcement

**Execution Order:**
- Tests designed now (TDD approach)
- Implementation waits for: TASK-29-1 → TASK-29-2 → TASK-29-3 → TASK-29-4
- All dependencies must reach `To Test Locally` before Developer can implement

**Status Update:** Moving to `To Write Tests` for PreTester to design test coverage.
