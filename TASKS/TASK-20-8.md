# TASK-20-8: Write Content for Heading Hierarchy Guide

## STATUS
`To Develop`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Write comprehensive SEO guide content for the Heading Hierarchy indicator, covering proper H1-H6 structure, semantic organization, and SEO/accessibility benefits.

## Acceptance Criteria
- [ ] Content covers: What is Heading Hierarchy?
- [ ] Content covers: SEO and accessibility benefits
- [ ] Content covers: How to structure headings properly
- [ ] Content covers: Common mistakes (skipping levels, wrong order)
- [ ] Content covers: Best practices checklist (5-10 items)
- [ ] Content includes visual hierarchy diagram
- [ ] Read time: 4-6 minutes
- [ ] Related guides: h1-headings, internal-links
- [ ] Content stored in guide registry

## Dependencies
- TASK-20-1 (Guide data structure)

## Effort Estimate
`Small` (1-2 hours)

## Technical Notes
- Include visual examples of correct vs incorrect hierarchy
- Cover accessibility (screen readers)
- Reference content outline best practices

## Comments
### [2026-03-02 10:45 GMT+2] ProjectManager - Prioritization
**Priority Confirmed: Medium**

**Rationale:**
- Phase 2 content task - not foundation infrastructure
- Low risk - content writing only, no code changes
- Small effort (1-2 hours)
- Part of SEO Guides Library feature (SPEC-020)

**Dependency Check:**
- TASK-20-1 (Types & Registry): Done ✅
- All Phase 2 content tasks can proceed in parallel

**Execution Order:**
- All Phase 2 content tasks (TASK-20-5 to 20-21) can run in parallel
- No dependencies between content tasks
- Template (TASK-20-3) is In Development - content can be prepared

**Content Scope:**
- What is Heading Hierarchy?
- SEO and accessibility benefits
- How to structure headings properly (H1-H6)
- Common mistakes (skipping levels, wrong order)
- Best practices checklist (5-10 items)
- Visual hierarchy diagram

**Related Guides:** h1-headings, internal-links

**Risk Assessment:** Low risk confirmed
- Content writing only
- No external APIs
- No security implications
- No database changes

Status: `To Write Tests`

### [2026-03-02 11:40 GMT+2] PreTester - Test Documentation Complete
**Test Documentation Created:**
- `TESTS/TASK-20-8.md` - Comprehensive test documentation

**Test Files Created:**
- `frontend/__tests__/lib/guides/heading-hierarchy-content.test.ts` - 50+ tests for guide content

**Test Coverage Summary:**
| Category | Tests | Description |
|----------|-------|-------------|
| Guide Registry Entry | 8 | Slug, title, category, readTime, icon, summary |
| Sections Structure | 5 | Array length, titles, content, code examples |
| Required Content Topics | 6 | Definition, SEO, accessibility, structure, mistakes |
| Section Titles | 5 | Definition, benefits, how-to, mistakes, best practices |
| Content Quality | 5 | Length, H1/H2-H6 mentions, code examples |
| Checklist Validation | 7 | 5-10 items, priorities, text validation |
| Related Guides | 4 | h1-headings, internal-links, valid slugs |
| Content-Specific Topics | 5 | H1 limit, order, skipping, screen readers, outline |
| Visual Hierarchy | 2 | Visual representation, correct structure examples |

**Notes for Developer:**
1. Content must be comprehensive enough for 4-6 minute read time (>= 3000 characters)
2. Include practical HTML examples showing correct and incorrect heading structures
3. Include visual diagram or tree structure showing heading hierarchy
4. Ensure `relatedGuides` includes both 'h1-headings' AND 'internal-links'
5. Checklist should have 5-10 items with at least 1 critical and 1 important
6. Content should emphasize both SEO and accessibility benefits
7. Cover screen reader navigation by headings
8. Warn against skipping heading levels (H1 → H3)

Status: `To Develop`
