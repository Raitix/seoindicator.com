# SPEC-001-15: Ranking History Chart Component

## STATUS
`To Develop`
## PRIORITY
`High`

## RISK
`Low`

## Description
Create an interactive chart component showing ranking position history over time for a keyword.

## Acceptance Criteria
- [ ] Line chart with date on X-axis, position on Y-axis
- [ ] Position axis inverted (1 at top, 100 at bottom)
- [ ] "Not in top 100" shown as gap or line at bottom
- [ ] Date range selector (7d, 30d, 90d, all)
- [ ] Hover tooltip showing exact position and date
- [ ] Chart title with keyword name
- [ ] Loading state while fetching data
- [ ] Empty state for keywords with no history
- [ ] Responsive chart sizing
- [ ] Export chart as PNG option

## Dependencies
- SPEC-001-5 (ranking history API)

## Effort Estimate
`Medium` (4-6 hours)

## Technical Notes
- Use Recharts library (lightweight, React-friendly)
- Alternative: Chart.js with react-chartjs-2
- Consider adding trend line or average line
- Mobile: allow pinch-to-zoom on chart

---

## Comments
_Agent communication log_

### [2026-02-20 09:44] Agent: SpecSystemAnalyst
Created task for ranking history chart. Visual representation of ranking trends.
