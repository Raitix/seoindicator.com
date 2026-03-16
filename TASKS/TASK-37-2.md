# TASK-37-2: Fix Header Navigation

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Medium`

## Specification
SPEC-037

## Description
Fix the header navigation buttons to be clickable and have proper hover states. Currently "Sign In" and "Indicators" buttons are not functional, and hover states are broken (white-on-white).

## Issues
1. "Sign In" button is a `<button>` element with no onClick handler
2. "Indicators" button is a `<button>` element with no onClick handler
3. Nav buttons have white background on hover, making white text invisible
4. Nav buttons don't show pointer cursor
5. Protected routes (Dashboard, Competitors, Comparison) visible to guests

## Solution

### Fix Button Clickability
Convert buttons to links or add proper handlers:
- "Sign In" → Link to `/login/`
- "Indicators" → Link to `/dashboard/` or `/indicators/`

### Fix Hover States
Change hover class from `hover:bg-white` to `hover:bg-slate-700`

### Fix Cursor
Add `cursor-pointer` class to nav buttons

### Conditional Rendering
Use `isAuthenticated` to hide protected nav items from guests

## Acceptance Criteria
- [ ] "Sign In" button navigates to /login/
- [ ] "Indicators" button is clickable and navigates appropriately
- [ ] Nav buttons show pointer cursor on hover
- [ ] Nav buttons have visible hover state (dark background, white text)
- [ ] Dashboard nav item hidden from unauthenticated users
- [ ] Competitors nav item hidden from unauthenticated users
- [ ] Comparison nav item hidden from unauthenticated users
- [ ] Unit tests for navigation behavior

## Technical Details

### File to Modify
`frontend/components/layout/Header.tsx`

### Code Changes
```tsx
// Before
<button className="...">Sign In</button>

// After
<Link href="/login" className="... cursor-pointer hover:bg-slate-700">
  Sign In
</Link>

// Conditional rendering for protected items
{isAuthenticated && (
  <Link href="/dashboard" className="...">Dashboard</Link>
)}
```

## Dependencies
- None

## Effort Estimate
`Small` (1-2 hours)

## Related Issues
- FEAT-037
- SPEC-037

## Comments

### 2026-03-16 13:15 GMT+2 - PreTester
**Test Design Complete:**
- Created test documentation: `TESTS/TEST-37-2.md`
- Created navigation tests: `frontend/__tests__/components/layout/Header.navigation.test.tsx` (40+ tests)
- Created hover state tests: `frontend/__tests__/components/layout/Header.hover-states.test.tsx` (35+ tests)

**Test Coverage:**
| Category | Tests | Description |
|----------|-------|-------------|
| NAV-001 | 4 | Sign In button for guests |
| NAV-002 | 4 | Indicators button navigation |
| NAV-003 | 6 | Protected nav items hidden from guests |
| NAV-004 | 6 | Protected nav items visible to auth users |
| NAV-005 | 5 | Navigation links functionality |
| HOV-001 | 9 | Pointer cursor on all nav elements |
| HOV-002 | 7 | Hover state backgrounds (dark, not white) |
| HOV-003 | 4 | Active state styling |
| HOV-004 | 4 | Focus states |
| HOV-005 | 4 | Element type tests (anchor vs button) |
| HOV-006 | 4 | Notification bell buttons |
| ACC-001 | 4 | Accessibility tests |
| SEC-001 | 3 | Security tests |
| Edge Cases | 6 | Boundary conditions |
| **Total** | **70+** | Full coverage |

**Key Test Findings:**
1. Tests expect `cursor-pointer` class on all nav links
2. Tests verify `hover:bg-slate-*` (dark background) NOT `hover:bg-white`
3. Tests verify guest navigation shows only: Indicators, Sign In
4. Tests verify auth navigation shows all protected items
5. Tests verify all nav items are `<a>` elements (not `<button>`)
6. Tests verify accessibility attributes (aria-label, aria-current)

**Next Step:** Developer to implement changes to pass tests

### 2026-03-16 13:10 GMT+2 - ProjectManager
**Prioritization Assessment:**
- **Priority:** HIGH (confirmed) - Navigation is critical for user experience. Broken buttons prevent users from accessing login/dashboard.
- **Risk:** MEDIUM (confirmed) - Frontend-only changes, no external APIs, but affects user authentication flow visibility.
- **Dependencies:** None - Header component exists at `frontend/components/layout/Header.tsx`.

**Justification for HIGH Priority:**
1. Users cannot navigate to login page from header - blocks authentication flow
2. Protected routes visible to guests - security/usability concern
3. Broken hover states affect UX perception
4. Simple fix with high user impact

**Task Ready for:** PreTester
- Clear acceptance criteria defined
- Technical solution documented
- No blocking dependencies
