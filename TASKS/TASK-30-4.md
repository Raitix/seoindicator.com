# TASK-30-4: Cookie Consent Component

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Specification
SPEC-030: Legal + About + How It Works + Cookie Consent

## Description
Create the CookieConsent banner component with state management for GDPR/CCPA compliance. This component manages user consent preferences for analytics and marketing cookies.

---

## Acceptance Criteria

### UI Behavior
- [ ] Fixed position at bottom of viewport
- [ ] Appears on first visit (no localStorage record)
- [ ] Disappears after user action (accept/decline)
- [ ] Does not reappear after consent given
- [ ] "Learn more" link to privacy policy (`/terms`)

### Buttons
- [ ] "Accept All" button (primary style)
- [ ] "Necessary Only" button (secondary style)
- [ ] Optional: "Customize" option (expandable preferences)

### State Management
- [ ] Interface defined:
  ```typescript
  interface CookieConsentState {
    necessary: boolean;      // Always true
    analytics: boolean;      // User choice
    marketing: boolean;      // User choice
    timestamp: string;       // When consent was given
  }
  ```
- [ ] localStorage key: `seoindicator_cookie_consent`
- [ ] State persisted after user action
- [ ] Consent record includes timestamp

### GDPR/CCPA Compliance
- [ ] Consent must be freely given, specific, and informed
- [ ] Pre-checked boxes NOT allowed
- [ ] Clear separation between cookie categories
- [ ] No tracking before consent
- [ ] Consent stored locally (localStorage only)

### Accessibility
- [ ] ARIA labels for all buttons
- [ ] Focus management on banner appearance/dismissal
- [ ] Screen reader announcements for consent changes
- [ ] Keyboard navigable (Tab, Enter, Escape)

### Technical
- [ ] Component: `frontend/components/layout/CookieConsent.tsx`
- [ ] Utilities: `frontend/lib/utils/cookieConsent.ts`
- [ ] No external dependencies (pure localStorage)

---

## Dependencies
- None (standalone component)

---

## Effort Estimate
`Medium` (4-8 hours)
- GDPR compliance requirements
- State management logic
- Accessibility considerations

---

## Files to Create/Modify

### New Files
```
frontend/components/layout/CookieConsent.tsx
frontend/lib/utils/cookieConsent.ts
```

### Modified Files
- None (integration handled by TASK-30-5)

---

## Comments
<!-- Agent communication happens here -->

---

*Created by SpecSystemAnalyst | 2026-03-04*
