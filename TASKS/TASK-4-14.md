# SPEC-004-14: Branding API

## STATUS
`To Test Locally`

## PRIORITY
`Low`

## RISK
`Low`

## Related
- Feature: [FEAT-004](../FEATURES/FEAT-004.md)
- Specification: [SPEC-004](../SPECIFICATIONS/SPEC-004.md)
- Test: TESTS/SPEC-004-14.md

## Description
Create REST API endpoints for report branding management. Get and update branding settings for white-label reports.

## Acceptance Criteria
- [x] GET /api/v1/sites/:siteId/branding - Get branding settings
- [x] PUT /api/v1/sites/:siteId/branding - Update branding settings
- [x] Request body: primaryColor, secondaryColor, fontFamily, companyName, companyWebsite
- [x] Creates branding record if not exists (first-time setup)
- [x] Authorization: user owns the site
- [x] Validation: colors must be valid hex (#RRGGBB)
- [x] Integration tests

## Dependencies
- TASK-4-1 (report_branding table)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- One branding record per site (unique constraint)
- Default colors: primary #2563eb, secondary #64748b
- Font options: Inter, Roboto, Open Sans, Lato

---

## Comments
_Agent communication log_

### [2026-02-20 10:30] Agent: SpecSystemAnalyst
Created as Phase 5 task. Post-MVP feature for white-labeling.

### [2026-03-12 19:52] Agent: Developer
Implementation already complete. Verified all acceptance criteria:

**Files:**
- `backend/app/Http/Controllers/Api/V1/ReportBrandingController.php` - Controller with show() and update() methods
- `backend/app/Models/ReportBranding.php` - Eloquent model with fillable attributes and getDefaults()
- `backend/app/Repositories/ReportBrandingRepository.php` - Repository for CRUD operations
- `backend/routes/api.php` - Routes registered under auth middleware
- `backend/tests/Feature/Api/V1/ReportBrandingControllerTest.php` - 31 tests (27 pass for SPEC-004-14)

**Endpoints:**
- `GET /api/v1/sites/{siteId}/branding` - Returns branding or defaults
- `PUT /api/v1/sites/{siteId}/branding` - Create/update branding

**Features Implemented:**
- Returns default branding values when no record exists (#2563eb, #64748b, Inter)
- Creates branding record on first PUT (updateOrCreate pattern)
- Authorization: 403 if user doesn't own site
- Validation: hex color format (#RRGGBB, case-insensitive)
- XSS protection: HTML stripped from companyName and fontFamily
- Cascade delete when site is deleted

**Test Results:**
- 27 tests pass for SPEC-004-14 (GET/PUT branding endpoints)
- 5 failing tests are for SPEC-004-15 (logo upload) - requires GD extension

**Note:** Route uses `/branding` instead of `/report-branding` for cleaner API design. Functionality is identical.
