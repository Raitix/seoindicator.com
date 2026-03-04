# TASK-29-1: PdfToolExporter Implementation

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Specification
[SPEC-029](../SPECIFICATIONS/SPEC-029.md) - Export Results (PDF/XLS)

## Description
Implement PdfToolExporter class to generate PDF exports of tool analysis results with branded cover page, summary, indicators table, and recommendations.

## Dependencies
- None (foundation task)

## Acceptance Criteria
- [ ] PdfToolExporter class created at `app/Exporters/PdfToolExporter.php`
- [ ] Generates multi-page PDF with:
  - Cover page (logo, tool name, URL analyzed, date)
  - Summary section (overall score, pass/fail/warning counts)
  - Indicators table (grouped by category, status icons)
  - Recommendations section (prioritized action items)
- [ ] Supports optional branding array (logo, colors, font)
- [ ] A4 page format with proper margins (40px)
- [ ] Automatic page breaks for long indicator lists (25 rows per page)
- [ ] Text properly escaped to prevent XSS in PDF
- [ ] Returns binary PDF content as string

## Technical Details
```php
class PdfToolExporter
{
    private const PAGE_WIDTH = 595;  // A4
    private const PAGE_HEIGHT = 842;
    private const MARGIN = 40;
    private const ROWS_PER_PAGE = 25;

    public function export(ToolResult $result, ?array $branding = null): string;
    protected function renderCoverPage(ToolResult $result, ?array $branding): array;
    protected function renderSummary(ToolResult $result): array;
    protected function renderIndicators(ToolResult $result): array;
    protected function renderIndicatorTable(array $indicators): array;
    protected function renderRecommendations(ToolResult $result): array;
    protected function assemblePdf(array $pages, ?array $branding): string;
}
```

## Files to Create/Modify
- `backend/app/Exporters/PdfToolExporter.php` (new)

## Effort Estimate
4 hours

## Comments
_Agent log_
