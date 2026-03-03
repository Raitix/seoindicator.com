# TEST-28-9: Skeleton Loader Variants Test Documentation

## Task Reference
- **Task:** [TASK-28-9](../TASKS/TASK-28-9.md)
- **Specification:** [SPEC-028](../SPECIFICATIONS/SPEC-028.md) - Modern UI/UX + Indicator Catalog
- **Dependency:** TASK-28-3 (shadcn/ui Components - skeleton)

---

## Overview

Comprehensive test suite for skeleton loader components used throughout the application for loading states. Tests cover:
- CardSkeleton (indicator cards)
- TableSkeleton (data tables)
- ChartSkeleton (chart placeholders)
- IndicatorCatalogSkeleton (catalog layout)

---

## Test Files

| File | Tests | Description |
|------|-------|-------------|
| `frontend/__tests__/components/ui/skeletons/SkeletonLoaders.test.tsx` | 130+ | Main test file for all skeleton components |

---

## Test Scenarios

### CardSkeleton Tests (CS-001 to CS-025)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CS-001 | Renders without crashing | - | Component in document |
| CS-002 | Has correct default structure | - | `.card-skeleton` element exists |
| CS-003 | Applies pulse animation | - | `animate-pulse` class on skeletons |
| CS-004 | Has rounded corners | - | `rounded` class present |
| CS-005 | Accepts custom className | `className="custom"` | Class applied to container |
| CS-006 | Shows icon placeholder by default | `showIcon: true` | `.icon-skeleton` exists |
| CS-007 | Hides icon when showIcon false | `showIcon: false` | No `.icon-skeleton` |
| CS-008 | Icon has correct dimensions | `showIcon: true` | Width/height classes present |
| CS-009 | Icon positioned correctly | `showIcon: true` | Flex/grid container |
| CS-010 | Multiple cards with different showIcon | Two cards | Correct icon count |
| CS-011 | Renders default 3 lines | - | 3 `.line-skeleton` elements |
| CS-012 | Renders specified line count | `lines: 5` | 5 line skeletons |
| CS-013 | Renders 0 lines | `lines: 0` | No line skeletons |
| CS-014 | Lines have varying widths | `lines: 4` | Different width classes |
| CS-015 | Lines have proper spacing | `lines: 3` | `space-y` or `gap` class |
| CS-016 | Applies muted background | - | `bg-muted` or `bg-gray` |
| CS-017 | Has border styling | - | `border` class |
| CS-018 | Dark mode classes | - | `dark:` prefixed classes |
| CS-019 | Shadow for depth | - | `shadow` class |
| CS-020 | Proper padding | - | `p-*` classes |
| CS-021 | Handles negative lines | `lines: -1` | 0 lines rendered |
| CS-022 | Handles large line count | `lines: 100` | 100 lines rendered |
| CS-023 | Handles undefined props | undefined props | Component renders |
| CS-024 | Handles null className | `className: null` | Component renders |
| CS-025 | Handles empty className | `className: ""` | Component renders |

### TableSkeleton Tests (TS-001 to TS-030)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TS-001 | Renders without crashing | - | Component in document |
| TS-002 | Has table structure | - | `table` or `[role="table"]` |
| TS-003 | Applies pulse animation | - | `animate-pulse` on all cells |
| TS-004 | Accepts custom className | `className="custom"` | Class applied |
| TS-005 | Has responsive overflow | - | `overflow` class |
| TS-006 | Renders default 5 rows | - | 6 rows (5 + 1 header) |
| TS-007 | Renders specified rows | `rows: 10` | 11 rows total |
| TS-008 | Renders 0 rows | `rows: 0` | 1 row (header only) |
| TS-009 | Handles large row count | `rows: 50` | 51 rows |
| TS-010 | Each row has correct cells | `rows: 3, columns: 4` | 4 cells per row |
| TS-011 | Renders default 4 columns | - | 4 cells in first row |
| TS-012 | Renders specified columns | `columns: 6` | 6 cells per row |
| TS-013 | Handles 0 columns | `columns: 0` | Component renders |
| TS-014 | Handles single column | `columns: 1` | 1 cell per row |
| TS-015 | Columns have appropriate widths | `columns: 4` | Header cells present |
| TS-016 | Shows header by default | - | `thead` present |
| TS-017 | Hides header when false | `showHeader: false` | No `thead` |
| TS-018 | Header cells have skeletons | `showHeader: true` | `th` elements present |
| TS-019 | Header has different styling | `showHeader: true` | Different class from body |
| TS-020 | Header not in rows count | `rows: 3` | 4 total rows |
| TS-021 | Has striped rows | `rows: 5` | Alternating styles |
| TS-022 | Has hover effects | - | `hover:` classes |
| TS-023 | Dark mode styling | - | `dark:` prefixed classes |
| TS-024 | Cell padding applied | `rows: 2` | `p-*` on cells |
| TS-025 | Border between cells | - | `border` or `divide` |
| TS-026 | Handles negative rows | `rows: -5` | 1 row (header) |
| TS-027 | Handles negative columns | `columns: -2` | Component renders |
| TS-028 | Handles fractional values | `rows: 2.5` | Component renders |
| TS-029 | Handles undefined props | undefined props | Component renders |
| TS-030 | Large table performance | `rows: 100, columns: 20` | < 1s render time |

