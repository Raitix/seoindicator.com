# TASK-17-9: Step 4 - Alert Preferences Component

## STATUS`n`To Write Tests`

## PRIORITY
`Medium`

## RISK
`Low`

## Specification
- **SPEC-017**: User Onboarding & Activation Flow

## Dependencies
- TASK-17-3 (Frontend - OnboardingProvider, Modal, and Wizard Shell)
- TASK-17-8 (Step 3 - Ranking Preview Component)

## Description
Create the fourth step of the onboarding wizard: Alert preferences. This component allows users to configure their notification preferences for ranking changes, establishing engagement hooks that bring users back to the platform.

## Acceptance Criteria
- [ ] Step title: "Get notified when your rankings change"
- [ ] Toggle: "Email alerts for ranking changes"
  - [ ] Default: ON
  - [ ] Clear label and description
- [ ] Toggle: "Weekly summary reports"
  - [ ] Default: OFF
  - [ ] Clear label and description
- [ ] Tooltip: "Stay informed without checking constantly"
- [ ] Preferences persisted to backend on continue
- [ ] "Continue" button advances to step 5
- [ ] "Back" button returns to step 3
- [ ] "Skip for now" link available (defaults to email ON)
- [ ] Accessible toggle switches with proper labels
- [ ] Visual feedback on toggle state change

## Effort Estimate
`S` (4-6 hours)

## Technical Details

### Component Props
```typescript
interface Step4AlertsProps {
  onComplete: (data: { alert_preferences: { email: boolean; weekly: boolean } }) => void;
  onBack: () => void;
  onSkip: () => void;
  initialData?: { email: boolean; weekly: boolean };
}
```

### Default Values
```typescript
const defaultPreferences = {
  email: true,
  weekly: false
};
```

### Data Structure
```json
{
  "alert_preferences": {
    "email": true,
    "weekly": false
  }
}
```

## Testing Requirements
- Unit test for toggle rendering
- Unit test for default values
- Unit test for preference changes
- Unit test for continue with preferences
- Unit test for skip (default values)
- Unit test for back navigation
- Accessibility test for toggle switches

## Notes
These preferences are stored in user_onboarding.alert_preferences JSON field. The actual email/scheduled report functionality is handled by separate features (TASK-4-11 scheduled reports).

---

*Created by SpecSystemAnalyst on 2026-02-28*
