# TEST-34-9: Frontend Create Project Form

## Task Reference
TASK-34-9: Frontend Create Project Form
Specification: SPEC-034 - AI Visibility Tracking (Generative Engine Optimization)

---

## Test Files

| File | Tests | Purpose |
|------|-------|---------|
| `frontend/__tests__/app/ai-visibility/new/page.test.tsx` | 40 | Page component, routing, SEO |
| `frontend/__tests__/components/ai-visibility/CreateProjectForm.test.tsx` | 120 | Form validation, submission, states |
| `frontend/__tests__/components/ai-visibility/TagInput.test.tsx` | 75 | Tag input behavior, limits, duplicates |
| **Total** | **235** | |

---

## Test Categories

### Page Component Tests (PAGE-*) - 40 tests

| Category | Tests | Description |
|----------|-------|-------------|
| PAGE-RENDER | 6 | Page rendering, layout, structure |
| PAGE-AUTH | 4 | Authentication required, redirect |
| PAGE-SEO | 4 | Metadata, title, OG tags |
| PAGE-NAV | 5 | Navigation, back button, breadcrumbs |
| PAGE-LOADING | 4 | Loading states, skeleton |
| PAGE-ERROR | 4 | Error boundaries, error display |
| PAGE-A11Y | 5 | Accessibility attributes |
| PAGE-INT | 8 | Integration with form component |

### CreateProjectForm Tests (FORM-*) - 120 tests

| Category | Tests | Description |
|----------|-------|-------------|
| FORM-RENDER | 8 | Form rendering, all fields visible |
| FORM-FIELDS | 15 | Field rendering, labels, placeholders |
| FORM-VALIDATION | 25 | Client-side validation, error messages |
| FORM-TAGS | 15 | Keywords/competitors tag behavior |
| FORM-FREQUENCY | 8 | Tracking frequency select |
| FORM-TEMPLATES | 8 | Query templates multi-select |
| FORM-SUBMIT | 15 | Form submission, loading state |
| FORM-SUCCESS | 8 | Success flow, redirect, toast |
| FORM-ERROR | 12 | Error handling, inline errors, retry |
| FORM-CANCEL | 5 | Cancel button, navigation |
| FORM-A11Y | 10 | Accessibility attributes |
| FORM-EDGE | 11 | Edge cases, boundary conditions |

### TagInput Tests (TAG-*) - 75 tests

| Category | Tests | Description |
|----------|-------|-------------|
| TAG-RENDER | 6 | Component rendering, structure |
| TAG-ADD | 12 | Adding tags via Enter, paste |
| TAG-REMOVE | 10 | Removing tags via X, Backspace |
| TAG-LIMITS | 12 | Min/max limits, visual feedback |
| TAG-DUPLICATES | 10 | Duplicate prevention, warnings |
| TAG-VALIDATION | 10 | Input validation, max length |
| TAG-FOCUS | 8 | Focus management, blur behavior |
| TAG-A11Y | 9 | Accessibility attributes |
| TAG-EDGE | 8 | Edge cases, unicode, empty |

---

## Component Interfaces

### CreateProjectFormProps

```typescript
interface CreateProjectFormProps {
  onSuccess?: (projectId: number) => void;
  onCancel?: () => void;
  className?: string;
  defaultValues?: Partial<CreateProjectFormData>;
}
```

### CreateProjectFormData

```typescript
interface CreateProjectFormData {
  brand_name: string;           // required, max 255 chars
  keywords: string[];           // required, 1-10 items, each max 100 chars
  competitors: string[];        // required, 1-3 items, each max 100 chars
  tracking_frequency: 'daily' | 'weekly' | 'monthly';  // default: 'weekly'
  query_templates?: string[];   // optional
}
```

### TagInputProps

```typescript
interface TagInputProps {
  id: string;
  name: string;
  label: string;
  value: string[];
  onChange: (tags: string[]) => void;
  minTags?: number;
  maxTags: number;
  maxLength?: number;
  placeholder?: string;
  required?: boolean;
  disabled?: boolean;
  error?: string;
  className?: string;
}
```

---

## Validation Rules

### Brand Name
- Required
- Max length: 255 characters
- Pattern: alphanumeric, spaces, hyphens, underscores

### Keywords
- Required
- Min items: 1
- Max items: 10
- Each item max length: 100 characters
- Pattern: alphanumeric, spaces, hyphens

### Competitors
- Required
- Min items: 1
- Max items: 3
- Each item max length: 100 characters

