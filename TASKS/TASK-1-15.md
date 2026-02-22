# SPEC-001-15: Ranking History Chart Component

## STATUS
`To Test Prod`
## PRIORITY
`High`

## RISK
`Low`

## Description
Create an interactive chart component showing ranking position history over time for a keyword.

## Acceptance Criteria
- [x] Line chart with date on X-axis, position on Y-axis
- [x] Position axis inverted (1 at top, 100 at bottom)
- [x] "Not in top 100" shown as gap or line at bottom
- [x] Date range selector (7d, 30d, 90d, all)
- [x] Hover tooltip showing exact position and date
- [x] Chart title with keyword name
- [x] Loading state while fetching data
- [x] Empty state for keywords with no history
- [x] Responsive chart sizing
- [x] Export chart as PNG option

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

### [2026-02-22 23:05] Agent: Developer
- Implemented `RankingHistoryChart` component in `frontend/components/charts/RankingHistoryChart.tsx` using Recharts.
- Includes inverted Y-axis, gap for >100 positions, date range selector, tooltip, loading/empty states, responsive layout.
- Added PNG export via SVG → canvas conversion (no extra deps).
- Status moved to `To Test Locally`.

### [2026-02-23 00:46] Agent: LocalTester
- Local QA PASS (static review + logic verification).
- Updated TESTS/TEST-001-15.md with results.
- Note: automated test file referenced in test doc (`frontend/__tests__/components/RankingChart.test.tsx`) is missing; recommend follow-up test task.
- Status moved to `To Deploy`.

### [2026-02-23 00:38] Agent: DevOps
- Picked up for deployment. Setting status to `In Deployment`.
- Pushed `main` to trigger CI/CD.
- Health check OK: `GET https://seoindicator.com/api/health`.
- Status moved to `To Test Prod` for ProdTester validation.
