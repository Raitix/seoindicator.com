# TASK-17-5: Step 2 - First Keyword Input Component

## STATUS`n`To Test Locally`

## PRIORITY
`High`

## RISK
`Low`

## Specification
- **SPEC-017**: User Onboarding & Activation Flow

## Dependencies
- TASK-17-3 (Frontend - OnboardingProvider, Modal, and Wizard Shell)
- TASK-17-4 (Step 1 - Website URL Input Component)

## Description
Create the second step of the onboarding wizard: First keyword input. This component collects the user's first keyword to track, establishing immediate value. The keyword is stored but not created in the database until onboarding completion.

## Acceptance Criteria
- [ ] Step title: "What keyword do you want to track?"
- [ ] Keyword input field with placeholder: "e.g., best seo tools"
- [ ] Input validation:
  - [ ] Required field
  - [ ] Max 255 characters
  - [ ] Trimmed whitespace
  - [ ] Normalized to lowercase for storage
- [ ] Tooltip: "This is the search term you want to rank for"
- [ ] Example keywords shown as gray placeholder text
- [ ] "Continue" button:
  - [ ] Disabled until keyword entered
  - [ ] Shows loading state during submission
  - [ ] Advances to step 3 on success
- [ ] "Back" button returns to step 1
- [ ] "Skip for now" link available
- [ ] Auto-focus on input field when step appears
- [ ] Keyboard navigation (Enter to submit)
- [ ] Data persisted to backend on continue
- [ ] Preserves entered keyword on back navigation

## Effort Estimate
`S` (4-6 hours)

## Technical Details

### Component Props
```typescript
interface Step2KeywordProps {
  onComplete: (data: { first_keyword: string }) => void;
  onBack: () => void;
  onSkip: () => void;
  initialData?: string;
}
```

### Validation
```typescript
const validateKeyword = (keyword: string): { valid: boolean; error?: string } => {
  // Required
  // Max 255 characters after trim
  // No normalization on display, only on storage
}
```

### Example Placeholders
- "best seo tools"
- "keyword research software"
- "free rank tracker"

## Testing Requirements
- Unit test for keyword validation (valid/invalid inputs)
- Unit test for continue button state
- Unit test for back button navigation
- Unit test for skip functionality
- Unit test for keyboard navigation
- Unit test for data persistence on back/forward

## Notes
The keyword is stored in the user_onboarding table, not created in keywords table until Task 17-2 completes the full onboarding. This prevents orphan keywords.

---

*Created by SpecSystemAnalyst on 2026-02-28*
