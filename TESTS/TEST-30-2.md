# TEST-30-2: About Page Tests

## Task Reference
- **Task:** TASK-30-2
- **Specification:** SPEC-030: Legal + About + How It Works + Cookie Consent
- **Priority:** High
- **Risk:** Low

## Overview
Comprehensive test suite for the About page (`/about`), covering component rendering, SEO metadata, accessibility, Schema.org markup, and responsive design.

---

## Test Files

| File | Tests | Assertions | Purpose |
|------|-------|------------|---------|
| `frontend/__tests__/pages/about/page.test.tsx` | 35 | 50+ | Page component rendering & SEO metadata |
| `frontend/__tests__/components/pages/AboutContent.test.tsx` | 45 | 70+ | Content component rendering & accessibility |
| `frontend/__tests__/integration/about-page.test.tsx` | 20 | 30+ | Full page integration tests |
| **Total** | **100** | **150+** | |

---

## Test Scenarios

### Page Component Tests (page.test.tsx)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PC-001 | Page renders without errors | Default props | Page container renders |
| PC-002 | Route is /about | Navigate to page | pathname equals '/about' |
| PC-003 | Static page generation | Build output | SSG enabled |
| PC-004 | Meta title correct | Page metadata | "About SEOIndicator \| SEO Analysis Platform" |
| PC-005 | Meta description present | Page metadata | Description 150-160 chars |
| PC-006 | Robots meta tag | Page metadata | "index, follow" |
| PC-007 | Canonical URL | Page metadata | "https://seoindicator.com/about/" |
| PC-008 | OG title | Page metadata | Matches meta title |
| PC-009 | OG description | Page metadata | Matches meta description |
| PC-010 | OG type | Page metadata | "website" |
| PC-011 | OG url | Page metadata | "https://seoindicator.com/about/" |
| PC-012 | Twitter card | Page metadata | "summary_large_image" |
| PC-013 | Schema.org Organization | JSON-LD script | @type: Organization |
| PC-014 | Schema.org WebPage | JSON-LD script | @type: WebPage |
| PC-015 | AboutContent renders | Default props | Component in document |
| PC-016 | Hero section exists | Render page | H1 with mission statement |
| PC-017 | Product overview exists | Render page | Section describing product |
| PC-018 | Features section exists | Render page | 3-5 feature highlights |
| PC-019 | Team section exists | Render page | Company/team information |
| PC-020 | CTA button exists | Render page | Sign up link/button |
| PC-021 | CTA links to signup | Click CTA | Navigate to /signup |
| PC-022 | Semantic HTML structure | Render page | main, section, article tags |
| PC-023 | H1 exists and is unique | Render page | Single h1 element |
| PC-024 | H2 hierarchy correct | Render page | h2 follows h1 |
| PC-025 | No heading level skips | Render page | h1 -> h2 -> h3 (no jumps) |
| PC-026 | Page is keyboard navigable | Tab through page | All interactive elements focusable |
| PC-027 | Focus indicators visible | Focus elements | Visible focus ring |
| PC-028 | Skip link exists | Render page | Skip to main content link |
| PC-029 | Language attribute | HTML tag | lang="en" |
| PC-030 | Color contrast sufficient | Check contrast | Ratio >= 4.5:1 |
| PC-031 | Responsive at 375px | Mobile viewport | No horizontal scroll |
| PC-032 | Responsive at 768px | Tablet viewport | Layout adapts |
| PC-033 | Responsive at 1024px | Desktop viewport | Layout adapts |
| PC-034 | No console errors | Render page | No error logs |
| PC-035 | Performance acceptable | Render time | < 500ms |

