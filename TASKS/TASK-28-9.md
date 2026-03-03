# TASK-28-9: Skeleton Loader Variants

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Low`

## Specification
[SPEC-028](../SPECIFICATIONS/SPEC-028.md) - Modern UI/UX + Indicator Catalog

## Dependencies
- TASK-28-3 (shadcn/ui Components - skeleton)

## Description
Create specialized skeleton loader components for different content types: cards, tables, charts, and the indicator catalog.

## Acceptance Criteria
- [ ] Base Skeleton component enhanced with animations
- [ ] CardSkeleton component (for indicator cards)
- [ ] TableSkeleton component (for data tables)
- [ ] ChartSkeleton component (for chart placeholders)
- [ ] IndicatorCatalogSkeleton component
- [ ] All skeletons use pulse animation
- [ ] Configurable row/column counts
- [ ] TypeScript types properly defined

## Implementation Notes
```typescript
// CardSkeleton
interface CardSkeletonProps {
  showIcon?: boolean;
  lines?: number;
  className?: string;
}

// TableSkeleton
interface TableSkeletonProps {
  rows?: number;
  columns?: number;
  showHeader?: boolean;
  className?: string;
}

// ChartSkeleton
interface ChartSkeletonProps {
  type?: 'line' | 'bar' | 'pie';
  className?: string;
}

// IndicatorCatalogSkeleton
interface IndicatorCatalogSkeletonProps {
  count?: number;  // Number of card skeletons
  columns?: ResponsiveColumns;
  className?: string;
}
```

## Files to Create
- `frontend/components/ui/skeletons/CardSkeleton.tsx`
- `frontend/components/ui/skeletons/TableSkeleton.tsx`
- `frontend/components/ui/skeletons/ChartSkeleton.tsx`
- `frontend/components/ui/skeletons/IndicatorCatalogSkeleton.tsx`
- `frontend/components/ui/skeletons/index.ts`

## Effort Estimate
`Medium` (2-4 hours)

## Comments
_Agent log_

### [2026-03-03 13:15 UTC+2] ProjectManager
**Prioritization Complete**

**Priority Confirmed: High**
- Skeleton loaders are essential for modern UX (improve perceived performance)
- Phase 2 task for SPEC-028 - foundation for loading states across the app
- Used by: IndicatorCatalog, data tables, charts, and card components

**Risk Confirmed: Low**
- Frontend-only: React components with Tailwind CSS
- No external APIs, no database changes
- Uses existing shadcn/ui `skeleton` component as base

**Dependency Analysis:**
- TASK-28-3 (shadcn/ui Components): Status `To Develop`
- **Partial Blocker for Implementation:** `skeleton` component not yet installed
- **TDD Ready:** PreTester CAN design tests NOW with mocked skeleton interface

**Component Hierarchy:**
```
skeleton (shadcn/ui base - TASK-28-3)
  └── CardSkeleton (this task)
  └── TableSkeleton (this task)
  └── ChartSkeleton (this task)
  └── IndicatorCatalogSkeleton (this task)
```

**Test Design Notes for PreTester:**
1. **CardSkeleton Tests:**
   - Renders with configurable `showIcon` prop
   - Renders with configurable `lines` count
   - Applies pulse animation class
   - Accepts custom className

2. **TableSkeleton Tests:**
   - Renders with configurable rows/columns
   - Shows/hides header row based on `showHeader` prop
   - Responsive column handling
   - Pulse animation applied

3. **ChartSkeleton Tests:**
   - Renders different types: line, bar, pie
   - Placeholder shapes match chart type
   - Pulse animation applied

4. **IndicatorCatalogSkeleton Tests:**
   - Renders configurable `count` of card skeletons
   - Responsive grid layout (ResponsiveColumns type)
   - Matches IndicatorCatalog layout

5. **Common Tests:**
   - All components export correctly from barrel file
   - TypeScript types are complete
   - Pulse animation class applied universally
   - Dark mode styling works

**Execution Order:**
- Tests can be designed NOW (TDD approach)
- Development waits for TASK-28-3 to reach `To Test Locally`
- After implementation: TASK-28-10 (IndicatorCatalog) can use these skeletons

### [2026-03-03 14:15 UTC+2] PreTester
**Test Design Complete**

Created comprehensive test suite for skeleton loader variants:
- **Test File:** `frontend/__tests__/components/ui/skeletons/SkeletonLoaders.test.tsx`
- **Documentation:** `TESTS/TEST-28-9.md`

**Test Coverage (130+ scenarios):**
1. **CardSkeleton (25 tests)** - Basic rendering, showIcon prop, lines prop, styling, dark mode, edge cases
2. **TableSkeleton (30 tests)** - Basic rendering, rows/columns props, showHeader prop, styling, edge cases
3. **ChartSkeleton (30 tests)** - Basic rendering, line/bar/pie types, styling, edge cases
4. **IndicatorCatalogSkeleton (30 tests)** - Basic rendering, count prop, columns prop, card integration, edge cases
5. **Barrel Exports (6 tests)** - Module structure verification
6. **TypeScript Types (8 tests)** - Interface validation
7. **Accessibility (10 tests)** - ARIA attributes, semantic structure, reduced motion
8. **Integration (5 tests)** - Composition, performance

**Key Test Patterns:**
- Mocked base `Skeleton` component from shadcn/ui
- All tests verify `animate-pulse` class on skeleton elements
- Accessibility tests require `aria-busy="true"` on containers
- Performance tests for large counts (< 1s for 100+ items)
- Edge case handling for negative/fractional/undefined values

**Status Changed:** `To Write Tests` → `To Develop`

Next: Developer implements components to pass tests. TASK-28-3 must be completed first (skeleton base component).

---
*Created by SpecSystemAnalyst from SPEC-028*
