# TEST-32-3: Comparison Page Route with Static Generation Tests

## Task Reference
- **Task:** [TASK-32-3](../TASKS/TASK-32-3.md)
- **Spec:** SPEC-32 (Competitor Comparison Landing Pages)
- **Status:** Tests Coverage In Progress

## Overview
Tests for the dynamic comparison page route using Next.js App Router with static site generation via `generateStaticParams()`. This is the core page that renders comparison content.

---

## Test Files

| File | Tests | Purpose |
|------|-------|---------|
| `frontend/__tests__/app/compare/[slug]/page.test.tsx` | 80+ | Main route page tests |
| `frontend/__tests__/app/compare/[slug]/not-found.test.tsx` | 25+ | 404 not-found page tests |

---

## Test Scenarios

### CP-PARAMS: generateStaticParams() Tests (12 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-PARAMS-001 | Returns array of slug objects | - | Array.isArray === true |
| CP-PARAMS-002 | Returns all COMPARISON_SLUGS | - | Length matches COMPARISON_SLUGS |
| CP-PARAMS-003 | Each entry has slug property | - | All entries have { slug: string } |
| CP-PARAMS-004 | Returns seoindicator-vs-ahrefs | - | Contains { slug: 'seoindicator-vs-ahrefs' } |
| CP-PARAMS-005 | Returns seoindicator-vs-semrush | - | Contains { slug: 'seoindicator-vs-semrush' } |
| CP-PARAMS-006 | Returns seoindicator-vs-moz | - | Contains { slug: 'seoindicator-vs-moz' } |
| CP-PARAMS-007 | Returns cross-competitor slugs | - | Contains ahrefs-vs-semrush |
| CP-PARAMS-008 | Slug values are strings | - | All slug values typeof === 'string' |
| CP-PARAMS-009 | Slug values match expected format | - | All match pattern `*-vs-*` |
| CP-PARAMS-010 | No duplicate slugs | - | Unique values only |
| CP-PARAMS-011 | Returns empty array if no slugs | [] | [] |
| CP-PARAMS-012 | Function is async-compatible | - | Returns Promise resolved |

### CP-META: generateMetadata() Tests (15 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-META-001 | Returns Metadata object | { params: { slug: 'seoindicator-vs-ahrefs' } } | Object with title/description |
| CP-META-002 | Title includes comparison title | { params: { slug: 'seoindicator-vs-ahrefs' } } | title contains 'SEOIndicator vs Ahrefs' |
| CP-META-003 | Title includes site suffix | { params: { slug: 'seoindicator-vs-ahrefs' } } | title ends with '| SEO Tool Comparison' |
| CP-META-004 | Description is comparison metaDescription | { params: { slug: 'seoindicator-vs-ahrefs' } } | description === comparison.metaDescription |
| CP-META-005 | Returns default title for invalid slug | { params: { slug: 'invalid-slug' } } | title === 'Comparison Not Found' |
| CP-META-006 | Returns null comparison gracefully | { params: { slug: 'nonexistent-vs-foo' } } | Returns fallback metadata |
| CP-META-007 | OpenGraph title matches page title | { params: { slug: 'seoindicator-vs-ahrefs' } } | openGraph.title === title |
| CP-META-008 | OpenGraph description matches | { params: { slug: 'seoindicator-vs-ahrefs' } } | openGraph.description === description |
| CP-META-009 | OpenGraph type is article | { params: { slug: 'seoindicator-vs-ahrefs' } } | openGraph.type === 'article' |
| CP-META-010 | Handles empty params | {} | Returns fallback metadata |
| CP-META-011 | Handles undefined params | undefined | Returns fallback metadata |
| CP-META-012 | Handles null slug in params | { params: { slug: null } } | Returns fallback metadata |
| CP-META-013 | Title length within SEO limits | valid slug | title.length <= 60 chars |
| CP-META-014 | Description length within SEO limits | valid slug | description.length <= 160 chars |
| CP-META-015 | Metadata is cacheable | valid slug | Function can be called multiple times |

