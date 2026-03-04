# TEST-29-1: PdfToolExporter Tests

## Task Reference
- Task: [TASK-29-1](../TASKS/TASK-29-1.md)
- Specification: [SPEC-029](../SPECIFICATIONS/SPEC-029.md)
- Feature: [FEAT-029](../FEATURES/FEAT-029.md)

## Overview
Unit tests for `PdfToolExporter` class that generates PDF exports of tool analysis results with branded cover page, summary, indicators table, and recommendations.

## Test Files

| File | Type | Tests |
|------|------|-------|
| `backend/tests/Unit/Exporters/PdfToolExporterTest.php` | Unit | 72 |

## Test Scenarios

### Service Instantiation (PDF-001 to PDF-002)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| PDF-001 | Service instantiated | Instance of PdfToolExporter |
| PDF-002 | Constants defined | PAGE_WIDTH=595, PAGE_HEIGHT=842, MARGIN=40, ROWS_PER_PAGE=25 |

### Export Method (PDF-010 to PDF-013)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PDF-010 | Export returns valid PDF | ToolResult | Valid PDF string starting with %PDF-1.4 |
| PDF-011 | Export with branding | ToolResult + branding | PDF with custom colors/font |
| PDF-012 | Export without branding | ToolResult + null | PDF with default styling |
| PDF-013 | Export with empty branding | ToolResult + [] | PDF with default styling |

### Cover Page (PDF-020 to PDF-024)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PDF-020 | Cover page has tool name | ToolResult with toolName | PDF contains tool name |
| PDF-021 | Cover page has URL | ToolResult with URL | PDF contains domain |
| PDF-022 | Cover page has date | ToolResult | PDF contains year/date |
| PDF-023 | Cover page has logo placeholder | ToolResult, no branding | PDF contains "Logo" placeholder |
| PDF-024 | Cover page with branding | ToolResult + companyName | PDF contains company name |

### Summary Section (PDF-030 to PDF-035)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PDF-030 | Summary has overall score | score=85 | PDF contains "85" |
| PDF-031 | Summary has pass count | pass=10 | PDF contains "10" |
| PDF-032 | Summary has warning count | warning=5 | PDF contains "5" |
| PDF-033 | Summary has fail count | fail=3 | PDF contains "3" |
| PDF-034 | Summary with zero score | score=0 | Valid PDF generated |
| PDF-035 | Summary with perfect score | score=100, pass=15 | PDF contains "100" |

### Indicators Table (PDF-040 to PDF-045)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PDF-040 | Table renders categories | 2 categories | PDF contains both categories |
| PDF-041 | Table renders indicator names | 2 indicators | PDF contains both names |
| PDF-042 | Table renders status icons | pass/warning/fail | Status represented in output |
| PDF-043 | Table renders values | value="60 characters" | PDF contains value |
| PDF-044 | Indicators grouped by category | Mixed categories | Categories appear in output |
| PDF-045 | Empty indicators handled | [] indicators | Valid PDF generated |

### Page Breaks (PDF-050 to PDF-052)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PDF-050 | Page break after 25 indicators | 30 indicators | Multiple pages in PDF |
| PDF-051 | Large list spans pages | 100 indicators | 4+ pages in PDF |
| PDF-052 | Exactly 25 indicators | 25 indicators | Valid PDF generated |

### Recommendations Section (PDF-060 to PDF-063)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PDF-060 | Recommendations section renders | 1 recommendation | PDF contains "Recommendation" |
| PDF-061 | Recommendations sorted by priority | high/medium/low | All priorities present |
| PDF-062 | Empty recommendations handled | [] | Valid PDF generated |
| PDF-063 | Recommendations with actions | message + action | PDF contains action text |

### A4 Page Format (PDF-070 to PDF-071)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PDF-070 | A4 dimensions | ToolResult | MediaBox [0 0 595 842] |
| PDF-071 | Margins applied | ToolResult | Content at 40pt margin |

### XSS Prevention (PDF-080 to PDF-084)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PDF-080 | Script tags escaped | `<script>alert("XSS")</script>` | No `<script>` in PDF |
| PDF-081 | Special chars escaped | `Test (with parens)` | Valid PDF |
| PDF-082 | Backslashes escaped | `Test\\with\\backslash` | Valid PDF |
| PDF-083 | HTML entities safe | `<strong>Bold</strong>` | No `<strong>` in PDF |
| PDF-084 | JavaScript in URL safe | URL with `<script>` | No `<script>` in PDF |

### Branding (PDF-090 to PDF-095)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PDF-090 | Custom primary color | primaryColor="#10b981" | Valid PDF with color |
| PDF-091 | Custom secondary color | secondaryColor="#6b7280" | Valid PDF with color |
| PDF-092 | Custom font | fontFamily="Times" | /Times-Roman in PDF |
| PDF-093 | Company name | companyName="ACME Corp" | PDF contains "ACME Corp" |
| PDF-094 | All branding options | All options | All options applied |
| PDF-095 | Invalid color fallback | primaryColor="not-a-color" | Valid PDF with default |

### PDF Structure (PDF-100 to PDF-105)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PDF-100 | Catalog object | ToolResult | /Type /Catalog in PDF |
| PDF-101 | Pages object | ToolResult | /Type /Pages in PDF |
| PDF-102 | Font object | ToolResult | /Type /Font in PDF |
| PDF-103 | xref table | ToolResult | xref in PDF |
| PDF-104 | Trailer | ToolResult | trailer in PDF |
| PDF-105 | Page numbering | ToolResult | "Page X of Y" in PDF |

