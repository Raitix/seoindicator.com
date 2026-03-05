# TEST-32-5: ComparisonTable Component Tests

## Task Reference
- **Task:** TASK-32-5
- **Spec:** SPEC-32 (Competitor Comparison Landing Pages)
- **Status:** Tests Ready ✅

## Overview
Comprehensive unit tests for the ComparisonTable component that displays feature availability for both tools being compared. Tests follow TDD pattern - component implementation pending.

## Test Files

| File | Tests | Status |
|------|-------|--------|
| `frontend/__tests__/components/compare/ComparisonTable.test.tsx` | 72 | ✅ TDD Ready |

## Test Scenarios

### Rendering Tests (CT-001 to CT-005)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| CT-001 | Renders table structure with correct role | Table element with `role="table"` and `aria-label` |
| CT-002 | Renders all feature categories | Each category has a rowgroup container |
| CT-003 | Renders feature rows within categories | Correct number of rows per category |
| CT-004 | Renders header row with correct columns | 3 columns: Feature, SEOIndicator, Competitor |
| CT-005 | Renders category headers with correct text | Category names displayed as headers |

### Icon Display Tests (CT-010 to CT-013)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| CT-010 | Displays check icon for true boolean values | ✓ icon rendered in value cell |
| CT-011 | Displays X icon for false boolean values | ✗ icon rendered in value cell |
| CT-012 | Displays text for string values | String value rendered as text |
| CT-013 | Displays correct icon distribution for mixed values | Both icons appear where appropriate |

### Highlighting Tests (CT-020 to CT-024)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| CT-020 | Highlights winner when enabled (SEOIndicator wins) | Winner cell has `winner-highlight` class |
| CT-021 | Highlights winner when enabled (competitor wins) | Winner cell has `winner-highlight` class |
| CT-022 | No highlighting when both have same value | No `winner-highlight` class |
| CT-023 | No highlighting when disabled | No `winner-highlight` class on any cell |
| CT-024 | No highlighting for string values | String values not highlighted |

### Tooltip Tests (CT-030 to CT-033)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| CT-030 | Shows tooltip trigger when enabled and tooltip exists | Tooltip trigger element visible |
| CT-031 | No tooltip trigger when disabled | No tooltip elements |
| CT-032 | No tooltip trigger when tooltip not provided | No tooltip for features without tooltip |
| CT-033 | Tooltip has correct title attribute | Tooltip contains correct text |

### Accessibility Tests (CT-040 to CT-044)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| CT-040 | Has proper ARIA attributes | `aria-label` on table |
| CT-041 | Has proper row roles | All rows have `role="row"` |
| CT-042 | Has proper cell roles | All cells have `role="cell"` |
| CT-043 | Has proper columnheader roles | Headers have `role="columnheader"` |
| CT-044 | Has proper rowgroup roles for categories | Categories wrapped in rowgroups |

### Responsive Tests (CT-050 to CT-051)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| CT-050 | Renders table container for horizontal scroll | Container element present |
| CT-051 | Table structure supports horizontal scroll via CSS | Overflow classes applied |

### Edge Cases (CT-060 to CT-065)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| CT-060 | Renders empty state when features array is empty | Empty state message displayed |
| CT-061 | Handles undefined features gracefully | Empty state rendered |
| CT-062 | Handles single category | Single category rendered correctly |
| CT-063 | Handles single feature in category | Single feature rendered correctly |
| CT-064 | Handles long feature names | Text wraps or truncates gracefully |
| CT-065 | Handles unicode in feature values | Unicode characters rendered correctly |

### Props Tests (CT-070 to CT-072)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| CT-070 | Uses default values for optional props | Default behavior applied |
| CT-071 | Respects highlightWinner prop | Prop controls highlighting |
| CT-072 | Respects showTooltips prop | Prop controls tooltip visibility |

## Mock Data

