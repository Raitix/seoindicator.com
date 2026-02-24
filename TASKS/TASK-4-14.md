# SPEC-004-14: Branding API

## STATUS
`In Development`

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
- [ ] GET /api/v1/sites/:siteId/report-branding - Get branding settings
- [ ] PUT /api/v1/sites/:siteId/report-branding - Update branding settings
- [ ] Request body: primaryColor, secondaryColor, fontFamily, companyName, companyWebsite
- [ ] Creates branding record if not exists (first-time setup)
- [ ] Authorization: user owns the site
- [ ] Validation: colors must be valid hex (#RRGGBB)
- [ ] Integration tests

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
