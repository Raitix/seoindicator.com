# TASK-20-1: Create Guide Data Structure and TypeScript Types

## STATUS
`To Prioritize`

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