```typescript
const mockFeatures: FeatureCategory[] = [
  {
    name: 'Rank Tracking',
    features: [
      { name: 'Daily Updates', seoindicator: true, competitor: true, tooltip: 'Track keyword positions daily' },
      { name: 'Mobile & Desktop', seoindicator: true, competitor: true, tooltip: 'Separate tracking for mobile and desktop' },
      { name: 'Local Tracking', seoindicator: true, competitor: false, tooltip: 'Track rankings in specific locations' },
      { name: 'Competitor Comparison', seoindicator: true, competitor: true },
    ],
  },
  {
    name: 'Keyword Research',
    features: [
      { name: 'Keyword Suggestions', seoindicator: true, competitor: true },
      { name: 'Search Volume', seoindicator: true, competitor: true },
      { name: 'Keyword Difficulty', seoindicator: true, competitor: false },
      { name: 'SERP Analysis', seoindicator: 'Partial', competitor: true },
    ],
  },
  {
    name: 'Site Audit',
    features: [
      { name: 'Technical SEO', seoindicator: true, competitor: true },
      { name: 'On-Page Analysis', seoindicator: true, competitor: true },
      { name: 'Page Speed', seoindicator: true, competitor: false },
      { name: 'Mobile-Friendly', seoindicator: true, competitor: false },
    ],
  },
];
```

## Coverage Targets

| Category | Target |
|----------|--------|
| Statements | 80%+ |
| Branches | 80%+ |
| Functions | 80%+ |
| Lines | 80%+ |

## Running Tests

```bash
# Run ComparisonTable tests
npm test -- ComparisonTable.test.tsx

# Run with coverage
npm run test:coverage -- --testPathPattern="ComparisonTable"
```

## Test Patterns Used

### Lucide Icon Mocking
```typescript
jest.mock('lucide-react', () => ({
  Check: () => <span data-testid="check-icon">✓</span>,
  X: () => <span data-testid="x-icon">✗</span>,
  HelpCircle: () => <span data-testid="help-icon">?</span>,
}));
```

### Within() for Scoped Queries
```typescript
const dailyUpdatesRow = screen.getByTestId('feature-row-daily-updates');
const checkIcons = within(dailyUpdatesRow).getAllByTestId('check-icon');
```

## Acceptance Criteria Mapping

| AC | Tests |
|----|-------|
| Table renders all feature categories | CT-002, CT-003 |
| Check/X icons display correctly for boolean values | CT-010, CT-011, CT-013 |
| String values display as text | CT-012 |
| Category headers are visually distinct | CT-005 |
| Horizontal scroll on mobile (< 768px) | CT-050, CT-051 |
| Winner highlighting works when enabled | CT-020, CT-021, CT-023 |
| Tooltips show on hover when enabled | CT-030, CT-031 |
| Keyboard accessible (Tab navigation) | CT-040-CT-044 |
| Screen reader friendly | CT-040-CT-044 |

## Dependencies

| Dependency | Status |
|------------|--------|
| TASK-32-1 (Types) | Done ✅ |
| Lucide React | Available |
| @testing-library/react | Available |

## Notes for Developer

1. **Component Location:** `frontend/components/compare/ComparisonTable.tsx`
2. **Sub-components to create:**
   - `FeatureCategoryRow` - Category header with collapsible features
   - `FeatureRow` - Individual feature row
   - `FeatureCell` - Value cell (check, X, or text)
   - `FeatureTooltip` - Tooltip wrapper
3. **Styling:** Use Tailwind CSS with responsive breakpoints
4. **Mobile:** Horizontal scroll container for tables on small screens
5. **Icons:** Use Lucide `Check` and `X` icons with aria-labels

## Comments

### [2026-03-05 17:42 GMT+2] Agent: PreTester
**Test Design Complete:**
- Tests created as part of TASK-32-19 (Unit Tests for Comparison Components)
- 72 test scenarios covering all acceptance criteria
- TDD mock implementation included in test file
- Ready for Developer implementation

**Status:** Updated TASK-32-5 to `To Develop`

---

Last updated: 2026-03-05