### Content Component Tests (AboutContent.test.tsx)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AC-001 | Component renders | Default props | Container in document |
| AC-002 | Hero section renders | Default props | Mission statement visible |
| AC-003 | Hero has correct text | Mission text | Contains expected content |
| AC-004 | Product overview renders | Default props | Section exists |
| AC-005 | Product overview has title | Section heading | "What is SEOIndicator?" or similar |
| AC-006 | Features section renders | Default props | 3-5 feature cards |
| AC-007 | Features count | Feature cards | Between 3-5 features |
| AC-008 | Feature has title | Feature card | Title visible |
| AC-009 | Feature has description | Feature card | Description visible |
| AC-010 | Feature has icon | Feature card | Icon element present |
| AC-011 | Team section renders | Default props | Section exists |
| AC-012 | Team has heading | Section heading | "Our Team" or similar |
| AC-013 | Team has content | Team section | Company/team info visible |
| AC-014 | CTA section renders | Default props | CTA container visible |
| AC-015 | CTA has button | CTA section | Button/link present |
| AC-016 | CTA button text | Button text | "Get Started" or "Sign Up" |
| AC-017 | CTA button links to signup | Click button | Navigate to /signup |
| AC-018 | Section structure | All sections | Each has proper heading |
| AC-019 | H1 text content | H1 element | Contains "About" or mission |
| AC-020 | H2 count | All h2 elements | 3-6 h2 elements |
| AC-021 | H2 hierarchy | h2 elements | Each under main section |
| AC-022 | Alt text for images | Images | All have alt attributes |
| AC-023 | Decorative images | Decorative images | alt="" (empty) |
| AC-024 | Links have accessible names | All links | aria-label or text content |
| AC-025 | Buttons have accessible names | All buttons | aria-label or text content |
| AC-026 | Landmarks exist | Render component | main, section landmarks |
| AC-027 | Section landmarks | Each section | aria-labelledby or aria-label |
| AC-028 | Tab order logical | Tab through | Logical focus order |
| AC-029 | Focus trap in modal | Open modal (if any) | Focus trapped |
| AC-030 | Screen reader text | Hidden text | sr-only class if needed |
| AC-031 | Mobile layout | 375px viewport | Stacked layout |
| AC-032 | Tablet layout | 768px viewport | 2-column layout (if applicable) |
| AC-033 | Desktop layout | 1024px viewport | Full layout |
| AC-034 | Images responsive | Resize viewport | Images scale properly |
| AC-035 | Text readable mobile | 375px viewport | Font size >= 16px |
| AC-036 | Touch targets mobile | 375px viewport | Min 44x44px |
| AC-037 | No horizontal scroll | Mobile viewport | Overflow-x hidden |
| AC-038 | Animation respects reduced motion | prefers-reduced-motion | Animations disabled/ simplified |
| AC-039 | Dark mode support | Dark theme | Colors invert properly |
| AC-040 | Loading state | loading prop | Skeleton/spinner visible |
| AC-041 | Error state | error prop | Error message visible |
| AC-042 | Custom className | className prop | Applied to container |
| AC-043 | Data-testid attribute | testId prop | Applied to container |
| AC-044 | Snapshot matches | Default props | Snapshot matches baseline |
| AC-045 | Accessibility audit | Run axe | No violations |

### Integration Tests (about-page.test.tsx)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| INT-001 | Full page renders | Navigate to /about | Page renders completely |
| INT-002 | SEO metadata complete | Check all meta | All meta tags present |
| INT-003 | Schema.org valid | Validate JSON-LD | Valid Organization schema |
| INT-004 | Navigation works | Click nav links | Navigate correctly |
| INT-005 | CTA flow works | Click CTA | Navigate to signup |
| INT-006 | Back navigation | Navigate back | Returns to previous page |
| INT-007 | Deep link works | Direct URL to /about | Page renders correctly |
| INT-008 | 404 on invalid route | Navigate to /about/invalid | 404 page renders |
| INT-009 | Analytics fires | View page | Page view event tracked |
| INT-010 | Breadcrumbs correct | Check breadcrumbs | Home > About |
| INT-011 | Header renders | Check header | Header visible |
| INT-012 | Footer renders | Check footer | Footer visible |
| INT-013 | Mobile menu works | Mobile viewport | Mobile nav accessible |
| INT-014 | Form submission (if any) | Submit form | Success/error handled |
| INT-015 | External links | Click external link | Opens in new tab |
| INT-016 | Internal links | Click internal link | SPA navigation |
| INT-017 | Cache behavior | Reload page | Cached response |
| INT-018 | SEO preview | Social preview | OG tags display correctly |
| INT-019 | Print styles | Print preview | Print-friendly layout |
| INT-020 | Performance | Lighthouse | Score >= 90 |

---

## Mock Data

