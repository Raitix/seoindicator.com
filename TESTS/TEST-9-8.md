# TESTS/TEST-9-8.md — AnnotationForm Component Tests

## Task Reference
- **Task:** [TASK-9-8](../TASKS/TASK-9-8.md)
- **Specification:** [SPEC-009](../SPECIFICATIONS/SPEC-009.md)
- **Feature:** [FEAT-009](../FEATURES/FEAT-009.md)

---

## Overview

Tests for the AnnotationForm React component used to create and edit keyword annotations. The form uses react-hook-form for validation and state management, with support for create and edit modes.

**Component:** `frontend/components/annotations/AnnotationForm.tsx`

---

## Test Files

| File | Location | Tests |
|------|----------|-------|
| `AnnotationForm.test.tsx` | `frontend/__tests__/components/annotations/` | 80+ |

---

## Test Scenarios

### RE-RENDER — Rendering (5 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RE-RENDER-01 | Form renders all fields | Default props | Date, Title, Category, Description fields visible |
| RE-RENDER-02 | Labels display correctly | Default props | "Date", "Title", "Category", "Description" labels |
| RE-RENDER-03 | Cancel button renders | Default props | Button with text "Cancel" |
| RE-RENDER-04 | Create button renders | Default props (no annotation) | Submit button shows "Create" |
| RE-RENDER-05 | Category dropdown has all options | Default props | 9 category options in dropdown |

### RE-CREATE — Create Mode (6 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RE-CREATE-01 | Default date is today | No annotation prop | Date field defaults to current date |
| RE-CREATE-02 | Default category is "other" | No annotation prop | Category dropdown shows "Other" |
| RE-CREATE-03 | Empty title on create | No annotation prop | Title field is empty |
| RE-CREATE-04 | Empty description on create | No annotation prop | Description field is empty |
| RE-CREATE-05 | Submit button says "Create" | No annotation prop | Submit button text is "Create" |
| RE-CREATE-06 | Form resets after successful create | Valid form submit | Form fields reset to defaults |

### RE-EDIT — Edit Mode (8 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RE-EDIT-01 | Pre-populated date | annotation prop with date | Date field shows annotation date |
| RE-EDIT-02 | Pre-populated title | annotation prop with title | Title field shows annotation title |
| RE-EDIT-03 | Pre-populated category | annotation prop with category | Category dropdown shows annotation category |
| RE-EDIT-04 | Pre-populated description | annotation prop with description | Description field shows annotation description |
| RE-EDIT-05 | Submit button says "Update" | annotation prop provided | Submit button text is "Update" |
| RE-EDIT-06 | Form does not reset after edit | Valid form submit in edit mode | Form fields retain values |
| RE-EDIT-07 | Null description handled | annotation.description = null | Description field is empty |
| RE-EDIT-08 | All categories pre-select correctly | Each category | Correct dropdown option selected |

### VAL — Validation (12 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| VAL-01 | Date required | Empty date field | Error message "Date is required" |
| VAL-02 | Invalid date format | Date = "invalid" | Error message "Please provide a valid date" |
| VAL-03 | Title required | Empty title field | Error message "Title is required" |
| VAL-04 | Title max length 255 | Title = 256 chars | Error message "Title must be 255 characters or less" |
| VAL-05 | Title exactly 255 chars valid | Title = 255 chars | No error message |
| VAL-06 | Valid date accepted | Date = "2026-03-08" | No error message |
| VAL-07 | Description optional | Empty description | No error message |
| VAL-08 | Category defaults to "other" | No selection | Form valid with category="other" |
| VAL-09 | Multiple errors display | Empty date + Empty title | Both error messages visible |
| VAL-10 | Error styling on invalid field | Invalid date | Input has red border class |
| VAL-11 | Past date accepted | Date = "2020-01-01" | No error message |
| VAL-12 | Today date accepted | Date = today | No error message |

### INTERACT — User Interactions (10 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| INTERACT-01 | Cancel button calls onCancel | Click Cancel | onCancel callback invoked |
| INTERACT-02 | Submit calls onSave with form data | Valid form submit | onSave called with {date, title, description, category} |
| INTERACT-03 | Submit button disabled while submitting | isSubmitting = true | Button has disabled attribute |
| INTERACT-04 | Category selection changes | Select "link-building" | Category value updated |
| INTERACT-05 | Date input change | Change date | Date value updated |
| INTERACT-06 | Title input change | Type in title | Title value updated |
| INTERACT-07 | Description textarea change | Type in description | Description value updated |
| INTERACT-08 | Submit prevents default | Click submit | Form submit prevented (handled by handleSubmit) |
| INTERACT-09 | Tab navigation through fields | Press Tab | Focus moves through fields in order |
| INTERACT-10 | Enter key submits form | Press Enter in field | Form submits |

### A11Y — Accessibility (6 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| A11Y-01 | Date input has label | Render form | Label "for=date" associated |
| A11Y-02 | Title input has label | Render form | Label "for=title" associated |
| A11Y-03 | Category select has label | Render form | Label "for=category" associated |
| A11Y-04 | Description textarea has label | Render form | Label "for=description" associated |
| A11Y-05 | Error messages associated with fields | Validation error | Error message linked to field |
| A11Y-06 | Focus visible on interactive elements | Tab through form | Visible focus ring on buttons/inputs |

