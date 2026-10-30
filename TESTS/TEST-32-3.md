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
| `frontend/__tests__/app/compare/[slug]/page.test.tsx` | 120+ | Page component tests |
| `frontend/__tests__/app/compare/[slug]/static-params.test.ts` | 30+ | Static params generation |
| `frontend/__tests__/app/compare/[slug]/metadata.test.ts` | 25+ | Metadata generation |
| `frontend/__tests__/app/compare/[slug]/not-found.test.tsx` | 15+ | 404 handling |
| `frontend/__tests__/app/compare/[slug]/performance.test.tsx` | 20+ | Performance & Lighthouse |

---

## Test Scenarios

### CP-GEN: generateStaticParams Tests (30 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-GEN-001 | Returns array of params | - | Array of { slug: string } |
| CP-GEN-002 | Contains all COMPARISON_SLUGS | - | Length matches COMPARISON_SLUGS |
| CP-GEN-003 | Returns seoindicator-vs-ahrefs | - | { slug: 'seoindicator-vs-ahrefs' } in array |
| CP-GEN-004 | Returns seoindicator-vs-semrush | - | { slug: 'seoindicator-vs-semrush' } in array |
| CP-GEN-005 | Returns seoindicator-vs-moz | - | { slug: 'seoindicator-vs-moz' } in array |
| CP-GEN-006 | Returns seoindicator-vs-ubersuggest | - | { slug: 'seoindicator-vs-ubersuggest' } in array |
| CP-GEN-007 | Returns cross-competitor slugs | - | ahrefs-vs-semrush in array |
| CP-GEN-008 | All params have slug property | - | Every item has slug key |
| CP-GEN-009 | All slugs are strings | - | typeof slug === 'string' |
| CP-GEN-010 | All slugs match pattern | - | `/^[a-z0-9]+-vs-[a-z0-9]+$/` |
| CP-GEN-011 | Returns unique slugs only | - | No duplicates |
| CP-GEN-012 | Returns at least 10 slugs | - | length >= 10 |
| CP-GEN-013 | Returns empty array for empty registry | Empty registry | [] |
| CP-GEN-014 | Handles registry with single slug | Single slug | [{ slug: '...' }] |
| CP-GEN-015 | Performance: Generates 100 slugs quickly | 100 slugs | < 100ms |
| CP-GEN-016 | Sync function (not async) | - | No Promise returned |
| CP-GEN-017 | Returns plain objects | - | No class instances |
| CP-GEN-018 | Slug values match registry exactly | - | Direct comparison passes |
| CP-GEN-019 | No extra properties in params | - | Only slug property |
| CP-GEN-020 | Memoization works correctly | Multiple calls | Same reference or deep equal |
| CP-GEN-021 | Works with Next.js build | - | Called during build |
| CP-GEN-022 | No external API calls during generation | - | No fetch/network calls |
| CP-GEN-023 | Static generation (no SSR) | - | Output is static HTML |
| CP-GEN-024 | Handles registry updates | Updated registry | Reflects new slugs |
| CP-GEN-025 | Returns frozen/immutable result | Attempt modification | No side effects |
| CP-GEN-026 | Null registry handled gracefully | Null registry | Returns [] |
| CP-GEN-027 | Undefined registry handled gracefully | Undefined registry | Returns [] |
| CP-GEN-028 | Registry with invalid slugs filtered | Invalid slugs | Only valid slugs returned |
| CP-GEN-029 | Called once per build | - | Single execution |
| CP-GEN-030 | Result is JSON-serializable | - | JSON.stringify works |

