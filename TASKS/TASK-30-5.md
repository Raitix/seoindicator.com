# TASK-30-5: Cookie Consent Integration

## STATUS
`To Write Tests`

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

### [2026-03-05 ProjectManager]
**Prioritization In Progress**

**Priority: HIGH (confirmed)**
- GDPR/CCPA compliance is legally required for EU/CA users
- Integration task essential for cookie consent feature to function
- Enables actual consent collection across all pages
- Part of SPEC-030 Legal + About + How It Works feature

**Risk: LOW (confirmed)**
- Frontend-only integration work (layout.tsx + footer)
- No external APIs or complex business logic
- Simple component placement and footer link
- Consent state utility is straightforward

**Dependencies Analysis:**
- **TASK-30-4** (Cookie Consent Component): `To Develop` ❌ - NOT DONE
  - Component not yet implemented
  - PreTester completed 150+ tests for TASK-30-4
  - Developer needs to implement component first

**TDD Approach - Tests CAN Be Designed:**
PreTester can design tests NOW with mocked CookieConsent component interface:

| Test Category | Description |
|---------------|-------------|
| Layout Integration | Component renders at root level in layout.tsx |
| Global Rendering | Component works on all pages |
| No Layout Shift | Banner appears/disappears without visual disruption |
| Footer Link | "Cookie Settings" link shows consent banner |
| Consent Withdrawal | Users can change preferences via footer link |
| Consent Persistence | Preferences persist across page refreshes |
| Consent Utility | `hasConsent('analytics' \| 'marketing')` works correctly |

**Execution Order:**
- TASK-30-4 (Component) → TASK-30-5 (Integration)
- Tests can be designed in parallel with TASK-30-4 development

**Status:** Moving to `To Write Tests` with TDD approach

---

*Created by SpecSystemAnalyst | 2026-03-04*