### CP-RENDER: Page Rendering Tests (20 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-RENDER-001 | Page component renders | { params: { slug: 'seoindicator-vs-ahrefs' } } | Renders without error |
| CP-RENDER-002 | Renders comparison title | valid slug | Displays comparison.title |
| CP-RENDER-003 | Renders comparison summary | valid slug | Displays comparison.summary |
| CP-RENDER-004 | Renders hero section | valid slug | Contains hero element |
| CP-RENDER-005 | Renders feature comparison table | valid slug | ComparisonTable rendered |
| CP-RENDER-006 | Renders pricing comparison | valid slug | PricingComparison rendered |
| CP-RENDER-007 | Renders pros/cons cards | valid slug | ProsConsCard for each tool |
| CP-RENDER-008 | Renders use cases section | valid slug | UseCaseSection rendered |
| CP-RENDER-009 | Renders CTA section | valid slug | ComparisonCTA rendered |
| CP-RENDER-010 | Renders FAQ section | valid slug | FAQ items displayed |
| CP-RENDER-011 | Renders related comparisons | valid slug | Links to related comparisons |
| CP-RENDER-012 | Calls notFound() for invalid slug | { params: { slug: 'invalid' } } | notFound() called |
| CP-RENDER-013 | Uses correct slug from params | { params: { slug: 'seoindicator-vs-semrush' } } | getComparisonBySlug called with correct slug |
| CP-RENDER-014 | Renders main element | valid slug | <main> element exists |
| CP-RENDER-015 | All sections in correct order | valid slug | Hero → Table → Pricing → ProsCons → UseCases → FAQ → CTA |
| CP-RENDER-016 | Handles loading state | - | Loading fallback if applicable |
| CP-RENDER-017 | Handles error state | - | Error boundary catches errors |
| CP-RENDER-018 | SEO-friendly HTML structure | valid slug | Semantic HTML (h1, h2, section, article) |
| CP-RENDER-019 | Renders for all comparison slugs | all COMPARISON_SLUGS | All render without error |
| CP-RENDER-020 | No hydration errors | valid slug | Client/server match |

### CP-NOTFOUND: 404 Handling Tests (10 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-NOTFOUND-001 | Invalid slug triggers notFound() | { params: { slug: 'invalid-slug' } } | notFound() called |
| CP-NOTFOUND-002 | Non-existent competitor triggers notFound | { params: { slug: 'nonexistent-vs-ahrefs' } } | notFound() called |
| CP-NOTFOUND-003 | Empty slug triggers notFound | { params: { slug: '' } } | notFound() called |
| CP-NOTFOUND-004 | Malformed slug triggers notFound | { params: { slug: 'seoindicator-ahrefs' } } | notFound() called |
| CP-NOTFOUND-005 | getComparisonBySlug returns null | invalid slug | notFound() called |
| CP-NOTFOUND-006 | not-found.tsx component exists | - | File exists |
| CP-NOTFOUND-007 | not-found.tsx renders correctly | - | Displays 404 content |
| CP-NOTFOUND-008 | not-found has link to /compare | - | Link to comparison index |
| CP-NOTFOUND-009 | not-found has proper heading | - | h1 with "Not Found" text |
| CP-NOTFOUND-010 | not-found is accessible | - | Proper ARIA attributes |

### CP-STATIC: Static Generation Tests (8 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-STATIC-001 | generateStaticParams is exported | - | Function exported |
| CP-STATIC-002 | Page uses static generation | - | No dynamic = 'force-dynamic' |
| CP-STATIC-003 | All slugs pre-rendered at build | - | All COMPARISON_SLUGS in output |
| CP-STATIC-004 | No SSR for valid slugs | valid slug | Static HTML served |
| CP-STATIC-005 | Build generates static files | build command | .html files created |
| CP-STATIC-006 | Fallback behavior correct | unknown slug | 404 or ISR fallback |
| CP-STATIC-007 | generateMetadata is static-compatible | - | Works with generateStaticParams |
| CP-STATIC-008 | No dynamic imports in critical path | - | Static analysis passes |

