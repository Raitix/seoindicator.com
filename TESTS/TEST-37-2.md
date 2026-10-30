# TEST-37-2: Header Navigation Fix Tests

## Task Reference
- **Task:** [TASK-37-2](../TASKS/TASK-37-2.md)
- **Specification:** SPEC-037
- **Feature:** FEAT-037

## Overview
Test suite for header navigation fixes including clickable navigation buttons, proper hover states, and conditional rendering for authenticated/unauthenticated users.

## Test Files

| File | Tests | Purpose |
|------|-------|---------|
| `frontend/__tests__/components/layout/Header.navigation.test.tsx` | 40+ | Navigation behavior, auth-based rendering |
| `frontend/__tests__/components/layout/Header.hover-states.test.tsx` | 20+ | Hover states, cursor, visual feedback |

## Test Scenarios

### NAV-001: Sign In Button Navigation (Guest)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| NAV-001a | Guest views header | userName=undefined | "Sign In" link visible |
| NAV-001b | Click Sign In | Click link | Navigates to /login/ |
| NAV-001c | Sign In link href | Check href attribute | href="/login" |
| NAV-001d | Sign In link text | Check text content | "Sign In" |

### NAV-002: Indicators Button Navigation
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| NAV-002a | Guest views Indicators | userName=undefined | "Indicators" link visible |
| NAV-002b | Auth user views Indicators | userName="John" | "Indicators" link visible |
| NAV-002c | Click Indicators | Click link | Navigates to /dashboard/ |
| NAV-002d | Indicators link href | Check href attribute | href="/dashboard" |

### NAV-003: Protected Nav Items Hidden from Guests
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| NAV-003a | Guest Dashboard visibility | userName=undefined | Dashboard NOT in nav |
| NAV-003b | Guest Competitors visibility | userName=undefined | Competitors NOT in nav |
| NAV-003c | Guest Keywords visibility | userName=undefined | Keywords NOT in nav |
| NAV-003d | Guest Reports visibility | userName=undefined | Reports NOT in nav |
| NAV-003e | Guest AI Visibility | userName=undefined | AI Visibility NOT in nav |

### NAV-004: Protected Nav Items Visible to Authenticated Users
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| NAV-004a | Auth Dashboard visibility | userName="John" | Dashboard visible in nav |
| NAV-004b | Auth Competitors visibility | userName="John" | Competitors visible in nav |
| NAV-004c | Auth Keywords visibility | userName="John" | Keywords visible in nav |
| NAV-004d | Auth Reports visibility | userName="John" | Reports visible in nav |
| NAV-004e | Auth AI Visibility | userName="John" | AI Visibility visible in nav |

### NAV-005: Navigation Links Functionality
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| NAV-005a | Dashboard link | Click Dashboard | Navigates to /dashboard |
| NAV-005b | Keywords link | Click Keywords | Navigates to /keywords |
| NAV-005c | Competitors link | Click Competitors | Navigates to /competitors |
| NAV-005d | Reports link | Click Reports | Navigates to /reports |
| NAV-005e | Settings link | Click Settings | Navigates to /settings |

### HOV-001: Pointer Cursor on Navigation
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| HOV-001a | Nav link cursor class | Check className | Contains "cursor-pointer" |
| HOV-001b | Sign In cursor class | Check className | Contains "cursor-pointer" |
| HOV-001c | Indicators cursor class | Check className | Contains "cursor-pointer" |

### HOV-002: Hover State Visibility
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| HOV-002a | Nav link hover bg | Check className | Contains "hover:bg-slate-700" or similar dark |
| HOV-002b | Nav link hover text | Check className | Contains "hover:text-white" |
| HOV-002c | Sign In hover bg | Check className | Contains dark hover background |
| HOV-002d | Indicators hover bg | Check className | Contains dark hover background |

### HOV-003: Active State Indication
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| HOV-003a | Active nav item style | pathname="/dashboard" | Dashboard has active class |
| HOV-003b | Active indicator bar | pathname="/dashboard" | Blue bar under active item |
| HOV-003c | Active aria-current | pathname="/dashboard" | aria-current="page" on Dashboard |