### CP-META: generateMetadata Tests (25 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-META-001 | Returns Metadata object | Valid slug | { title, description, ... } |
| CP-META-002 | Title includes both tool names | 'seoindicator-vs-ahrefs' | Title contains both names |
| CP-META-003 | Title follows SEO format | 'seoindicator-vs-ahrefs' | 'SEOIndicator vs Ahrefs: Which SEO Tool Is Better?' |
| CP-META-004 | Description is optimized length | Valid slug | 120-160 characters |
| CP-META-005 | Description includes tool names | 'seoindicator-vs-ahrefs' | Contains both names |
| CP-META-006 | OpenGraph title matches page title | Valid slug | og:title === title |
| CP-META-007 | OpenGraph description matches | Valid slug | og:description === description |
| CP-META-008 | OpenGraph type is article | Valid slug | og:type === 'article' |
| CP-META-009 | Returns default for invalid slug | Invalid slug | { title: 'Comparison Not Found' } |
| CP-META-010 | Returns default for null slug | null | { title: 'Comparison Not Found' } |
| CP-META-011 | Returns default for empty slug | '' | { title: 'Comparison Not Found' } |
| CP-META-012 | Twitter card is summary_large_image | Valid slug | twitter:card === 'summary_large_image' |
| CP-META-013 | Twitter title matches | Valid slug | twitter:title === title |
| CP-META-014 | Twitter description matches | Valid slug | twitter:description === description |
| CP-META-015 | Meta description under 160 chars | Valid slug | length <= 160 |
| CP-META-016 | Title under 60 chars | Valid slug | length <= 60 |
| CP-META-017 | Alternates canonical URL set | Valid slug | canonical URL correct |
| CP-META-018 | Robots meta allows indexing | Valid slug | index, follow |
| CP-META-019 | Async function works | Valid slug | Returns Promise<Metadata> |
| CP-META-020 | Handles non-existent competitor | 'nonexistent-vs-ahrefs' | Default metadata |
| CP-META-021 | Handles both non-existent | 'nonexistent-vs-fake' | Default metadata |
| CP-META-022 | Metadata cached appropriately | Multiple calls | Same result |
| CP-META-023 | No sensitive data in metadata | Valid slug | No API keys, tokens |
| CP-META-024 | Cross-competitor metadata correct | 'ahrefs-vs-semrush' | Correct tool names |
| CP-META-025 | JSON-LD schema in metadata | Valid slug | Structured data included |

### CP-RENDER: Page Rendering Tests (40 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-RENDER-001 | Page renders without error | Valid slug | Component renders |
| CP-RENDER-002 | Main element present | Valid slug | <main> exists |
| CP-RENDER-003 | Hero section renders | Valid slug | Hero content visible |
| CP-RENDER-004 | Title in hero matches | Valid slug | H1 matches comparison title |
| CP-RENDER-005 | Summary text renders | Valid slug | Summary paragraph visible |
| CP-RENDER-006 | Feature comparison table renders | Valid slug | Table exists |
| CP-RENDER-007 | Pricing section renders | Valid slug | Pricing visible |
| CP-RENDER-008 | Pros/cons cards render | Valid slug | Both cards visible |
| CP-RENDER-009 | Use cases section renders | Valid slug | Use cases visible |
| CP-RENDER-010 | FAQ section renders | Valid slug | FAQs visible |
| CP-RENDER-011 | CTA section renders | Valid slug | CTA button visible |
| CP-RENDER-012 | Related comparisons render | Valid slug | Links visible |
| CP-RENDER-013 | Schema component renders | Valid slug | JSON-LD in DOM |
| CP-RENDER-014 | All 8 sections present | Valid slug | 8 sections count |
| CP-RENDER-015 | SEOIndicator branding correct | Valid slug | Correct brand name |
| CP-RENDER-016 | Competitor name correct | Valid slug | Correct competitor name |
| CP-RENDER-017 | Comparison data used | Valid slug | Data from registry |
| CP-RENDER-018 | Page structure semantic | Valid slug | Proper heading hierarchy |
| CP-RENDER-019 | Section headings have IDs | Valid slug | ID attributes for anchor links |
| CP-RENDER-020 | Accessible landmarks | Valid slug | role/aria attributes |
| CP-RENDER-021 | Schema.org JSON-LD valid | Valid slug | Valid JSON structure |
| CP-RENDER-022 | Breadcrumb navigation | Valid slug | Breadcrumb present |
| CP-RENDER-023 | Back to compare index link | Valid slug | Link to /compare |
| CP-RENDER-024 | Pricing toggle (monthly/annual) | Valid slug | Toggle works |
| CP-RENDER-025 | Feature checkmarks render | Valid slug | ✓ and ✗ symbols |
| CP-RENDER-026 | Winner highlighting works | Valid slug | Highlighted features |
| CP-RENDER-027 | Tooltips on features | Valid slug | Tooltip content |
| CP-RENDER-028 | FAQ accordion works | Valid slug | Expand/collapse |
| CP-RENDER-029 | CTA button clickable | Valid slug | Button has href |
| CP-RENDER-030 | CTA tracking attributes | Valid slug | data-event attributes |
| CP-RENDER-031 | Responsive grid layout | Valid slug | Grid classes |
| CP-RENDER-032 | Image alt texts | Valid slug | Alt attributes |
| CP-RENDER-033 | External links have rel | Valid slug | rel="noopener noreferrer" |
| CP-RENDER-034 | No broken images | Valid slug | All images load |
| CP-RENDER-035 | Loading state handled | Slow data | Loading indicator |
| CP-RENDER-036 | Error boundary catches errors | Throw error | Fallback UI |
| CP-RENDER-037 | Structured data for FAQ | Valid slug | FAQPage schema |
| CP-RENDER-038 | Structured data for Product | Valid slug | Product schema |
| CP-RENDER-039 | Comparison schema valid | Valid slug | Compare schema |
| CP-RENDER-040 | All slugs render correctly | All slugs | Each renders |