### EDGE — Edge Cases (10 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EDGE-01 | Unicode in title | Title = "内容更新 📝" | Title renders correctly |
| EDGE-02 | Unicode in description | Description = "描述详情" | Description renders correctly |
| EDGE-03 | Special characters in title | Title = "Test <script>alert(1)</script>" | Title escaped, no script execution |
| EDGE-04 | Special characters in description | Description = "<b>Bold</b>" | Description escaped |
| EDGE-05 | Very long description | Description = 5000 chars | Accepted (no max validation) |
| EDGE-06 | Newlines in description | Description = "Line1\nLine2" | Preserved in textarea |
| EDGE-07 | Whitespace-only title | Title = "   " | Required validation triggers |
| EDGE-08 | Past date formatting | Date from 2020 | Formats to yyyy-MM-dd |
| EDGE-09 | Null keywordId | keywordId = undefined | Component handles gracefully |
| EDGE-10 | Empty onSave callback | onSave = undefined | No crash (prop is required) |

### PROP — Props Validation (5 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PROP-01 | Required props present | keywordId, onSave, onCancel | Component renders |
| PROP-02 | Optional annotation prop omitted | No annotation | Create mode active |
| PROP-03 | Optional annotation prop provided | annotation object | Edit mode active |
| PROP-04 | Description null in annotation | annotation.description = null | Empty textarea |
| PROP-05 | All annotation fields populated | Full annotation object | All fields pre-populated |

### STYL — Styling (5 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| STYL-01 | Error input has red border | Validation error | border-red-300 class |
| STYL-02 | Valid input has gray border | No validation error | border-gray-300 class |
| STYL-03 | Submit button blue styling | Default state | bg-blue-600 class |
| STYL-04 | Cancel button white styling | Default state | bg-white class |
| STYL-05 | Disabled button opacity | isSubmitting = true | opacity-50 class |

### CAT — Category Options (5 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CAT-01 | All 9 categories present | Render form | 9 option elements |
| CAT-02 | Category icons display | Render form | Each option has emoji icon |
| CAT-03 | Category labels display | Render form | Each option has label text |
| CAT-04 | Category values match types | Render form | Values match AnnotationCategory type |
| CAT-05 | Default category is "other" | No selection | "other" option selected |

### AC — Acceptance Criteria (4 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AC-01 | Form creates annotation | Submit valid create form | onSave called with correct data |
| AC-02 | Form edits annotation | Submit valid edit form | onSave called with updated data |
| AC-03 | Cancel action works | Click Cancel | onCancel callback invoked |
| AC-04 | Validation prevents invalid submit | Invalid form | Submit not called, errors shown |

---

## Mock Data

```typescript
import { Annotation, CreateAnnotationDTO, ANNOTATION_CATEGORIES } from '@/lib/types/annotations';

// Mock annotation for edit mode
const mockAnnotation: Annotation = {
  id: 1,
  keyword_id: 123,
  project_id: 1,
  date: '2026-03-05',
  title: 'Google Core Update Impact',
  description: 'Rankings dropped 5 positions after core update',
  category: 'algorithm-update',
  created_at: '2026-03-05T10:30:00Z',
  updated_at: '2026-03-05T10:30:00Z',
};

// Mock annotation with null description
const mockAnnotationNullDescription: Annotation = {
  ...mockAnnotation,
  id: 2,
  description: null,
};

// Mock create DTO
const mockCreateDTO: CreateAnnotationDTO = {
  date: '2026-03-08',
  title: 'New Annotation',
  description: 'Test description',
  category: 'content-update',
};

// Mock handlers
const mockOnSave = jest.fn();
const mockOnCancel = jest.fn();
```

---

## Test Setup

```typescript
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import AnnotationForm from '@/components/annotations/AnnotationForm';
import { Annotation } from '@/lib/types/annotations';

// Mock date-fns format
jest.mock('date-fns', () => ({
  format: jest.fn((date: Date | string, formatStr: string) => {
    const d = typeof date === 'string' ? new Date(date) : date;
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }),
}));

describe('AnnotationForm', () => {
  const defaultProps = {
    keywordId: 123,
    onSave: jest.fn(),
    onCancel: jest.fn(),
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  // ... test cases
});
```

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Statements | 90% | All branches covered |
| Branches | 85% | Create/edit modes, validation |
| Functions | 95% | All handlers tested |
| Lines | 90% | All code paths |

---

## Test Execution

```bash
# Run tests
npm test -- --testPathPattern="AnnotationForm"

# Run with coverage
npm test -- --coverage --testPathPattern="AnnotationForm"

# Watch mode
npm test -- --watch --testPathPattern="AnnotationForm"
```

---

## Dependencies

- react-hook-form (form state management)
- date-fns (date formatting)
- @testing-library/react (testing utilities)
- @testing-library/user-event (user interactions)

---

## Notes for Developer

1. **Form resets after create**: The form calls `reset()` after successful create submission to clear fields for next entry.

2. **Edit mode preserves values**: After submit in edit mode, the form does NOT reset to allow user to make additional edits if needed.

3. **Date validation**: The date input uses native HTML5 date validation plus custom validation for valid date parsing.

4. **Category default**: If no category is provided, defaults to "other".

5. **Description optional**: No validation on description field - can be empty or any length.

---

## Comments

### [2026-03-08 11:15] Agent: PreTester
Created test documentation for AnnotationForm component. Tests cover all 9 categories, create/edit modes, validation, accessibility, and edge cases.

### [2026-03-08 11:15] Agent: PreTester
Ready for test file implementation.
