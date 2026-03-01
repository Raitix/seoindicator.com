# TASK-20-1: Create Guide Data Structure and TypeScript Types

## STATUS
`Prioritization In Progress`

## PRIORITY
`High`

## RISK
`Low`

## Description
Create the TypeScript types and data structures for SEO guides, including the `SEOGuide` interface, `GuideSection`, `ChecklistItem`, and the guide registry with all 17 guide definitions.

## Acceptance Criteria
- [ ] Create `lib/guides/types.ts` with TypeScript interfaces:
  - `SEOGuide` interface with all fields from SPEC-020
  - `GuideSection` interface for content sections
  - `ChecklistItem` interface with priority levels
  - `CodeExample` interface for code snippets
- [ ] Create `lib/guides/registry.ts` with guide data array
- [ ] Export `getGuideBySlug(slug: string)` helper function
- [ ] Export `getAllGuides()` helper function
- [ ] Export `getRelatedGuides(slug: string)` helper function
- [ ] All 17 guide stubs defined with slug, title, summary, category
- [ ] Type definitions compile without errors

## Dependencies
- None (foundation task)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Use TypeScript strict mode
- Category enum: 'on-page' | 'technical' | 'content'
- Icon field can be emoji string or icon component name
- Content sections will be populated in Phase 2 tasks

## Comments
### [2026-03-01 11:45 GMT+2] ProjectManager - Prioritization
**Priority Confirmed: High**

**Rationale:**
- Foundation task for SPEC-020 (SEO Guides Library)
- No dependencies - can start immediately
- All Phase 1 tasks (TASK-20-1 to 20-4) can be developed in parallel
- Required by all Phase 2 content tasks (TASK-20-5 to 20-21)

**Dependency Chain:**
```
TASK-20-1 (Types) ← TASK-20-5 to 20-21 (Content tasks)
                  ← TASK-20-22 to 20-25 (Integration tasks)
```

**Execution Order:**
- Phase 1 tasks (20-1, 20-2, 20-3, 20-4) can run in parallel
- Phase 2 content tasks depend on TASK-20-1
- Phase 3 integration tasks depend on Phase 2

**Risk Assessment:** Low risk confirmed
- TypeScript types only - no external APIs
- No database changes
- No security implications

Status: `To Write Tests`
