# TEST-30-5: Cookie Consent Integration

## Task Reference
- **Task:** TASK-30-5: Cookie Consent Integration
- **Specification:** SPEC-030: Legal + About + How It Works + Cookie Consent
- **Status:** Tests Coverage In Progress

## Overview
Comprehensive test suite for integrating the CookieConsent component with the root layout, adding a footer "Cookie Settings" link for consent management/withdrawal, and creating the `hasConsent` utility hook for gating analytics/marketing scripts.

---

## Test Files

| File | Type | Tests | Description |
|------|------|-------|-------------|
| `frontend/__tests__/app/layout-integration.test.tsx` | Integration | 20+ | Layout.tsx CookieConsent integration tests |
| `frontend/__tests__/components/layout/Footer.test.tsx` | Component | 25+ | Footer component with Cookie Settings link |
| `frontend/__tests__/lib/hooks/useConsent.test.ts` | Hook | 30+ | Consent checking hook tests |
| `frontend/__tests__/integration/cookie-consent-flow.test.tsx` | E2E | 15+ | Full consent flow integration tests |
| **Total** | | **90+** | |

---

## Test Scenarios

### Layout Integration Tests (layout-integration.test.tsx)

#### Component Rendering (LR-001 to LR-006)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| LR-001 | CookieConsent rendered in layout | Render RootLayout | CookieConsent in document |
| LR-002 | Component at root level | Render layout | Component not nested in other containers |
| LR-003 | Renders after children | Render layout | Children rendered before banner |
| LR-004 | Present on all routes | Navigate to /dashboard | Banner visible (no consent) |
| LR-005 | Present on tools pages | Navigate to /tools | Banner visible (no consent) |
| LR-006 | Present on admin pages | Navigate to /admin | Banner visible (no consent) |

#### No Layout Shift (LS-001 to LS-004)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| LS-001 | Banner doesn't shift content | Banner appears | No CLS (Cumulative Layout Shift) |
| LS-002 | Fixed positioning used | Inspect banner styles | `position: fixed` class |
| LS-003 | Bottom positioning | Inspect banner styles | `bottom: 0` or equivalent |
| LS-004 | High z-index | Inspect banner styles | z-index > 1000 |

#### Provider Integration (PI-001 to PI-003)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PI-001 | Works within Providers | Render with Providers | No context errors |
| PI-002 | Access to theme context | Toggle dark mode | Banner styles adapt |
| PI-003 | No hydration mismatch | SSR render | No hydration warnings |

#### Conditional Rendering (CR-001 to CR-004)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CR-001 | Hidden when consent exists | hasCookieConsent=true | No banner rendered |
| CR-002 | Visible when no consent | hasCookieConsent=false | Banner visible |
| CR-003 | Visible when consent cleared | clearCookieConsent() | Banner appears |
| CR-004 | Re-renders on consent change | Accept consent | Banner disappears |

---

### Footer Component Tests (Footer.test.tsx)

#### Basic Rendering (FR-001 to FR-005)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FR-001 | Footer renders | Component mount | Footer visible |
| FR-002 | Has contentinfo role | Inspect attributes | role="contentinfo" |
| FR-003 | Contains copyright | Component mount | "© 2026 SEOIndicator" text |
| FR-004 | Has navigation links | Component mount | Links to /terms, /about, etc. |
| FR-005 | Responsive layout | Mobile viewport | Stacked layout on mobile |

#### Cookie Settings Link (CS-001 to CS-010)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CS-001 | Link renders in footer | Component mount | "Cookie Settings" link visible |
| CS-002 | Link has accessible name | Inspect link | aria-label or text |
| CS-003 | Link is keyboard accessible | Tab to link | Link focusable |
| CS-004 | Link visible after consent | hasCookieConsent=true | Link still visible |
| CS-005 | Link visible without consent | hasCookieConsent=false | Link visible |
| CS-006 | Click shows consent banner | Click link | CookieConsent appears |
| CS-007 | Click triggers showBanner | Click link | showBanner(true) called |
| CS-008 | Focus moves to banner | Click link | Focus on Accept button |
| CS-009 | Link has appropriate icon | Component mount | Cookie/settings icon present |
| CS-010 | Link position in footer | Component mount | In secondary/legal links area |

#### Footer Structure (FS-001 to FS-005)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FS-001 | Multi-column layout | Desktop viewport | Columns for links |
| FS-002 | Mobile responsive | Mobile viewport | Single column |
| FS-003 | Separator before Cookie Settings | Component mount | Visual separator |
| FS-004 | Consistent styling | Multiple renders | Same appearance |
| FS-005 | Links have hover states | Hover link | Hover styling applied |

---

### useConsent Hook Tests (useConsent.test.ts)

