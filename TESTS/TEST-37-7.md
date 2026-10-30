# TEST-37-7: Indicators Button Fix Tests

## Task Reference
- **Task:** [TASK-37-7](../TASKS/TASK-37-7.md)
- **Specification:** SPEC-037
- **Feature:** FEAT-037

## Overview
Test suite for the Indicators button fix in the header navigation. The Indicators button should be converted from a non-functional `<button>` element to a clickable `<Link>` component that navigates to `/dashboard/`.

## Test Files

| File | Tests | Purpose |
|------|-------|---------|
| `frontend/__tests__/components/layout/Header.navigation.test.tsx` | NAV-002 (4 tests) | Navigation behavior |
| `frontend/__tests__/components/layout/Header.hover-states.test.tsx` | HOV-001h, HOV-002f/g, HOV-005c (4 tests) | Cursor, hover, element type |

> **Note:** Tests are in the combined test files created for TASK-37-2. See [TEST-37-2.md](./TEST-37-2.md) for full test documentation.

## Test Scenarios

### NAV-002: Indicators Button Navigation
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| NAV-002a | Guest views Indicators | userName=undefined | "Indicators" link visible |
| NAV-002b | Indicators link href | Check href attribute | href="/dashboard" |
| NAV-002c | Auth user views Indicators | userName="John" | "Indicators" NOT visible (Dashboard instead) |
| NAV-002d | Indicators cursor class | Check className | Contains "cursor-pointer" |

### HOV-001: Pointer Cursor
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| HOV-001h | Indicators cursor class | Check className | Contains "cursor-pointer" |

### HOV-002: Hover State Backgrounds
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| HOV-002f | Indicators dark hover bg | Check className | Matches "hover:bg-slate-\d+" |
| HOV-002g | Indicators NOT white hover | Check className | Does NOT match "hover:bg-white" |

### HOV-005: Element Type Tests
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| HOV-005c | Indicators is anchor | Check tagName | "a" (not "button") |

## Acceptance Criteria Mapping

| AC | Description | Test IDs |
|----|-------------|----------|
| AC-1 | "Indicators" button navigates to /dashboard/ | NAV-002b |
| AC-2 | Button is a Link component (anchor element) | HOV-005c |
| AC-3 | Has cursor-pointer class | NAV-002d, HOV-001h |
| AC-4 | Has proper hover state (dark background) | HOV-002f, HOV-002g |

## Mock Data

### Guest Props
```typescript
const guestProps = {
  userName: undefined,
  userEmail: undefined,
  userAvatar: undefined,
  onLogout: undefined,
};
```

### Expected Nav Structure (Guest)
```typescript
const guestNavItems = [
  { label: "Indicators", href: "/dashboard" },  // This task
  { label: "Sign In", href: "/login" },          // TASK-37-5
];
```

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Navigation | 100% | 4 |
| Hover States | 100% | 4 |
| **Total** | **100%** | **8** |

## Test Execution

```bash
# Run specific navigation tests
cd frontend
npm test -- Header.navigation.test.tsx -t "NAV-002"

# Run specific hover state tests
npm test -- Header.hover-states.test.tsx -t "Indicators"

# Run all header tests
npm test -- Header

# Run with coverage
npm test -- --coverage Header
```

## Dependencies

- React Testing Library
- Jest / Vitest
- Next.js Navigation Mocks
- Next.js Link Mock

## Notes for Developer

### Implementation Requirements
1. Add "Indicators" link to header for **guest users only**
2. Link should point to `/dashboard`
3. Use `<Link>` component from Next.js (not `<button>`)
4. Add `cursor-pointer` class
5. Add dark hover background (`hover:bg-slate-700`)
6. Hide for authenticated users (show Dashboard instead)

### Code Changes Required
```tsx
// Before (broken - no action)
<button className="...">Indicators</button>

// After (fixed - navigable link)
<Link 
  href="/dashboard" 
  className="... cursor-pointer hover:bg-slate-700 hover:text-white"
>
  <LayoutDashboard className="w-4 h-4" aria-hidden="true" />
  Indicators
</Link>
```

### Conditional Rendering Pattern
```tsx
// In Header component
const isGuest = !userName;

// Guest sees Indicators + Sign In
{isGuest && (
  <Link 
    href="/dashboard" 
    className="text-sm cursor-pointer hover:bg-slate-700 hover:text-white ..."
  >
    <LayoutDashboard className="w-4 h-4" />
    Indicators
  </Link>
)}

// Authenticated users see Dashboard in main nav instead
{!isGuest && (
  // Dashboard, Keywords, Competitors, etc.
)}
```

### CSS Classes for Indicators Link
```tsx
const indicatorsLinkClasses = `
  text-sm 
  transition-all 
  duration-200 
  relative 
  inline-flex 
  items-center 
  gap-1.5 
  px-3 
  py-2 
  rounded-lg 
  cursor-pointer
  text-slate-400 
  hover:text-white 
  hover:bg-slate-700
  focus-visible:outline-none 
  focus-visible:ring-2 
  focus-visible:ring-blue-500
`;
```

---

## Comments

### 2026-03-16 15:30 GMT+2 - PreTester
**Test Documentation Complete:**
- Tests already exist in combined test files from TASK-37-2
- All 4 acceptance criteria are covered by 8 test scenarios
- Tests follow TDD pattern - will verify implementation

**Key Test Focus:**
1. Indicators link visible for guests
2. Indicators navigates to /dashboard
3. Indicators uses anchor element (Link component)
4. Proper cursor and hover states

**Test File Updates (2026-03-16 15:26 GMT+2):**
- Fixed test files to use Vitest instead of Jest
- Added missing `Menu` icon mock to lucide-react mock
- Tests now run properly

**TDD Verification (2026-03-16 15:27 GMT+2):**
Ran `npm test -- --run __tests__/components/layout/Header.navigation.test.tsx`

Results for TASK-37-7 specific tests:
| Test ID | Result | Reason |
|---------|--------|--------|
| NAV-002a | ❌ FAIL | Expected - Indicators link not in guest nav yet |
| NAV-002b | ❌ FAIL | Expected - Indicators link not in guest nav yet |
| NAV-002c | ✅ PASS | Indicators correctly hidden for authenticated users |
| NAV-002d | ❌ FAIL | Expected - Indicators link not in guest nav yet |

**Status:** Ready for Developer implementation
- Tests are properly failing (TDD approach)
- Developer should implement Indicators link to make tests pass
