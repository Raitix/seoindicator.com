# TASK-34-9: Frontend Create Project Form

## STATUS
`To Prioritize`

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

*Created by SpecSystemAnalyst on 2026-03-07*