### About Page Props
```typescript
const mockAboutProps = {
  mission: "Helping websites achieve better SEO through intelligent analysis",
  features: [
    { title: "SEO Analysis", description: "Comprehensive SEO audits", icon: "search" },
    { title: "Keyword Tracking", description: "Monitor your rankings", icon: "trending-up" },
    { title: "Competitor Insights", description: "Analyze competitors", icon: "users" },
    { title: "Actionable Reports", description: "Get recommendations", icon: "file-text" }
  ],
  teamInfo: {
    title: "Our Mission",
    description: "SEOIndicator is built by a team of SEO experts..."
  },
  cta: {
    text: "Get Started Free",
    href: "/signup"
  }
};
```

### SEO Metadata Mock
```typescript
const mockSEOMetadata = {
  title: "About SEOIndicator | SEO Analysis Platform",
  description: "Learn about SEOIndicator's mission to help websites improve their search visibility. Discover our AI-powered SEO analysis tools and start optimizing today.",
  robots: "index, follow",
  canonical: "https://seoindicator.com/about/",
  openGraph: {
    title: "About SEOIndicator | SEO Analysis Platform",
    description: "Learn about SEOIndicator's mission...",
    type: "website",
    url: "https://seoindicator.com/about/"
  },
  twitter: {
    card: "summary_large_image",
    title: "About SEOIndicator | SEO Analysis Platform",
    description: "Learn about SEOIndicator's mission..."
  }
};
```

### Schema.org Mock
```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "name": "SEOIndicator",
      "url": "https://seoindicator.com",
      "logo": "https://seoindicator.com/logo.png",
      "sameAs": [
        "https://twitter.com/seoindicator",
        "https://linkedin.com/company/seoindicator"
      ]
    },
    {
      "@type": "WebPage",
      "name": "About SEOIndicator",
      "description": "Learn about SEOIndicator's mission...",
      "url": "https://seoindicator.com/about/",
      "isPartOf": {
        "@id": "https://seoindicator.com/#website"
      }
    }
  ]
}
```

---

## Coverage Targets

| Category | Target | Current |
|----------|--------|---------|
| Statements | 80% | - |
| Branches | 75% | - |
| Functions | 80% | - |
| Lines | 80% | - |

---

## Test Execution

```bash
# Run all About page tests
npm test -- --testPathPattern="about"

# Run specific test file
npm test -- frontend/__tests__/pages/about/page.test.tsx

# Run with coverage
npm test -- --coverage --testPathPattern="about"

# Run accessibility tests
npm test -- --testPathPattern="about" --testNamePattern="accessibility"
```

---

## Dependencies

- TASK-28-1 (CSS Variables Enhancement) - for theming
- TASK-28-3 (shadcn/ui Components) - for UI components
- Next.js 14+ App Router
- React Testing Library
- Jest
- @testing-library/jest-dom
- jest-axe (accessibility testing)

---

## Notes for Developer

### Component Files to Create
```
frontend/app/about/page.tsx
frontend/components/pages/AboutContent.tsx
```

### Key Implementation Points

1. **SEO Metadata**
   - Use Next.js `generateMetadata()` function
   - Meta title: "About SEOIndicator | SEO Analysis Platform"
   - Meta description: 150-160 characters
   - Include robots: "index, follow"

2. **Schema.org Markup**
   - Use JSON-LD in `<script type="application/ld+json">`
   - Include both Organization and WebPage schemas
   - Use @graph for multiple entities

3. **Accessibility**
   - Single H1 per page
   - No heading level skips (h1 -> h2 -> h3)
   - Color contrast >= 4.5:1
   - Touch targets >= 44x44px on mobile
   - Skip link to main content

4. **Responsive Design**
   - Mobile-first approach
   - Breakpoints: sm (640px), md (768px), lg (1024px)
   - No horizontal scroll on mobile

5. **Performance**
   - Static page generation (SSG)
   - No client-side JavaScript for content
   - Optimized images with Next.js Image component

---

## Comments

### [2026-03-04 PreTester]
**Test Design Complete:**
- 100 test scenarios covering all acceptance criteria
- 3 test files: page, component, integration
- Accessibility tests use jest-axe
- Responsive tests at 375px, 768px, 1024px
- Schema.org validation tests included

**Key Patterns:**
- Use `@testing-library/react` for component testing
- Use `jest-axe` for accessibility audits
- Use `next/router` mock for navigation tests
- Mock `next/head` for SEO metadata tests

**Status:** Tests Coverage In Progress → Ready for Developer

---

*Created by PreTester | 2026-03-04*
