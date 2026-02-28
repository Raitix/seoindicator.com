# TASK-17-3: Frontend - OnboardingProvider, Modal, and Wizard Shell

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Specification
- **SPEC-017**: User Onboarding & Activation Flow

## Dependencies
- TASK-17-2 (Backend - OnboardingService and Controller)

## Description
Create the core frontend infrastructure for the onboarding wizard, including the React Context provider for state management, the modal entry point, and the wizard container component. This task establishes the foundation that individual step components will plug into.

## Acceptance Criteria
- [ ] OnboardingProvider context created with state management
- [ ] State interface defined:
  - [ ] `isOpen: boolean`
  - [ ] `currentStep: number`
  - [ ] `completedSteps: number[]`
  - [ ] `data: { websiteUrl, firstKeyword, keywordId, alertPreferences }`
  - [ ] `isLoading: boolean`
  - [ ] `error: string | null`
- [ ] Context methods implemented:
  - [ ] `startOnboarding(): Promise<void>`
  - [ ] `nextStep(data?: StepData): Promise<void>`
  - [ ] `prevStep(): void`
  - [ ] `skipOnboarding(): Promise<void>`
  - [ ] `completeOnboarding(): Promise<void>`
- [ ] OnboardingModal component created
- [ ] Modal triggers on user registration (first login)
- [ ] OnboardingWizard component created with step navigation
- [ ] Wizard renders correct step component based on currentStep
- [ ] Back/Next navigation buttons work correctly
- [ ] Loading states displayed during API calls
- [ ] Error states displayed with user-friendly messages
- [ ] Modal close button with confirmation dialog
- [ ] Responsive design (mobile-friendly)
- [ ] Integration with backend API endpoints

## Effort Estimate
`M` (1-2 days)

## Technical Details

### Component Structure
```
frontend/components/onboarding/
├── OnboardingProvider.tsx      # Context provider
├── OnboardingModal.tsx         # Entry point modal
├── OnboardingWizard.tsx        # Main wizard container
└── steps/
    └── (step components in separate tasks)
```

### State Management
```typescript
interface OnboardingState {
  isOpen: boolean;
  currentStep: number;
  completedSteps: number[];
  data: {
    websiteUrl: string | null;
    firstKeyword: string | null;
    keywordId: number | null;
    alertPreferences: {
      email: boolean;
      weekly: boolean;
    } | null;
  };
  isLoading: boolean;
  error: string | null;
}
```

### API Integration
- GET `/api/v1/onboarding` - Fetch progress on mount
- POST `/api/v1/onboarding` - Save step data
- POST `/api/v1/onboarding/complete` - Finish onboarding
- POST `/api/v1/onboarding/skip` - Skip onboarding

## Testing Requirements
- Unit tests for OnboardingProvider state management
- Unit tests for context methods (mocked API)
- Unit tests for OnboardingModal rendering
- Unit tests for OnboardingWizard navigation
- Integration test for full wizard flow (with mocked steps)

## Notes
This task creates the infrastructure. Individual step components (Tasks 17-4 through 17-6) will plug into this system.

---

*Created by SpecSystemAnalyst on 2026-02-28*
