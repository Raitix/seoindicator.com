# TASK-28-8: Homepage Integration

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Specification
[SPEC-028](../SPECIFICATIONS/SPEC-028.md) - Modern UI/UX + Indicator Catalog

## Dependencies
- TASK-28-7 (IndicatorCatalog)
- TASK-28-1 (CSS Variables)

## Description
Integrate the IndicatorCatalog component into the homepage, replacing or enhancing the existing hero section with the new indicator display.

## Acceptance Criteria
- [ ] IndicatorCatalog integrated into homepage
- [ ] Hero section updated with modern design
- [ ] Gradient background using new brand colors
- [ ] Responsive layout for all breakpoints
- [ ] Smooth entrance animations
- [ ] Links to dashboard sections work correctly
- [ ] Performance optimized (no layout shift)
- [ ] Existing functionality preserved

## Implementation Notes
- Consider keeping existing features grid below indicators
- Hero should have: title, subtitle, CTA button, indicator catalog
- Use surface layer variables for depth

## Files to Modify
- `frontend/app/page.tsx`

## Effort Estimate
`Medium` (2-4 hours)

## Comments
_Agent log_

---
*Created by SpecSystemAnalyst from SPEC-028*
