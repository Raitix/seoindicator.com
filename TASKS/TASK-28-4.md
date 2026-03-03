# TASK-28-4: IndicatorIcon Wrapper Component

## STATUS
`To Write Tests`

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

### [2026-03-03 12:34 UTC+2] ProjectManager
**Prioritization Complete**

**Priority Confirmed: High**
- Phase 1 foundation component for SPEC-028 Modern UI/UX
- IndicatorIcon is a reusable wrapper used throughout the application
- Enables consistent iconography across all indicator components
- Dependency: TASK-28-3 (shadcn/ui Components) at `To Write Tests` - progressing through pipeline

**Risk Confirmed: Low**
- Frontend-only component creation
- No external APIs or database changes
- Uses existing Lucide icons library
- TypeScript types are standard React patterns

**Dependency Analysis:**
- TASK-28-3 (shadcn/ui Components): Status `To Write Tests`
- Components (skeleton, badge, tooltip) will be available when this task reaches development
- IndicatorIcon wraps Lucide icons - no hard dependency on shadcn components
- **Verdict:** Can proceed; unblocked for PreTester to design tests

**Execution Order:**
- Can proceed in parallel with TASK-28-3
- Should complete before Phase 2 tasks (Indicator Catalog components)
- Enables: TASK-28-5 through TASK-28-15 (indicator display components)

**Test Design Notes for PreTester:**
1. **Component Rendering Tests:**
   - Renders Lucide icon by name
   - Renders custom SVG via customIcon prop
   - Size variants render correctly (sm=16px, md=24px, lg=32px)
2. **Style Tests:**
   - Color variants apply correct classes
   - Default inherits text color
   - Rounded corners applied consistently
   - Stroke width is 2px
3. **TypeScript Tests:**
   - Props interface correctly typed
   - Icon name type matches Lucide icon names
4. **Export Tests:**
   - Component exported from indicators/index.ts

**Acceptance Criteria for Tests:**
- Component renders all size variants
- Component renders all color variants
- Custom SVG prop works correctly
- TypeScript compilation succeeds
- No console errors on render

---
*Created by SpecSystemAnalyst from SPEC-028*
