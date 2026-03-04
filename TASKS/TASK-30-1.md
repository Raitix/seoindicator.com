# TASK-30-1: Terms & Privacy Page

## STATUS
`To Develop`

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

### PreTester Test Coverage (2026-03-04)

**Test Files Created:**
- `frontend/__tests__/app/terms/page.test.tsx` (45+ tests)
- `TESTS/TEST-30-1.md` (test documentation)

**Test Categories:**
| Category | Tests | Coverage |
|----------|-------|----------|
| Page Component | 10 | File structure, static generation |
| SEO Metadata | 7 | Title, description, robots, OG, canonical, schema |
| Accessibility | 4 | Heading hierarchy, keyboard, contrast, screen reader |
| Content | 8 | All 5 sections, last updated date, navigation |
| Edge Cases | 4 | Empty content, long text, re-render, special chars |
| Performance | 2 | Render time < 100ms, stability |
| Responsive | 3 | Mobile, tablet, desktop |
| Integration | 3 | Homepage link, consistent styling, navigation |

**Key Test Patterns:**
- Dynamic import for file existence verification
- `generateMetadata()` for SEO testing
- JSON-LD schema validation via script tag parsing
- Heading hierarchy validation (single h1, h2s follow)
- Content section verification by role/name queries

**Acceptance Criteria Coverage:**
- All content criteria: ✅ Tests created
- All technical criteria: ✅ Tests created
- All SEO criteria: ✅ Tests created
- All accessibility criteria: ✅ Tests created

**Notes for Developer:**
1. Implement `generateMetadata()` export for SEO tests to pass
2. Include JSON-LD WebPage schema in page component
3. TermsContent component should accept `className` prop
4. Use semantic HTML (`<main>`, `<article>`, proper headings)
5. Consider table of contents for navigation (anchor links or TOC nav)

**Handoff:** Ready for Developer to implement.

---

### ProjectManager Prioritization (2026-03-04)

**Priority Assessment:** HIGH (confirmed)
- Legal requirement page (Terms of Service + Privacy Policy) - essential for production
- User trust and compliance requirement
- Low risk, high value deliverable

**Risk Assessment:** LOW (confirmed)
- Static content page, no external APIs
- No database modifications required
- No authentication/authorization changes
- Easily reversible

**Dependency Analysis:**
- ✅ No dependencies (standalone static page)
- Can proceed immediately to PreTester

**Priority Reasoning:**
1. Legal compliance - Terms & Privacy pages are required for any production website
2. SEO benefit - Proper schema markup and meta tags
3. Low effort (2-4 hours) with high value
4. No blockers - can be developed independently

**Handoff:** Ready for PreTester to design tests.

---

*Created by SpecSystemAnalyst | 2026-03-04*
