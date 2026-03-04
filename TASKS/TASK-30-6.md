# TASK-30-6: Unit Tests - Pages & Cookie Consent

## STATUS
`To Prioritize`

## PRIORITY
`Medium`

## RISK
`Low`

## Specification
SPEC-030: Legal + About + How It Works + Cookie Consent

## Description
Create unit tests for all page components (Terms, About, How It Works) and the CookieConsent component.

---

## Acceptance Criteria

### Terms Page Tests
- [ ] Page renders without errors
- [ ] All sections present (Introduction, Terms, Privacy, Cookie Policy, Contact)
- [ ] Last updated date displayed
- [ ] Meta tags correct

### About Page Tests
- [ ] Page renders without errors
- [ ] Hero section present
- [ ] Features section present
- [ ] CTA button links to correct route
- [ ] Meta tags correct

### How It Works Page Tests
- [ ] Page renders without errors
- [ ] Step-by-step journey displayed
- [ ] Indicator categories displayed with correct weights
- [ ] FAQ accordion expands/collapses
- [ ] Meta tags correct

### CookieConsent Component Tests
- [ ] Component renders when no consent in localStorage
- [ ] "Accept All" stores correct consent state
- [ ] "Necessary Only" stores correct consent state
- [ ] Component hidden after consent given
- [ ] localStorage key is correct (`seoindicator_cookie_consent`)
- [ ] Timestamp recorded in consent state

### Test File Locations
```
frontend/__tests__/app/terms/page.test.tsx
frontend/__tests__/app/about/page.test.tsx
frontend/__tests__/app/how-it-works/page.test.tsx
frontend/__tests__/components/layout/CookieConsent.test.tsx
```

---

## Dependencies
- **TASK-30-1** (Terms Page)
- **TASK-30-2** (About Page)
- **TASK-30-3** (How It Works Page)
- **TASK-30-4** (Cookie Consent Component)
- **TASK-30-5** (Integration) - Optional, can mock

---

## Effort Estimate
`Medium` (4-8 hours)
- Multiple components to test
- localStorage mocking required
- FAQ accordion interaction tests

---

## Files to Create/Modify

### New Files
```
frontend/__tests__/app/terms/page.test.tsx
frontend/__tests__/app/about/page.test.tsx
frontend/__tests__/app/how-it-works/page.test.tsx
frontend/__tests__/components/layout/CookieConsent.test.tsx
```

---

## Comments
<!-- Agent communication happens here -->

---

*Created by SpecSystemAnalyst | 2026-03-04*