### CP-RESPONSIVE: Mobile Responsive Tests (8 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-RESPONSIVE-001 | Hero responsive on mobile | mobile viewport | Stacks vertically |
| CP-RESPONSIVE-002 | Comparison table scrollable on mobile | mobile viewport | Horizontal scroll |
| CP-RESPONSIVE-003 | Pricing cards stack on mobile | mobile viewport | Cards stack |
| CP-RESPONSIVE-004 | Pros/Cons side-by-side on desktop | desktop viewport | Side-by-side layout |
| CP-RESPONSIVE-005 | Pros/Cons stack on mobile | mobile viewport | Stacked layout |
| CP-RESPONSIVE-006 | Use cases grid responsive | mobile viewport | Single column |
| CP-RESPONSIVE-007 | CTA button full-width on mobile | mobile viewport | Full-width button |
| CP-RESPONSIVE-008 | Touch targets 44x44px minimum | mobile viewport | All interactive elements |

### CP-A11Y: Accessibility Tests (10 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-A11Y-001 | Page has single h1 | valid slug | Exactly one <h1> |
| CP-A11Y-002 | Heading hierarchy correct | valid slug | h1 → h2 → h3 order |
| CP-A11Y-003 | Main landmark exists | valid slug | <main> or role="main" |
| CP-A11Y-004 | Comparison table has caption | valid slug | <caption> or aria-label |
| CP-A11Y-005 | All images have alt text | valid slug | alt attributes present |
| CP-A11Y-006 | Links have descriptive text | valid slug | No "click here" links |
| CP-A11Y-007 | Color contrast ratio >= 4.5:1 | valid slug | Text contrast compliant |
| CP-A11Y-008 | Focus indicators visible | valid slug | Focus styles present |
| CP-A11Y-009 | Keyboard navigation works | valid slug | Tab order logical |
| CP-A11Y-010 | Screen reader announces sections | valid slug | aria-labelledby for sections |

### CP-PERF: Performance Tests (8 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-PERF-001 | Page render time < 100ms | valid slug | Render completes quickly |
| CP-PERF-002 | No unnecessary re-renders | valid slug | Single render on mount |
| CP-PERF-003 | Component lazy loading works | valid slug | Heavy components lazy |
| CP-PERF-004 | Static assets cached | valid slug | Cache headers present |
| CP-PERF-005 | No layout shift on load | valid slug | CLS < 0.1 |
| CP-PERF-006 | First contentful paint < 1.8s | valid slug | LCP < 2.5s |
| CP-PERF-007 | Total blocking time < 200ms | valid slug | TBT acceptable |
| CP-PERF-008 | Cumulative layout shift < 0.1 | valid slug | CLS acceptable |

### CP-SEO: SEO Tests (8 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-SEO-001 | Title tag present | valid slug | <title> in head |
| CP-SEO-002 | Meta description present | valid slug | <meta name="description"> |
| CP-SEO-003 | Canonical URL correct | valid slug | /compare/[slug] |
| CP-SEO-004 | Open Graph tags present | valid slug | og:title, og:description |
| CP-SEO-005 | Schema.org markup present | valid slug | JSON-LD script |
| CP-SEO-006 | Robots meta allows indexing | valid slug | index, follow |
| CP-SEO-007 | Breadcrumb structured data | valid slug | BreadcrumbList schema |
| CP-SEO-008 | FAQ schema for FAQ section | valid slug | FAQPage schema |

### NF-BASIC: Not Found Page Tests (12 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| NF-BASIC-001 | Component renders | - | Renders without error |
| NF-BASIC-002 | Displays "Not Found" heading | - | h1 with not found text |
| NF-BASIC-003 | Displays helpful message | - | User-friendly text |
| NF-BASIC-004 | Has link to /compare | - | Link to comparison index |
| NF-BASIC-005 | Link has correct href | - | href="/compare" |
| NF-BASIC-006 | Link text is descriptive | - | "View all comparisons" or similar |
| NF-BASIC-007 | Has proper heading structure | - | Single h1 |
| NF-BASIC-008 | Has main landmark | - | <main> or role="main" |
| NF-BASIC-009 | Accessible with screen reader | - | aria-live or role="alert" |
| NF-BASIC-010 | Returns to compare index on click | - | Navigation works |
| NF-BASIC-011 | Uses Next.js Link component | - | <Link> used |
| NF-BASIC-012 | Responsive layout | - | Works on all viewports |

