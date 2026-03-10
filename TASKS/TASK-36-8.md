# TASK-36-8: ContentDecayExportService

## STATUS
`To Write Tests`

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

## Comments

### ProjectManager (2026-03-10)
**Prioritization Complete**

**Priority: MEDIUM** (confirmed - already set by SpecSystemAnalyst)
- Export service task for reporting/sharing content decay data
- Leaf task in dependency tree - no downstream tasks blocked by this
- Reuses existing infrastructure from FEAT-029 (PdfToolExporter, XlsToolExporter patterns)
- Standard Laravel service implementation with no external API dependencies

**Risk: LOW** (confirmed - already set)
- Standard Laravel service with Blade templates for PDF
- No external APIs, no security-sensitive operations
- Reuses proven export patterns from FEAT-029
- CSV/PDF generation is well-documented and testable

**Dependencies Analysis:**
| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-36-2 (Eloquent Models) | `To Develop` | Tests complete (385+ tests), Developer implementation pending |
| FEAT-029 (Export Results) | `Done` ✅ | PDF/CSV infrastructure exists and functional |

**TDD Pattern Applied:**
- PreTester CAN design tests immediately using mocked model interfaces
- ContentDecayProject, ContentDecayCandidate models have defined interfaces
- Test categories: CSV generation, PDF generation, filtering, token-based downloads
- Implementation waits for TASK-36-2 to reach `To Test Locally`
- This enables PARALLEL development - test design proceeds with model implementation

**Export Architecture:**
| Export Type | Format | Content |
|-------------|--------|---------|
| Queue Export | CSV/PDF | Candidate list with metrics, severity, status |
| Recovery Report | CSV/PDF | Before/after comparison, timeline, outcomes |
| Candidate Detail | CSV/PDF | Single page deep-dive with all signals |

**Key Features:**
- Reuses PdfToolExporter patterns for PDF generation
- Reuses XlsToolExporter patterns for CSV generation
- Token-based download system (already from FEAT-029)
- Filtering by status, severity, date range
- Executive summary section in PDF (totals, top pages)

**Phase Context:**
- Phase 4 Backend - Export layer on top of detection services
- Depends on models (TASK-36-2) but NOT on detection services directly
- Can be developed in parallel with TASK-36-3 through TASK-36-7 (services)

**Status:** `To Write Tests` → Ready for PreTester

---

*Created by SpecSystemAnalyst agent on 2026-03-10*
