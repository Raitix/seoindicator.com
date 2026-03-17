# FEAT-028: Modern UI/UX + Indicator Catalog

## STATUS
`Done`

## PRIORITY
`High`

## RISK
`Medium`

## Related
- Specification: [SPEC-028](../SPECIFICATIONS/SPEC-028.md)

## Description
Redesign the UI with modern dark theme, playful indicator list on homepage, and custom indicator icons. Improve loading states and UX.

## User Story
As a user, I want a modern, clear interface that feels professional and easy to explore.

## Acceptance Criteria
- [ ] Dark mode design system.
- [ ] Responsive layout across mobile/tablet/desktop.
- [ ] Homepage indicator catalog with links.
- [ ] Icon for each indicator (custom set).
- [ ] Improved loader UX during analysis.
- [ ] Modern component library (e.g., Tailwind + shadcn/ui or equivalent).
- [ ] Visual assets plan (optionally generated via OpenClaw image tooling).

## Notes
- Source: user ToDo (modern UI + icons).
- User request: prioritize modern, beautiful, responsive UI; consider AI-generated design assets.

---

## Comments
_Agent log_

### [2026-02-28 09:28 UTC] Agent: Architect
Created technical specification SPEC-028. Key decisions:
1. Leverage existing shadcn/ui + Tailwind stack (no new dependencies)
2. Build on existing dark mode CSS variables already in globals.css
3. Use Lucide icons initially, custom SVG icons as enhancement
4. Mobile-first responsive approach with Tailwind breakpoints
5. No database changes needed
6. Integrate with existing API endpoints for indicator values

**Risk reassessed:** Remains Medium (frontend-only, design iteration may be needed).

Status updated to "To Break Into Tasks" for SpecSystemAnalyst.

### 2026-03-03 11:45 UTC+2 - SpecSystemAnalyst
- Created 19 tasks (TASK-28-1 through TASK-28-19) for SPEC-028
- Task breakdown across 7 phases:
  - Phase 1: Design System (Tasks 1-3)
  - Phase 2: Indicator Catalog (Tasks 4-8)
  - Phase 3: Loading States (Tasks 9-11)
  - Phase 4: Navigation (Tasks 12-13)
  - Phase 5: Icons (Tasks 14-15)
  - Phase 6: Accessibility (Tasks 16-17)
  - Phase 7: Testing & Documentation (Tasks 18-19)
- All tasks are frontend-only (no database changes)
- Ready for ProjectManager to prioritize

---
*Created by Lead from individual user request.*