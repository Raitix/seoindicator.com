# TASK-28-7: IndicatorCatalog Main Container

## STATUS
`To Write Tests`

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
