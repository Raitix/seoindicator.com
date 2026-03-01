# TASK-20-2: Create Guide Index Page Component

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Description
Create the guide index page at `/seo-guides/` that displays all 17 SEO guides in a grid layout with category filtering and search functionality.

## Acceptance Criteria
- [ ] Create `app/seo-guides/page.tsx` with guide index
- [ ] Grid layout showing all 17 guides
- [ ] Each guide card shows: icon, title, summary, read time, tool indicator
- [ ] Category filter tabs: All, On-Page, Technical, Content
- [ ] Search input filters guides by title
- [ ] Responsive grid (3 cols desktop, 2 cols tablet, 1 col mobile)
- [ ] "Has Tool" badge for guides with embedded tools
- [ ] Page meta tags for SEO
- [ ] Links to individual guide pages

## Dependencies
- TASK-20-1 (Guide data structure)

## Effort Estimate
`Small` (3-4 hours)

## Technical Notes
- Use Shadcn/UI Card component for guide cards
- Use Shadcn/UI Input for search
- Use Shadcn/UI Tabs for category filter
- Client-side filtering with useState