### ChartSkeleton Tests (CHS-001 to CHS-030)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CHS-001 | Renders without crashing | - | Component in document |
| CHS-002 | Has chart container | - | `.chart-skeleton` exists |
| CHS-003 | Applies pulse animation | - | `animate-pulse` on skeletons |
| CHS-004 | Accepts custom className | `className="custom"` | Class applied |
| CHS-005 | Has aspect ratio | - | `aspect-*` or `w-full` |
| CHS-006 | Renders line chart default | - | Component renders |
| CHS-007 | Line chart has curve placeholder | `type: "line"` | SVG or chart area |
| CHS-008 | Line chart has axis placeholders | `type: "line"` | 2+ axis elements |
| CHS-009 | Line chart has grid lines | `type: "line"` | Grid elements present |
| CHS-010 | Line chart has data points | `type: "line"` | Point elements present |
| CHS-011 | Renders bar chart type | `type: "bar"` | Component renders |
| CHS-012 | Bar chart has vertical bars | `type: "bar"` | Bar elements present |
| CHS-013 | Bar chart has varying heights | `type: "bar"` | Multiple bars exist |
| CHS-014 | Bar chart has x-axis labels | `type: "bar"` | Label elements present |
| CHS-015 | Bar chart has y-axis labels | `type: "bar"` | Label elements present |
| CHS-016 | Renders pie chart type | `type: "pie"` | Component renders |
| CHS-017 | Pie chart has circular shape | `type: "pie"` | Circle/pie element |
| CHS-018 | Pie chart has segments | `type: "pie"` | Segment elements present |
| CHS-019 | Pie chart has legend | `type: "pie"` | Legend element present |
| CHS-020 | Pie chart centered | `type: "pie"` | Centering classes |
| CHS-021 | Has title placeholder | - | Title element present |
| CHS-022 | Dark mode support | - | `dark:` prefixed classes |
| CHS-023 | Background color applied | - | `bg-muted` or similar |
| CHS-024 | Border styling | - | `border` or `rounded` |
| CHS-025 | Proper padding | - | `p-*` classes |
| CHS-026 | Handles unknown type | `type: "unknown"` | Component renders |
| CHS-027 | Handles undefined type | `type: undefined` | Component renders |
| CHS-028 | Handles null className | `className: null` | Component renders |
| CHS-029 | Responsive width | - | `w-full` or `w-*` |
| CHS-030 | Minimum height | - | `h-*` or `min-h` |