### CP-404: 404 Not Found Tests (15 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-404-001 | Invalid slug shows 404 | 'invalid-slug' | Not found page |
| CP-404-002 | Empty slug shows 404 | '' | Not found page |
| CP-404-003 | Null slug shows 404 | null | Not found page |
| CP-404-004 | Single word slug shows 404 | 'seoindicator' | Not found page |
| CP-404-005 | Wrong format shows 404 | 'seoindicator-ahrefs' | Not found page |
| CP-404-006 | 404 has clear heading | Invalid slug | "Comparison Not Found" |
| CP-404-007 | 404 has helpful message | Invalid slug | Descriptive text |
| CP-404-008 | 404 has link to compare index | Invalid slug | /compare link |
| CP-404-009 | 404 has link to homepage | Invalid slug | / link |
| CP-404-010 | 404 styled correctly | Invalid slug | Proper layout |
| CP-404-011 | 404 has search suggestions | Invalid slug | Search input or links |
| CP-404-012 | 404 returns 404 status code | Invalid slug | HTTP 404 |
| CP-404-013 | 404 noindex meta | Invalid slug | noindex, nofollow |
| CP-404-014 | Non-existent competitor shows 404 | 'fake-vs-nonexistent' | Not found page |
| CP-404-015 | Case sensitivity handled | 'SEOINDICATOR-vs-AHREFS' | 404 or redirect |

### CP-PERF: Performance Tests (20 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-PERF-001 | Lighthouse performance > 90 | Valid slug | Score >= 90 |
| CP-PERF-002 | Lighthouse accessibility > 90 | Valid slug | Score >= 90 |
| CP-PERF-003 | Lighthouse best practices > 90 | Valid slug | Score >= 90 |
| CP-PERF-004 | Lighthouse SEO > 90 | Valid slug | Score >= 90 |
| CP-PERF-005 | First Contentful Paint < 1.5s | Valid slug | FCP < 1500ms |
| CP-PERF-006 | Largest Contentful Paint < 2.5s | Valid slug | LCP < 2500ms |
| CP-PERF-007 | Cumulative Layout Shift < 0.1 | Valid slug | CLS < 0.1 |
| CP-PERF-008 | Total Blocking Time < 200ms | Valid slug | TBT < 200ms |
| CP-PERF-009 | Time to Interactive < 3s | Valid slug | TTI < 3000ms |
| CP-PERF-010 | No render-blocking resources | Valid slug | Critical CSS inline |
| CP-PERF-011 | Images optimized | Valid slug | WebP/AVIF format |
| CP-PERF-012 | Images lazy loaded | Valid slug | loading="lazy" |
| CP-PERF-013 | Fonts preloaded | Valid slug | preload links |
| CP-PERF-014 | No unused JavaScript | Valid slug | Tree-shaking works |
| CP-PERF-015 | No unused CSS | Valid slug | Purged CSS |
| CP-PERF-016 | Static generation (no SSR) | Valid slug | Pre-rendered HTML |
| CP-PERF-017 | Build time reasonable | All pages | < 60s total |
| CP-PERF-018 | Bundle size optimized | Valid slug | < 200KB JS |
| CP-PERF-019 | No hydration mismatches | Valid slug | Clean hydration |
| CP-PERF-020 | Memory usage acceptable | Valid slug | < 100MB heap |

