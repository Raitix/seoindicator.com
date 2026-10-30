# TEST-28-3: shadcn/ui Components Installation Tests

## Task Reference
- Task: [TASK-28-3](../TASKS/TASK-28-3.md)
- Specification: [SPEC-028](../SPECIFICATIONS/SPEC-028.md)
- Feature: Modern UI/UX + Indicator Catalog

## Overview
Tests for verifying shadcn/ui component installation and dark theme integration.

**Components Status:**
- **Already Installed (verify):** progress, dropdown-menu, separator
- **To Install:** skeleton, badge, tooltip, avatar, sheet, tabs

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `frontend/__tests__/components/ui/shadcn-components.test.tsx` | Unit | Component existence, imports, rendering, dark theme |

## Test Scenarios

### Component Existence Tests

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| CE-001 | skeleton.tsx exists in components/ui/ | File exists |
| CE-002 | progress.tsx exists in components/ui/ | File exists |
| CE-003 | badge.tsx exists in components/ui/ | File exists |
| CE-004 | tooltip.tsx exists in components/ui/ | File exists |
| CE-005 | avatar.tsx exists in components/ui/ | File exists |
| CE-006 | dropdown-menu.tsx exists in components/ui/ | File exists |
| CE-007 | sheet.tsx exists in components/ui/ | File exists |
| CE-008 | tabs.tsx exists in components/ui/ | File exists |
| CE-009 | separator.tsx exists in components/ui/ | File exists |

### Component Export Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CX-001 | Skeleton exports correctly | import Skeleton | Component exported |
| CX-002 | Progress exports correctly | import Progress | Component exported |
| CX-003 | Badge exports correctly | import Badge | Component exported |
| CX-004 | Tooltip exports correctly | import Tooltip, TooltipContent, etc. | All exports available |
| CX-005 | Avatar exports correctly | import Avatar, AvatarImage, etc. | All exports available |
| CX-006 | DropdownMenu exports correctly | import all sub-components | All exports available |
| CX-007 | Sheet exports correctly | import Sheet, SheetContent, etc. | All exports available |
| CX-008 | Tabs exports correctly | import Tabs, TabsList, etc. | All exports available |
| CX-009 | Separator exports correctly | import Separator | Component exported |

### Component Rendering Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CR-001 | Skeleton renders without error | <Skeleton /> | Renders, has role="skeleton" or aria-busy |
| CR-002 | Progress renders with value | <Progress value={50} /> | Renders with width: 50% |
| CR-003 | Progress renders with 0 value | <Progress value={0} /> | Renders with width: 0% |
| CR-004 | Progress renders with 100 value | <Progress value={100} /> | Renders with width: 100% |
| CR-005 | Badge renders with text | <Badge>Test</Badge> | Renders "Test" text |
| CR-006 | Badge variants render | <Badge variant="destructive">Error</Badge> | Renders with variant class |
| CR-007 | Tooltip renders with trigger | TooltipProvider + Tooltip + content | Renders trigger element |
| CR-008 | Avatar renders with fallback | <Avatar><AvatarFallback>AB</AvatarFallback></Avatar> | Renders fallback |
| CR-009 | DropdownMenu renders with trigger | DropdownMenu + Trigger | Renders trigger element |
| CR-010 | Sheet renders with trigger | Sheet + SheetTrigger | Renders trigger element |
| CR-011 | Tabs renders with list | Tabs + TabsList | Renders tabs list |
| CR-012 | Separator renders horizontal | <Separator /> | Renders as horizontal line |
| CR-013 | Separator renders vertical | <Separator orientation="vertical" /> | Renders as vertical line |

### Dark Theme Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DT-001 | Skeleton has dark theme classes | <Skeleton /> | Contains dark: classes or dark mode styles |
| DT-002 | Progress has dark theme classes | <Progress /> | Contains dark:bg- classes |
| DT-003 | Badge has dark theme classes | <Badge /> | Contains dark: classes |
| DT-004 | DropdownMenu has dark theme classes | <DropdownMenuContent /> | Contains dark: classes |
| DT-005 | Sheet has dark theme classes | <SheetContent /> | Contains dark: classes |
| DT-006 | Tabs has dark theme classes | <TabsList /> | Contains dark: classes |
| DT-007 | Separator has dark theme classes | <Separator /> | Contains dark: classes |

### Tailwind Integration Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TI-001 | Components use cn() utility | Check imports | Imports cn from @/lib/utils |
| TI-002 | Components accept className prop | <Skeleton className="custom" /> | Applies custom class |
| TI-003 | Components merge classes correctly | className + default | Both classes applied |
| TI-004 | Components use Tailwind classes | Check class names | Uses Tailwind utilities |

