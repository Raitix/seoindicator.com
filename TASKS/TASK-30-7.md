# TASK-30-7: Accessibility Tests

## STATUS
`To Write Tests`

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

### [2026-03-05 ProjectManager]
**Prioritization Analysis:**

| Factor | Assessment | Justification |
|--------|------------|---------------|
| **Priority** | Medium (confirmed) | Testing task validates compliance but doesn't enable other work |
| **Risk** | Low (confirmed) | Testing task, no production impact, no external dependencies |
| **Dependencies** | ALL SATISFIED ✅ | All 5 dependencies are Done |
| **Effort** | Medium (4-8 hours) | Multiple pages, keyboard navigation scenarios, manual screen reader testing |

**Dependency Status:**
| Task | Status | Component |
|------|--------|-----------|
| TASK-30-1 (Terms Page) | ✅ Done | `/terms` route |
| TASK-30-2 (About Page) | ✅ Done | `/about` route |
| TASK-30-3 (How It Works Page) | ✅ Done | `/how-it-works` route |
| TASK-30-4 (Cookie Consent Component) | ✅ Done | CookieConsent banner |
| TASK-30-5 (Integration) | ✅ Done | Layout + Footer integration |

**Priority Reasoning:**
1. **WCAG 2.1 AA Compliance** - Important for accessibility and legal compliance
2. **No downstream blockers** - Testing task doesn't enable other work
3. **All dependencies satisfied** - Can proceed immediately to PreTester
4. **Manual testing component** - Screen reader tests require human validation

**Test Categories for PreTester:**

| Category | Pages/Components | Test Type |
|----------|------------------|-----------|
| Automated A11y | Terms, About, How It Works, CookieConsent | jest-axe |
| Keyboard Navigation | All pages + FAQ accordion + Cookie banner | Tab, Enter, Escape |
| Focus Management | CookieConsent banner | First visit, dismissal |
| Color Contrast | All text and interactive elements | 4.5:1 ratio |
| Screen Reader | All pages + Cookie banner | Manual testing |

**TDD Ready:** ✅
- PreTester can design tests immediately using existing page components
- All pages deployed and accessible in production
- CookieConsent component tested but needs A11y-specific coverage

**Key Implementation Notes for PreTester:**
1. **Automated Tests:** Use jest-axe or similar library for WCAG 2.1 AA automated checks
2. **Keyboard Tests:** Focus on FAQ accordion (TASK-30-3) and CookieConsent banner (TASK-30-4)
3. **Focus Management:** CookieConsent focus trap and restoration already implemented
4. **Manual Tests:** Screen reader testing should document:
   - All content readable
   - ARIA labels announced correctly
   - Consent changes announced
   - FAQ state changes announced

**Status:** Ready for PreTester - all dependencies satisfied

---

*Created by SpecSystemAnalyst | 2026-03-04*
