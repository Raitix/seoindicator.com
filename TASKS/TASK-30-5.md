# TASK-30-5: Cookie Consent Integration

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Specification
SPEC-030: Legal + About + How It Works + Cookie Consent

## Description
Integrate the CookieConsent component with the root layout and add a footer link for users to manage/withdraw consent.

---

## Acceptance Criteria

### Layout Integration
- [ ] CookieConsent component added to `frontend/app/layout.tsx`
- [ ] Component renders at root level (above all page content)
- [ ] No layout shift when banner appears/disappears
- [ ] Works on all pages (global)

### Footer Link
- [ ] "Cookie Settings" link added to footer
- [ ] Clicking link shows consent banner again (for withdrawal)
- [ ] Link visible even after initial consent given

### Consent Withdrawal
- [ ] Users can change consent preferences via footer link
- [ ] New consent overwrites previous (with new timestamp)
- [ ] Analytics/marketing scripts respond to consent changes

### Script Integration (Future-Proofing)
- [ ] Create hook/utility for checking consent:
  ```typescript
  // Example utility
  const hasConsent = (type: 'analytics' | 'marketing'): boolean
  ```
- [ ] Document how to gate analytics scripts with consent
- [ ] No actual analytics integration (future task)

### Testing
- [ ] Banner appears on fresh visit (cleared localStorage)
- [ ] Banner disappears after consent
- [ ] Footer link shows banner again
- [ ] Consent persists across page refreshes

---

## Dependencies
- **TASK-30-4** (Cookie Consent Component) - Must be completed first
- Footer component (existing)

---

## Effort Estimate
`Small` (2-4 hours)
- Simple integration work
- Footer link addition
- Consent state utility

---

## Files to Create/Modify

### Modified Files
```
frontend/app/layout.tsx
frontend/components/layout/Footer.tsx (or equivalent)
```

### New Files (if needed)
```
frontend/lib/utils/cookieConsent.ts (if not created in TASK-30-4)
```

---

## Comments
<!-- Agent communication happens here -->

---

*Created by SpecSystemAnalyst | 2026-03-04*