### IndicatorCatalogSkeleton Tests (ICS-001 to ICS-030)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ICS-001 | Renders without crashing | - | Component in document |
| ICS-002 | Has catalog container | - | `.indicator-catalog-skeleton` |
| ICS-003 | Applies pulse animation | - | `animate-pulse` on children |
| ICS-004 | Accepts custom className | `className="custom"` | Class applied |
| ICS-005 | Has grid layout | - | `grid` or `flex` class |
| ICS-006 | Renders default 6 cards | - | 6 card skeletons |
| ICS-007 | Renders specified count | `count: 12` | 12 card skeletons |
| ICS-008 | Renders single card | `count: 1` | 1 card skeleton |
| ICS-009 | Renders 0 cards | `count: 0` | No card skeletons |
| ICS-010 | Handles large count | `count: 50` | 50 card skeletons |
| ICS-011 | Uses default columns | - | `grid-cols` present |
| ICS-012 | Accepts custom columns | `{ sm: 1, md: 2, lg: 3, xl: 4 }` | Responsive classes |
| ICS-013 | Single column layout | `{ default: 1 }` | `grid-cols-1` |
| ICS-014 | Responsive breakpoints | `{ sm: 2, md: 3, lg: 4 }` | Tailwind responsive prefixes |
| ICS-015 | Grid gap applied | - | `gap-*` class |
| ICS-016 | Uses CardSkeleton components | `count: 3` | 3 `.card-skeleton` |
| ICS-017 | Cards have consistent size | `count: 5` | `h-full` or `h-*` |
| ICS-018 | Cards look interactive | `count: 3` | `border`, `shadow`, `rounded` |
| ICS-019 | Cards have icon and lines | `count: 2` | Icon and line elements |
| ICS-020 | Cards match catalog layout | `count: 4` | Grid structure |
| ICS-021 | Has header skeleton | - | Header element present |
| ICS-022 | Has search/filter skeleton | - | Search/filter element |
| ICS-023 | Dark mode support | - | `dark:` prefixed classes |
| ICS-024 | Proper spacing | - | `space-y` or `gap` |
| ICS-025 | Container max width | - | `max-w-*` or `container` |
| ICS-026 | Handles negative count | `count: -5` | 0 cards |
| ICS-027 | Handles undefined count | `count: undefined` | Component renders |
| ICS-028 | Handles undefined columns | `columns: undefined` | Component renders |
| ICS-029 | Large count performance | `count: 200` | < 1s render time |
| ICS-030 | Fractional count handled | `count: 4.7` | 4 cards (floored) |

### Barrel Export Tests (BE-001 to BE-006)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BE-001 | CardSkeleton exported | - | Export defined |
| BE-002 | TableSkeleton exported | - | Export defined |
| BE-003 | ChartSkeleton exported | - | Export defined |
| BE-004 | IndicatorCatalogSkeleton exported | - | Export defined |
| BE-005 | All exports are valid components | - | typeof === 'function' |
| BE-006 | TypeScript types exported | - | Module defined |

### TypeScript Type Tests (TT-001 to TT-008)

| ID | Scenario | Type Check |
|----|----------|------------|
| TT-001 | CardSkeletonProps interface | `showIcon?: boolean; lines?: number; className?: string` |
| TT-002 | TableSkeletonProps interface | `rows?: number; columns?: number; showHeader?: boolean; className?: string` |
| TT-003 | ChartSkeletonProps interface | `type?: 'line' \| 'bar' \| 'pie'; className?: string` |
| TT-004 | ResponsiveColumns type | `default?, sm?, md?, lg?, xl?, 2xl?` (all number) |
| TT-005 | IndicatorCatalogSkeletonProps | `count?: number; columns?: ResponsiveColumns; className?: string` |
| TT-006 | ChartSkeleton type values | `'line' \| 'bar' \| 'pie'` |
| TT-007 | Default props values | Documented defaults |
| TT-008 | HTMLAttributes extension | Accepts className, id, data-testid |

### Accessibility Tests (AC-001 to AC-010)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| AC-001 | CardSkeleton aria-busy | `aria-busy="true"` |
| AC-002 | TableSkeleton aria-busy | `aria-busy="true"` |
| AC-003 | ChartSkeleton aria-busy | `aria-busy="true"` |
| AC-004 | IndicatorCatalogSkeleton aria-busy | `aria-busy="true"` |
| AC-005 | Skeletons have aria-label | Label present |
| AC-006 | Reduced motion respected | `animate-pulse` (Tailwind handles) |
| AC-007 | Not focusable (no tab stop) | `tabindex !== "0"` |
| AC-008 | TableSkeleton semantic elements | `table`, `thead`, `tbody` |
| AC-009 | ChartSkeleton appropriate role | `role` or `aria-label` |
| AC-010 | IndicatorCatalogSkeleton semantics | `role` or list semantics |

### Integration Tests (INT-001 to INT-005)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| INT-001 | CardSkeleton in IndicatorCatalogSkeleton | Correct card count |
| INT-002 | Multiple skeleton types on page | All render correctly |
| INT-003 | Multiple skeletons render efficiently | < 500ms for 50 cards |
| INT-004 | Re-render performance | < 100ms for 10 rerenders |
| INT-005 | Error handling | No throw on invalid props |

---

## Component Interfaces

