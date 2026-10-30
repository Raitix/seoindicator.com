# TEST-17-9: Step 4 - Alert Preferences Component

## Task Reference
- **Task**: [TASK-17-9](../TASKS/TASK-17-9.md)
- **Specification**: SPEC-017 (User Onboarding & Activation Flow)
- **Feature**: FEAT-017 (User Onboarding)

## Overview

Test suite for the Step 4 Alert Preferences Component - the fourth step of the onboarding wizard that allows users to configure notification preferences for ranking changes.

---

## Test Files

| File | Type | Tests | Description |
|------|------|-------|-------------|
| `frontend/tests/components/onboarding/Step4AlertPreferences.test.tsx` | Unit | 40 | Component rendering, toggles, interactions |

---

## Test Scenarios

### AP-001: Component Rendering

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AP-001-01 | Component renders with required props | defaultProps | Component renders without crash |
| AP-001-02 | Step title displayed | - | "Get notified when your rankings change" |
| AP-001-03 | Has correct data-testid | - | data-testid="step4-alert-preferences" |
| AP-001-04 | Renders both toggle switches | - | Email and Weekly toggles visible |
| AP-001-05 | Renders continue button | - | "Continue" button visible |
| AP-001-06 | Renders back button | - | "Back" button visible |
| AP-001-07 | Renders skip link | - | "Skip for now" link visible |
| AP-001-08 | Tooltip rendered | - | "Stay informed without checking constantly" |

### AP-002: Toggle Switches - Rendering

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AP-002-01 | Email toggle has label | - | "Email alerts for ranking changes" |
| AP-002-02 | Email toggle has description | - | Description text visible |
| AP-002-03 | Weekly toggle has label | - | "Weekly summary reports" |
| AP-002-04 | Weekly toggle has description | - | Description text visible |
| AP-002-05 | Email toggle default ON | - | Toggle checked by default |
| AP-002-06 | Weekly toggle default OFF | - | Toggle unchecked by default |
| AP-002-07 | Toggles have proper role | - | role="switch" |
| AP-002-08 | Toggles have aria-checked | - | aria-checked attribute present |

### AP-003: Toggle Switches - Interaction

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AP-003-01 | Toggle email OFF | Click email toggle | Email toggle unchecked |
| AP-003-02 | Toggle email ON again | Click again | Email toggle checked |
| AP-003-03 | Toggle weekly ON | Click weekly toggle | Weekly toggle checked |
| AP-003-04 | Toggle weekly OFF again | Click again | Weekly toggle unchecked |
| AP-003-05 | Toggle state persists | Toggle email OFF, check state | State remains OFF |
| AP-003-06 | Visual feedback on change | Toggle any | Animation/style change visible |
| AP-003-07 | Keyboard toggle (Enter) | Focus + Enter | Toggle state changes |
| AP-003-08 | Keyboard toggle (Space) | Focus + Space | Toggle state changes |

### AP-004: Initial Data

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AP-004-01 | Uses initialData when provided | initialData: { email: false, weekly: true } | Toggles match initialData |
| AP-004-02 | InitialData email OFF honored | initialData: { email: false } | Email toggle unchecked |
| AP-004-03 | InitialData weekly ON honored | initialData: { weekly: true } | Weekly toggle checked |
| AP-004-04 | Empty initialData uses defaults | initialData: undefined | Email ON, Weekly OFF |
| AP-004-05 | Partial initialData merges | initialData: { email: false } | Email OFF, Weekly OFF (default) |

### AP-005: Continue Button

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AP-005-01 | Triggers onComplete with preferences | Click continue | onComplete called with alert_preferences |
| AP-005-02 | Sends correct data structure | Click continue | `{ alert_preferences: { email: true, weekly: false } }` |
| AP-005-03 | Sends updated preferences | Toggle weekly ON, continue | `{ alert_preferences: { email: true, weekly: true } }` |
| AP-005-04 | Sends email OFF | Toggle email OFF, continue | `{ alert_preferences: { email: false, weekly: false } }` |
| AP-005-05 | Has accessible label | - | aria-label="Continue to next step" |
| AP-005-06 | Is primary button | - | Has primary styling variant |

### AP-006: Back Button

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AP-006-01 | Back button renders | - | "Back" button visible |
| AP-006-02 | Back triggers callback | Click back | onBack() called |
| AP-006-03 | Back has accessible label | - | aria-label="Go back to previous step" |
| AP-006-04 | Back preserves state | Toggle weekly ON, back, return | Weekly still ON |
| AP-006-05 | Back is secondary button | - | Has secondary/outline styling |

