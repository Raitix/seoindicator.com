# TESTS/TASK-30-1.md — Terms & Privacy Page Tests

## Task Reference
- **Task:** TASK-30-1: Terms & Privacy Page
- **Specification:** SPEC-030: Legal + About + How It Works + Cookie Consent
- **Status:** Tests Coverage In Progress

---

## Overview

Tests for the `/terms` route with Terms of Service and Privacy Policy content. Static page with SEO-optimized legal content.

**Files Under Test:**
- `frontend/app/terms/page.tsx` — Page component
- `frontend/components/pages/TermsContent.tsx` — Content component

---

## Test Files

| File | Tests | Purpose |
|------|-------|---------|
| `frontend/__tests__/app/terms/page.test.tsx` | 45+ | Page component tests |

---

## Test Scenarios

### Page Component Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PC-001 | File exists at terms/page.tsx | Dynamic import | Module defined |
| PC-002 | Default export exists | Import component | Function type |
| PC-003 | Static generation | Check for dynamic exports | No generateStaticParams |
| PC-004 | Meta title correct | generateMetadata() | "Terms & Privacy \| SEOIndicator" |
| PC-005 | Meta description length | generateMetadata().description | 150-160 characters |
| PC-006 | Robots meta | generateMetadata().robots | { index: true, follow: true } |
| PC-007 | Open Graph tags | generateMetadata().openGraph | title, description, url |
| PC-008 | Canonical URL | generateMetadata().alternates | Contains /terms |
| PC-009 | WebPage schema | JSON-LD script | @type: "WebPage" |
| PC-010 | Schema properties | JSON-LD content | name, description, url |

### Accessibility Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ACC-001 | Heading hierarchy | DOM structure | One h1, h2s after h1 |
| ACC-002 | Keyboard navigation | Focusable elements | Present and reachable |
| ACC-003 | Color contrast | Text elements | Semantic check pass |
| ACC-004 | Screen reader support | main landmark, headings | Present and structured |

### Content Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CT-001 | Combined Terms & Privacy | Render page | Both headings present |
| CT-002 | Introduction section | Find heading | h2 with "Introduction" |
| CT-003 | Terms of Use section | Find heading | h2 with "Terms of Use" |
| CT-004 | Privacy Policy section | Find heading | h2 with "Privacy Policy" |
| CT-005 | Cookie Policy section | Find heading | h2 with "Cookie Policy" |
| CT-006 | Contact section | Find heading | h2 with "Contact" |
| CT-007 | Last updated date | Find date element | Prominently displayed |
| CT-008 | Section navigation | TOC or anchor links | Present and functional |

### Edge Case Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Empty content | Render | No errors |
| EC-002 | Long legal text | Long paragraphs | Graceful overflow handling |
| EC-003 | Re-render | Double render | Consistent output |
| EC-004 | Special characters | Legal text | No undefined/null in output |

### Performance Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PF-001 | Render time | Performance.now() | < 100ms |
| PF-002 | No unnecessary re-renders | Rerender component | Stable |

### Responsive Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RS-001 | Mobile (375px) | Render | Readable layout |
| RS-002 | Tablet (768px) | Render | Proper spacing |
| RS-003 | Desktop (1024px+) | Render | Max-width container |

### Integration Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IT-001 | Homepage link | Find home link | Present |
| IT-002 | Consistent styling | Layout classes | Matches site theme |
| IT-003 | Navigation/breadcrumbs | Find nav | Back link or breadcrumb |

---

## Acceptance Criteria Mapping

### Content
| Criteria | Test ID | Status |
|----------|---------|--------|
| Combined Terms of Service and Privacy policy displayed | CT-001 | ✅ |
| Sections: Introduction, Terms of Use, Privacy Policy, Cookie Policy, Contact | CT-002 to CT-006 | ✅ |
| Last updated date displayed prominently | CT-007 | ✅ |
| Clear section navigation (table of contents or anchor links) | CT-008 | ✅ |

### Technical
| Criteria | Test ID | Status |
|----------|---------|--------|
| Route: `/terms` | PC-001 | ✅ |
| Content component: `frontend/components/pages/TermsContent.tsx` | See below | ⏳ |
| Static page generation | PC-003 | ✅ |
| Proper semantic HTML structure | ACC-001, ACC-004 | ✅ |

