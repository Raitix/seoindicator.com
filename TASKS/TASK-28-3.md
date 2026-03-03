# TASK-28-3: Add shadcn/ui Components

## STATUS
`To Prioritize`

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

---
*Created by SpecSystemAnalyst from SPEC-028*