### NF-STYLE: Not Found Style Tests (8 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| NF-STYLE-001 | Has centered layout | - | Content centered |
| NF-STYLE-002 | Has proper spacing | - | Padding/margin present |
| NF-STYLE-003 | Heading is prominent | - | Large font size |
| NF-STYLE-004 | Link styled as button | - | Button-like appearance |
| NF-STYLE-005 | Dark mode support | - | dark: classes present |
| NF-STYLE-006 | Consistent with site theme | - | Matches brand colors |
| NF-STYLE-007 | Mobile-friendly | - | Works on small screens |
| NF-STYLE-008 | Focus visible on link | - | Focus styles present |

### AC: Acceptance Criteria (7 tests)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| AC-001 | generateStaticParams() returns all valid comparison slugs | All COMPARISON_SLUGS returned |
| AC-002 | generateMetadata() generates correct title and description | Metadata matches comparison data |
| AC-003 | Invalid slugs trigger 404 page | notFound() called, 404 rendered |
| AC-004 | Page renders correctly for all comparison slugs | All slugs render without error |
| AC-005 | Static pages generated at build time | No SSR, static HTML |
| AC-006 | Lighthouse score > 90 | All metrics pass |
| AC-007 | Mobile responsive layout | Works on all viewport sizes |

---

## Mock Data

### Mock COMPARISON_SLUGS

```typescript
const mockComparisonSlugs = [
  'seoindicator-vs-ahrefs',
  'seoindicator-vs-semrush',
  'seoindicator-vs-moz',
  'seoindicator-vs-ubersuggest',
  'ahrefs-vs-semrush',
];
```

### Mock ComparisonData

```typescript
const mockComparisonData: ComparisonData = {
  slug: 'seoindicator-vs-ahrefs',
  title: 'SEOIndicator vs Ahrefs: Which SEO Tool Is Right for You?',
  metaDescription: 'Compare SEOIndicator and Ahrefs features, pricing, and use cases. Find the best SEO tool for your needs.',
  competitors: ['seoindicator', 'ahrefs'],
  summary: 'A comprehensive comparison of SEOIndicator and Ahrefs for SEO professionals.',
  featureComparison: [
    {
      name: 'Rank Tracking',
      features: [
        { name: 'Daily Updates', seoindicator: true, competitor: true },
        { name: 'Mobile Rankings', seoindicator: true, competitor: true },
      ]
    }
  ],
  pricingComparison: {
    seoindicator: [
      { name: 'Free', monthlyPrice: 'Free', annualPrice: 'Free', features: [] },
      { name: 'Pro', monthlyPrice: 29, annualPrice: 290, features: [] }
    ],
    competitor: [
      { name: 'Lite', monthlyPrice: 99, annualPrice: 990, features: [] }
    ]
  },
  prosCons: {
    seoindicator: { pros: ['Free tier', 'Easy to use'], cons: ['Fewer features'] },
    competitor: { pros: ['Comprehensive', 'Large database'], cons: ['Expensive'] }
  },
  useCases: {
    seoindicator: [
      { title: 'Solo Bloggers', description: 'Perfect for individual SEO', audience: 'solo', budget: 'free' }
    ],
    competitor: [
      { title: 'Enterprise Teams', description: 'For large organizations', audience: 'enterprise', budget: 'premium' }
    ]
  },
  verdict: 'SEOIndicator is best for individuals and small teams.',
  faqs: [
    { question: 'Which tool is better for beginners?', answer: 'SEOIndicator is more beginner-friendly.' }
  ],
  relatedComparisons: ['seoindicator-vs-semrush', 'ahrefs-vs-semrush']
};
```

---

## Coverage Targets

