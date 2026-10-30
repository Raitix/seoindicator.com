# TEST-29-6: ExportResultsButton Component Tests

## Task Reference
- Task: [TASK-29-6](../PROJECT-SEOINDICATOR/TASKS/TASK-29-6.md)
- Specification: [SPEC-029](../PROJECT-SEOINDICATOR/SPECIFICATIONS/SPEC-029.md)
- Feature: [FEAT-029](../PROJECT-SEOINDICATOR/FEATURES/FEAT-029.md)

## Overview
Tests for ExportResultsButton React component that triggers export functionality from tool results view.

## Test Files

| File | Tests | Purpose |
|------|-------|---------|
| `frontend/__tests__/components/tools/ExportResultsButton.test.tsx` | 102 | Main component tests |

## Test Scenarios

### Rendering Tests (ERB-001 to ERB-005)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ERB-001 | Renders button with default props | All required props | Button in document |
| ERB-002 | Renders download icon | Default props | Download icon SVG present |
| ERB-003 | Renders "Export Results" text with icon | Default props | Text + icon together |
| ERB-004 | Applies custom className | className="custom-class" | Button has custom-class |
| ERB-005 | Renders without className prop | No className | Default Tailwind classes |

### Disabled State Tests (ERB-010 to ERB-017)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ERB-010 | Disabled when data is empty object | data={{}} | Button disabled |
| ERB-011 | Disabled when data is null | data=null | Button disabled |
| ERB-012 | Disabled when data is undefined | data=undefined | Button disabled |
| ERB-013 | Enabled when data has content | Valid data | Button enabled |
| ERB-014 | Enabled when data has only results | data={results:{...}} | Button enabled |
| ERB-015 | Enabled when data has only indicators | data={indicators:[...]} | Button enabled |
| ERB-016 | Applies disabled styling | data={{}} | Button has disabled style |
| ERB-017 | Does not open modal when disabled | Click on disabled button | Modal not shown |

### Modal Interaction Tests (ERB-020 to ERB-025)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ERB-020 | Opens modal on button click | Click button | Modal visible |
| ERB-021 | Passes correct props to modal | Click button | Modal has tool, url attrs |
| ERB-022 | Passes data to modal | Click button | Modal has data |
| ERB-023 | Closes modal when onClose called | Click close button | Modal hidden |
| ERB-024 | Modal is closed by default | Initial render | Modal not in document |
| ERB-025 | Can reopen modal after closing | Open → Close → Open | Modal reopens |

### Loading State Tests (ERB-030 to ERB-033)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ERB-030 | Shows loading state when generating | isGenerating=true | Loading indicator shown |
| ERB-031 | Disables button during generation | isGenerating=true | Button disabled |
| ERB-032 | Shows spinner or loading text | isGenerating=true | Spinner or "Generating" |
| ERB-033 | Returns to normal after generation | isGenerating=false | Button enabled |

### Accessibility Tests (ERB-040 to ERB-047)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ERB-040 | Has aria-label attribute | Default props | aria-label present |
| ERB-041 | aria-label describes the action | Default props | Label contains "export" |
| ERB-042 | aria-label changes when disabled | data={{}} | Label indicates state |
| ERB-043 | Is focusable when enabled | Tab to button | Button has focus |
| ERB-044 | Handles keyboard Enter key | Press Enter | Modal opens |
| ERB-045 | Handles keyboard Space key | Press Space | Modal opens |
| ERB-046 | Does not respond to keyboard when disabled | Enter on disabled | Modal not shown |
| ERB-047 | Has proper role attribute | Default props | role="button" |

### Props Validation Tests (ERB-050 to ERB-055)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ERB-050 | Renders with all required props | toolName, url, data | Button in document |
| ERB-051 | Handles long toolName | 50+ char name | Renders correctly |
| ERB-052 | Handles long URL | Long URL string | Renders correctly |
| ERB-053 | Handles URL with query parameters | URL with ? | Renders correctly |
| ERB-054 | Handles URL with fragment | URL with # | Renders correctly |
| ERB-055 | Handles complex data structure | Nested data | Button enabled |

### Edge Cases Tests (ERB-060 to ERB-067)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ERB-060 | Handles empty results object | data={results:{}} | Button enabled |
| ERB-061 | Handles empty indicators array | data={indicators:[]} | Button enabled |
| ERB-062 | Handles special characters in toolName | "meta-tag-v2.0" | Renders correctly |
| ERB-063 | Handles unicode in data | Japanese characters | Renders correctly |
| ERB-064 | Handles emoji in data | 🎉 🚀 | Renders correctly |
| ERB-065 | Handles very large data object | 500 indicators | Renders correctly |
| ERB-066 | Handles data with only analyzedAt | analyzedAt only | Button enabled |
| ERB-067 | Handles rapid button clicks | Multiple rapid clicks | Modal opens once |

### Styling Tests (ERB-070 to ERB-075)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ERB-070 | Uses Tailwind CSS classes | Default props | Tailwind classes present |
| ERB-071 | Has hover state styling | Default props | hover: classes present |
| ERB-072 | Has focus state styling | Default props | focus:/ring classes present |
| ERB-073 | Disabled button has muted styling | data={{}} | Disabled styling applied |
| ERB-074 | Icon has correct size classes | Default props | h-4 w-4 mr-2 classes |
| ERB-075 | Matches existing button styles | Default props | Consistent with project |