### ACC-001: Accessibility
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ACC-001a | Nav role | Check nav element | role="navigation" |
| ACC-001b | Nav aria-label | Check nav element | aria-label="Main navigation" |
| ACC-001c | Logo aria-label | Check logo link | aria-label present |
| ACC-001d | Focus visible | Tab navigation | Focus ring visible |

### SEC-001: Security
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SEC-001a | No sensitive data in nav | Render component | No tokens/passwords visible |
| SEC-001b | No XSS in nav labels | userName="<script>" | Script not executed |

## Mock Data

### Authenticated User Props
```typescript
const authenticatedProps = {
  userName: "John Doe",
  userEmail: "john@example.com",
  userAvatar: "https://example.com/avatar.jpg",
  onLogout: jest.fn(),
};
```

### Guest Props
```typescript
const guestProps = {
  userName: undefined,
  userEmail: undefined,
  userAvatar: undefined,
  onLogout: undefined,
};
```

### Nav Items (Expected)
```typescript
const guestNavItems = [
  { label: "Indicators", href: "/dashboard" },
  { label: "Sign In", href: "/login" },
];

const authNavItems = [
  { label: "Dashboard", href: "/dashboard" },
  { label: "AI Visibility", href: "/ai-visibility" },
  { label: "Keywords", href: "/keywords" },
  { label: "Competitors", href: "/competitors" },
  { label: "Reports", href: "/reports" },
  { label: "Settings", href: "/settings" },
];
```

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Navigation Structure | 100% | 15+ |
| Auth-Based Rendering | 100% | 10+ |
| Hover States | 100% | 10+ |
| Accessibility | 100% | 5+ |
| Edge Cases | 100% | 5+ |
| **Total** | **100%** | **45+** |

## Test Execution

```bash
# Run specific test file
cd frontend
npm test -- Header.navigation.test.tsx
npm test -- Header.hover-states.test.tsx

# Run all header tests
npm test -- Header

# Run with coverage
npm test -- --coverage Header
```

## Dependencies

- React Testing Library
- Jest
- Next.js Navigation Mocks

## Notes for Developer

### Implementation Requirements
1. Add "Sign In" link for guests linking to `/login`
2. Add "Indicators" link for guests linking to `/dashboard`
3. Hide protected nav items (Dashboard, Competitors, Keywords, Reports, AI Visibility) from guests
4. Add `cursor-pointer` class to all clickable nav elements
5. Use dark hover background (`hover:bg-slate-700` or similar) instead of white

### Conditional Rendering Pattern
```tsx
// In Header component
const isGuest = !userName;

// Protected nav items only for authenticated users
const protectedNavItems = isGuest ? [] : [
  { label: "Dashboard", href: "/dashboard", icon: LayoutDashboard },
  { label: "AI Visibility", href: "/ai-visibility", icon: Eye },
  // ...
];

// Guest-specific nav items
const guestNavItems = isGuest ? [
  { label: "Indicators", href: "/dashboard", icon: LayoutDashboard },
  { label: "Sign In", href: "/login", icon: LogIn },
] : [];
```

### CSS Classes for Navigation
```tsx
// Nav link base classes with cursor and proper hover
const navLinkClasses = `
  text-sm transition-all duration-200 
  relative inline-flex items-center gap-1.5 
  px-3 py-2 rounded-lg 
  cursor-pointer
  focus-visible:outline-none 
  focus-visible:ring-2 
  focus-visible:ring-blue-500
  ${isActive 
    ? "text-white bg-slate-800/50" 
    : "text-slate-400 hover:text-white hover:bg-slate-700"
  }
`;
```

---

## Comments

### 2026-03-16 13:15 GMT+2 - PreTester
**Test Design Complete:**
- Created test documentation covering all 8 acceptance criteria
- Designed 45+ test scenarios across navigation, hover, accessibility, and security categories
- Documented expected component behavior for Developer implementation
- Tests written in TDD style - will guide implementation

**Key Test Focus Areas:**
1. Auth-based conditional rendering (guests see limited nav)
2. Cursor pointer class on all interactive elements
3. Dark hover backgrounds (not white-on-white)
4. Sign In and Indicators links for guests

**Status:** Ready to write test files
