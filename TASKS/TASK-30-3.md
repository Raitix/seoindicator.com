# TASK-30-3: How It Works Page

## STATUS
`To Prioritize`

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

---

*Created by SpecSystemAnalyst | 2026-03-04*