### Edge Cases (PDF-110 to PDF-115)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PDF-110 | Empty tool name | toolName="" | Valid PDF generated |
| PDF-111 | Very long URL | 500+ char URL | Valid PDF generated |
| PDF-112 | Unicode in indicators | 日本語, Описание | Valid PDF generated |
| PDF-113 | Emoji in content | 🎉 | Valid PDF generated |
| PDF-114 | Failed result | ToolResult::fail() | Valid PDF generated |
| PDF-115 | Null values | value=null | Valid PDF generated |

### Integration (PDF-120 to PDF-122)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PDF-120 | Full export all sections | Full ToolResult + branding | All content in PDF |
| PDF-121 | Consistent output | Same ToolResult twice | Identical PDFs (minus timestamps) |
| PDF-122 | Binary content | ToolResult | String > 100 bytes |

## Mock Data

### ToolResult with Full Data
```php
ToolResult::ok('Meta Tag Analyzer', 'https://example.com', [
    'indicators' => [
        ['category' => 'Meta Tags', 'name' => 'Title', 'value' => 'Example Page', 'status' => 'pass'],
        ['category' => 'Meta Tags', 'name' => 'Description', 'value' => 'A description', 'status' => 'warning'],
        ['category' => 'Content', 'name' => 'H1 Count', 'value' => '1', 'status' => 'pass'],
    ],
    'summary' => [
        'score' => 85,
        'pass' => 10,
        'warning' => 3,
        'fail' => 2,
    ],
    'recommendations' => [
        ['priority' => 'high', 'message' => 'Add meta description', 'action' => 'Write 150-160 characters'],
    ],
]);
```

### Branding Configuration
```php
[
    'primaryColor' => '#2563eb',    // Blue
    'secondaryColor' => '#64748b',  // Gray
    'fontFamily' => 'Inter',        // Maps to Helvetica
    'companyName' => 'ACME Corp',
    'logoFile' => 'logos/company.png',
]
```

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Service Instantiation | 100% | 2 |
| Export Method | 100% | 4 |
| Cover Page | 100% | 5 |
| Summary Section | 100% | 6 |
| Indicators Table | 100% | 6 |
| Page Breaks | 100% | 3 |
| Recommendations | 100% | 4 |
| A4 Format | 100% | 2 |
| XSS Prevention | 100% | 5 |
| Branding | 100% | 6 |
| PDF Structure | 100% | 6 |
| Edge Cases | 100% | 6 |
| Integration | 100% | 3 |
| **Total** | **100%** | **58** |

## Test Execution

```bash
# Run all tests
cd backend
php artisan test --filter PdfToolExporterTest

# Run specific test
php artisan test --filter "test_cover_page_has_tool_name"

# Run with coverage
php artisan test --filter PdfToolExporterTest --coverage
```

## Dependencies

- `App\Exporters\PdfToolExporter` - Class under test
- `App\Services\Tools\ToolResult` - DTO for tool results
- PHPUnit - Test framework
- Laravel test infrastructure

## Notes for Developer

### Expected Class Structure
```php
class PdfToolExporter
{
    private const PAGE_WIDTH = 595;   // A4 width in points
    private const PAGE_HEIGHT = 842;  // A4 height in points
    private const MARGIN = 40;        // Margin in points
    private const ROWS_PER_PAGE = 25; // Max indicator rows per page

    public function export(ToolResult $result, ?array $branding = null): string;
    protected function renderCoverPage(ToolResult $result, ?array $branding): array;
    protected function renderSummary(ToolResult $result): array;
    protected function renderIndicators(ToolResult $result): array;
    protected function renderIndicatorTable(array $indicators): array;
    protected function renderRecommendations(ToolResult $result): array;
    protected function assemblePdf(array $pages, ?array $branding): string;
}
```

### Key Implementation Points

1. **Reuses PdfReportExporter patterns** - Same PDF structure, different content
2. **Page breaks at 25 rows** - `ROWS_PER_PAGE` constant
3. **XSS prevention** - Escape `(`, `)`, `\` in all text
4. **A4 format** - 595x842 points with 40pt margins
5. **Branding support** - Colors, font, company name, logo

### PDF Content Sections
1. Cover Page (logo, tool name, URL, date)
2. Summary (score, pass/warning/fail counts)
3. Indicators Table (grouped by category)
4. Recommendations (sorted by priority)

### Status Icons
- `pass` → ✓ (checkmark)
- `warning` → ⚠ (warning sign)
- `fail` → ✗ (x mark)

## Comments
_Agent log_

### [2026-03-04 12:45 GMT+2] Agent: PreTester
**Tests Coverage Complete**

Created 72 unit tests for PdfToolExporter covering:
- Service instantiation and constants
- Export method with/without branding
- Cover page rendering (tool name, URL, date, logo)
- Summary section (score, counts)
- Indicators table (categories, values, status)
- Page breaks (25 rows per page)
- Recommendations section
- A4 page format and margins
- XSS prevention (text escaping)
- Full branding support
- PDF structure validation
- Edge cases (unicode, empty, failed results)
- Integration tests

Status: `Tests Coverage In Progress` → `To Develop`

Ready for Developer implementation.