### TypeScript Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TS-001 | No TypeScript errors in skeleton | tsc check | No errors |
| TS-002 | No TypeScript errors in badge | tsc check | No errors |
| TS-003 | No TypeScript errors in tooltip | tsc check | No errors |
| TS-004 | No TypeScript errors in avatar | tsc check | No errors |
| TS-005 | No TypeScript errors in sheet | tsc check | No errors |
| TS-006 | No TypeScript errors in tabs | tsc check | No errors |

### Edge Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Progress value below 0 | <Progress value={-10} /> | Clamps to 0% |
| EC-002 | Progress value above 100 | <Progress value={150} /> | Clamps to 100% |
| EC-003 | Badge with empty content | <Badge></Badge> | Renders empty |
| EC-004 | Avatar without image | <Avatar><AvatarFallback>F</AvatarFallback></Avatar> | Shows fallback |
| EC-005 | Tabs with no content | <Tabs></Tabs> | Renders without error |
| EC-006 | Sheet controlled state | open={true} | Renders open state |

### Acceptance Criteria Tests

| ID | Criterion | Test | Expected |
|----|-----------|------|----------|
| AC-001 | skeleton component installed and styled | File exists, renders, dark theme | PASS |
| AC-002 | progress component installed and styled | File exists, renders, dark theme | PASS |
| AC-003 | badge component installed and styled | File exists, renders, dark theme | PASS |
| AC-004 | tooltip component installed and styled | File exists, renders, dark theme | PASS |
| AC-005 | avatar component installed and styled | File exists, renders, dark theme | PASS |
| AC-006 | dropdown-menu component installed and styled | File exists, renders, dark theme | PASS |
| AC-007 | sheet component installed and styled | File exists, renders, dark theme | PASS |
| AC-008 | tabs component installed and styled | File exists, renders, dark theme | PASS |
| AC-009 | separator component installed and styled | File exists, renders, dark theme | PASS |
| AC-010 | All components use dark theme styling | Dark theme classes present | PASS |
| AC-011 | Components integrate with Tailwind config | cn() utility, className prop | PASS |

## Mock Data

```typescript
// Test wrapper for components requiring context
const TestWrapper = ({ children }: { children: React.ReactNode }) => (
  <TooltipProvider>{children}</TooltipProvider>
);

// Sample avatar test data
const testAvatar = {
  src: "https://example.com/avatar.png",
  alt: "Test Avatar",
  fallback: "AB"
};

// Sample tabs test data
const testTabs = [
  { id: "tab1", label: "Tab 1", content: "Content 1" },
  { id: "tab2", label: "Tab 2", content: "Content 2" }
];
```

## Coverage Targets
- Component existence: 100%
- Export verification: 100%
- Rendering tests: 100%
- Dark theme tests: 100%
- Tailwind integration: 100%
- Edge cases: 80%

## Test Execution

```bash
# Run component tests
cd frontend
npm test -- __tests__/components/ui/shadcn-components.test.tsx

# Run with coverage
npm test -- --coverage __tests__/components/ui/shadcn-components.test.tsx
```

## Dependencies
- @testing-library/react
- @testing-library/jest-dom
- jest
- jest-environment-jsdom

## Notes for Developer
1. **Already Installed Components (verify only):**
   - `progress.tsx` - exists, verify dark theme styling
   - `dropdown-menu.tsx` - exists, verify dark theme styling
   - `separator.tsx` - exists, verify dark theme styling

2. **Components to Install:**
   ```bash
   npx shadcn@latest add skeleton
   npx shadcn@latest add badge
   npx shadcn@latest add tooltip
   npx shadcn@latest add avatar
   npx shadcn@latest add sheet
   npx shadcn@latest add tabs
   ```

3. **Expected Component Patterns:**
   - All components use `cn()` from `@/lib/utils`
   - Dark theme with `dark:` Tailwind classes
   - TypeScript with proper prop interfaces
   - Radix UI primitives for complex components

4. **Dark Theme Verification:**
   - Check for `dark:` prefixed classes
   - Verify background/text colors for dark mode
   - Test with theme provider if available

---

## Comments
_Agent log_

### [2026-03-03 12:45 UTC+2] PreTester
**Test Design Complete**

Created test documentation for shadcn/ui component verification.

**Key Findings:**
- 3 components already installed: progress, dropdown-menu, separator
- 6 components need installation: skeleton, badge, tooltip, avatar, sheet, tabs
- Tests verify both new and existing components

**Test Categories:**
- Component Existence (9 tests)
- Component Exports (9 tests)
- Component Rendering (13 tests)
- Dark Theme (7 tests)
- Tailwind Integration (4 tests)
- TypeScript (6 tests)
- Edge Cases (6 tests)
- Acceptance Criteria (11 tests)

**Total: 65 test scenarios**

Tests ready for Developer implementation.
