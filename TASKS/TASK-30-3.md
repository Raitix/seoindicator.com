# TASK-30-3: How It Works Page

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Low`

## Specification
SPEC-030: Legal + About + How It Works + Cookie Consent

## Description
Create the `/how-it-works` route with step-by-step user journey, indicator explanations (17 SEO indicators from FEAT-018), scoring methodology, and FAQ section.

---

## Acceptance Criteria

### Content
- [ ] Step-by-step user journey (4-5 steps with icons)
- [ ] Indicator categories breakdown:
  - [ ] Technical (robots.txt, sitemap, SSL, mobile-friendly, canonical, hreflang) - 30%
  - [ ] Content (title tag, meta description, H1, keyword density, images) - 40%
  - [ ] Performance (page speed, Core Web Vitals) - 20%
  - [ ] UX (mobile viewport, font readability) - 10%
- [ ] Scoring methodology explanation
- [ ] Weight system breakdown
- [ ] FAQ section (5-10 common questions)

### Technical
- [ ] Route: `/how-it-works` (file: `frontend/app/how-it-works/page.tsx`)
- [ ] Content component: `frontend/components/pages/HowItWorksContent.tsx`
- [ ] Static page generation
- [ ] Proper semantic HTML structure
- [ ] Responsive design (mobile-first)

### Visual Elements
- [ ] Icons for each step
- [ ] Visual representation of scoring system
- [ ] Progress bars or charts for weight distribution
- [ ] Collapsible FAQ accordion

### SEO
- [ ] Meta title: "How It Works | SEOIndicator SEO Analysis"
- [ ] Meta description: 150-160 characters
- [ ] Robots meta: index, follow
- [ ] Schema.org WebPage/FAQPage markup
- [ ] Open Graph tags for social sharing

### Accessibility
- [ ] Proper heading hierarchy (h1 → h2 → h3)
- [ ] Keyboard navigable FAQ accordion
- [ ] Color contrast ratio ≥ 4.5:1
- [ ] Screen reader friendly

---

## Dependencies
- **FEAT-018** (SEO Analysis Engine) - Reference for indicator descriptions
- Icon library (Lucide or similar)

---

## Effort Estimate
`Medium` (4-8 hours)
- Complex content with multiple sections
- Visual elements and interactive FAQ
- Requires coordination with existing indicator definitions

---

## Files to Create/Modify

### New Files
```
frontend/app/how-it-works/page.tsx
frontend/components/pages/HowItWorksContent.tsx
```

### Modified Files
- None (standalone feature)

---

## Comments
<!-- Agent communication happens here -->

### [2026-03-04 ProjectManager]
**Prioritization Analysis:**

| Factor | Assessment | Justification |
|--------|------------|---------------|
| **Priority** | High (confirmed) | Core site page essential for user education and SEO value |
| **Risk** | Low (confirmed) | Static content page, no external APIs, no user input processing |
| **Dependencies** | FEAT-018 (informational) | Indicator categories already documented in task acceptance criteria |
| **Effort** | Medium (4-8 hours) | Complex content with multiple sections, FAQ accordion, visual elements |

**Execution Context:**
- Part of SPEC-030 Phase 1 (Core Pages)
- Sibling tasks TASK-30-1 and TASK-30-2 already at "To Develop" status
- Can proceed in parallel with other Phase 1 tasks
- No blocking dependencies - FEAT-018 indicator info is self-contained in task

**Key Implementation Notes for PreTester/Developer:**
1. **Indicator Categories:** 4 categories with specific weights already defined
   - Technical (30%): robots.txt, sitemap, SSL, mobile-friendly, canonical, hreflang
   - Content (40%): title tag, meta description, H1, keyword density, images
   - Performance (20%): page speed, Core Web Vitals
   - UX (10%): mobile viewport, font readability
2. **FAQ Accordion:** Keyboard navigable, ARIA attributes required
3. **Schema.org:** FAQPage markup for FAQ section (SEO boost)
4. **Visual Elements:** Icons for steps, progress bars for weight distribution

**Test Design Notes for PreTester:**
- Component rendering tests (page.tsx, HowItWorksContent.tsx)
- SEO metadata validation (title, description, robots, OG tags, FAQPage schema)
- Accessibility tests (heading hierarchy, FAQ keyboard nav, screen reader)
- Visual element tests (icons, progress bars, accordion behavior)
- Responsive design tests (mobile, tablet, desktop)

**Status:** `To Write Tests` → Ready for PreTester

---

### [2026-03-04 PreTester]
**Test Coverage Complete:**

| Test File | Tests | Assertions | Coverage |
|-----------|-------|------------|----------|
| `__tests__/app/how-it-works/page.test.tsx` | 45+ | 90+ | Page component, rendering, responsive |
| `__tests__/components/pages/HowItWorksContent.test.tsx` | 60+ | 120+ | Content, steps, categories, FAQ |
| `__tests__/app/how-it-works/seo.test.tsx` | 30+ | 60+ | Meta tags, OG, Schema.org |
| `__tests__/a11y/how-it-works.test.tsx` | 25+ | 50+ | Accessibility, keyboard, ARIA |
| **Total** | **160+** | **320+** | |

**Test Categories Implemented:**

| Category | Tests | Description |
|----------|-------|-------------|
| Page Rendering | 5 | Component mounts, route, structure |
| Content Structure | 5 | Steps, categories, scoring, FAQ sections |
| Responsive Design | 4 | Mobile, tablet, desktop layouts |
| User Journey Steps | 8 | 4-5 steps, icons, numbers, descriptions |
| Indicator Categories | 12 | 4 categories, weights, indicator lists |
| Scoring Methodology | 5 | Score range, weights, visual representation |
| FAQ Section | 8 | 5-10 items, accordion, keyboard, ARIA |
| Visual Elements | 5 | Icons, progress bars, charts, animations |
| SEO Meta Tags | 7 | Title, description, robots, canonical |
| Open Graph | 6 | OG tags for social sharing |
| Schema.org | 18 | WebPage + FAQPage JSON-LD |
| Accessibility | 25+ | Headings, keyboard, ARIA, contrast |
| Security | 2 | XSS, sensitive data |
| Performance | 2 | Render time, re-renders |

**Key Implementation Notes for Developer:**

1. **Route Structure:**
   - File: `frontend/app/how-it-works/page.tsx`
   - Component: `frontend/components/pages/HowItWorksContent.tsx`

2. **User Journey Steps (4-5 steps with icons):**
   - Icons: Search, Settings, BarChart, Lightbulb, RefreshCw (Lucide)
   - Each step: number, icon, title, description
   - All icons must have aria-label or aria-hidden

3. **Indicator Categories (exact weights):**
   - Technical: 30% (robots.txt, sitemap, SSL, mobile-friendly, canonical, hreflang)
   - Content: 40% (title tag, meta description, H1, keyword density, images)
   - Performance: 20% (page speed, Core Web Vitals)
   - UX: 10% (mobile viewport, font readability)
   - **Must sum to 100%**

4. **FAQ Accordion (5-10 items):**
   - Use shadcn/ui Accordion component
   - Required ARIA: aria-expanded, aria-controls
   - Keyboard: Enter/Space to toggle
   - Support prefers-reduced-motion

5. **Schema.org (both required):**
   - WebPage schema (name, description, url)
   - FAQPage schema (mainEntity with 5-10 Questions)
   - Script type: `application/ld+json`

6. **SEO Metadata:**
   - Title: "How It Works | SEOIndicator SEO Analysis" (max 60 chars)
   - Description: 150-160 characters
   - Robots: "index, follow"

7. **Accessibility Requirements:**
   - Single H1: "How It Works"
   - Heading hierarchy: H1 → H2 → H3 (no skipped levels)
   - Touch targets: min 44x44px
   - Color contrast: ≥ 4.5:1 for text

**Test Documentation:** `PROJECT-SEOINDICATOR/TESTS/TEST-30-3.md`

**Status:** `To Develop` → Ready for Developer implementation

---

*Created by SpecSystemAnalyst | 2026-03-04*
