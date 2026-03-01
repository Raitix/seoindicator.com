# TASK-20-3: Create Guide Page Template Component

## STATUS
`Tests Coverage In Progress`

## PRIORITY
`High`

## RISK
`Low`

## Description
Create the reusable guide page template component that renders individual SEO guide pages with consistent layout, breadcrumb, hero, content sections, and navigation.

## Acceptance Criteria
- [ ] Create `app/seo-guides/[slug]/page.tsx` with dynamic routing
- [ ] Create `components/guides/GuideLayout.tsx` wrapper component
- [ ] Create `components/guides/GuideBreadcrumb.tsx` component
- [ ] Create `components/guides/GuideHero.tsx` component
- [ ] Create `components/guides/GuideContent.tsx` component (renders markdown)
- [ ] Create `components/guides/RelatedGuides.tsx` component
- [ ] Create `components/guides/GuideNavigation.tsx` (prev/next)
- [ ] All components properly typed with TypeScript
- [ ] Responsive design for all screen sizes
- [ ] Error handling for invalid slugs (404)

## Dependencies
- TASK-20-1 (Guide data structure)

## Effort Estimate
`Medium` (4-6 hours)

## Technical Notes
- Use react-markdown or similar for content rendering
- Shadcn/UI components for styling
- useSearchParams for client-side routing
- generateStaticParams() for static generation

## Comments
### [2026-03-01 12:08 GMT+2] ProjectManager - Prioritization
**Priority Confirmed: High**

**Rationale:**
- Foundation task for SPEC-020 (SEO Guides Library)
- Phase 1 task - required for all Phase 2 content rendering
- Creates reusable template component for all 17 guide pages
- No external API dependencies - pure frontend work

**Dependency Analysis:**
- TASK-20-1 (Types & Registry) - Status: `To Deploy` - Nearly complete
- PreTester can begin test design immediately (types/registry tests already passed)
- Development should wait for TASK-20-1 to reach `To Test Locally` minimum

**Execution Order:**
```
Phase 1 (parallel):
├── TASK-20-1 (Types) ← To Deploy
├── TASK-20-2 (Index Page) ← To Prioritize
├── TASK-20-3 (Template) ← This task
└── TASK-20-4 (Routing) ← To Prioritize

Phase 2 (depends on Phase 1):
├── TASK-20-5 to 20-21 (Content pages)
└── All require template from TASK-20-3
```

**Risk Assessment:** Low risk confirmed
- Frontend-only, no database changes
- No external APIs
- Uses existing Shadcn/UI components
- Standard Next.js dynamic routing pattern

**Notes for PreTester:**
- Template imports from `lib/guides/types.ts` and `lib/guides/registry.ts`
- Components to test: GuideLayout, GuideBreadcrumb, GuideHero, GuideContent, RelatedGuides, GuideNavigation
- Key test scenarios: valid slug, invalid slug (404), prev/next navigation, related guides
- generateStaticParams() for SSG testing

Status: `To Write Tests`
