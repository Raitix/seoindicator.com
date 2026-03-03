# TASK-28-5: IndicatorCard Component

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## Specification
[SPEC-028](../SPECIFICATIONS/SPEC-028.md) - Modern UI/UX + Indicator Catalog

## Dependencies
- TASK-28-4 (IndicatorIcon Wrapper)
- TASK-28-3 (shadcn/ui Components - badge, tooltip)

## Description
Create the IndicatorCard component that displays individual SEO indicators with icon, title, value, status, and hover interactions.

## Acceptance Criteria
- [ ] Card displays icon, title, value, and description
- [ ] Status indicator (good/warning/critical) with color coding
- [ ] Hover animations (scale, subtle glow effect)
- [ ] Click navigates to relevant dashboard section
- [ ] Responsive design (works on mobile and desktop)
- [ ] Skeleton loading state supported
- [ ] Badge component for status display
- [ ] Tooltip on hover for additional info
- [ ] TypeScript types properly defined

## Implementation Notes
```typescript
interface IndicatorCardProps {
  id: string;
  icon: LucideIconName | React.ReactNode;
  title: string;
  value: string | number;
  description: string;
  status: 'good' | 'warning' | 'critical' | 'neutral';
  href: string;
  tooltip?: string;
  isLoading?: boolean;
}
```

### Indicator Types (Initial Set)
| Indicator | Icon | Description | Link |
|-----------|------|-------------|------|
| Keyword Rankings | TrendingUp | Track SERP positions | /dashboard |
| Visibility Score | Eye | Overall SEO visibility | /dashboard |
| Competitor Analysis | Users | Compare with competitors | /comparison |
| Technical Health | Wrench | Site audit score | /tools |
| Backlink Profile | Link | Link analysis | /dashboard |
| Content Opportunities | FileText | Keyword gaps | /dashboard |
| SERP Features | Star | Featured snippets, etc. | /dashboard |
| Page Speed | Zap | Performance metrics | /tools |

## Files to Create
- `frontend/components/indicators/IndicatorCard.tsx`

## Effort Estimate
`Medium` (2-4 hours)

## Comments
_Agent log_

### [2026-03-03 12:43 UTC+2] ProjectManager
**Prioritization Complete**

**Priority Confirmed: High**
- Phase 2 Indicator Catalog component for SPEC-028 Modern UI/UX
- IndicatorCard is a core display component used throughout the application
- Displays individual SEO indicators with icon, title, value, status, and interactions
- Enables 8 indicator types: Keyword Rankings, Visibility Score, Competitor Analysis, Technical Health, Backlink Profile, Content Opportunities, SERP Features, Page Speed
- High user-facing impact - primary dashboard visualization component

**Risk Confirmed: Low**
- Frontend-only React/TypeScript component
- No external APIs or database changes
- Uses existing Lucide icons and shadcn/ui components
- Standard component patterns with TypeScript types

**Dependency Analysis:**
- TASK-28-3 (shadcn/ui Components): Status `To Write Tests` - PreTester designing tests
  - Components: skeleton, badge, tooltip - all used by IndicatorCard
  - Badge for status display, Tooltip for hover info, Skeleton for loading state
- TASK-28-4 (IndicatorIcon Wrapper): Status `To Write Tests` - PreTester designing tests
  - IndicatorIcon component wraps Lucide icons for consistent styling
  - Direct dependency: IndicatorCard uses IndicatorIcon for icon display
- **Verdict:** Dependencies progressing; PreTester can design tests NOW using TDD approach with mocked interfaces

**Execution Order:**
- Can proceed to PreTester immediately (TDD with mocked interfaces)
- Should complete before Phase 3 tasks (Loading States, Responsive Layout)
- Enables: Dashboard pages, Analysis result displays

**Test Design Notes for PreTester:**
1. **Component Rendering Tests:**
   - Renders all 4 status variants (good/warning/critical/neutral)
   - Renders with icon (Lucide name or custom SVG)
   - Renders with loading state (skeleton)
2. **Interaction Tests:**
   - Click navigates to href
   - Hover shows tooltip
   - Hover animations (scale, glow effect)
3. **Accessibility Tests:**
   - Keyboard navigation support
   - ARIA labels for status
   - Focus states visible
4. **Responsive Tests:**
   - Mobile layout (stacked)
   - Desktop layout (side-by-side)
5. **TypeScript Tests:**
   - Props interface correctly typed
   - All optional props handled
6. **Integration Tests:**
   - Badge component renders status
   - Tooltip shows additional info
   - Skeleton shows when isLoading=true

**Acceptance Criteria for Tests:**
- All 4 status variants render correctly
- Loading state shows skeleton
- Click triggers navigation
- Hover shows tooltip
- Responsive breakpoints work
- TypeScript compilation succeeds
- No console errors on render

---
*Created by SpecSystemAnalyst from SPEC-028*
