# SPEC-003-8: Frontend - Historical Comparison Charts

## STATUS
`In Deployment`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Create interactive charts showing ranking history over time for user vs competitors. Multi-line charts with interactive legend and tooltips.

## Acceptance Criteria
- [x] Multi-line chart showing position over time
- [x] User's line in primary color, competitors in distinct colors
- [x] X-axis: dates, Y-axis: position (inverted - lower is better)
- [x] Interactive legend to show/hide individual lines
- [x] Hover tooltip showing exact position for each line
- [x] Date range selector (7d, 30d, 90d)
- [x] Keyword selector dropdown
- [x] Competitor filter (select which competitors to show)
- [x] Responsive chart (scales on mobile)
- [x] Loading state while fetching data

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

### [2026-03-18 10:10] Agent: LocalTester
**Local Testing Complete - PASS**

**Test Results:**
- All 52 unit tests passed for HistoricalComparisonChart component
- Test suite: `frontend/__tests__/components/charts/HistoricalComparisonChart.test.tsx`
- Duration: 1.23s

**Test Coverage:**
- Rendering (10 tests): Card, title, description, responsive container, line chart, selectors
- Multi-line Chart (5 tests): User line color, competitor colors, Y-axis inverted, X-axis dates
- Date Range Selector (4 tests): 7d/30d/90d presets, API parameter updates
- Keyword Selector (4 tests): Dropdown, selection, initialKeywordId prop
- Competitor Filter (5 tests): Checkboxes, toggle visibility, displayName fallback
- Loading & Error States (5 tests): Spinner, empty state, error message, retry button
- Trend Indicator (4 tests): Improved, dropped, no change, insufficient data
- Null Position Handling (2 tests): Graceful null handling, no trend with null
- Performance (3 tests): Large dataset, 90 days, multiple competitors
- Accessibility (3 tests): Heading structure, checkbox accessibility, labels
- Props Validation (5 tests): className, empty arrays, initialDateRange
- Integration (2 tests): API parameters, hook integration

**Build Verification:**
- `npm run build` succeeded
- No TypeScript errors in component

**Pre-existing Project Issues (not task-related):**
- TypeScript errors exist in other files (content-decay pages, backlinks tests, etc.)
- These are unrelated to the HistoricalComparisonChart component

**Handoff:** → DevOps for deployment

### [2026-03-18 09:50] Agent: Developer
**Implementation Complete**

**Files Modified:**
1. `frontend/components/charts/HistoricalComparisonChart.tsx` - Main component
   - Fixed competitor line visibility (lines stay in DOM, use `hide` prop)
   - Added accessible loading state with `role="status"` and sr-only text
   - Uses Recharts library (already in project)
   - Y-axis inverted with `reversed` prop
   - Custom tooltip with full date and position display
   - Custom legend with toggle functionality

2. `frontend/__tests__/components/charts/HistoricalComparisonChart.test.tsx` - Unit tests
   - Fixed test expectations for empty keywords array (mock returns null data)
   - Fixed loading state test (check for "Loading..." text)
   - All 52 tests passing

**Features Implemented:**
- Multi-line chart with user (blue #2563eb) and competitors (distinct colors)
- Inverted Y-axis (position 1 at top)
- Interactive legend to toggle line visibility
- Custom tooltip showing exact positions on hover
- Date range selector (7d, 30d, 90d buttons)
- Keyword selector dropdown
- Competitor filter checkboxes
- Responsive container (Recharts ResponsiveContainer)
- Loading spinner with accessible label
- Error state with retry button
- Empty state with helpful messages
- Trend indicator showing position change over period

**API Integration:**
- Uses `useComparisonHistory` hook from `lib/api/comparison-history.ts`
- Backend endpoint: `/api/v1/rankings/comparison/history` (ComparisonController::history)
- Supports keywordId, days, and competitorId query parameters

**Handoff:** → LocalTester

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
