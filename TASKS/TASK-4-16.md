# SPEC-004-16: Branding in PDF Generation

## STATUS
`To Test Locally`

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
- [x] PDFExporter accepts branding parameter
- [x] Logo embedded in cover page (if uploaded)
- [x] Custom primary/secondary colors applied to headers, borders
- [x] Custom font applied to all text
- [x] Company name displayed on cover page
- [x] Fallback to defaults if branding not configured
- [x] Unit tests for branded vs unbranded PDFs

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

### [2026-03-12 20:05] Agent: Developer
**Implementation already complete.** Verified all acceptance criteria:

**Files:**
- `backend/app/Exporters/PdfReportExporter.php` - PDF exporter with full branding support
- `backend/app/Http/Controllers/Api/V1/ReportController.php` - Integrates branding in PDF generation
- `backend/tests/Unit/Exporters/PdfReportExporterTest.php` - 37 unit tests (11 branding-specific)

**Features Implemented:**
1. **Branding parameter**: `generate(array $report, array $data, ?array $branding = null)` accepts optional branding
2. **Logo embedding**: `getLogoData()` and `buildLogoXObject()` methods embed PNG/JPEG logos in PDF
3. **Custom colors**: `hexToRgb()` converts hex colors; applied in `buildContentStream()`
4. **Custom fonts**: `mapFontFamily()` maps font names to PDF core fonts (Inter/Roboto → Helvetica, Times → Times-Roman, Courier)
5. **Company name**: `buildCoverPage()` displays "Prepared for {company_name}" on cover
6. **Fallback defaults**: `normalizeBranding()` merges with defaults (primary: #2563eb, secondary: #64748b, font: Helvetica)
7. **Unit tests**: 11 branding-specific tests covering all scenarios

**Test Results:**
- 37 tests passed (104 assertions)
- All TEST-004-16 scenarios covered:
  - 16.1: PDF with branding - colors and font applied ✓
  - 16.2: PDF without branding - defaults used ✓
  - 16.3: Company name on cover page ✓
  - 16.4: Missing logo file - PDF renders without error ✓
  - 16.5: Invalid color - falls back to defaults ✓
- Edge cases: font fallback, hex-to-RGB conversion, empty branding, all sections

**Integration:**
- ReportController.fetches branding via `ReportBrandingRepository::findBySite()`
- Passes branding to PDF exporter when generating reports

Status: To Test Locally
