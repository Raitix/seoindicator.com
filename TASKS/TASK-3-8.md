# SPEC-003-8: Frontend - Historical Comparison Charts

## STATUS
`In Progress`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Create interactive charts showing ranking history over time for user vs competitors. Multi-line charts with interactive legend and tooltips.

## Acceptance Criteria
- [ ] Multi-line chart showing position over time
- [ ] User's line in primary color, competitors in distinct colors
- [ ] X-axis: dates, Y-axis: position (inverted - lower is better)
- [ ] Interactive legend to show/hide individual lines
- [ ] Hover tooltip showing exact position for each line
- [ ] Date range selector (7d, 30d, 90d, custom)
- [ ] Keyword selector dropdown
- [ ] Competitor filter (select which competitors to show)
- [ ] Responsive chart (scales on mobile)
- [ ] Loading state while fetching data

## Dependencies
- SPEC-003-5 (Historical comparison API must exist)

## Effort Estimate
`Medium` (4-6 hours)

## Technical Notes
- Use Recharts library (already in project or add via npm)
- Alternative: Chart.js with react-chartjs-2
- Position axis should be inverted (1 at top)
- Consider adding trend indicators

---

## Comments
_Agent communication log_

### [2026-02-20 09:54] Agent: PreTester
**Test Design Complete**

**Tests Created:**
1. `TESTS/SPEC-003-8.md` - Test documentation (28 test cases)
   - HistoricalChart (multi-line, colors, axis)
   - ChartLegend (toggle visibility)
   - ChartTooltip (hover, all values, null handling)
   - DateRangeSelector (presets, custom)
   - KeywordSelector (dropdown, selection)
   - CompetitorFilter (checkboxes, toggle)
   - Responsive design (mobile, touch)
   - Loading/error states
   - Performance (large date range, many competitors)

**Testing Framework Required:**
- Jest + React Testing Library (not yet installed)

**Developer Requirements:**
- Recharts or Chart.js library
- Inverted Y-axis (position 1 at top)

**Handoff:** → Developer for implementation

### [2026-02-20 00:42] Agent: ProjectManager
**Prioritization Complete**
- Priority: Medium ✓ (enhancement to dashboard, not critical path)
- Risk: Low ✓ (charting library, well-defined scope)
- Dependency: SPEC-003-5 (Historical comparison API)
- Can be developed in parallel with SPEC-003-7 (same API dependency)
- **Handoff:** → PreTester

### [2026-02-19 23:24] Agent: SpecSystemAnalyst
Created task for historical comparison charts. Enhances dashboard with visual trend analysis. Can be developed in parallel with comparison dashboard.
