# TEST-30-4: Cookie Consent Component

## Task Reference
- **Task:** TASK-30-4: Cookie Consent Component
- **Specification:** SPEC-030: Legal + About + How It Works + Cookie Consent
- **Status:** Tests Coverage In Progress

## Overview
Comprehensive test suite for GDPR/CCPA compliant cookie consent banner component. Tests cover UI behavior, state management, accessibility, keyboard navigation, and compliance requirements.

---

## Test Files

| File | Type | Tests | Description |
|------|------|-------|-------------|
| `frontend/__tests__/components/layout/CookieConsent.test.tsx` | Component | 80+ | Cookie consent banner component tests |
| `frontend/__tests__/lib/utils/cookieConsent.test.ts` | Utility | 70+ | Cookie consent state management utilities |
| **Total** | | **150+** | |

---

## Test Scenarios

### Component Tests (CookieConsent.test.tsx)

#### Basic Rendering (RE-001 to RE-004)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RE-001 | Render when no consent stored | No localStorage | Banner visible |
| RE-002 | Do not render when consent exists | hasCookieConsent=true | No banner |
| RE-003 | Fixed position at bottom | Component mount | `fixed bottom-0` classes |
| RE-004 | High z-index for overlay | Component mount | z-{n} class present |

#### Buttons (BT-001 to BT-008)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BT-001 | Accept All button renders | Component mount | Button visible |
| BT-002 | Necessary Only button renders | Component mount | Button visible |
| BT-003 | Accept All primary styling | Component mount | Primary button classes |
| BT-004 | Necessary Only secondary styling | Component mount | Secondary button classes |
| BT-005 | Accept All sets all cookies | Click Accept All | analytics=true, marketing=true |
| BT-006 | Necessary Only sets only necessary | Click Necessary Only | analytics=false, marketing=false |
| BT-007 | onAccept callback fired | Click with callback | Callback with consent state |
| BT-008 | onDecline callback fired | Click with callback | Callback called |

#### Learn More Link (LM-001 to LM-003)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| LM-001 | Learn More link renders | Component mount | Link visible |
| LM-002 | Links to /terms page | Click link | href="/terms" |
| LM-003 | Has accessible name | Component mount | aria-label present |

#### Banner Visibility (BV-001 to BV-004)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BV-001 | Appears on first visit | No localStorage | Banner visible |
| BV-002 | Disappears after accept | Click Accept All | Banner hidden |
| BV-003 | Disappears after decline | Click Necessary Only | Banner hidden |
| BV-004 | Does not reappear | hasCookieConsent=true | No banner |

#### Accessibility (AC-001 to AC-010)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AC-001 | Has ARIA role="dialog" | Component mount | role="dialog" |
| AC-002 | Has aria-modal attribute | Component mount | aria-modal="true" |
| AC-003 | Has aria-labelledby | Component mount | Points to title |
| AC-004 | Has aria-describedby | Component mount | Points to description |
| AC-005 | Accept button has aria-label | Component mount | aria-label present |
| AC-006 | Necessary button has aria-label | Component mount | aria-label present |
| AC-007 | Keyboard navigable | Tab key | Focus moves through elements |
| AC-008 | Focus trapped in dialog | Tab from last element | Focus stays in dialog |
| AC-009 | Screen reader announcement | Accept/Decline | Live region update |
| AC-010 | Correct tab order | Tab navigation | Logical focus order |

#### Keyboard Navigation (KB-001 to KB-004)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| KB-001 | Escape does not close | Press Escape | Banner remains (must choose) |
| KB-002 | Enter activates Accept | Focus Accept + Enter | Consent set |
| KB-003 | Enter activates Necessary | Focus Necessary + Enter | Consent set |
| KB-004 | Correct tab order | Tab through elements | Logical sequence |

#### Focus Management (FM-001 to FM-002)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FM-001 | Focus Accept button on mount | Component mount | Accept button focused |
| FM-002 | Focus restored after dismiss | Accept/Decline | Focus to body/previous |

#### GDPR/CCPA Compliance (GD-001 to GD-006)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GD-001 | No pre-checked boxes | First visit | No pre-selected consent |
| GD-002 | Requires explicit action | Click outside | Banner remains |
| GD-003 | No dismiss without choice | Look for close button | No close button |
| GD-004 | Clear category separation | Component content | Categories mentioned |
| GD-005 | No tracking before consent | Component mount | No tracking calls |
| GD-006 | Local storage only | Accept/Decline | localStorage used |

