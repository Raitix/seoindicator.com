# TASK-36-8: ContentDecayExportService

## STATUS
`To Prioritize`

## PRIORITY
`Medium`

## RISK
`Low`

## SPECIFICATION
SPEC-036: Content Decay Detection & Refresh Planner

---

## Description
Create the export service that generates CSV and PDF reports from the refresh queue and recovery outcomes.

## Dependencies
- TASK-36-2 (Eloquent Models) - To Prioritize
- FEAT-029 (Export Results) - Done ✅

## Acceptance Criteria
- [ ] `ContentDecayExportService` class created with:
  - `exportQueue(ContentDecayProject $project, string $format): string` method
  - `exportRecoveryReport(ContentDecayProject $project, string $format): string` method
  - `exportCandidateDetail(ContentDecayCandidate $candidate, string $format): string` method
- [ ] CSV export includes columns:
  - Page URL, Page Title, Page Type
  - Decay Score, Severity, Confidence
  - Primary Reason, All Reason Codes
  - Lost Clicks Estimate, Recovery Upside Estimate
  - Status, First Detected, Last Detected
  - Planned Date, Started Date, Refreshed Date, Recovered Date
- [ ] PDF export includes:
  - Summary section with totals
  - Detailed candidate list with metrics
  - Reason code breakdown chart
  - Recovery timeline
- [ ] Reuse existing PDF infrastructure from PdfReportExporter (FEAT-029)
- [ ] Reuse existing CSV infrastructure from XlsToolExporter (FEAT-029)
- [ ] Support filtering by status, severity, date range
- [ ] Unit tests verify CSV generation
- [ ] Unit tests verify PDF generation

## Effort Estimate
**4 hours** - Export functionality reusing existing infrastructure

## Technical Notes
### Reuse from FEAT-029
- `PdfToolExporter` patterns for PDF generation
- `XlsToolExporter` patterns for CSV generation
- Token-based download system

### PDF Template Structure
```
Page 1: Executive Summary
- Total candidates, Critical count, Lost clicks
- Recovery upside estimate
- Top 5 priority pages

Page 2+: Detailed Queue
- Full candidate list with metrics
- Reason code breakdown
- Status distribution

Appendix: Recovery Report
- Before/after comparison for recovered pages
- Click recovery timeline
```

## Files to Create/Modify
- `backend/app/Services/ContentDecay/ContentDecayExportService.php`
- `backend/resources/views/pdf/content-decay-queue.blade.php`
- `backend/resources/views/pdf/content-decay-recovery.blade.php`
- `backend/tests/Unit/Services/ContentDecay/ContentDecayExportServiceTest.php`

---

*Created by SpecSystemAnalyst agent on 2026-03-10*
