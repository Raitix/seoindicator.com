# TASK-20-4: Set Up Routing with Static Params Generation

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## Description
Configure Next.js routing for SEO guides with static site generation, trailing slash handling, and proper static params generation for all 17 guide slugs.

## Acceptance Criteria
- [ ] Update `next.config.js` with trailingSlash: true
- [ ] Implement `generateStaticParams()` in guide page
- [ ] All 17 guide slugs generate static HTML files
- [ ] Build produces correct output structure:
  - `/seo-guides/index.html`
  - `/seo-guides/[slug]/index.html` for each guide
- [ ] Client-side navigation works between guides
- [ ] Direct URL access works for all guides
- [ ] 404 handling for invalid slugs

## Dependencies
- TASK-20-1 (Guide data structure)
- TASK-20-3 (Guide page template)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Next.js App Router with output: 'export'
- Use generateStaticParams for build-time generation
- Test with `npm run build` and `npm run start`

## Comments
### [2026-03-01 12:28 GMT+2] ProjectManager - Prioritization Complete
**Priority Confirmed: High**

**Rationale:**
- Foundation task for SPEC-020 (SEO Guides Library)
- Phase 1 task - required for all static guide pages to work
- Enables clean URLs and proper SSG for SEO optimization
- No external API dependencies - pure Next.js configuration

**Dependency Analysis:**
- TASK-20-1 (Types & Registry) - Status: `Done` ✅
- TASK-20-3 (Template) - Status: `To Write Tests` - can develop in parallel

**Execution Order:**
```
Phase 1 (parallel development):
├── TASK-20-1 (Types) ← Done
├── TASK-20-2 (Index Page) ← In Progress
├── TASK-20-3 (Template) ← To Write Tests
└── TASK-20-4 (Routing) ← This task

Phase 2 (depends on Phase 1):
├── TASK-20-5 to 20-21 (Content pages)
└── All require routing from TASK-20-4
```

**Technical Scope:**
1. Update `next.config.js` with `trailingSlash: true`
2. Implement `generateStaticParams()` in guide page component
3. Ensure build produces correct output structure:
   - `/seo-guides/index.html`
   - `/seo-guides/[slug]/index.html` for each guide
4. Configure 404 handling for invalid slugs

**Risk Assessment:** Low risk confirmed
- Configuration changes only - no code logic
- Easily reversible
- Standard Next.js SSG pattern
- No database or API changes

**Notes for PreTester:**
- Test with `npm run build` to verify static generation
- Verify all 17 guide slugs generate HTML files
- Test direct URL access for each guide
- Test 404 handling for invalid slugs
- Test client-side navigation between guides

Status: `To Write Tests`
