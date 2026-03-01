# TASK-20-2: Create Guide Index Page Component

## STATUS
`To Write Tests`

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

## Comments
### [2026-03-01 11:53 GMT+2] ProjectManager - Prioritization
**Priority Confirmed: High**

**Rationale:**
- Foundation task for SPEC-020 Phase 1 (alongside TASK-20-1, 20-3, 20-4)
- Creates the main entry point for the SEO Guides Library
- UI component with no external dependencies beyond TASK-20-1

**Dependency Status:**
- TASK-20-1 (Guide data structure): `To Develop` - in progress
- This task can be prioritized now, but PreTester should wait for TASK-20-1 to reach `To Test Locally` before test design

**Phase 1 Parallel Execution:**
| Task | Description | Status | Can Start |
|------|-------------|--------|-----------|
| TASK-20-1 | Types & Registry | To Develop | Now |
| TASK-20-2 | Guide Index Page | To Write Tests | After TASK-20-1 tests |
| TASK-20-3 | Guide Detail Page | To Prioritize | Parallel with 20-2 |
| TASK-20-4 | Navigation Integration | To Prioritize | Parallel with 20-2 |

**Implementation Notes for Developer:**
1. Import types from `lib/guides/types.ts` (TASK-20-1)
2. Import `getAllGuides()` from `lib/guides/registry.ts` (TASK-20-1)
3. Categories: `on-page`, `technical`, `content` (from TASK-20-1)
4. Each guide card needs: icon, title, summary, readTime, toolSlug indicator
5. Search filter: client-side useState, filter by title
6. Category filter: Tabs component, filter by category field

**Risk Assessment:** Low risk confirmed
- Frontend-only, no external APIs
- No database changes
- Uses existing Shadcn/UI components

Status: `To Write Tests`
