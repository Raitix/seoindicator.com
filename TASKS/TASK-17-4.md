# TASK-17-4: Step 1 - Website URL Input Component

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Specification
- **SPEC-017**: User Onboarding & Activation Flow

## Dependencies
- TASK-17-3 (Frontend - OnboardingProvider, Modal, and Wizard Shell)

## Description
Create the first step of the onboarding wizard: Website URL input. This component welcomes the user and collects their website URL for tracking context. Includes URL validation, SSRF-safe submission, and tooltip explanations.

## Acceptance Criteria
- [ ] Welcome message displayed: "Welcome to SEOIndicator! Let's get you set up in 5 minutes."
- [ ] URL input field with placeholder: "https://example.com"
- [ ] URL validation on blur:
  - [ ] Valid URL format (http/https only)
  - [ ] Max 500 characters
  - [ ] Clear error message for invalid URLs
- [ ] Tooltip component: "We'll track rankings for pages on this site"
- [ ] "Continue" button:
  - [ ] Disabled until valid URL entered
  - [ ] Shows loading state during submission
  - [ ] Advances to step 2 on success
- [ ] "Skip for now" link:
  - [ ] Calls skipOnboarding API
  - [ ] Closes modal on success
- [ ] SSRF-protected URL validation matches backend
- [ ] Auto-focus on input field when step appears
- [ ] Keyboard navigation (Enter to submit)
- [ ] Accessible labels and ARIA attributes

## Effort Estimate
`S` (4-6 hours)

## Technical Details

### Component Props
```typescript
interface Step1WebsiteProps {
  onComplete: (data: { website_url: string }) => void;
  onSkip: () => void;
  initialData?: string;
}
```

### Validation Rules
```typescript
const validateUrl = (url: string): { valid: boolean; error?: string } => {
  // Must be http or https
  // Must be valid URL format
  // Max 500 characters
  // Block localhost, private IPs, file:// protocol
}
```

### UI Elements
- Shadcn/UI Input component
- Shadcn/UI Button component
- Custom Tooltip component
- Error message display area

## Testing Requirements
- Unit test for URL validation (valid/invalid URLs)
- Unit test for SSRF protection (blocked URLs)
- Unit test for continue button disabled state
- Unit test for skip functionality
- Unit test for keyboard navigation
- Accessibility test (ARIA labels, focus management)

## Notes
This is the user's first interaction with the onboarding flow. Focus on a welcoming, non-intimidating experience.

---

*Created by SpecSystemAnalyst on 2026-02-28*