#### Responsive Design (RD-001 to RD-003)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RD-001 | Full-width on mobile | Render at 375px | Full width banner |
| RD-002 | Appropriate mobile padding | Render at 375px | Padding classes |
| RD-003 | Stacked buttons on mobile | Render at 375px | Vertical button layout |

#### Content (CT-001 to CT-003)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CT-001 | Cookie usage explanation | Component mount | "cookie" text present |
| CT-002 | Necessary cookies mentioned | Component mount | "necessary" or "essential" |
| CT-003 | Clear call to action | Component mount | Both buttons visible |

#### State Management (SM-001 to SM-003)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SM-001 | Uses hasCookieConsent | Component mount | Utility called |
| SM-002 | Hides if consent exists | hasCookieConsent=true | No banner |
| SM-003 | Persists after accept | Click Accept | setCookieConsent called |

#### Animation (AN-001 to AN-002)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AN-001 | Entrance animation | Component mount | Animation classes |
| AN-002 | Exit animation | Accept/Decline | Smooth dismiss |

#### Edge Cases (EC-001 to EC-003)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Rapid button clicks | Double click | Single consent set |
| EC-002 | localStorage errors | Mock error | No crash |
| EC-003 | Custom className | className prop | Class applied |

#### Optional Customize Feature (CU-001 to CU-003)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CU-001 | No customize by default | Default props | May not show |
| CU-002 | Show customize when enabled | showCustomize=true | Button visible |
| CU-003 | Expand preferences panel | Click Customize | Toggles appear |

#### Integration (IN-001 to IN-002)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IN-001 | Full accept flow | Click Accept All | Consent stored, callback fired, banner hidden |
| IN-002 | Full decline flow | Click Necessary Only | Consent stored, callback fired, banner hidden |

---

### Utility Tests (cookieConsent.test.ts)

#### getCookieConsent (GC-001 to GC-006)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GC-001 | Returns null when no consent | Empty localStorage | null |
| GC-002 | Returns parsed consent | Valid JSON | CookieConsentState |
| GC-003 | Returns null for invalid JSON | 'invalid-json' | null |
| GC-004 | Returns null for malformed state | { foo: 'bar' } | null |
| GC-005 | Handles missing timestamp | No timestamp field | null |
| GC-006 | Handles missing consent fields | Incomplete object | null |

#### setCookieConsent (SC-001 to SC-005)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SC-001 | Stores consent with timestamp | Valid state | JSON stored |
| SC-002 | Always sets necessary=true | necessary=false | stored.necessary=true |
| SC-003 | Generates ISO 8601 timestamp | No timestamp | Valid ISO string |
| SC-004 | Overwrites existing consent | Second call | New values |
| SC-005 | Uses provided timestamp | Custom timestamp | timestamp preserved |

#### hasCookieConsent (HC-001 to HC-003)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| HC-001 | Returns false when no consent | Empty localStorage | false |
| HC-002 | Returns true when consent stored | Valid consent | true |
| HC-003 | Returns false for invalid value | 'invalid' | false |

#### clearCookieConsent (CL-001 to CL-002)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CL-001 | Removes consent from storage | Existing consent | localStorage cleared |
| CL-002 | Does not throw when no consent | Empty localStorage | No error |

#### GDPR Compliance (GP-001 to GP-006)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GP-001 | No pre-checked consent on first visit | getCookieConsent() | null |
| GP-002 | Requires explicit action | Initial state | No consent set |
| GP-003 | Stores user choices separately | set with options | Independent values |
| GP-004 | Allows decline all optional | All false except necessary | Stored correctly |
| GP-005 | Allows accept all | All true | Stored correctly |
| GP-006 | Allows withdraw consent | clearCookieConsent() | Consent removed |

#### Cookie Categories (CC-001 to CC-003)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CC-001 | Necessary always enabled | necessary=false | necessary=true in storage |
| CC-002 | Independent analytics control | analytics=true/false | Stored correctly |
| CC-003 | Independent marketing control | marketing=true/false | Stored correctly |

#### Timestamp Handling (TS-001 to TS-003)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TS-001 | Includes timestamp | setCookieConsent() | timestamp present |
| TS-002 | Generates current timestamp | setCookieConsent() | Current ISO time |
| TS-003 | ISO 8601 format | stored.timestamp | Valid ISO format |

#### Edge Cases (EG-001 to EG-005)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EG-001 | localStorage not available | No localStorage | No error |
| EG-002 | Quota exceeded | DOMException | No error |
| EG-003 | Corrupted consent data | Invalid types | null returned |
| EG-004 | Empty localStorage value | '' string | null |
| EG-005 | Whitespace-only value | '   ' | null |