| Category | Target |
|----------|--------|
| generateStaticParams | 100% |
| generateMetadata | 100% |
| Page Rendering | 100% |
| 404 Handling | 100% |
| Static Generation | 100% |
| Mobile Responsive | 100% |
| Accessibility | 100% |
| Performance | 100% |
| SEO | 100% |
| Not Found Page | 100% |
| Acceptance Criteria | 100% |

**Overall Target: 100% coverage for comparison page route**

---

## Test Execution

```bash
# Run page tests
npm test -- frontend/__tests__/app/compare/[slug]/page.test.tsx

# Run not-found tests
npm test -- frontend/__tests__/app/compare/[slug]/not-found.test.tsx

# Run with coverage
npm run test:coverage -- frontend/__tests__/app/compare/

# Run all comparison tests
npm test -- frontend/__tests__/app/compare/
```

---

## Dependencies

- TASK-32-1 (Types and Interfaces) - Done ✅
- TASK-32-2 (Registry) - Tests Coverage In Progress (mocked)
  - COMPARISON_SLUGS
  - getComparisonBySlug()
- TASK-32-5 to TASK-32-10 (Components) - To Prioritize (mocked)

---

## Notes for Developer

1. **Page Component Structure:**
   ```typescript
   // frontend/app/compare/[slug]/page.tsx
   import { generateStaticParams, generateMetadata } from 'next';
   import { getComparisonBySlug, COMPARISON_SLUGS } from '@/lib/compare/registry';
   import { notFound } from 'next/navigation';
   
   export function generateStaticParams() {
     return COMPARISON_SLUGS.map((slug) => ({ slug }));
   }
   
   export async function generateMetadata({ params }) {
     const comparison = getComparisonBySlug(params.slug);
     if (!comparison) return { title: 'Comparison Not Found' };
     return {
       title: `${comparison.title} | SEO Tool Comparison`,
       description: comparison.metaDescription,
       openGraph: {
         title: comparison.title,
         description: comparison.metaDescription,
         type: 'article',
       },
     };
   }
   
   export default function ComparisonPage({ params }) {
     const comparison = getComparisonBySlug(params.slug);
     if (!comparison) notFound();
     
     return (
       <main>
         {/* Sections */}
       </main>
     );
   }
   ```

2. **Not Found Page Structure:**
   ```typescript
   // frontend/app/compare/[slug]/not-found.tsx
   import Link from 'next/link';
   
   export default function CompareNotFound() {
     return (
       <div>
         <h1>Comparison Not Found</h1>
         <p>The comparison you're looking for doesn't exist.</p>
         <Link href="/compare">View all comparisons</Link>
       </div>
     );
   }
   ```

3. **Static Generation Notes:**
   - Use `generateStaticParams()` for all known slugs
   - No `dynamic = 'force-dynamic'` needed
   - Let Next.js handle 404 via notFound()

4. **Performance Optimizations:**
   - Lazy load heavy components (ComparisonTable, PricingComparison)
   - Use streaming for large pages if needed
   - Ensure all images have width/height for CLS prevention

5. **Mobile Responsive:**
   - Use Tailwind responsive classes (sm:, md:, lg:)
   - Test all viewport sizes
   - Ensure touch targets are 44x44px minimum

---

## Comments

### [2026-03-05 17:15 GMT+2] Agent: PreTester
**Test Design Complete:**
- Created 136+ test scenarios across 12 categories
- Tests cover all acceptance criteria
- Mock data provided for valid structures
- Edge cases and error handling included
- Accessibility and performance tests included

**Test Categories Summary:**
| Category | Tests |
|----------|-------|
| generateStaticParams | 12 |
| generateMetadata | 15 |
| Page Rendering | 20 |
| 404 Handling | 10 |
| Static Generation | 8 |
| Mobile Responsive | 8 |
| Accessibility | 10 |
| Performance | 8 |
| SEO | 8 |
| Not Found Page Basic | 12 |
| Not Found Page Style | 8 |
| Acceptance Criteria | 7 |
| **Total** | **136** |

**Status:** Updated to `Tests Coverage In Progress`
