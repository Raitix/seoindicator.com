# TASK-20-3: Create Guide Page Template Component

## STATUS
`To Prioritize`

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
