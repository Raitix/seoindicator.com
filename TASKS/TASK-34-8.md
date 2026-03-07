# TASK-34-8: Frontend Project List Page

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Specification
SPEC-034: AI Visibility Tracking (Generative Engine Optimization)

---

## Description

Create the AI Visibility project list page showing all user's tracking projects.

---

## Acceptance Criteria

- [ ] Page created at `frontend/app/ai-visibility/page.tsx`

- [ ] Project list component displays
  - [ ] Card for each project showing: brand_name, is_active, last_queried_at, mention_rate
  - [ ] "Create Project" button prominently displayed
  - [ ] Empty state with call-to-action when no projects

- [ ] Project card actions
  - [ ] "View Dashboard" link to project detail
  - [ ] "Run Query Now" button (respects rate limit)
  - [ ] "Settings" link to edit project
  - [ ] "Delete" button with confirmation modal

- [ ] Filtering and sorting
  - [ ] Filter by active/inactive status
  - [ ] Sort by: created_at (newest), last_queried_at (recent), brand_name (A-Z)
  - [ ] Search by brand name

- [ ] Pagination
  - [ ] 10 projects per page
  - [ ] Infinite scroll or pagination controls

- [ ] Loading states
  - [ ] Skeleton cards during data fetch
  - [ ] Loading spinner on action buttons

- [ ] Error states
  - [ ] Error message if API fails
  - [ ] Retry button

- [ ] API integration
  - [ ] Uses `GET /api/v1/ai-visibility/projects`
  - [ ] Handles authentication errors
  - [ ] Shows query limit warning if approaching limit

- [ ] Responsive design
  - [ ] Mobile: 1 column cards
  - [ ] Tablet: 2 columns
  - [ ] Desktop: 3 columns

- [ ] Component tests created
  - [ ] Test rendering with projects
  - [ ] Test empty state
  - [ ] Test filtering/sorting
  - [ ] Test delete confirmation
  - [ ] Test responsive layouts

---

## Dependencies
- TASK-34-6 (API Endpoints)

---

## Effort Estimate
**3 hours**

---

## Technical Notes

- Use React Query (TanStack Query) for data fetching
- Use shadcn/ui Card component
- Consider using `useSWR` as alternative to React Query
- Implement optimistic updates for delete action

---

*Created by SpecSystemAnalyst on 2026-03-07*
