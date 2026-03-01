# TASK-20-4: Set Up Routing with Static Params Generation

## STATUS
`To Prioritize`

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
