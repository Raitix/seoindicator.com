# SPEC-004-16: Branding in PDF Generation

## STATUS
`To Prioritize`

## PRIORITY
`Low`

## RISK
`Low`

## Related
- Feature: [FEAT-004](../FEATURES/FEAT-004.md)
- Specification: [SPEC-004](../SPECIFICATIONS/SPEC-004.md)
- Test: TESTS/SPEC-004-16.md

## Description
Integrate branding settings into PDF generation. Apply custom colors, fonts, and logo to generated reports.

## Acceptance Criteria
- [ ] PDFExporter accepts branding parameter
- [ ] Logo embedded in cover page (if uploaded)
- [ ] Custom primary/secondary colors applied to headers, borders
- [ ] Custom font applied to all text
- [ ] Company name displayed on cover page
- [ ] Fallback to defaults if branding not configured
- [ ] Unit tests for branded vs unbranded PDFs

## Dependencies
- TASK-4-5 (PDF Exporter)
- TASK-4-14 (Branding API)
- TASK-4-15 (Logo Upload)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- PDFKit supports custom fonts (register font files)
- Color values must be converted for PDF (hex to RGB)
- Handle missing logo gracefully (show placeholder or nothing)

---

## Comments
_Agent communication log_

### [2026-02-20 10:30] Agent: SpecSystemAnalyst
Created as Phase 5 task. Post-MVP feature for white-labeling.