### Tracking Frequency
- Optional
- Values: 'daily' | 'weekly' | 'monthly'
- Default: 'weekly'

### Query Templates
- Optional
- Multi-select with custom input

---

## API Integration

### POST /api/v1/ai-visibility/projects

**Request:**
```json
{
  "brand_name": "Acme Corp",
  "keywords": ["brand tracking", "seo tools"],
  "competitors": ["Competitor A", "Competitor B"],
  "tracking_frequency": "weekly",
  "query_templates": ["template-1", "template-2"]
}
```

**Success Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "brand_name": "Acme Corp",
    "keywords": ["brand tracking", "seo tools"],
    "competitors": ["Competitor A", "Competitor B"],
    "tracking_frequency": "weekly",
    "query_templates": ["template-1", "template-2"],
    "is_active": true,
    "created_at": "2026-03-07T20:00:00Z"
  }
}
```

**Validation Error (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "brand_name": ["The brand name field is required."],
    "keywords": ["You must provide at least 1 keyword."]
  }
}
```

**Rate Limit Error (429):**
```json
{
  "success": false,
  "message": "Too many requests. Please try again later."
}
```

---

## Test Patterns

### Mock Setup

```typescript
// Mock React Query mutation
const mockMutateAsync = vi.fn();
vi.mock('@tanstack/react-query', () => ({
  useMutation: (options: any) => ({
    mutate: mockMutate,
    mutateAsync: mockMutateAsync,
    isLoading: false,
    isError: false,
    error: null
  }),
  useQueryClient: () => ({
    invalidateQueries: vi.fn()
  })
}));

// Mock next/navigation
vi.mock('next/navigation', () => ({
  useRouter: () => ({
    push: mockPush,
    replace: vi.fn()
  }),
  usePathname: () => '/ai-visibility/new'
}));

// Mock sonner toast
vi.mock('sonner', () => ({
  toast: {
    success: vi.fn(),
    error: vi.fn()
  }
}));
```

### User Flow Tests

```typescript
// Successful form submission
it('should submit form and redirect on success', async () => {
  const user = userEvent.setup();
  mockMutateAsync.mockResolvedValueOnce({ id: 1, brand_name: 'Test Brand' });

  render(<CreateProjectForm />);

  await user.type(screen.getByLabelText(/brand name/i), 'Test Brand');
  await user.type(screen.getByLabelText(/keywords/i), 'keyword1{enter}');
  await user.type(screen.getByLabelText(/competitors/i), 'Competitor{enter}');
  await user.click(screen.getByRole('button', { name: /create project/i }));

  await waitFor(() => {
    expect(mockMutateAsync).toHaveBeenCalledWith({
      brand_name: 'Test Brand',
      keywords: ['keyword1'],
      competitors: ['Competitor'],
      tracking_frequency: 'weekly',
      query_templates: []
    });
  });

  expect(mockPush).toHaveBeenCalledWith('/ai-visibility/1');
});
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-34-6 (API Endpoints) | To Write Tests | Mocked in tests |
| TASK-34-8 (Project List) | To Write Tests | Navigation destination |

---

## Acceptance Criteria Coverage

| AC | Tests | Status |
|----|-------|--------|
| Page created at `frontend/app/ai-visibility/new/page.tsx` | PAGE-RENDER-001 | ✅ |
| Form fields with validation | FORM-FIELDS-*, FORM-VALIDATION-* | ✅ |
| Client-side validation | FORM-VALIDATION-* | ✅ |
| Keyword/Competitor tag input | TAG-*, FORM-TAGS-* | ✅ |
| Form actions (Create/Cancel) | FORM-SUBMIT-*, FORM-CANCEL-* | ✅ |
| Success flow (redirect + toast) | FORM-SUCCESS-* | ✅ |
| Error handling | FORM-ERROR-* | ✅ |
| API integration | FORM-SUBMIT-*, FORM-ERROR-* | ✅ |
| Accessibility | PAGE-A11Y-*, FORM-A11Y-*, TAG-A11Y-* | ✅ |
| Component tests | All test files | ✅ |

---

## TDD Pattern

Tests are designed with mock implementations included in test files. Developer should:

1. Create page at `frontend/app/ai-visibility/new/page.tsx`
2. Create `CreateProjectForm` component
3. Create `TagInput` component
4. Implement React Hook Form + Zod validation
5. Integrate with API endpoint (TASK-34-6)
6. Add shadcn/ui components (Input, Select, Button)

---

*Created by PreTester on 2026-03-07*
