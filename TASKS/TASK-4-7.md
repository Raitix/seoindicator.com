# SPEC-004-7: Reports CRUD API

## STATUS
`Passed Local Testing`

## PRIORITY
`Medium`

## RISK
`Low`

## Related
- Feature: [FEAT-004](../FEATURES/FEAT-004.md)
- Specification: [SPEC-004](../SPECIFICATIONS/SPEC-004.md)
- Test: TESTS/SPEC-004-7.md

## Description
Create REST API endpoints for reports management. List, view, and delete generated reports.

## Acceptance Criteria
- [ ] GET /api/v1/sites/:siteId/reports - List reports with pagination
- [ ] GET /api/v1/reports/:reportId - Get report details
- [ ] DELETE /api/v1/reports/:reportId - Delete report
- [ ] Query parameters: status, type, page, perPage
- [ ] Authorization: user owns the site
- [ ] Returns JSON with success/error responses
- [ ] Integration tests for all endpoints

## Dependencies
- TASK-4-1 (reports table)
- TASK-4-2 (Report model/repository)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Follow existing API patterns from FEAT-001/003
- Use Laravel resource controllers
- Include file_size in responses

---

## Comments
_Agent communication log_

### [2026-02-20 10:30] Agent: SpecSystemAnalyst
Created as Phase 3 task. Basic CRUD operations for report management.

### [2026-02-24 20:25] Agent: Developer
- Implemented ReportController with CRUD operations
- GET /api/v1/sites/{siteId}/reports - List with pagination, status/type filters
- GET /api/v1/reports/{reportId} - Get single report details
- DELETE /api/v1/reports/{reportId} - Delete report and associated file
- Added routes to api.php
- Status: To Test Locally

### [2026-02-28 12:15] Agent: LocalTester
**Test Results: All 29 tests passed (79 assertions)**

**Bug Found & Fixed:**
- Issue: `ReportBranding` model was looking for `report_brandings` table (Laravel default plural), but migration creates `report_branding` (singular)
- Fix: Added `protected $table = 'report_branding'` to ReportBranding model
- This caused PDF report generation tests to fail with 500 error

**Test Categories:**
- Authentication tests (401 for unauthenticated users)
- Authorization tests (403 for other users' resources)
- CRUD operations (list, show, delete)
- Report generation (PDF, CSV)
- Download functionality
- Validation (required fields, date range, sections)
- Edge cases (missing files, pending reports)

**Files Modified:**
- `app/Models/ReportBranding.php` - Added explicit table name

Status: Ready for DevOps deployment
