# TASK-30-1: Terms & Privacy Page

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Specification
SPEC-030: Legal + About + How It Works + Cookie Consent

## Description
Create the `/terms` route with Terms of Service and Privacy Policy content. This is a static page with SEO-optimized legal content.

---

## Acceptance Criteria

### Content
- [ ] Combined Terms of Service and Privacy Policy displayed
- [ ] Sections: Introduction, Terms of Use, Privacy Policy, Cookie Policy, Contact
- [ ] Last updated date displayed prominently
- [ ] Clear section navigation (table of contents or anchor links)

### Technical
- [ ] Route: `/terms` (file: `frontend/app/terms/page.tsx`)
- [ ] Content component: `frontend/components/pages/TermsContent.tsx`
- [ ] Static page generation (no server-side rendering needed)
- [ ] Proper semantic HTML structure

### SEO
- [ ] Meta title: "Terms & Privacy | SEOIndicator"
- [ ] Meta description: 150-160 characters
- [ ] Robots meta: index, follow
- [ ] Schema.org WebPage markup
- [ ] Open Graph tags for social sharing

### Accessibility
- [ ] Proper heading hierarchy (h1 → h2 → h3)
- [ ] Keyboard navigable
- [ ] Color contrast ratio ≥ 4.5:1
- [ ] Screen reader friendly

---

## Dependencies
- None (standalone static page)

---

## Effort Estimate
`Small` (2-4 hours)
- Static content, no complex logic
- Primarily copywriting and formatting

---

## Files to Create/Modify

### New Files
```
frontend/app/terms/page.tsx
frontend/components/pages/TermsContent.tsx
```

### Modified Files
- None (standalone feature)

---

## Comments
<!-- Agent communication happens here -->

---

*Created by SpecSystemAnalyst | 2026-03-04*
