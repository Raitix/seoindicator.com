# TASK-34-9: Frontend Create Project Form

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Low`

## Specification
SPEC-034: AI Visibility Tracking (Generative Engine Optimization)

---

## Description

Create the project creation form for setting up new AI visibility tracking projects.

---

## Acceptance Criteria

- [ ] Page created at `frontend/app/ai-visibility/new/page.tsx`

- [ ] Form fields
  - [ ] Brand Name: text input, required, max 255 chars
  - [ ] Keywords: multi-text input (tags), required, 1-10 keywords, each max 100 chars
  - [ ] Competitors: multi-text input (tags), required, 1-3 competitors, each max 100 chars
  - [ ] Tracking Frequency: select (daily, weekly, monthly), default: weekly
  - [ ] Query Templates: optional, multi-select with custom input

- [ ] Form validation
  - [ ] Client-side validation before submit
  - [ ] Real-time validation feedback
  - [ ] Server error display

- [ ] Keyword/Competitor tag input
  - [ ] Add tags by typing + Enter
  - [ ] Remove tags by clicking X
  - [ ] Visual feedback for max limits
  - [ ] Duplicate prevention

- [ ] Form actions
  - [ ] "Create Project" submit button
  - [ ] "Cancel" link back to project list
  - [ ] Loading state during submission

- [ ] Success flow
  - [ ] Redirect to project dashboard on success
  - [ ] Success toast notification

- [ ] Error handling
  - [ ] Display validation errors inline
  - [ ] Generic error toast for server errors
  - [ ] Retry option on failure

- [ ] API integration
  - [ ] Uses `POST /api/v1/ai-visibility/projects`
  - [ ] Handles rate limit (429) response

- [ ] Accessibility
  - [ ] Proper form labels
  - [ ] Focus management
  - [ ] Keyboard navigation
  - [ ] ARIA attributes for errors

- [ ] Component tests created
  - [ ] Test form submission
  - [ ] Test validation
  - [ ] Test tag input behavior
  - [ ] Test error states

---

## Dependencies
- TASK-34-6 (API Endpoints)
- TASK-34-8 (Project List - for navigation)

---

## Effort Estimate
**3 hours**

---

## Technical Notes

- Use React Hook Form for form state management
- Use Zod for validation schema
- Use shadcn/ui components: Input, Select, Button, TagInput
- Consider react-select or react-tagsinput for tag management

---

## Comments

### ProjectManager Prioritization (2026-03-07 19:08 UTC)

**Priority: HIGH (confirmed)** ✅

**Rationale:**
- **Core User Workflow** - Project creation form is essential for onboarding users to AI visibility tracking
- **MVP Critical Path** - Part of Phase 1 MVP (Core Tracking) in SPEC-034
- **User-Facing Functionality** - Without this form, users cannot create projects to track their brand visibility
- **Business Value** - Entry point for the entire GEO feature - users must create projects before any tracking can occur
- **High User Impact** - First interaction with AI visibility feature for new users

**Risk: LOW (confirmed)** ✅

**Rationale:**
- **Frontend-Only Implementation** - React/Next.js form, no external APIs directly
- **Standard Form Patterns** - React Hook Form + Zod validation are well-established
- **Component Library** - Uses shadcn/ui components (Input, Select, Button, TagInput)
- **No Security Concerns** - Authentication and authorization handled by API layer
- **Client-Side Only** - Validation happens client-side, server errors displayed gracefully

**Dependency Status:**

| Dependency | Status | Ready |
|------------|--------|-------|
| TASK-34-6 (API Endpoints) | To Write Tests | ❌ NOT IMPLEMENTED |
| TASK-34-8 (Project List) | To Write Tests | ❌ NOT IMPLEMENTED |

**TDD Pattern Applied:**

PreTester CAN design tests immediately using mocked API responses:

1. **Mock `POST /api/v1/ai-visibility/projects`** - Define success/error response shapes
2. **React Testing Library** - Component rendering with form interactions
3. **Test Categories:**
   - Form rendering with all fields (brand_name, keywords, competitors, tracking_frequency, query_templates)
   - Tag input behavior (add by Enter, remove by X, max limits, duplicate prevention)
   - Client-side validation (required fields, max lengths, format validation)
   - Real-time validation feedback
   - Form submission success (redirect + toast)
   - Form submission errors (validation, server, rate limit)
   - Loading state during submission
   - Cancel button navigation
   - Accessibility (labels, focus management, ARIA, keyboard nav)
   - Responsive design (mobile stacked, desktop side-by-side)

**Component Structure to Test:**

| Component | Purpose |
|-----------|---------|
| `CreateProjectForm` | Main form container with validation |
| `TagInput` | Multi-text input for keywords/competitors |
| `FormField` | Reusable field with label, input, error display |
| `FormActions` | Submit + Cancel buttons with loading states |

**Form Fields to Test:**

| Field | Type | Validation |
|-------|------|------------|
| `brand_name` | text | required, max 255 chars |
| `keywords` | tags | required, 1-10 items, each max 100 chars |
| `competitors` | tags | required, 1-3 items, each max 100 chars |
| `tracking_frequency` | select | optional, default: weekly |
| `query_templates` | multi-select | optional |

**API Integration:**
- `POST /api/v1/ai-visibility/projects` - Create project
- Handles 429 rate limit response
- Success: redirect to project dashboard
- Error: inline validation errors or toast notification

**Acceptance Criteria Summary:**
- 10 acceptance criteria covering: page creation, form fields, validation, tag input, actions, success/error flows, API integration, accessibility, component tests

**Parallel Development:**
- PreTester designs tests NOW with mocked API responses
- Tests use MSW (Mock Service Worker) or Jest mocks for fetch
- Developer implements when TASK-34-6 reaches `To Test Locally`
- Integration tests run against real API when ready

**Key Design Decisions:**
- React Hook Form for form state management (reduces re-renders, built-in validation)
- Zod schema for validation (shares types with backend)
- shadcn/ui TagInput for keyword/competitor tags (consistent UI)
- Client-side validation before submit (better UX)
- Server errors displayed inline with fallback toast

**Handoff to PreTester:** Ready for test design. Focus on:
- Mock POST API responses for success/validation error/server error
- Form field validation (required, max lengths, tag limits)
- Tag input behavior (add, remove, max limits, duplicates)
- Form submission states (idle, loading, success, error)
- Accessibility requirements (labels, focus, keyboard, ARIA)
- Responsive form layout

---

## Comments

### PreTester Test Coverage (2026-03-07 20:15 UTC)

**Status:** To Develop ✅

**Tests Created:** 235 test scenarios across 3 test files

| File | Tests | Purpose |
|------|-------|---------|
| `TESTS/TASK-34-9.md` | - | Test documentation |
| `frontend/__tests__/app/ai-visibility/new/page.test.tsx` | 40 | Page component, routing, SEO, accessibility |
| `frontend/__tests__/components/ai-visibility/CreateProjectForm.test.tsx` | 120 | Form validation, submission, states, error handling |
| `frontend/__tests__/components/ai-visibility/TagInput.test.tsx` | 75 | Tag input behavior, limits, duplicates, accessibility |

**Test Categories:**

| Category | Tests | Description |
|----------|-------|-------------|
| PAGE-* | 40 | Page rendering, auth, SEO, navigation, accessibility |
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
| TAG-* | 75 | TagInput component tests |

**Component Interfaces Defined:**

```typescript
interface CreateProjectFormProps {
  onSuccess?: (projectId: number) => void;
  onCancel?: () => void;
  className?: string;
  defaultValues?: Partial<CreateProjectFormData>;
}

