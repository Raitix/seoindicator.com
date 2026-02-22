# SPEC-004-3: Template Model and Repository

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Low`

## Related
- Feature: [FEAT-004](../FEATURES/FEAT-004.md)
- Specification: [SPEC-004](../SPECIFICATIONS/SPEC-004.md)
- Test: TESTS/SPEC-004-3.md

## Description
Create ReportTemplate model with Eloquent relationships and repository pattern. Handles reusable report configurations.

## Acceptance Criteria
- [ ] ReportTemplate model with fillable attributes: site_id, name, is_default, sections, branding, cover_page_text, executive_summary
- [ ] ReportTemplate belongs to Site relationship
- [ ] ReportTemplate casts sections and branding as array (JSON)
- [ ] ReportTemplate has makeDefault method to set as default template
- [ ] TemplateRepository with methods: create, find, findBySite, findDefault, update, delete
- [ ] Only one default template per site (enforced)
- [ ] Unit tests for model and repository

## Dependencies
- TASK-4-1 (report_templates table migration)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Default template logic: when setting is_default=true, unset other defaults for same site
- Allow site_id=null for global templates (future feature)

---

## Comments
_Agent communication log_

### [2026-02-22 23:10] Agent: LocalTester
- Set status to Testing Locally and ran `php artisan test --filter=ReportTemplate` (backend). Result: **No tests found**.
- Created TESTS/SPEC-004-3.md with failed test report (missing unit tests).
- Potential gap: default-template enforcement only in `makeDefault()`; create/update with `is_default=true` does not unset other defaults (needs tests and/or logic enforcement).
- Returning to Developer. Status set back to **To Develop**.

### [2026-02-20 10:30] Agent: SpecSystemAnalyst
Created as Phase 1 task. Templates enable reusable report configurations.

### [2026-02-22 18:30] Agent: Developer
- Added ReportTemplate model with casts, site relationship, and makeDefault() helper
- Added ReportTemplateRepository with CRUD + findDefault
