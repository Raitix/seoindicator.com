# TASK-17-7: Progress Tracking Integration

## STATUS
`Done`

## PRIORITY
`High`

## RISK
`Low`

## Specification
- **SPEC-017**: User Onboarding & Activation Flow

## Dependencies
- TASK-17-1 (Database Migration - User Onboarding Table)
- TASK-17-2 (Backend - OnboardingService and Controller)
- TASK-17-3 (Frontend - OnboardingProvider, Modal, and Wizard Shell)

## Description
Implement the resume functionality that allows users to continue their onboarding if they previously skipped or abandoned it. This includes checking for existing progress on login, displaying a resume banner, and restoring the wizard to the correct step.

## Acceptance Criteria
- [ ] On login, check for existing onboarding progress via GET `/api/v1/onboarding`
- [ ] If incomplete onboarding exists:
  - [ ] Show "Complete your setup" banner on dashboard
  - [ ] Banner shows progress: "Complete your setup (X/5 steps)"
  - [ ] Banner has "Continue" button that opens wizard at correct step
  - [ ] Banner has "Dismiss" option that respects for session
- [ ] If onboarding was skipped:
  - [ ] Show subtle reminder banner (lower priority)
  - [ ] Option to restart onboarding from beginning
- [ ] Wizard restores to correct step on open
- [ ] Previously entered data pre-populated in form fields
- [ ] Progress bar in wizard shows completion percentage
- [ ] Backend correctly returns progress percentage (0-100)
- [ ] State persisted across browser refresh (localStorage backup)

## Effort Estimate
`S` (4-6 hours)

## Technical Details

### Banner Component
```typescript
interface OnboardingBannerProps {
  currentStep: number;
  completedSteps: number[];
  onContinue: () => void;
  onDismiss: () => void;
}
```

### Progress Calculation
```typescript
const calculateProgress = (completedSteps: number[]): number => {
  // MVP: 2 steps (1, 2) + completion (5) = 3 "completable" items
  // Full: 5 steps
  return Math.round((completedSteps.length / 5) * 100);
};
```

### localStorage Backup
```typescript
// Persist on each step completion
localStorage.setItem('onboarding_progress', JSON.stringify({
  currentStep,
  completedSteps,
  data,
  timestamp: Date.now()
}));
```

## Testing Requirements
- Unit test for progress calculation
- Unit test for banner rendering (different states)
- Unit test for resume functionality
- Unit test for data restoration
- Feature test for progress persistence across refresh
- Integration test for full resume flow

## Notes
This task ensures users who abandon onboarding can easily resume, improving completion rates.

---

*Created by SpecSystemAnalyst on 2026-02-28*
