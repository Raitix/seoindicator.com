# TASK-20-3: Create Guide Page Template Component

## STATUS
`In Development`

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

### [2026-03-01 12:40 GMT+2] PreTester - Test Coverage Complete
**Test Documentation Created:**
- `TESTS/TASK-20-3.md` - Comprehensive test documentation with 114 test scenarios

**Test Files Created:**
| File | Tests | Purpose |
|------|-------|---------|
| `__tests__/app/seo-guides/[slug]/page.test.tsx` | 30+ | Dynamic routing, generateStaticParams, metadata |
| `__tests__/components/guides/GuideLayout.test.tsx` | 15+ | Layout wrapper, JSON-LD schema |
| `__tests__/components/guides/GuideBreadcrumb.test.tsx` | 15+ | Breadcrumb navigation, Schema.org |
| `__tests__/components/guides/GuideHero.test.tsx` | 20+ | Hero section, badges, read time |
| `__tests__/components/guides/GuideContent.test.tsx` | 25+ | Markdown rendering, code blocks, XSS |
| `__tests__/components/guides/RelatedGuides.test.tsx` | 15+ | Related guides grid |
| `__tests__/components/guides/GuideNavigation.test.tsx` | 18+ | Prev/next navigation |

**Test Coverage Summary:**
- **Dynamic Page (DP-001 to DP-013):** Valid/invalid slugs, generateStaticParams, SEO metadata, 404 handling
- **GuideLayout (GL-001 to GL-008):** Children rendering, responsive classes, dark mode, JSON-LD schema
- **GuideBreadcrumb (GB-001 to GB-012):** Home link, guides link, current page, Schema.org markup
- **GuideHero (GH-001 to GH-015):** Icon, title, summary, category badge, tool badge, read time formatting
- **GuideContent (GC-001 to GC-020):** Section rendering, markdown parsing, code blocks, copy button, XSS prevention
- **RelatedGuides (RG-001 to RG-012):** Related cards, max 3, empty state, responsive grid
- **GuideNavigation (GN-001 to GN-015):** Prev/next links, first/last handling, index link

**Security Tests Included:**
- XSS prevention in markdown content (GC-016)
- Script tag injection in slugs (DP-011)
- SQL injection attempts in slugs (DP-011)
- Special character handling (GC-017)

**Accessibility Tests Included:**
- ARIA labels on navigation sections
- Proper heading hierarchy (h1 for titles)
- Keyboard navigation support
- Main landmark elements
- Focus management

**Notes for Developer:**
1. **Dynamic Routing:** Use Next.js App Router with `[slug]` folder under `app/seo-guides/`
2. **Static Generation:** Implement `generateStaticParams()` returning all 17 slugs
3. **Metadata:** Use `generateMetadata()` for SEO (title, description, OpenGraph)
4. **404 Handling:** Use Next.js `notFound()` function for invalid slugs
5. **Markdown Rendering:** Use `react-markdown` with syntax highlighting for code blocks
6. **Dark Mode:** All components should support dark mode via Tailwind `dark:` classes
7. **JSON-LD:** Include Article schema in GuideLayout and BreadcrumbList schema in GuideBreadcrumb
8. **Copy Button:** Code blocks should have clipboard copy functionality

**Files Expected:**
- `app/seo-guides/[slug]/page.tsx` - Main page component
- `components/guides/GuideLayout.tsx` - Layout wrapper
- `components/guides/GuideBreadcrumb.tsx` - Breadcrumb navigation
- `components/guides/GuideHero.tsx` - Hero section
- `components/guides/GuideContent.tsx` - Markdown content renderer
- `components/guides/RelatedGuides.tsx` - Related guides section
- `components/guides/GuideNavigation.tsx` - Prev/next navigation

Status: `To Develop`
