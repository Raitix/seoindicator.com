# SPEC-004-6: Report Data Aggregation Service

## STATUS
`Done`

## PRIORITY
`Medium`

## RISK
`Medium`

## Related
- Feature: [FEAT-004](../FEATURES/FEAT-004.md)
- Specification: [SPEC-004](../SPECIFICATIONS/SPEC-004.md)
- Test: TESTS/SPEC-004-6.md

## Description
Create service to aggregate ranking data for reports. Collects keyword positions, changes, trends, and competitor data into a unified structure for exporters.

## Acceptance Criteria
- [ ] ReportDataAggregator service class
- [ ] aggregate(siteId, dateRange, compareRange) method
- [ ] Collects keyword rankings for date range
- [ ] Calculates position changes vs comparison period
- [ ] Identifies top performers (biggest gains)
- [ ] Identifies needs attention (biggest drops)
- [ ] Aggregates trend data for charts
- [ ] Optional: competitor comparison data (if FEAT-003 active)
- [ ] Returns structured ReportData object
- [ ] Unit tests with mock ranking data

## Dependencies
- FEAT-001 (keyword rankings data)
- FEAT-003 (optional: competitor data)

## Effort Estimate
`Medium` (4-6 hours)

## Technical Notes
- This is the "brain" of report generation
- Must efficiently query large datasets
- Consider caching for frequently accessed data
- Handle missing data gracefully (no rankings for date)

---

## Comments
_Agent communication log_

### [2026-02-20 10:30] Agent: SpecSystemAnalyst
Created as Phase 2 task. Critical for report content. Medium risk due to data complexity.

### [2026-02-22 18:36] Agent: Developer
- Added ReportDataAggregator service to compile keyword rankings, changes, top performers, needs-attention, and trend series
- Includes optional competitor summary when available

### [2026-02-22 23:55] Agent: LocalTester
- Set status to Testing Locally and reviewed implementation
- FAIL: required unit tests are missing for ReportDataAggregator (acceptance criteria requires mock ranking data tests)
- Created local test report: TESTS/SPEC-004-6.md
- Returning task to Developer for test coverage
