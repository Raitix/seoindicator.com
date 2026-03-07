# TASK-34-8: Frontend Project List Page

## STATUS
`To Write Tests`

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

## Comments

### ProjectManager Prioritization (2026-03-07 19:05 UTC)

**Priority: HIGH (confirmed)** ✅

**Rationale:**
- **Primary User Interface for GEO Feature** - Project list is the main entry point for AI visibility tracking
- **User-Facing Core Functionality** - Users cannot manage projects without this page
- **MVP Critical Path** - Part of Phase 1 MVP (Core Tracking) in SPEC-034
- **Enables Feature Discovery** - First page users see when accessing AI visibility feature
- **Downstream Foundation** - Understanding list page structure informs TASK-34-9 (Create Form), TASK-34-10 (Dashboard)

**Risk: LOW (confirmed)** ✅

**Rationale:**
- **Frontend-Only Implementation** - React/Next.js page, no external APIs directly
- **Standard Data Fetching** - React Query (TanStack Query) with well-documented patterns
- **Component Library** - Uses shadcn/ui Card, standard responsive design
- **No Security Concerns** - Authentication handled by API layer
- **Error Handling Built-In** - Loading skeletons, error states, retry buttons

**Dependency Status:**

| Dependency | Status | Ready |
|------------|--------|-------|
| TASK-34-6 (API Endpoints) | To Write Tests | ❌ NOT IMPLEMENTED |

**TDD Pattern Applied:**

PreTester CAN design tests immediately using mocked API responses:

1. **Mock `GET /api/v1/ai-visibility/projects`** - Define paginated response shape
2. **React Testing Library** - Component rendering with mocked fetch
3. **Test Categories:**
   - Project list rendering with projects data
   - Empty state with call-to-action
   - Project card actions (View Dashboard, Run Query, Settings, Delete)
   - Filtering (active/inactive status)
   - Sorting (created_at, last_queried_at, brand_name)
   - Search by brand name
   - Pagination controls (10 per page)
   - Loading skeleton states
   - Error states with retry
   - Delete confirmation modal
   - Responsive layouts (1/2/3 columns)

**Component Structure to Test:**

| Component | Purpose |
|-----------|---------|
| `ProjectList` | Main container with filtering/sorting |
| `ProjectCard` | Individual project display with actions |
| `EmptyState` | Call-to-action when no projects |
| `DeleteConfirmModal` | Confirmation dialog for deletion |
| `ProjectListSkeleton` | Loading placeholder cards |

**API Response Shape (from TASK-34-6):**
```json
{
  "data": [
    {
      "id": 1,
      "brand_name": "Acme Corp",
      "is_active": true,
      "last_queried_at": "2026-03-07T18:00:00Z",
      "mention_rate": 0.45
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 5,
    "per_page": 10
  }
}
```

**Responsive Design Requirements:**
- Mobile: 1 column cards
- Tablet: 2 columns
- Desktop: 3 columns

**Acceptance Criteria Summary:**
- 12 acceptance criteria covering: page creation, project cards, actions, filtering/sorting, pagination, loading/error states, API integration, responsive design, component tests

**Parallel Development:**
- PreTester designs tests NOW with mocked API responses
- Tests use MSW (Mock Service Worker) or Jest mocks for fetch
- Developer implements when TASK-34-6 reaches `To Test Locally`
- Integration tests run against real API when ready

**Key Lesson:** Frontend pages that serve as primary entry points for features should be HIGH priority even when their API dependencies are not yet implemented. TDD with mocked API responses enables parallel development - test design proceeds with backend implementation.

**Handoff to PreTester:** Ready for test design. Focus on:
- Mock API responses for paginated project list
- Component rendering with various data states
- User interactions (filter, sort, search, delete)
- Responsive layout testing
- Error handling and retry flows
- Delete confirmation modal behavior

---

*Created by SpecSystemAnalyst on 2026-03-07*