### SEO
| Criteria | Test ID | Status |
|----------|---------|--------|
| Meta title: "Terms & Privacy \| SEOIndicator" | PC-004 | ✅ |
| Meta description: 150-160 characters | PC-005 | ✅ |
| Robots meta: index, follow | PC-006 | ✅ |
| Schema.org WebPage markup | PC-009, PC-010 | ✅ |
| Open Graph tags for social sharing | PC-007 | ✅ |

### Accessibility
| Criteria | Test ID | Status |
|----------|---------|--------|
| Proper heading hierarchy (h1 → h2 → h3) | ACC-001 | ✅ |
| Keyboard navigable | ACC-002 | ✅ |
| Color contrast ratio ≥ 4.5:1 | ACC-003 | ⏳ (visual tool) |
| Screen reader friendly | ACC-004 | ✅ |

---

## Mock Data

### Expected Section Structure
```typescript
const EXPECTED_SECTIONS = [
  { id: 'introduction', title: 'Introduction' },
  { id: 'terms-of-use', title: 'Terms of Use' },
  { id: 'privacy-policy', title: 'Privacy Policy' },
  { id: 'cookie-policy', title: 'Cookie Policy' },
  { id: 'contact', title: 'Contact' },
];
```

### Expected Metadata
```typescript
const EXPECTED_META = {
  title: 'Terms & Privacy | SEOIndicator',
  description: '...', // 150-160 characters
  robots: 'index, follow',
  openGraph: {
    title: 'Terms & Privacy | SEOIndicator',
    description: '...',
    url: 'https://seoindicator.com/terms/',
  },
};
```

### Expected Schema
```json
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "Terms & Privacy",
  "description": "...",
  "url": "https://seoindicator.com/terms/"
}
```

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Page Component | 100% | 20+ |
| SEO Metadata | 100% | 7 |
| Accessibility | 100% | 4 |
| Content | 100% | 8 |
| Edge Cases | 100% | 4 |
| Performance | 100% | 2 |
| Responsive | 100% | 3 |
| Integration | 100% | 3 |
| **Total** | **100%** | **45+** |

---

## Test Execution

```bash
# Run all terms page tests
npm test -- frontend/__tests__/app/terms/page.test.tsx

# Run with coverage
npm run test:coverage -- frontend/__tests__/app/terms/page.test.tsx

# Run specific test category
npm test -- frontend/__tests__/app/terms/page.test.tsx -t "SEO Metadata"
npm test -- frontend/__tests__/app/terms/page.test.tsx -t "Accessibility"
npm test -- frontend/__tests__/app/terms/page.test.tsx -t "Content"
```

---

## Dependencies

- None (standalone static page)

---

## Notes for Developer

### Implementation Requirements

1. **Page Component** (`frontend/app/terms/page.tsx`):
   - Use Next.js App Router conventions
   - Export `generateMetadata()` for SEO
   - Include JSON-LD schema in page or layout
   - Import and render `TermsContent` component

2. **Content Component** (`frontend/components/pages/TermsContent.tsx`):
   - Accept `className` prop for styling
   - Render all 5 sections with proper heading hierarchy
   - Include last updated date
   - Implement table of contents or anchor navigation

3. **SEO Implementation**:
   ```typescript
   export async function generateMetadata(): Promise<Metadata> {
     return {
       title: 'Terms & Privacy | SEOIndicator',
       description: 'Read our Terms of Service and Privacy Policy...',
       robots: { index: true, follow: true },
       openGraph: {
         title: 'Terms & Privacy | SEOIndicator',
         description: '...',
         url: 'https://seoindicator.com/terms/',
       },
       alternates: {
         canonical: 'https://seoindicator.com/terms/',
       },
     };
   }
   ```

4. **Schema.org Implementation**:
   ```typescript
   const schema = {
     "@context": "https://schema.org",
     "@type": "WebPage",
     "name": "Terms & Privacy",
     "description": "...",
     "url": "https://seoindicator.com/terms/"
   };
   ```

5. **Accessibility Checklist**:
   - Single `<h1>` for page title
   - `<h2>` for each section
   - `<main>` landmark with proper role
   - Skip link to main content (optional)
   - Proper color contrast (use design tokens)

---

## Comments

### PreTester Notes (2026-03-04)
- Created comprehensive test suite covering all acceptance criteria
- Tests designed for TDD approach - write tests first, then implement
- Static page with no API calls - all tests are unit/integration
- Content component tests will pass once component is created
- SEO tests verify metadata generation function exists

---

*Created by PreTester | 2026-03-04*
