# TEST-29-7: ExportModal Component Tests

## Overview
Test documentation for the ExportModal React component that handles format selection (PDF/XLS) and download initiation for tool results export.

## Test File Location
`frontend/__tests__/components/tools/__tests__/ExportModal.test.tsx`

## Component Under Test
`frontend/components/tools/ExportModal.tsx`

## Test Categories

### 1. Rendering Tests (EM-001 to EM-010)

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| EM-001 | Renders when isOpen is true | Modal is visible in DOM |
| EM-002 | Does not render when isOpen is false | Modal is not in DOM |
| EM-003 | Renders modal title "Export Results" | Title text is displayed |
| EM-004 | Renders PDF format option | Radio button for PDF exists |
| EM-005 | Renders XLS format option | Radio button for XLS exists |
| EM-006 | Renders format descriptions | PDF and XLS descriptions shown |
| EM-007 | Renders Cancel button | Cancel button is visible |
| EM-008 | Renders Export button | Export button is visible |
| EM-009 | Renders preview section | Preview of export data shown |
| EM-010 | Applies correct data-testid | Modal has data-testid="export-modal" |

### 2. Format Selection Tests (EM-011 to EM-020)

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| EM-011 | PDF is selected by default | PDF radio is checked initially |
| EM-012 | Can select XLS format | XLS radio becomes checked on click |
| EM-013 | Can switch back to PDF | PDF radio becomes checked again |
| EM-014 | Only one format selected at a time | Mutual exclusion of radio buttons |
| EM-015 | PDF description is correct | Shows branded document description |
| EM-016 | XLS description is correct | Shows spreadsheet description |
| EM-017 | Selected format is stored in state | Internal state tracks selection |
| EM-018 | Format persists on re-render | Selection maintained |
| EM-019 | Format selection updates preview | Preview reflects selected format |
| EM-020 | Keyboard navigation between formats | Tab/Arrow keys work |

### 3. Export Functionality Tests (EM-021 to EM-035)

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| EM-021 | Export button triggers API call | POST /api/v1/tools/export called |
| EM-022 | API called with correct payload | tool, url, format, data sent |
| EM-023 | Shows loading spinner during export | Spinner visible during API call |
| EM-024 | Export button disabled during export | Button disabled while loading |
| EM-025 | Cancel button disabled during export | Cancel disabled while loading |
| EM-026 | On success, triggers file download | window.location.href updated |
| EM-027 | On success, modal closes | Modal closed after download |
| EM-028 | On error, shows error message | Error displayed in modal |
| EM-029 | On error, retry option available | Export button re-enabled |
| EM-030 | Handles rate limit error (429) | Shows rate limit message |
| EM-031 | Rate limit shows time until next | Countdown or time displayed |
| EM-032 | Handles network error gracefully | Shows connection error message |
| EM-033 | Handles server error (5xx) | Shows server error message |
| EM-034 | Handles invalid response format | Shows parse error message |
| EM-035 | Loading state clears after completion | Spinner hidden, buttons enabled |

### 4. Modal Interaction Tests (EM-036 to EM-045)

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| EM-036 | Cancel button closes modal | onClose callback called |
| EM-037 | Close button (X) closes modal | onClose callback called |
| EM-038 | Clicking outside closes modal | onClose callback called |
| EM-039 | Escape key closes modal | onClose callback called |
| EM-040 | Modal can be reopened after close | State reset on close |
| EM-041 | Props update when reopened | New data reflected |
| EM-042 | Modal traps focus within | Tab cycles through modal elements |
| EM-043 | Focus moves to modal on open | First element focused |
| EM-044 | Focus returns to trigger on close | Previous element focused |
| EM-045 | No memory leak on unmount | Event listeners cleaned up |

### 5. Props Validation Tests (EM-046 to EM-055)

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| EM-046 | Renders with all required props | Modal displays correctly |
| EM-047 | Handles missing toolName | Default or error state |
| EM-048 | Handles missing url | Default or error state |
| EM-049 | Handles missing data | Disabled export button |
| EM-050 | Handles empty data object | Shows "no data" message |
| EM-051 | Handles null data | Shows "no data" message |
| EM-052 | Handles complex data structure | Renders without error |
| EM-053 | Handles special characters in props | Escaped correctly |
| EM-054 | Handles very long toolName | Truncated or wrapped |
| EM-055 | Handles very long URL | Truncated or wrapped |

### 6. Accessibility Tests (EM-056 to EM-065)

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| EM-056 | Has correct ARIA role | role="dialog" |
| EM-057 | Has aria-modal="true" | Modal is marked as modal |
| EM-058 | Has aria-labelledby | Points to title element |
| EM-059 | Has aria-describedby | Points to description |
| EM-060 | Radio buttons have labels | Associated label elements |
| EM-061 | Buttons have accessible names | aria-label or text content |
| EM-062 | Focus visible on all elements | Focus indicators present |
| EM-063 | Screen reader announces modal | Live region updates |
| EM-064 | Keyboard only navigation | Full functionality available |
| EM-065 | No accessibility violations | jest-axe passes |

### 7. Edge Cases Tests (EM-066 to EM-075)

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| EM-066 | Multiple rapid export clicks | Only one request sent |
| EM-067 | Modal closes during export | Request cancelled or handled |
| EM-068 | Network timeout during export | Timeout error shown |
| EM-069 | Very large data payload | Export still works |
| EM-070 | Unicode characters in data | Handled correctly |
| EM-071 | Emoji in data | Handled correctly |
| EM-072 | Concurrent modal instances | Independent state |
| EM-073 | Props change during loading | State handled correctly |
| EM-074 | Browser back button pressed | Modal closes |
| EM-075 | Page refresh during export | Clean state on return |

### 8. Preview Section Tests (EM-076 to EM-080)

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| EM-076 | Shows what will be exported | Tool name, URL displayed |
| EM-077 | Shows indicator count | Number of items shown |
| EM-078 | Preview updates with format change | Content reflects format |
| EM-079 | Handles no indicators case | Shows appropriate message |
| EM-080 | Truncates long preview | Overflow handled |

## Mocked Dependencies

### API Mock
```typescript
// Mock POST /api/v1/tools/export
const mockExportApi = {
  success: {
    downloadUrl: '/api/v1/tools/export/download/token123',
    expiresAt: '2026-03-05T15:00:00Z',
  },
  rateLimit: {
    status: 429,
    message: 'Rate limit exceeded',
    retryAfter: 3600,
  },
  serverError: {
    status: 500,
    message: 'Internal server error',
  },
};
```

### File Download Mock
```typescript
// Mock window.location.href for download
delete window.location;
window.location = { href: '' } as Location;
```

## Test Implementation Notes

### Setup Requirements
1. Mock lucide-react icons
2. Mock fetch or API client
3. Mock window.location for download tests
4. Use @testing-library/react for component testing
5. Use userEvent for user interactions
6. Use jest-axe for accessibility tests

### Cleanup Requirements
1. Clear all mocks after each test
2. Restore window.location after tests
3. Unmount components after tests
4. Clear any pending timers

### Coverage Target
- Statements: 80%+
- Branches: 75%+
- Functions: 80%+
- Lines: 80%+

## Dependencies
- TASK-29-3: ToolExportService API (mocked)
- TASK-29-6: ExportResultsButton (tested separately)
- shadcn/ui Modal component (existing)

## Related Tests
- TEST-29-6: ExportResultsButton tests
- TEST-29-10: Frontend component integration tests
