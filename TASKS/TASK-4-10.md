# SPEC-004-10: Template Management API

## STATUS
`To Deploy`

## PRIORITY
`Medium`

## RISK
`Low`

## Related
- Feature: [FEAT-004](../FEATURES/FEAT-004.md)
- Specification: [SPEC-004](../SPECIFICATIONS/SPEC-004.md)
- Test: TESTS/SPEC-004-10.md

## Description
Create REST API endpoints for report template management. Create, list, update, and delete templates.

## Acceptance Criteria
- [x] GET /api/v1/sites/:siteId/report-templates - List templates
- [x] GET /api/v1/report-templates/:templateId - Get template details
- [x] POST /api/v1/sites/:siteId/report-templates - Create template
- [x] PUT /api/v1/report-templates/:templateId - Update template
- [x] DELETE /api/v1/report-templates/:templateId - Delete template
- [x] Request validation: name required, sections must be valid
- [x] Authorization: user owns the site
- [x] Integration tests

## Dependencies
- TASK-4-1 (report_templates table)
- TASK-4-3 (Template model/repository)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Follow existing API patterns
- Include default template creation on site creation (optional)
- Validate sections against allowed section names

---

## Comments
_Agent communication log_

### [2026-02-20 10:30] Agent: SpecSystemAnalyst
Created as Phase 3 task. Templates enable reusable report configurations.

### [2026-02-24 20:25] Agent: Developer
- Implemented ReportTemplateController with full CRUD
- GET /api/v1/sites/{siteId}/report-templates - List templates
- GET /api/v1/report-templates/{templateId} - Get template details
- POST /api/v1/sites/{siteId}/report-templates - Create template
- PUT /api/v1/report-templates/{templateId} - Update template
- DELETE /api/v1/report-templates/{templateId} - Delete template
- Validation: name required, sections must be valid (cover, summary, keywords, etc.)
- Branding validation: colors as hex (#RRGGBB), logoUrl as URL
- Default template logic: only one default per site
- Status: To Test Locally

### [2026-02-24 20:30] Agent: LocalTester
- Created feature test: tests/Feature/Api/V1/ReportTemplateControllerTest.php
- All 29 API endpoint tests PASS (90 assertions)
- All 41 ReportTemplate-related tests PASS (117 assertions)
  - 6 model tests (Unit/Models/ReportTemplateTest)
  - 6 repository tests (Unit/Repositories/ReportTemplateRepositoryTest)
  - 29 API controller tests (Feature/Api/V1/ReportTemplateControllerTest)
- Fixed XSS vulnerability: Added sanitizeString() to strip HTML tags from name, coverPageText, executiveSummary
- Test coverage includes:
  - CRUD operations (list, get, create, update, delete)
  - Validation (name required, sections required/valid, branding URL/color format)
  - Security (SQL injection, XSS protection)
  - Edge cases (empty sections, default template rules, ordering)
  - 404 handling for invalid site/template IDs
- Status: To Deploy
