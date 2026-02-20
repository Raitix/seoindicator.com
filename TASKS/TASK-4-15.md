# SPEC-004-15: Logo Upload Handling

## STATUS
`To Prioritize`

## PRIORITY
`Low`

## RISK
`Medium`

## Related
- Feature: [FEAT-004](../FEATURES/FEAT-004.md)
- Specification: [SPEC-004](../SPECIFICATIONS/SPEC-004.md)
- Test: TESTS/SPEC-004-15.md

## Description
Implement logo upload functionality for report branding. Handles file validation, storage, and serving.

## Acceptance Criteria
- [ ] POST /api/v1/sites/:siteId/report-branding/logo - Upload logo
- [ ] DELETE /api/v1/sites/:siteId/report-branding/logo - Remove logo
- [ ] Accepts multipart/form-data with file field
- [ ] Supported formats: PNG, JPG, SVG
- [ ] Max file size: 2MB
- [ ] Validates image dimensions (recommended: 200x50 to 400x100)
- [ ] Stores file in storage/app/logos/
- [ ] Updates logo_url and logo_file in branding record
- [ ] Returns logo URL in response
- [ ] Integration tests

## Dependencies
- TASK-4-14 (Branding API)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Use Laravel's Storage facade
- Generate unique filename: site-{id}-{timestamp}.{ext}
- Consider image optimization for large files
- Validate SVG for security (no embedded scripts)

---

## Comments
_Agent communication log_

### [2026-02-20 10:30] Agent: SpecSystemAnalyst
Created as Phase 5 task. Post-MVP feature for white-labeling. Medium risk due to file handling security.