### CP-MOBILE: Mobile Responsiveness Tests (20 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-MOBILE-001 | Viewport meta tag present | Mobile viewport | Meta viewport set |
| CP-MOBILE-002 | Mobile menu works | Mobile viewport | Hamburger menu |
| CP-MOBILE-003 | Touch targets >= 48px | Mobile viewport | Button sizes |
| CP-MOBILE-004 | Text readable without zoom | Mobile viewport | Font size >= 16px |
| CP-MOBILE-005 | No horizontal scroll | Mobile viewport | No overflow |
| CP-MOBILE-006 | Table scrolls horizontally | Mobile viewport | Overflow scroll |
| CP-MOBILE-007 | Cards stack vertically | Mobile viewport | Column layout |
| CP-MOBILE-008 | Pros/cons side by side on desktop | Desktop | Row layout |
| CP-MOBILE-009 | Pros/cons stack on mobile | Mobile viewport | Column layout |
| CP-MOBILE-010 | CTA visible without scroll | Mobile viewport | Above fold |
| CP-MOBILE-011 | Font sizes scale appropriately | Mobile viewport | Readable text |
| CP-MOBILE-012 | Images scale properly | Mobile viewport | Responsive images |
| CP-MOBILE-013 | Buttons full width on mobile | Mobile viewport | Width 100% |
| CP-MOBILE-014 | Navigation collapses on mobile | Mobile viewport | Hidden nav |
| CP-MOBILE-015 | Footer stacks on mobile | Mobile viewport | Column layout |
| CP-MOBILE-016 | Comparison table scrollable | Mobile viewport | Horizontal scroll |
| CP-MOBILE-017 | Touch-friendly interactions | Mobile viewport | Swipe, tap work |
| CP-MOBILE-018 | No hover-only UI | Mobile viewport | Tap alternatives |
| CP-MOBILE-019 | Safe area insets respected | Mobile viewport | Notch safe |
| CP-MOBILE-020 | Responsive breakpoints correct | Various widths | Layout changes |

### CP-INT: Integration Tests (15 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-INT-001 | Registry integration works | Valid slug | Data from registry |
| CP-INT-002 | Components receive correct props | Valid slug | Props match data |
| CP-INT-003 | Links to related comparisons work | Valid slug | Valid hrefs |
| CP-INT-004 | Cross-page navigation works | Click link | Navigate correctly |
| CP-INT-005 | Analytics tracking fires | Interaction | Events tracked |
| CP-INT-006 | Cookie consent respected | First visit | Banner shown |
| CP-INT-007 | Auth state for CTA | Logged out | Login redirect |
| CP-INT-008 | Schema validation passes | Valid slug | Valid JSON-LD |
| CP-INT-009 | Sitemap includes this page | Sitemap check | URL present |
| CP-INT-010 | robots.txt allows page | Robots check | Allow directive |
| CP-INT-011 | Canonical URL correct | Valid slug | Correct canonical |
| CP-INT-012 | Internal links valid | All links | No 404s |
| CP-INT-013 | External links open new tab | External link | target="_blank" |
| CP-INT-014 | Social share buttons work | Click share | Share dialog |
| CP-INT-015 | Print styles work | Print preview | Readable output |

### AC: Acceptance Criteria (7 tests)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| AC-001 | generateStaticParams() returns all valid slugs | All COMPARISON_SLUGS covered |
| AC-002 | generateMetadata() generates correct title and description | SEO-optimized metadata |
| AC-003 | Invalid slugs trigger 404 page | Not found page rendered |
| AC-004 | Page renders correctly for all comparison slugs | All pages work |
| AC-005 | Static pages generated at build time (no SSR) | Pre-rendered HTML |
| AC-006 | Lighthouse score > 90 | All categories > 90 |
| AC-007 | Mobile responsive layout | Passes mobile tests |

---

## Mock Data

### Valid Comparison Data

```typescript
const mockComparisonData: ComparisonData = {
  slug: 'seoindicator-vs-ahrefs',
  title: 'SEOIndicator vs Ahrefs: Which SEO Tool Is Better?',
  metaDescription: 'Compare SEOIndicator vs Ahrefs. Detailed analysis of features, pricing, pros and cons to help you choose the best SEO tool.',
  competitors: ['seoindicator', 'ahrefs'],
  summary: 'Find out how SEOIndicator compares to Ahrefs in this comprehensive comparison.',
  featureComparison: [
    {
      name: 'Rank Tracking',
      features: [
        { name: 'Daily updates', seoindicator: true, competitor: true },
        { name: 'Mobile rankings', seoindicator: true, competitor: true },
        { name: 'Local rankings', seoindicator: false, competitor: true },
      ],
    },
  ],
  pricingComparison: {
    seoindicator: [
      { name: 'Free', monthlyPrice: 'Free', annualPrice: 'Free', features: ['100 keywords'] },
      { name: 'Pro', monthlyPrice: 29, annualPrice: 290, features: ['Unlimited keywords'] },
    ],
    competitor: [
      { name: 'Lite', monthlyPrice: 99, annualPrice: 990, features: ['Basic features'] },
    ],
  },
  prosCons: {
    seoindicator: {
      pros: ['Affordable pricing', 'Easy to use'],
      cons: ['Fewer features than enterprise tools'],
    },
    competitor: {
      pros: ['Comprehensive backlink data', 'Industry standard'],
      cons: ['Expensive', 'Steep learning curve'],
    },
  },
  useCases: {
    seoindicator: [
      { title: 'Solo SEO', description: 'Perfect for individuals', audience: 'solo', budget: 'budget' },
    ],
    competitor: [
      { title: 'Enterprise SEO', description: 'For large teams', audience: 'enterprise', budget: 'premium' },
    ],
  },
  verdict: 'SEOIndicator is the best choice for budget-conscious users.',
  faqs: [
    { question: 'Which tool is cheaper?', answer: 'SEOIndicator offers a free tier.' },
  ],
  relatedComparisons: ['seoindicator-vs-semrush', 'ahrefs-vs-semrush'],
};
```

