# TASK-30-7: Accessibility Tests

## STATUS
`To Prioritize`

## PRIORITY
`Medium`

## RISK
`Low`

## Specification
SPEC-030: Legal + About + How It Works + Cookie Consent

## Description
Create accessibility (A11y) tests for all pages and the cookie consent banner to ensure WCAG 2.1 AA compliance.

---

## Acceptance Criteria

### Automated A11y Tests
- [ ] Jest-axe or similar library configured
- [ ] All pages pass automated accessibility scan:
  - [ ] Terms page
  - [ ] About page
  - [ ] How It Works page
- [ ] CookieConsent component passes automated scan

### Keyboard Navigation Tests
- [ ] All pages navigable via keyboard (Tab, Enter)
- [ ] CookieConsent banner:
  - [ ] Tab reaches all buttons
  - [ ] Enter triggers button actions
  - [ ] Escape closes banner (optional)
- [ ] FAQ accordion:
  - [ ] Tab reaches each question
  - [ ] Enter/Space toggles expand/collapse

### Focus Management Tests
- [ ] Focus moves to banner on first visit
- [ ] Focus returns to page after banner dismissal
- [ ] No focus traps on any page

### Color Contrast Tests
- [ ] All text meets 4.5:1 contrast ratio
- [ ] Interactive elements meet 3:1 contrast ratio
- [ ] Document any acceptable contrast exceptions

### Screen Reader Tests (Manual)
- [ ] All content readable by screen reader
- [ ] ARIA labels announced correctly
- [ ] Consent changes announced
- [ ] FAQ state changes announced

### Test File Locations
```
frontend/__tests__/a11y/terms.a11y.test.tsx
frontend/__tests__/a11y/about.a11y.test.tsx
frontend/__tests__/a11y/how-it-works.a11y.test.tsx
frontend/__tests__/a11y/cookie-consent.a11y.test.tsx
```

---

## Dependencies
- **TASK-30-1** (Terms Page)
- **TASK-30-2** (About Page)
- **TASK-30-3** (How It Works Page)
- **TASK-30-4** (Cookie Consent Component)
- **TASK-30-5** (Integration)

---

## Effort Estimate
`Medium` (4-8 hours)
- Multiple pages to test
- Keyboard navigation scenarios
- Manual screen reader testing

---

## Files to Create/Modify

### New Files
```
frontend/__tests__/a11y/terms.a11y.test.tsx
frontend/__tests__/a11y/about.a11y.test.tsx
frontend/__tests__/a11y/how-it-works.a11y.test.tsx
frontend/__tests__/a11y/cookie-consent.a11y.test.tsx
```

---

## Comments
<!-- Agent communication happens here -->

---

*Created by SpecSystemAnalyst | 2026-03-04*
