# TASK-20-7: Write Content for H1 Headings Guide

## STATUS
`To Develop`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Write comprehensive SEO guide content for the H1 Headings indicator, including definition, SEO impact, optimization guide, best practices, and checklist.

## Acceptance Criteria
- [ ] Content covers: What are H1 Headings?
- [ ] Content covers: Why they matter for SEO
- [ ] Content covers: How to optimize H1 tags
- [ ] Content covers: Common mistakes (multiple H1s, missing H1)
- [ ] Content covers: Best practices checklist (5-10 items)
- [ ] Content includes 1-2 code examples
- [ ] Read time: 3-5 minutes
- [ ] Related guides: heading-hierarchy, meta-title
- [ ] Content stored in guide registry

## Dependencies
- TASK-20-1 (Guide data structure)

## Effort Estimate
`Small` (1-2 hours)

## Technical Notes
- Focus on single H1 per page rule
- Include accessibility considerations
- Reference HTML5 semantic structure

## Comments
### [2026-03-02 10:38 GMT+2] ProjectManager - Prioritization
**Priority Confirmed: Medium**

**Rationale:**
- Content task for SPEC-020 (SEO Guides Library)
- Dependency satisfied: TASK-20-1 (Guide data structure) is Done
- Low risk - content writing only, no code changes to core functionality
- Can be developed in parallel with other Phase 2 content tasks (TASK-20-5 to 20-21)

**Execution Context:**
- Part of Phase 2 content tasks
- Related guides: heading-hierarchy (TASK-20-8), meta-title (TASK-20-5)
- Content will populate `h1-headings` guide in registry

**Risk Assessment:** Low risk confirmed
- Content writing only - no external APIs
- No database changes
- No security implications

Status: `To Develop`

### [2026-03-02 11:19 GMT+2] PreTester - Test Design Complete
**Test File Created:** `frontend/__tests__/lib/guides/h1-headings-content.test.ts`

**Test Coverage:**
- **HE-001 to HE-005:** Guide existence and basic properties
- **WH-001 to WH-005:** "What are H1 Headings?" section validation
- **WHY-001 to WHY-006:** Why H1 headings matter for SEO section
- **HO-001 to HO-006:** How to optimize H1 tags section
- **CM-001 to CM-006:** Common mistakes section (multiple H1s, missing H1)
- **BC-001 to BC-005:** Best practices checklist (5-10 items validation)
- **CE-001 to CE-004:** Code examples (1-2 examples)
- **RT-001 to RT-003:** Read time validation (3-5 minutes)
- **RG-001 to RG-004:** Related guides (heading-hierarchy, meta-title)
- **GR-001 to GR-003:** Registry integration tests
- **CQ tests:** Content quality validation
- **Security tests:** XSS prevention

**Key Test Requirements for Developer:**
1. Content must have 4+ sections covering all acceptance criteria
2. Checklist must have 5-10 items with valid priorities
3. Code examples must show H1 tag usage (1-2 examples max)
4. Read time must be 3-5 minutes
5. Related guides must include: heading-hierarchy, meta-title
6. Must mention: one H1 per page, keywords, accessibility, SEO impact