#### Basic Hook Behavior (HB-001 to HB-006)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| HB-001 | Returns consent state | Render hook | { analytics, marketing, necessary } |
| HB-002 | Returns false when no consent | Empty localStorage | all false except necessary |
| HB-003 | Returns stored values | Valid consent | Stored values returned |
| HB-004 | Subscribes to storage changes | Storage event | Updated values |
| HB-005 | Handles invalid consent | Malformed JSON | Returns defaults |
| HB-006 | Returns hasConsent function | Render hook | Function available |

#### hasConsent Function (HC-001 to HC-010)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| HC-001 | hasConsent('analytics') with consent | analytics=true | true |
| HC-002 | hasConsent('analytics') without | analytics=false | false |
| HC-003 | hasConsent('marketing') with consent | marketing=true | true |
| HC-004 | hasConsent('marketing') without | marketing=false | false |
| HC-005 | hasConsent with no stored consent | Empty localStorage | false |
| HC-006 | hasConsent after consent change | Update consent | Reflects change |
| HC-007 | hasConsent with invalid type | 'invalid' | TypeScript error / false |
| HC-008 | Returns false for null consent | null consent | false |
| HC-009 | Returns false for malformed consent | Invalid structure | false |
| HC-010 | Memoized result | Same consent | Same reference |

#### Consent Update (CU-001 to HC-006)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CU-001 | updateConsent available | Render hook | Function available |
| CU-002 | updateConsent writes storage | Call with options | localStorage updated |
| CU-003 | Triggers re-render | Update consent | Component re-renders |
| CU-004 | Updates timestamp | Update consent | New timestamp generated |
| CU-005 | Preserves other values | Partial update | Unchanged values preserved |
| CU-006 | Notifies subscribers | Update consent | All hooks updated |

#### Cross-Tab Sync (CT-001 to CT-003)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CT-001 | Syncs across tabs | Storage event in another tab | Updated state |
| CT-002 | Handles tab visibility | Tab hidden, then visible | Syncs on visibility change |
| CT-003 | Cleanup on unmount | Unmount hook | Event listener removed |

#### Edge Cases (EC-001 to EC-004)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | localStorage unavailable | Mock undefined | No error, default values |
| EC-002 | Quota exceeded | DOMException | No error |
| EC-003 | SSR environment | No window | Default values |
| EC-004 | Rapid consent changes | Multiple updates | Latest value used |

---

### Integration Flow Tests (cookie-consent-flow.test.tsx)

#### Full Consent Flow (FF-001 to FF-007)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FF-001 | Fresh visit shows banner | Clear storage, visit | Banner visible |
| FF-002 | Accept All stores consent | Click Accept All | Consent stored with all true |
| FF-003 | Banner hides after accept | Click Accept All | Banner disappears |
| FF-004 | Consent persists refresh | Refresh page | Banner still hidden |
| FF-005 | Footer link shows banner | Click Cookie Settings | Banner appears again |
| FF-006 | Can change consent | Click Necessary Only | Consent updated |
| FF-007 | New consent overwrites old | Multiple changes | Latest consent stored |

#### Consent Withdrawal Flow (WF-001 to WF-005)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| WF-001 | Withdraw via footer link | Click Cookie Settings | Banner visible |
| WF-002 | Change from all to necessary | Was accepted, click Necessary | Only necessary=true |
| WF-003 | Change from necessary to all | Was declined, click Accept All | All true |
| WF-004 | Timestamp updated on change | Change consent | New timestamp |
| WF-005 | Previous consent replaced | Multiple changes | Only latest stored |

#### Analytics Integration (AI-001 to AI-005)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AI-001 | No tracking before consent | Fresh visit | No analytics calls |
| AI-002 | Analytics enabled after accept | Accept All | canEnableAnalytics=true |
| AI-003 | Analytics disabled after decline | Necessary Only | canEnableAnalytics=false |
| AI-004 | Consent change reflected | Update consent | Hook returns new value |
| AI-005 | Marketing consent separate | Accept only analytics | marketing=false |

#### Cross-Page Consistency (CP-001 to CP-004)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-001 | Same consent on dashboard | Navigate to /dashboard | Same consent state |
| CP-002 | Same consent on tools | Navigate to /tools | Same consent state |
| CP-003 | Footer link works everywhere | Click from /tools | Banner shows |
| CP-004 | Banner state preserved | Navigate between pages | Consistent visibility |

#### Error Recovery (ER-001 to ER-003)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ER-001 | Corrupted consent ignored | Invalid JSON in storage | Banner shows, defaults used |
| ER-002 | Clear consent recovers | Clear storage | Banner shows |
| ER-003 | API failure graceful | Network error | Local state still works |

---

## Mock Data

### Consent Hook Return Type
```typescript
interface UseConsentReturn {
  consent: CookieConsentState | null;
  hasConsent: (type: 'analytics' | 'marketing') => boolean;
  updateConsent: (updates: Partial<CookieConsentState>) => void;
  clearConsent: () => void;
  showBanner: boolean;
  setShowBanner: (show: boolean) => void;
}
```

### Footer Props
```typescript
interface FooterProps {
  showCookieSettings?: boolean;  // Default: true
  className?: string;
}
```

