# FEAT-029: Export Results (PDF/XLS)

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Allow users to export SEO analysis results as PDF and XLS for reporting and sharing.

## User Story
As a user, I want to export my results for reports or client sharing.

## Acceptance Criteria
- [ ] PDF export of summary + indicators.
- [ ] XLS export of indicator table.
- [ ] Export available from results view.

## Notes
- Source: user ToDo (export).

## Comments
_Agent log_

### [2026-03-04 12:15 GMT+2] Agent: SpecSystemAnalyst
Created 10 tasks for PDF/XLS export functionality:
- Phase 1: Backend exporters and service (TASK-29-1 to 29-3)
- Phase 2: Security - token system and rate limiting (TASK-29-4 to 29-5)
- Phase 3: Frontend components and integration (TASK-29-6 to 29-8)
- Phase 4: Testing (TASK-29-9 to 29-10)

**High Risk Task:** TASK-29-4 (Download Token System) - security-sensitive, requires IDOR prevention.

Status: FEAT-029 and SPEC-029 → "To Prioritize"

### [2026-02-28 11:45 GMT+2] Agent: Architect
Created SPEC-029 with technical specification for PDF/XLS export functionality.

**Key Design Decisions:**
1. **Reuse Existing Infrastructure** - PdfToolExporter extends patterns from PdfReportExporter for consistency
2. **PhpSpreadsheet for XLS** - Industry standard via Laravel Excel package
3. **Token-Based Downloads** - One-time use tokens with 1-hour expiry for security
4. **Rate Limiting** - Integrated with existing ToolsRateLimitService (10 exports/hour)
5. **Max 500 Indicators** - Prevents memory issues with large exports

**Architecture:**
- 3 exporters: PdfToolExporter, XlsToolExporter, ToolExportService
- API: POST /api/v1/tools/export + GET /api/v1/tools/export/download/{token}
- Frontend: ExportResultsButton + ExportModal components

**Task Breakdown:** 10 tasks across 4 phases (~25 hours total)

Status updated to "To Break Into Tasks" for SpecSystemAnalyst.

---
*Created by Lead from individual user request.*