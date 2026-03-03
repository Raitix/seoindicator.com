# TASK-28-3: Add shadcn/ui Components

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## Specification
[SPEC-028](../SPECIFICATIONS/SPEC-028.md) - Modern UI/UX + Indicator Catalog

## Dependencies
- TASK-28-1 (CSS Variables Enhancement) - recommended but not required

## Description
Install additional shadcn/ui components needed for the UI overhaul: skeleton, progress, badge, tooltip, avatar, dropdown-menu, sheet, tabs, separator.

## Acceptance Criteria
- [ ] `skeleton` component installed and styled
- [ ] `progress` component installed and styled
- [ ] `badge` component installed and styled
- [ ] `tooltip` component installed and styled
- [ ] `avatar` component installed and styled
- [ ] `dropdown-menu` component installed and styled
- [ ] `sheet` component installed and styled
- [ ] `tabs` component installed and styled
- [ ] `separator` component installed and styled
- [ ] All components use dark theme styling
- [ ] Components integrate with existing Tailwind config

## Implementation Notes
```bash
npx shadcn@latest add skeleton
npx shadcn@latest add progress
npx shadcn@latest add badge
npx shadcn@latest add tooltip
npx shadcn@latest add avatar
npx shadcn@latest add dropdown-menu
npx shadcn@latest add sheet
npx shadcn@latest add tabs
npx shadcn@latest add separator
```

## Files to Create/Modify
- `frontend/components/ui/skeleton.tsx`
- `frontend/components/ui/progress.tsx`
- `frontend/components/ui/badge.tsx`
- `frontend/components/ui/tooltip.tsx`
- `frontend/components/ui/avatar.tsx`
- `frontend/components/ui/dropdown-menu.tsx`
- `frontend/components/ui/sheet.tsx`
- `frontend/components/ui/tabs.tsx`
- `frontend/components/ui/separator.tsx`

## Effort Estimate
`Small` (1-2 hours)

## Comments
_Agent log_

### [2026-03-03 12:28 UTC+2] ProjectManager
**Prioritization Complete**

**Priority Confirmed: High**
- Phase 1 foundation task for SPEC-028 Modern UI/UX
- Components installed here (skeleton, progress, badge, tooltip, avatar, dropdown-menu, sheet, tabs, separator) are used throughout the application
- Enables all subsequent phases (Indicator Catalog, Loading States, Navigation)

**Risk Confirmed: Low**
- Frontend-only: shadcn/ui component installation via npx
- No external APIs or database changes
- Components are tree-shakeable (no bundle bloat)
- Well-tested shadcn/ui library

**Dependency Analysis:**
- TASK-28-1 (CSS Variables Enhancement): Status `To Develop`
- Dependency is "recommended but not required" per task file
- Components work with existing Tailwind config
- Will integrate better with design system once TASK-28-1 completes
- **Verdict:** Can proceed independently; unblocked

**Execution Order:**
- Can run in parallel with TASK-28-1 (CSS Variables)
- Should complete before Phase 2 tasks (Indicator Catalog)
- Enables: TASK-28-4 through TASK-28-19 (most subsequent tasks)

**Test Design Notes for PreTester:**
1. **Component Existence Tests:**
   - Verify each component file exists in `frontend/components/ui/`
   - Verify component exports are correct
2. **Import Tests:**
   - Each component can be imported without errors
   - Component renders without crashing
3. **Dark Theme Tests:**
   - Components render correctly in dark mode
   - CSS variables/integration works
4. **Tailwind Integration:**
   - Components use Tailwind classes correctly
   - No styling conflicts with existing config
5. **Tree-shaking:**
   - Components are individually importable
   - No barrel file import issues

**Acceptance Criteria for Tests:**
- All 9 component files exist
- Each component exports correctly
- Components render in dark theme
- No TypeScript errors
- No console errors on load

---
*Created by SpecSystemAnalyst from SPEC-028*
