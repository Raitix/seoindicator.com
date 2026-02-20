# TASK-9-8: AnnotationForm Component

## STATUS
`To Prioritize`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Implement the `AnnotationForm` React component for creating and editing annotations. The component should be reusable and able to differentiate create vs edit modes.

## Implementation Requirements
1. **Props**
   - `keywordId: number` - ID of the associated keyword.
   - `annotation?: Annotation` - Used for edit mode.
   - `onSave: (data: CreateAnnotationDTO) => void` - Callback triggered upon save.
   - `onCancel: () => void` - Callback for closing the form.

2. **Behavior**
   - Populates fields in edit mode.
   - Clears fields in create mode.
   - Supports category dropdown with predefined options (e.g., "Content Update", "Algorithm Update").

3. **Integration**
   - Used with the `AnnotationList` for editing annotations.
   - Data submission triggers backend create or update API.

## Example Usage
```tsx
<AnnotationForm 
  keywordId={keyword.id}
  onSave={handleSave}
  onCancel={handleCancel}
  annotation={existingAnnotation}
/>
```

## Acceptance Criteria
- [ ] Component renders accurately in create and edit modes.
- [ ] Save action sends validated annotations to the backend.
- [ ] Form includes all annotation fields (date, title, description, category).

## Dependencies
- TASK-9-4: Backend CRUD endpoints.

## Effort Estimate
3-5 hours.

---

## Comments
### [2026-02-20 17:54] Agent: SpecSystemAnalyst
Task created for creating a form component to manage both the creation and editing of annotations.