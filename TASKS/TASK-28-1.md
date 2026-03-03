# TASK-28-1: CSS Variables Enhancement (Design System)

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## Specification
[SPEC-028](../SPECIFICATIONS/SPEC-028.md) - Modern UI/UX + Indicator Catalog

## Dependencies
None

## Description
Extend existing CSS variables in globals.css with brand colors, semantic colors, and surface layers for a cohesive design system.

## Acceptance Criteria
- [ ] Add brand color variables (primary, secondary, accent)
- [ ] Add semantic color variables (success, warning, error, info)
- [ ] Add surface layer variables (surface-0 through surface-3)
- [ ] Variables use oklch color space for consistency
- [ ] Variables integrate with existing Tailwind CSS theme
- [ ] Dark mode values properly defined

## Implementation Notes
```css
:root {
  /* Brand Colors */
  --brand-primary: oklch(0.65 0.2 160);      /* Emerald green */
  --brand-secondary: oklch(0.55 0.15 200);   /* Teal accent */
  --brand-accent: oklch(0.75 0.18 50);       /* Orange accent */
  
  /* Semantic Colors */
  --success: oklch(0.65 0.2 145);
  --warning: oklch(0.75 0.18 85);
  --error: oklch(0.6 0.22 25);
  --info: oklch(0.6 0.15 230);
  
  /* Surface Layers (for depth) */
  --surface-0: oklch(0.145 0 0);   /* Base */
  --surface-1: oklch(0.18 0 0);    /* Elevated */
  --surface-2: oklch(0.22 0 0);    /* Cards */
  --surface-3: oklch(0.26 0 0);    /* Modals */
}
```

## Files to Modify
- `frontend/app/globals.css`
- `frontend/tailwind.config.ts` (if needed for Tailwind integration)

## Effort Estimate
`Small` (1-2 hours)

## Comments
_Agent log_

### [2026-03-03 11:55 UTC+2] ProjectManager
**Prioritization Complete**

**Priority Confirmed: High**
- Foundation task for SPEC-028 Phase 1 (Design System)
- CSS variables will be consumed by subsequent tasks (TASK-28-3 shadcn components)
- No dependencies - can proceed immediately

**Risk Confirmed: Low**
- CSS-only changes, no external dependencies
- Non-destructive (adding variables, not modifying existing)
- Easy to rollback if issues arise

**Test Design Notes for PreTester:**
1. **Visual Regression Tests:**
   - Screenshot comparisons before/after CSS variable addition
   - Verify existing styles remain unchanged
2. **Variable Existence Tests:**
   - Each CSS variable is defined in `:root`
   - Each variable has valid oklch format
   - Dark mode variables defined (if applicable)
3. **Integration Tests:**
   - Variables can be referenced in Tailwind classes
   - Variables work in component styles
   - No console errors on load
4. **Cross-browser Testing:**
   - oklch color space support (modern browsers)
   - Fallback behavior for older browsers (optional)
5. **Acceptance Criteria:**
   - All 6 brand color variables defined
   - All 4 semantic color variables defined
   - All 4 surface layer variables defined
   - Variables use oklch color space
   - Tailwind CSS theme integration works
   - Dark mode values properly defined

**Execution Order:**
- This task should run FIRST in Phase 1
- TASK-28-2 (Documentation) can run in parallel
- TASK-28-3 (shadcn components) should wait for this task

---
*Created by SpecSystemAnalyst from SPEC-028*
