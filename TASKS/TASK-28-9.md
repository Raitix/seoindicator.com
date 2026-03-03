# TASK-28-9: Skeleton Loader Variants

## STATUS
`To Prioritize`

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

---
*Created by SpecSystemAnalyst from SPEC-028*
