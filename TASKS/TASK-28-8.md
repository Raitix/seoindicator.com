# TASK-28-8: Homepage Integration

## STATUS
`To Develop`

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

### [2026-03-03 12:58 UTC+2] ProjectManager
**Prioritization Complete**

**Priority Confirmed: High**
- Phase 2 CAPSTONE task for SPEC-028 Modern UI/UX
- Homepage is the primary user entry point - critical for first impressions
- Integrates IndicatorCatalog component into actual page context
- Depends on CSS Variables (design system foundation) and IndicatorCatalog (main container)

**Risk Confirmed: Medium**
- Modifies homepage (`frontend/app/page.tsx`) - main entry point
- No external APIs - uses existing project endpoints
- No database changes
- Layout changes could affect existing functionality
- Reversible with git rollback

**Dependency Analysis:**
| Dependency | Status | Impact |
|------------|--------|--------|
| TASK-28-7 (IndicatorCatalog) | `To Write Tests` | Component integration needed |
| TASK-28-1 (CSS Variables) | `To Develop` | Design system foundation |
| **Verdict:** | Dependencies progressing | Can proceed to test design |

**TDD Approach:**
- PreTester CAN design tests NOW using mocked IndicatorCatalog component
- Tests should cover: integration, layout, responsive breakpoints, animations, links
- Integration tests wait for IndicatorCatalog to reach `To Test Locally`

**Critical Role in Architecture:**
- This task is the **final integration point** for Phase 2
- All indicator components (Card, Grid, Catalog) flow into the homepage
- Hero section redesign affects user's first impression
- Gradient background and entrance animations enhance UX

**Test Design Notes for PreTester:**
1. **Integration Tests:**
   - IndicatorCatalog renders within homepage
   - Props passed correctly (indicators data, handlers)
   - Navigation links point to correct dashboard sections
2. **Layout Tests:**
   - Hero section: title, subtitle, CTA button, indicator catalog
   - Responsive breakpoints: mobile, tablet, desktop
   - No layout shift during load
3. **Animation Tests:**
   - Entrance animations trigger on mount
   - Smooth transitions (CSS transforms preferred)
   - Performance: GPU-accelerated animations
4. **Preservation Tests:**
   - Existing features grid still renders below indicators
   - No console errors on load
   - No hydration mismatches
5. **Accessibility Tests:**
   - Proper heading hierarchy (h1 for hero title)
   - CTA button accessible via keyboard
   - Color contrast meets WCAG 2.1 AA

**Acceptance Criteria for Tests:**
- IndicatorCatalog integrated and functional
- Hero section updated with modern design
- Gradient background applied
- Responsive layout for all breakpoints
- Entrance animations work smoothly
- Dashboard links functional
- No layout shift on load
- Existing features preserved

**Execution Order:**
- Tests can be designed NOW (TDD with mocks)
- Development waits for TASK-28-7 → `To Test Locally`
- Homepage integration is final step of Phase 2

---

### [2026-03-03 14:00 UTC+2] PreTester
**Test Design Complete**

**Tests Created:**
- `frontend/__tests__/pages/home-page.test.tsx` - 71 test scenarios
- `TESTS/TEST-28-8.md` - Test documentation

**Test Coverage:**
| Category | Tests | Description |
|----------|-------|-------------|
| Basic Rendering (HI-001) | 5 | Component mount, structure, sections |
| IndicatorCatalog Integration (HI-002) | 5 | Catalog in hero, data flow |
| Hero Section Design (HI-003) | 6 | h1, subtitle, CTA, gradient |
| Responsive Layout (HI-004) | 6 | Mobile/tablet/desktop breakpoints |
| Navigation Links (HI-005) | 6 | Dashboard, tools, comparison links |
| Entrance Animations (HI-006) | 4 | Animation classes, reduced motion |
| Performance Optimization (HI-007) | 5 | CLS, hydration, critical CSS |
| Existing Functionality (HI-008) | 8 | Features grid, status bar, footer |
| Accessibility (HI-009) | 7 | Heading hierarchy, focus, ARIA |
| CSS Variables Integration (HI-010) | 3 | Brand colors, surface layers |
| Error Handling (HI-011) | 2 | Missing data, loading states |
| Edge Cases (HI-012) | 5 | Long values, unicode, rapid renders |
| Snapshots (HI-013) | 3 | Visual regression baseline |
| TypeScript Types (HI-TS) | 2 | Return type, interface compatibility |

**Key Test Patterns:**
- Mocked IndicatorCatalog, Button, Input, Card components
- Mocked Next.js Link component for navigation testing
- Role-based queries for accessibility testing
- prefers-reduced-motion media query mock for animation testing
- Snapshot tests for visual regression baseline

**Dependencies Verified:**
- TASK-28-7 (IndicatorCatalog): ✅ Mocked in tests - interface defined
- TASK-28-1 (CSS Variables): ✅ Tests verify class usage

**Implementation Notes for Developer:**
1. Create `frontend/app/page.tsx` (modify existing)
2. Import IndicatorCatalog from `@/components/indicators`
3. Place IndicatorCatalog in hero section after h1/description
4. Apply gradient background using CSS variables or brand colors
5. Add entrance animations with `animate-*` or CSS transitions
6. Ensure all existing sections preserved (features, status, footer)
7. Implement responsive padding: `py-12 md:py-16 lg:py-20`
8. Test accessibility: one h1, ARIA landmarks, keyboard focus

**Hero Section Structure:**
```tsx
<section role="region" aria-label="Hero" className="...">
  <h1>Track Your SEO Performance</h1>
  <p>Description text...</p>
  <IndicatorCatalog indicators={indicators} />
  <div className="flex gap-4 justify-center">
    <Input placeholder="Enter your email" />
    <Button>Get Started</Button>
  </div>
</section>
```

**Status Changed:** `To Write Tests` → `To Develop`

---
*Created by SpecSystemAnalyst from SPEC-028*
