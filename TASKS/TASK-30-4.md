# TASK-30-4: Cookie Consent Component

## STATUS
`To Write Tests`

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

### [2026-03-04 ProjectManager]
**Prioritization Complete**

**Priority: HIGH (confirmed)**
- GDPR/CCPA compliance is legally required for EU/CA users
- No dependencies - standalone component that can proceed immediately
- Enables TASK-30-5 (Consent Integration) once complete

**Risk: MEDIUM (confirmed)**
- GDPR compliance requirements (freely given, specific, informed consent)
- Pre-checked boxes NOT allowed
- No tracking before consent
- Consent stored locally (localStorage only - no server-side storage required)

**Dependencies: None**
- Standalone component with no blocking dependencies
- Can proceed to PreTester immediately

**TDD Ready:** ✅
- PreTester can design tests for:
  - UI behavior (fixed position, appear/dismiss, localStorage persistence)
  - State management (interface structure, persistence)
  - GDPR compliance (no pre-checked boxes, consent categories)
  - Accessibility (ARIA labels, focus management, keyboard navigation)

**Execution Order:**
- This task → TASK-30-5 (Consent Integration)
- Can be developed in parallel with Phase 1 tasks (TASK-30-1, 30-2, 30-3)

**Status:** `To Write Tests` → ready for PreTester

---

*Created by SpecSystemAnalyst | 2026-03-04*