```typescript
// CardSkeleton
interface CardSkeletonProps {
  showIcon?: boolean;    // Default: true
  lines?: number;        // Default: 3
  className?: string;
}

// TableSkeleton
interface TableSkeletonProps {
  rows?: number;         // Default: 5
  columns?: number;      // Default: 4
  showHeader?: boolean;  // Default: true
  className?: string;
}

// ChartSkeleton
interface ChartSkeletonProps {
  type?: 'line' | 'bar' | 'pie';  // Default: 'line'
  className?: string;
}

// IndicatorCatalogSkeleton
interface ResponsiveColumns {
  default?: number;
  sm?: number;
  md?: number;
  lg?: number;
  xl?: number;
  '2xl'?: number;
}

interface IndicatorCatalogSkeletonProps {
  count?: number;                    // Default: 6
  columns?: ResponsiveColumns;       // Default: responsive grid
  className?: string;
}
```

---

## Files to Create

| File | Description |
|------|-------------|
| `frontend/components/ui/skeletons/CardSkeleton.tsx` | Card loading placeholder |
| `frontend/components/ui/skeletons/TableSkeleton.tsx` | Table loading placeholder |
| `frontend/components/ui/skeletons/ChartSkeleton.tsx` | Chart loading placeholder |
| `frontend/components/ui/skeletons/IndicatorCatalogSkeleton.tsx` | Catalog loading placeholder |
| `frontend/components/ui/skeletons/index.ts` | Barrel export file |

---

## Coverage Targets

| Component | Target Coverage |
|-----------|-----------------|
| CardSkeleton | 100% |
| TableSkeleton | 100% |
| ChartSkeleton | 100% |
| IndicatorCatalogSkeleton | 100% |
| Barrel exports | 100% |
| TypeScript types | 100% |
| Accessibility | 100% |

---

## Test Execution

```bash
# Run all skeleton tests
npm test -- --testPathPattern="SkeletonLoaders"

# Run with coverage
npm test -- --coverage --testPathPattern="SkeletonLoaders"

# Run specific describe block
npm test -- --testNamePattern="CardSkeleton"
npm test -- --testNamePattern="TableSkeleton"
npm test -- --testNamePattern="ChartSkeleton"
npm test -- --testNamePattern="IndicatorCatalogSkeleton"
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-28-3 (shadcn/ui skeleton) | To Develop | Base Skeleton component required |
| @testing-library/react | Installed | Testing utilities |
| @testing-library/jest-dom | Installed | Jest DOM matchers |

---

## Notes for Developer

1. **Base Component:** Use shadcn/ui `Skeleton` component as base
2. **Animation:** All skeletons must use `animate-pulse` Tailwind class
3. **Dark Mode:** Include `dark:` prefixed classes for dark theme support
4. **Accessibility:** Add `aria-busy="true"` to all skeleton containers
5. **Performance:** Avoid heavy computations; use simple CSS animations
6. **Defaults:** Use sensible defaults (documented above)
7. **Responsive:** IndicatorCatalogSkeleton should match actual IndicatorCatalog grid layout
8. **Edge Cases:** Handle negative/fractional values gracefully
9. **TypeScript:** Export all interfaces from barrel file
10. **Barrel File:** Create `index.ts` that exports all components

---

## Comments
_Agent log_

### [2026-03-03 14:10 UTC+2] PreTester
**Test Design Complete**

Created comprehensive test suite for skeleton loader variants:
- **CardSkeleton:** 25 tests covering props, styling, edge cases
- **TableSkeleton:** 30 tests covering rows, columns, header, styling
- **ChartSkeleton:** 30 tests covering line/bar/pie types, styling
- **IndicatorCatalogSkeleton:** 30 tests covering count, columns, card integration
- **Barrel Exports:** 6 tests for module structure
- **TypeScript Types:** 8 tests for interface validation
- **Accessibility:** 10 tests for ARIA and semantic structure
- **Integration:** 5 tests for composition and performance

**Total:** 130+ test scenarios

**Key Patterns:**
- Mock base `Skeleton` component from shadcn/ui
- Test all prop combinations and defaults
- Verify pulse animation on all skeleton elements
- Test responsive grid layouts for IndicatorCatalogSkeleton
- Accessibility: `aria-busy="true"` on all containers
- Performance tests for large counts
- Edge case handling for invalid inputs

**Status Changed:** `To Write Tests` → `Tests Coverage In Progress`

Next: Developer implements components to pass tests.

---
*Created by PreTester from TASK-28-9*
