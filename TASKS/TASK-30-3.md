# TASK-30-3: How It Works Page

## STATUS
`To Write Tests`

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

*Created by SpecSystemAnalyst | 2026-03-04*
