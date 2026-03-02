# TASK-20-5: Write Content for Meta Title Guide

## STATUS
`To Write Tests`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Write comprehensive SEO guide content for the Meta Title indicator, including definition, SEO impact, optimization guide, best practices, and checklist.

## Acceptance Criteria
- [ ] Content covers: What is Meta Title?
- [ ] Content covers: Why it matters for SEO
- [ ] Content covers: How to optimize (step-by-step)
- [ ] Content covers: Common mistakes to avoid
- [ ] Content covers: Best practices checklist (5-10 items)
- [ ] Content includes 1-2 code examples
- [ ] Read time: 3-5 minutes
- [ ] Links to Meta Tag Analyzer tool
- [ ] Related guides: meta-description, h1-headings
- [ ] Content stored in guide registry

## Dependencies
- TASK-20-1 (Guide data structure)

## Effort Estimate
`Small` (1-2 hours)

## Technical Notes
- Use markdown format for content sections
- Include real-world examples
- Reference Google's title guidelines

## Comments
### [2026-03-02 14:30 GMT+2] ProjectManager - Prioritization
**Priority Confirmed: Medium**

**Rationale:**
- Phase 2 content task - content writing only, no infrastructure changes
- Low risk - no code changes, purely documentation/content
- Dependency satisfied: TASK-20-1 (Types & Registry) is **Done** ✅
- Can be developed in parallel with all other Phase 2 content tasks (TASK-20-6 to 20-21)

**Dependency Status:**
```
TASK-20-1 (Types & Registry): Done ✅
```

**Content Requirements for PreTester:**
1. **What is Meta Title?** - Definition, purpose, display in SERPs
2. **SEO Impact** - Click-through rates, ranking factors, user experience
3. **Optimization Guide** - Step-by-step: length, keywords, branding, unique titles
4. **Common Mistakes** - Duplicate titles, too long/short, keyword stuffing, no branding
5. **Best Practices Checklist** - 5-10 actionable items with priorities
6. **Code Examples** - 1-2 HTML examples
7. **Tool Integration** - Link to Meta Tag Analyzer (FEAT-016)
8. **Related Guides** - meta-description, h1-headings

**Tool Integration:**
- Links to: `meta-tag-analyzer` tool (FEAT-016 - Done)
- Related guides: `meta-description`, `h1-headings`

**Risk Assessment:**
- Risk: Low (confirmed) - content writing only
- No external APIs
- No database changes
- No security implications

**Execution Notes:**
- Content should reference Google's title guidelines
- Include real-world examples of good vs bad titles
- Target read time: 3-5 minutes
- Use existing `SEOGuide` interface from TASK-20-1

Status: `To Write Tests`