### Mock Component Props

```typescript
// ComparisonTable props
const mockComparisonTableProps = {
  features: mockComparisonData.featureComparison,
  highlightWinner: true,
  showTooltips: true,
};

// PricingComparison props
const mockPricingProps = {
  seoindicator: mockComparisonData.pricingComparison.seoindicator,
  competitor: mockComparisonData.pricingComparison.competitor,
  showFreeTier: true,
};

// ProsConsCard props
const mockProsConsProps = {
  tool: 'SEOIndicator',
  pros: ['Pro 1', 'Pro 2'],
  cons: ['Con 1'],
  overallRating: 4.5,
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
| Performance | 100% |
| Mobile Responsiveness | 100% |
| Integration | 100% |
| Acceptance Criteria | 100% |

**Overall Target: 100% coverage for comparison page route**

---

## Test Execution

```bash
# Run all comparison page tests
npm test -- frontend/__tests__/app/compare/

# Run specific test file
npm test -- frontend/__tests__/app/compare/[slug]/page.test.tsx

# Run with coverage
npm run test:coverage -- frontend/__tests__/app/compare/

# Run performance tests
npm test -- frontend/__tests__/app/compare/[slug]/performance.test.tsx
```

---

## Dependencies

- TASK-32-1 (Types) - Done ✅
- TASK-32-2 (Registry) - To Test Locally (usable)
- Components from TASK-32-5 to TASK-32-10 (To Prioritize)

---

## Notes for Developer

### 1. Page Structure

Create `frontend/app/compare/[slug]/page.tsx`:

```typescript
import { generateStaticParams, generateMetadata } from 'next';
import { getComparisonBySlug, COMPARISON_SLUGS } from '@/lib/compare/registry';
import { notFound } from 'next/navigation';

export function generateStaticParams() {
  return COMPARISON_SLUGS.map((slug) => ({ slug }));
}

export async function generateMetadata({ params }: ComparisonPageProps) {
  const comparison = getComparisonBySlug(params.slug);
  if (!comparison) {
    return { title: 'Comparison Not Found' };
  }
  return {
    title: comparison.title,
    description: comparison.metaDescription,
    openGraph: {
      title: comparison.title,
      description: comparison.metaDescription,
      type: 'article',
    },
  };
}

export default function ComparisonPage({ params }: ComparisonPageProps) {
  const comparison = getComparisonBySlug(params.slug);
  if (!comparison) {
    notFound();
  }
  // Render page sections
}
```

### 2. Not Found Page

Create `frontend/app/compare/[slug]/not-found.tsx`:

```typescript
import Link from 'next/link';

export default function CompareNotFound() {
  return (
    <div className="not-found">
      <h1>Comparison Not Found</h1>
      <p>The comparison you&apos;re looking for doesn&apos;t exist.</p>
      <Link href="/compare">View all comparisons</Link>
    </div>
  );
}
```

### 3. Performance Optimization

- Use `loading.tsx` for loading states
- Implement streaming for large pages
- Preload critical resources
- Inline critical CSS
- Lazy load below-fold content

### 4. Static Generation

- `generateStaticParams` must be sync (not async)
- All pages pre-rendered at build time
- No server-side rendering required
- Use ISR (Incremental Static Regeneration) if needed

### 5. Mobile Responsiveness

- Use Tailwind responsive classes
- Test on 320px, 375px, 768px, 1024px, 1440px
- Ensure touch targets are 48px minimum
- Implement horizontal scroll for tables

---

## Comments

### [2026-03-05 17:25 GMT+2] Agent: PreTester
**Test Design Complete:**
- Created test documentation with 172+ test scenarios
- Covers all acceptance criteria
- Performance and mobile responsiveness included
- Integration tests for registry connection
- Mock data and component props provided

**Test Categories Summary:**
| Category | Tests |
|----------|-------|
| generateStaticParams | 30 |
| generateMetadata | 25 |
| Page Rendering | 40 |
| 404 Handling | 15 |
| Performance | 20 |
| Mobile Responsiveness | 20 |
| Integration | 15 |
| Acceptance Criteria | 7 |
| **Total** | **172** |

**Status:** Ready to create test files