### AP-007: Skip Functionality

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AP-007-01 | Skip link renders | - | "Skip for now" visible |
| AP-007-02 | Skip triggers callback | Click skip | onSkip() called |
| AP-007-03 | Skip uses defaults | Click skip | Default preferences (email ON, weekly OFF) |
| AP-007-04 | Skip has accessible name | - | aria-label="Skip alert preferences setup" |
| AP-007-05 | Skip is keyboard accessible | Tab to skip, Enter | onSkip() called |

### AP-008: Accessibility

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AP-008-01 | Toggles have accessible names | - | aria-label or aria-labelledby |
| AP-008-02 | Email toggle aria-checked true | Default state | aria-checked="true" |
| AP-008-03 | Weekly toggle aria-checked false | Default state | aria-checked="false" |
| AP-008-04 | Focus visible on toggles | Tab to toggle | Focus ring visible |
| AP-008-05 | Focus visible on buttons | Tab to buttons | Focus ring visible |
| AP-008-06 | All interactive elements focusable | Tab through | All elements reachable |
| AP-008-07 | Screen reader announces state | Toggle, SR | "Email alerts, checked" or similar |
| AP-008-08 | Color contrast sufficient | All states | WCAG AA contrast ratio |
| AP-008-09 | Labels properly associated | - | Labels reference toggle IDs |
| AP-008-10 | Component has accessible name | - | aria-label or aria-labelledby on container |

### AP-009: Edge Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AP-009-01 | Both toggles OFF | Toggle both OFF | Both unchecked, continue works |
| AP-009-02 | Both toggles ON | Toggle both ON | Both checked, continue works |
| AP-009-03 | Rapid toggle clicks | Click toggle 5 times quickly | State matches final click |
| AP-009-04 | Missing onComplete | onComplete: undefined | No crash on continue click |
| AP-009-05 | Missing onBack | onBack: undefined | No crash on back click |
| AP-009-06 | Missing onSkip | onSkip: undefined | No crash on skip click |

---

## Mock Data

### Default Props
```typescript
const defaultProps = {
  onComplete: vi.fn(),
  onBack: vi.fn(),
  onSkip: vi.fn(),
  initialData: undefined,
};
```

### Initial Data Examples
```typescript
// Email ON, Weekly OFF (defaults)
const defaultPreferences = { email: true, weekly: false };

// Email OFF, Weekly ON
const customPreferences = { email: false, weekly: true };

// Both OFF
const minimalPreferences = { email: false, weekly: false };

// Both ON
const allPreferences = { email: true, weekly: true };
```

### Expected onComplete Data
```typescript
// Default state
{
  alert_preferences: {
    email: true,
    weekly: false
  }
}

// Both enabled
{
  alert_preferences: {
    email: true,
    weekly: true
  }
}
```

---

## Coverage Targets

| Metric | Target |
|--------|--------|
| Statements | 90% |
| Branches | 85% |
| Functions | 95% |
| Lines | 90% |

---

## Test Execution Commands

```bash
# Unit tests
cd frontend
npm test -- --testPathPattern="Step4AlertPreferences"

# Coverage
npm test -- --coverage --testPathPattern="Step4AlertPreferences"
```

---

## Dependencies

- TASK-17-3 (OnboardingProvider, Modal, Wizard Shell)
- TASK-17-8 (Step 3 - Ranking Preview Component)

---

## Notes for Developer

1. **Component Location**: `frontend/components/onboarding/Step4AlertPreferences.tsx`

2. **Props Interface**:
```typescript
interface Step4AlertsProps {
  onComplete: (data: { alert_preferences: { email: boolean; weekly: boolean } }) => void;
  onBack: () => void;
  onSkip: () => void;
  initialData?: { email: boolean; weekly: boolean };
}
```

3. **Default Values**:
```typescript
const defaultPreferences = {
  email: true,   // Email alerts ON by default
  weekly: false, // Weekly reports OFF by default
};
```

4. **UI Elements Required**:
   - Step title: "Get notified when your rankings change"
   - Email toggle with label and description
   - Weekly toggle with label and description
   - Tooltip: "Stay informed without checking constantly"
   - Continue button (primary)
   - Back button (secondary)
   - Skip link

5. **Toggle Switch Requirements**:
   - Use role="switch"
   - Include aria-checked attribute
   - Support keyboard (Enter and Space)
   - Visible focus state

6. **Data Structure**:
```json
{
  "alert_preferences": {
    "email": true,
    "weekly": false
  }
}
```

7. **Accessibility Checklist**:
   - [ ] All toggles have accessible names
   - [ ] aria-checked reflects current state
   - [ ] Focus visible on all interactive elements
   - [ ] Labels associated with controls
   - [ ] Color contrast WCAG AA
   - [ ] Screen reader announces state changes

---

## Comments

### [2026-03-18 18:50 GMT+2] Agent: PreTester
- Test documentation created
- 40 test scenarios defined across 9 categories
- Ready for Developer implementation
- Status: To Write Tests → To Develop
