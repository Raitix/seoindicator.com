# SPEC-004-4: CSV Exporter

## STATUS
`To Test Locally`

## PRIORITY
`Medium`

## RISK
`Low`

## Related
- Feature: [FEAT-004](../FEATURES/FEAT-004.md)
- Specification: [SPEC-004](../SPECIFICATIONS/SPEC-004.md)
- Test: TESTS/SPEC-004-4.md

## Description
Implement CSV export functionality for SEO reports. Generates downloadable CSV files with keyword ranking data.

## Acceptance Criteria
- [ ] CSVExporter class with generate(report, data) method
- [ ] CSV includes columns: Keyword, Current Position, Previous Position, Change, Search Engine, Location, Last Checked
- [ ] Proper CSV escaping for special characters (quotes, commas)
- [ ] UTF-8 encoding for international keywords
- [ ] Returns string content (for file download)
- [ ] Handles missing/null values gracefully
- [ ] Unit tests with various data scenarios

## Dependencies
- TASK-4-2 (Report model for data structure reference)

## Effort Estimate
`Small` (1-2 hours)

## Technical Notes
- No external dependencies needed (native PHP CSV functions)
- Use fputcsv for proper escaping
- Include BOM for Excel UTF-8 compatibility

---

## Comments
_Agent communication log_

### [2026-02-20 10:30] Agent: SpecSystemAnalyst
Created as Phase 2 task. CSV export is simpler than PDF, good starting point for exporters.

### [2026-02-22 18:30] Agent: Developer
- Added CsvReportExporter with UTF-8 BOM, header row, value normalization, and fputcsv escaping
