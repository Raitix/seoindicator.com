# TASK-28-7: IndicatorCatalog Main Container

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Low`

## Specification
[SPEC-028](../SPECIFICATIONS/SPEC-028.md) - Modern UI/UX + Indicator Catalog

## Dependencies
- TASK-28-6 (IndicatorGrid)
- TASK-28-3 (shadcn/ui Components - tabs, separator)

## Description
Create the main IndicatorCatalog container component that orchestrates the display of all indicators with optional filtering, grouping, and refresh capabilities.

## Acceptance Criteria
- [ ] Container wraps IndicatorGrid with header and controls
- [ ] Section title and description configurable
- [ ] Optional category tabs for grouping indicators
- [ ] Loading state with skeleton grid
- [ ] Error state with retry option
- [ ] Empty state with helpful message
- [ ] Refresh capability (manual trigger)
- [ ] Responsive padding and margins
- [ ] TypeScript types properly defined

## Implementation Notes
```typescript
interface IndicatorCatalogProps {
  title?: string;
  description?: string;
  categories?: string[];
  selectedCategory?: string;
  onCategoryChange?: (category: string) => void;
  onRefresh?: () => void;
  isLoading?: boolean;
  error?: Error | null;
  indicators: IndicatorData[];
}

interface IndicatorData {
  id: string;
  icon: LucideIconName;
  title: string;
  value: string | number;
  description: string;
  status: 'good' | 'warning' | 'critical' | 'neutral';
  href: string;
  category?: string;
  tooltip?: string;
}
```

## Files to Create
- `frontend/components/indicators/IndicatorCatalog.tsx`

## Effort Estimate
`Medium` (2-4 hours)

## Comments
_Agent log_

### [2026-03-03 13:20 UTC+2] PreTester
**Test Design Complete**

**Tests Created:**
- `TESTS/TEST-28-7.md` - Test documentation (108 test scenarios)
- `frontend/components/indicators/__tests__/IndicatorCatalog.test.tsx` - Test file

**Test Categories:**
- TypeScript Type Definitions (2 tests) - Props and IndicatorData interfaces
- Component Rendering (9 tests) - Basic mount, title/description, className, role
- Title and Description (8 tests) - h2/p tags, conditional render, aria-labelledby
- Category Tabs (12 tests) - Tabs rendering, selection, keyboard nav, ARIA
- Loading State (10 tests) - Skeleton grid, disabled interactions, attributes
- Error State (9 tests) - Error message, retry button, accessibility
- Empty State (8 tests) - Empty message, icon, category-specific message
- Refresh Capability (7 tests) - Button rendering, callback, loading state
- Responsive Padding and Margins (8 tests) - Mobile/tablet/desktop padding
- Integration with IndicatorGrid (9 tests) - Grid rendering, filtering, props flow
- Integration with shadcn/ui Components (7 tests) - Tabs, Separator, Skeleton, Button
- Accessibility (10 tests) - ARIA roles, live regions, keyboard nav
- Edge Cases (10 tests) - Empty arrays, invalid categories, long text, rapid changes
- Module Exports (3 tests) - Barrel export, named export, type export
- Snapshots (8 tests) - Visual regression baseline

**Key Test Patterns:**
- Mocked IndicatorGrid, Tabs, Separator, Skeleton, Button components
- State priority testing: Loading > Error > Empty > Content
- Matrix testing for all state combinations
- Category filtering logic verification
- Accessibility-first approach with ARIA attributes

**Implementation Notes for Developer:**
1. Create `frontend/components/indicators/IndicatorCatalog.tsx`
2. Use `IndicatorGrid` component for indicator display (TASK-28-6)
3. Use `Tabs` from shadcn for category filtering (TASK-28-3)
4. Use `Separator` from shadcn for visual division (TASK-28-3)
5. Use `Skeleton` from shadcn for loading state (TASK-28-3)
6. Use `Button` from shadcn for refresh button (TASK-28-3)
7. Implement state priority: Loading > Error > Empty > Content
8. Filter indicators by `indicator.category` when category selected
9. Ensure `data-testid`, `data-loading`, `data-error`, `data-empty` attributes
10. Apply `focus-visible:ring-2` for keyboard focus indicator
11. Respect `prefers-reduced-motion` for animations
12. Default title: "SEO Indicators"

**State Priority Implementation:**
```
1. Loading → Show skeleton grid
2. Error → Show error message with retry
3. Empty → Show empty state message
4. Content → Show IndicatorGrid
```

**Category Filtering Logic:**
```typescript
const filteredIndicators = selectedCategory === 'All'
  ? indicators
  : indicators.filter(i => i.category === selectedCategory);
```

**Dependencies Verified:**
- TASK-28-6 (IndicatorGrid): ✅ To Develop - tests ready
- TASK-28-5 (IndicatorCard): ✅ To Develop - tests ready
- TASK-28-3 (shadcn/ui Components): ✅ To Develop - tests ready

**Status Changed:** `To Write Tests` → `To Develop`

---
*Handed off to Developer for implementation.*

### [2026-03-03 12:55 UTC+2] ProjectManager
**Prioritization Complete**

**Priority Confirmed: High**
- Phase 2 ORCHESTRATOR component for SPEC-028 Modern UI/UX
- IndicatorCatalog is the MAIN CONTAINER that integrates all indicator components
- Critical for homepage dashboard display - user's primary entry point
- Enables real-time SEO metric display with filtering/grouping capabilities

**Risk Confirmed: Low**
- Frontend-only React/TypeScript component
- No external APIs (uses existing project endpoints)
- No database changes
- TypeScript interfaces already defined in task description
- Pure composition of existing components (Grid, Card, shadcn/ui)

**Dependency Analysis:**
- TASK-28-6 (IndicatorGrid): Status `To Write Tests` ✅ Ready for parallel development
- TASK-28-3 (shadcn/ui tabs, separator): Status `To Develop` ✅ Further along
- **Verdict:** Dependencies satisfied - can proceed to test design

**Critical Role in Architecture:**
- IndicatorCatalog = Container + Controls
- Wraps IndicatorGrid (layout) which contains IndicatorCards (items)
- Provides: title, description, category tabs, loading/error/empty states, refresh
- Integrates with existing API endpoints (no new endpoints needed)

**Execution Order:**
- TASK-28-5 (Card) + TASK-28-6 (Grid) should reach `To Develop` first
- Catalog can start test design NOW using mocked sub-components
- Integration testing happens after Card and Grid are implemented

**Test Design Notes for PreTester:**
1. **Container Tests:**
   - Renders with title and description
   - Optional category tabs rendered correctly
   - Selected category state managed
2. **State Tests:**
   - Loading state shows skeleton grid
   - Error state shows retry button
   - Empty state shows helpful message
   - Refresh triggers onRefresh callback
3. **Integration Tests:**
   - Category change triggers onCategoryChange
   - Tabs use shadcn/ui Tabs component
   - Separator rendered between sections
4. **Accessibility Tests:**
   - Proper heading hierarchy (title, description)
   - Tab keyboard navigation
   - ARIA labels for interactive elements
5. **TypeScript Tests:**
   - IndicatorCatalogProps interface correctly typed
   - IndicatorData interface matches downstream components

**Acceptance Criteria for Tests:**
- Container wraps grid with header and controls
- Category tabs functional with selection state
- All 3 states (loading/error/empty) render correctly
- Refresh callback fires on button click
- Responsive padding/margins applied
- TypeScript compilation succeeds
- No console errors on render

---
*Created by SpecSystemAnalyst from SPEC-028*