### Mock Scenarios
```typescript
// Fresh visit - no consent
const noConsentStorage = {};

// Full consent
const fullConsentStorage = {
  seoindicator_cookie_consent: JSON.stringify({
    necessary: true,
    analytics: true,
    marketing: true,
    timestamp: '2026-03-05T10:00:00Z'
  })
};

// Necessary only
const necessaryOnlyStorage = {
  seoindicator_cookie_consent: JSON.stringify({
    necessary: true,
    analytics: false,
    marketing: false,
    timestamp: '2026-03-05T10:00:00Z'
  })
};
```

---

## Coverage Targets

| Category | Target | Description |
|----------|--------|-------------|
| Layout Integration | 100% | All layout scenarios covered |
| Footer Component | 100% | All footer interactions |
| Consent Hook | 100% | All hook behaviors |
| Integration Flow | 100% | All user flows |
| Error Handling | 100% | All error scenarios |

---

## Test Execution

```bash
# Run all tests
npm test

# Run specific test files
npm test -- layout-integration.test.tsx
npm test -- Footer.test.tsx
npm test -- useConsent.test.ts
npm test -- cookie-consent-flow.test.tsx

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
| @testing-library/user-event | Available | User interaction |
| @testing-library/react-hooks | Available | Hook testing |
| localStorage Mock | Available | Browser storage mock |
| TASK-30-4 Tests | Complete | CookieConsent component interface |

---

## Notes for Developer

### Files to Create/Modify

1. **Modified Files:**
   ```
   frontend/app/layout.tsx           - Add CookieConsent component
   frontend/app/page.tsx             - Add Footer component (or create separate)
   ```

2. **New Files:**
   ```
   frontend/components/layout/Footer.tsx       - Footer with Cookie Settings link
   frontend/lib/hooks/useConsent.ts           - Consent checking hook
   ```

### Implementation Notes

1. **Layout Integration:**
   ```tsx
   // frontend/app/layout.tsx
   import { CookieConsent } from '@/components/layout/CookieConsent';
   
   export default function RootLayout({ children }) {
     return (
       <html lang="en">
         <body>
           <SkipLink />
           <GoogleAnalytics />
           <Providers>
             <SkipToMain>{children}</SkipToMain>
             <CookieConsent />
           </Providers>
         </body>
       </html>
     );
   }
   ```

2. **Footer with Cookie Settings:**
   ```tsx
   // frontend/components/layout/Footer.tsx
   'use client';
   
   import { useConsent } from '@/lib/hooks/useConsent';
   
   export function Footer() {
     const { setShowBanner } = useConsent();
     
     return (
       <footer role="contentinfo">
         {/* ... other footer content ... */}
         <button onClick={() => setShowBanner(true)}>
           Cookie Settings
         </button>
       </footer>
     );
   }
   ```

3. **useConsent Hook:**
   ```tsx
   // frontend/lib/hooks/useConsent.ts
   export function useConsent(): UseConsentReturn {
     const [consent, setConsent] = useState(getCookieConsent());
     const [showBanner, setShowBanner] = useState(false);
     
     const hasConsent = (type: 'analytics' | 'marketing') => {
       return consent?.[type] ?? false;
     };
     
     const updateConsent = (updates) => {
       const newConsent = { ...consent, ...updates };
       setCookieConsent(newConsent);
       setConsent(newConsent);
     };
     
     // ... rest of implementation
   }
   ```

4. **Gating Analytics (Future):**
   ```tsx
   // Example usage in analytics component
   const { hasConsent } = useConsent();
   
   useEffect(() => {
     if (hasConsent('analytics')) {
       // Initialize Google Analytics
     }
   }, [hasConsent]);
   ```

5. **Key Implementation Points:**
   - CookieConsent must be rendered at root level (layout.tsx)
   - Footer must be a client component (uses useConsent hook)
   - useConsent hook should sync across tabs via storage event
   - showBanner state controls CookieConsent visibility
   - hasConsent is memoized for performance

---

## Comments

### [2026-03-05 PreTester]
**Tests Coverage In Progress**

Created comprehensive test suite for Cookie Consent Integration:

**Test Categories:**
- Layout Integration (12 tests)
- No Layout Shift (4 tests)
- Provider Integration (3 tests)
- Conditional Rendering (4 tests)
- Footer Basic Rendering (5 tests)
- Cookie Settings Link (10 tests)
- Footer Structure (5 tests)
- Hook Basic Behavior (6 tests)
- hasConsent Function (10 tests)
- Consent Update (6 tests)
- Cross-Tab Sync (3 tests)
- Edge Cases (4 tests)
- Full Consent Flow (7 tests)
- Consent Withdrawal Flow (5 tests)
- Analytics Integration (5 tests)
- Cross-Page Consistency (4 tests)
- Error Recovery (3 tests)

**Total:** 90+ tests

**Status:** Ready for Developer implementation

---

*Created by PreTester | 2026-03-05*
