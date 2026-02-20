# SPEC-004-10: Template Management API

## STATUS
`To Prioritize`

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
- [ ] GET /api/v1/sites/:siteId/report-templates - List templates
- [ ] GET /api/v1/report-templates/:templateId - Get template details
- [ ] POST /api/v1/sites/:siteId/report-templates - Create template
- [ ] PUT /api/v1/report-templates/:templateId - Update template
- [ ] DELETE /api/v1/report-templates/:templateId - Delete template
- [ ] Request validation: name required, sections must be valid
- [ ] Authorization: user owns the site
- [ ] Integration tests

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