interface CreateProjectFormData {
  brand_name: string;           // required, max 255 chars
  keywords: string[];           // required, 1-10 items, each max 100 chars
  competitors: string[];        // required, 1-3 items, each max 100 chars
  tracking_frequency: 'daily' | 'weekly' | 'monthly';  // default: 'weekly'
  query_templates?: string[];   // optional
}

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

**Validation Rules Tested:**
- Brand Name: required, max 255 chars, alphanumeric/spaces/hyphens
- Keywords: required, 1-10 items, each max 100 chars
- Competitors: required, 1-3 items, each max 100 chars
- Tracking Frequency: optional, default 'weekly'

**API Integration Mocked:**
- `POST /api/v1/ai-visibility/projects` - Create project
- Success (201): Redirect to `/ai-visibility/{projectId}`
- Validation Error (422): Display inline errors
- Rate Limit (429): Show toast notification
- Server Error (500): Show toast with retry option

**Key Test Patterns:**
- Mock React Query mutation with `vi.mock('@tanstack/react-query')`
- Mock Next.js navigation with `vi.mock('next/navigation')`
- Mock sonner toast with `vi.mock('sonner')`
- Mock Lucide icons with `data-testid` attributes
- TDD mock components included in test files for reference

**Dependencies (Mocked):**
- TASK-34-6 (API Endpoints) - Mocked in tests
- TASK-34-8 (Project List) - Navigation destination

**Handoff to Developer:**
1. Create page at `frontend/app/ai-visibility/new/page.tsx`
2. Create `CreateProjectForm` component with React Hook Form + Zod
3. Create `TagInput` reusable component
4. Integrate with API endpoint (TASK-34-6)
5. Add shadcn/ui components (Input, Select, Button)
6. Run tests: `npm test -- __tests__/ai-visibility/new`

---

*Created by SpecSystemAnalyst on 2026-03-07*