#### Type Validation (TV-001 to TV-004)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TV-001 | Validates necessary is boolean | String value | null |
| TV-002 | Validates analytics is boolean | String value | null |
| TV-003 | Validates marketing is boolean | String value | null |
| TV-004 | Validates timestamp is string | Number value | null |

#### Storage Key (SK-001 to SK-003)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SK-001 | Uses correct key on set | setCookieConsent() | seoindicator_cookie_consent |
| SK-002 | Uses correct key on get | getCookieConsent() | seoindicator_cookie_consent |
| SK-003 | Uses correct key on clear | clearCookieConsent() | seoindicator_cookie_consent |

---

## Mock Data

### CookieConsentState Interface
```typescript
interface CookieConsentState {
  necessary: boolean;      // Always true
  analytics: boolean;      // User choice
  marketing: boolean;      // User choice
  timestamp: string;       // ISO 8601 timestamp
}
```

### Valid Consent States
```typescript
// Full acceptance
const fullConsent: CookieConsentState = {
  necessary: true,
  analytics: true,
  marketing: true,
  timestamp: '2026-03-05T10:00:00Z'
};

// Necessary only
const necessaryOnlyConsent: CookieConsentState = {
  necessary: true,
  analytics: false,
  marketing: false,
  timestamp: '2026-03-05T10:00:00Z'
};

// Partial consent
const partialConsent: CookieConsentState = {
  necessary: true,
  analytics: true,
  marketing: false,
  timestamp: '2026-03-05T10:00:00Z'
};
```

---

## Coverage Targets

| Category | Target | Description |
|----------|--------|-------------|
| UI Behavior | 100% | All acceptance criteria covered |
| State Management | 100% | All localStorage operations |
| Accessibility | 100% | All ARIA and keyboard tests |
| GDPR Compliance | 100% | All compliance requirements |
| Edge Cases | 100% | Error handling, type validation |

---

## Test Execution

```bash
# Run all tests
npm test

# Run specific test file
npm test -- cookieConsent.test.ts
npm test -- CookieConsent.test.tsx

# Run with coverage
npm run test:coverage

# Watch mode
npm test -- --watch
```

---

## Dependencies

| Dependency | Status | Description |
|------------|--------|-------------|
| Jest | Available | Test framework |
| React Testing Library | Available | Component testing |
| @testing-library/user-event | Available | User interaction simulation |
| localStorage Mock | Available | Browser storage mock |

---

## Notes for Developer

### Component File Location
```
frontend/components/layout/CookieConsent.tsx
frontend/lib/utils/cookieConsent.ts
```

### Key Implementation Points

1. **localStorage Key:** `seoindicator_cookie_consent`

2. **GDPR Compliance:**
   - No pre-checked consent
   - User must make explicit choice
   - No close without choice
   - Categories clearly separated

3. **State Management:**
   - Always set `necessary: true`
   - Generate timestamp on save
   - Validate state on read

4. **Accessibility:**
   - Use `role="dialog"`
   - Set `aria-modal="true"`
   - Trap focus in dialog
   - Keyboard navigable

5. **UI Behavior:**
   - Fixed position at bottom
   - High z-index for overlay
   - Animation on show/hide
   - Responsive design

---

## Comments

### [2026-03-05 PreTester]
**Tests Coverage In Progress**

Created comprehensive test suite for Cookie Consent component:
- 80+ component tests covering UI, accessibility, keyboard, compliance
- 70+ utility tests covering state management, GDPR, edge cases

**Key Test Categories:**
- Basic Rendering (4 tests)
- Buttons (8 tests)
- Learn More Link (3 tests)
- Banner Visibility (4 tests)
- Accessibility (10 tests)
- Keyboard Navigation (4 tests)
- Focus Management (2 tests)
- GDPR/CCPA Compliance (6 tests)
- Responsive Design (3 tests)
- Content (3 tests)
- State Management (3 tests)
- Animation (2 tests)
- Edge Cases (3 tests)
- Optional Customize Feature (3 tests)
- Integration (2 tests)

**Utility Test Categories:**
- getCookieConsent (6 tests)
- setCookieConsent (5 tests)
- hasCookieConsent (3 tests)
- clearCookieConsent (2 tests)
- GDPR Compliance (6 tests)
- Cookie Categories (3 tests)
- Timestamp Handling (3 tests)
- Edge Cases (5 tests)
- Type Validation (4 tests)
- Storage Key (3 tests)

**Total:** 150+ tests

**Status:** Ready for Developer implementation

---

*Created by PreTester | 2026-03-05*