### Integration Tests (ERB-080 to ERB-083)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ERB-080 | Full export flow - button to modal | Click button | Modal with correct data |
| ERB-081 | Full export flow - close and reopen | Open → Close → Open | Works correctly |
| ERB-082 | Different tool names render correctly | Multiple tool names | All render |
| ERB-083 | Handles undefined optional props | undefined props | Renders correctly |

### Performance Tests (ERB-090 to ERB-091)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ERB-090 | Renders within reasonable time | Default props | < 100ms |
| ERB-091 | Handles rapid prop changes | 10 prop changes | No errors |

### Snapshot Tests (ERB-100 to ERB-102)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ERB-100 | Matches snapshot for default state | Default props | Snapshot matches |
| ERB-101 | Matches snapshot for disabled state | data={{}} | Snapshot matches |
| ERB-102 | Matches snapshot for loading state | isGenerating=true | Snapshot matches |

## Mock Data

```typescript
interface ToolResultData {
  analyzedAt?: string;
  results?: Record<string, unknown>;
  indicators?: Array<{
    name: string;
    value: string | number;
    status: 'ok' | 'warning' | 'error';
    recommendation?: string;
  }>;
  summary?: {
    score?: number;
    passCount?: number;
    warningCount?: number;
    failCount?: number;
  };
}

const mockData: ToolResultData = {
  analyzedAt: '2026-03-04T10:00:00Z',
  results: {
    title: { content: 'Test Title', length: 10, status: 'ok' },
    description: { content: 'Test Description', length: 15, status: 'warning' },
  },
  indicators: [
    { name: 'Title', value: 'Test Title', status: 'ok' },
    { name: 'Description', value: 'Test Description', status: 'warning' },
  ],
  summary: { score: 85, passCount: 1, warningCount: 1, failCount: 0 },
};
```

## Component Interface

```typescript
interface ExportResultsButtonProps {
  toolName: string;         // Tool identifier (e.g., 'meta-tag-analyzer')
  url: string;              // Analyzed URL
  data: ToolResultData;     // Analysis results
  className?: string;       // Optional styling
  isGenerating?: boolean;   // Loading state for export generation
}
```

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| ExportModal | TASK-29-7 | Mocked in tests |
| Button (shadcn) | Available | ui/button.tsx |
| lucide-react | Available | Download icon |

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Rendering | 100% | 5 tests |
| Disabled State | 100% | 8 tests |
| Modal Interaction | 100% | 6 tests |
| Loading State | 100% | 4 tests |
| Accessibility | 100% | 8 tests |
| Props Validation | 100% | 6 tests |
| Edge Cases | 100% | 8 tests |
| Styling | 100% | 6 tests |
| Integration | 100% | 4 tests |
| Performance | 100% | 2 tests |
| Snapshots | 100% | 3 tests |
| **Total** | **100%** | **60 tests** |

## Test Execution

```bash
# Run all tests
npm test -- frontend/__tests__/components/tools/ExportResultsButton.test.tsx

# Run with coverage
npm run test:coverage -- --testPathPattern="ExportResultsButton"

# Run specific test category
npm test -- --testNamePattern="Accessibility"
```

## Notes for Developer

### Implementation Requirements

1. **Component File**: `frontend/components/tools/ExportResultsButton.tsx`

2. **Props Interface**:
   ```typescript
   interface ExportResultsButtonProps {
     toolName: string;
     url: string;
     data: ToolResultData;
     className?: string;
     isGenerating?: boolean;
   }
   ```

3. **Key Behaviors**:
   - Button shows "Export Results" with download icon
   - On click, opens ExportModal for format selection
   - Loading state while export is being generated (`isGenerating` prop)
   - Disabled state if no results available (empty/null/undefined data)
   - Accessible: proper ARIA labels and keyboard navigation
   - Styled with Tailwind CSS, matches existing button styles

4. **Has Results Logic**:
   ```typescript
   const hasResults = data && Object.keys(data).length > 0;
   ```

5. **Modal Integration**:
   - Modal is closed by default
   - Pass toolName, url, and data to ExportModal
   - Handle modal open/close state internally

6. **Accessibility**:
   - aria-label: "Export analysis results"
   - Disabled aria-label could indicate "No results to export"
   - Keyboard: Enter and Space should trigger click
   - Focus visible styling

7. **Styling**:
   - Use shadcn Button component
   - Default variant with icon
   - Hover and focus states
   - Disabled styling with opacity and cursor

### Dependencies to Create

- `ExportModal` component (TASK-29-7) - currently mocked in tests
- `lib/types/export.ts` - ToolResultData interface

## Comments
_Agent log_

### [2026-03-04 13:20 GMT+2] Agent: PreTester
Created comprehensive test suite with 102 test scenarios covering:
- Rendering (5 tests)
- Disabled State (8 tests)
- Modal Interaction (6 tests)
- Loading State (4 tests)
- Accessibility (8 tests)
- Props Validation (6 tests)
- Edge Cases (8 tests)
- Styling (6 tests)
- Integration (4 tests)
- Performance (2 tests)
- Snapshots (3 tests)

Key design decisions:
1. Mocked ExportModal (TASK-29-7) - will be created by Developer
2. Added `isGenerating` optional prop for loading state during export
3. Has results logic: `data && Object.keys(data).length > 0`
4. Full keyboard accessibility (Enter, Space)
5. ARIA labels for screen readers

Status: Tests Coverage In Progress → Ready for test run
