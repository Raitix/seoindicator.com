# TASK-28-7: IndicatorCatalog Main Container

## STATUS
`To Prioritize`

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

---
*Created by SpecSystemAnalyst from SPEC-028*
