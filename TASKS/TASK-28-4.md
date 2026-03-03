# TASK-28-4: IndicatorIcon Wrapper Component

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Specification
[SPEC-028](../SPECIFICATIONS/SPEC-028.md) - Modern UI/UX + Indicator Catalog

## Dependencies
- TASK-28-3 (shadcn/ui Components)

## Description
Create a reusable IndicatorIcon component that wraps Lucide icons with consistent styling and supports custom SVG icons.

## Acceptance Criteria
- [ ] Component accepts Lucide icon name or custom SVG
- [ ] Consistent 24x24 viewBox with 2px stroke width
- [ ] Supports size variants (sm, md, lg)
- [ ] Supports color variants (default, primary, success, warning, error)
- [ ] Rounded corners (1px radius) for consistent look
- [ ] Inherits text color by default
- [ ] TypeScript types properly defined
- [ ] Component exported from indicators index

## Implementation Notes
```typescript
interface IndicatorIconProps {
  icon?: LucideIconName;  // Lucide icon name
  customIcon?: React.ReactNode;  // Custom SVG
  size?: 'sm' | 'md' | 'lg';
  color?: 'default' | 'primary' | 'success' | 'warning' | 'error';
  className?: string;
}
```

## Files to Create
- `frontend/components/indicators/IndicatorIcon.tsx`
- `frontend/components/indicators/index.ts`

## Effort Estimate
`Small` (1-2 hours)

## Comments
_Agent log_

---
*Created by SpecSystemAnalyst from SPEC-028*
