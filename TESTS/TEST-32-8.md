# TEST-32-8: UseCaseSection Component Tests

## Task Reference
- **Task:** TASK-32-8
- **Specification:** SPEC-32 (Competitor Comparison Landing Pages)
- **Feature:** FEAT-032

## Overview
Test suite for the UseCaseSection component that displays use cases for both SEOIndicator and competitors side by side. Tests cover rendering, accessibility, mobile responsiveness, and edge cases.

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `frontend/__tests__/components/compare/UseCaseSection.test.tsx` | Unit | Component rendering and behavior tests |
| `frontend/__tests__/a11y/UseCaseSection.a11y.test.tsx` | Accessibility | WCAG compliance tests |

## Test Scenarios

### UC-001: Component Rendering

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| 001 | Renders with valid use cases for both tools | Valid props with use cases | Both columns displayed with use cases |
| 002 | Renders with empty seoindicator array | `seoindicator: []` | SEOIndicator column empty, competitor column shows |
| 003 | Renders with empty competitor array | `competitor: []` | Competitor column empty, seoindicator column shows |
| 004 | Renders with both arrays empty | `seoindicator: [], competitor: []` | Empty state message displayed |
| 005 | Renders with null seoindicator | `seoindicator: null` | Handles gracefully |
| 006 | Renders with null competitor | `competitor: null` | Handles gracefully |
| 007 | Renders with undefined props | `seoindicator: undefined` | Handles gracefully |

### UC-002: UseCase Card Rendering

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| 001 | Renders use case with all fields | Complete UseCase object | Title, description, audience badge, budget badge |
| 002 | Renders audience badge with correct label | `audience: 'solo'` | Badge shows "Solo Users" |
| 003 | Renders audience badge for small-team | `audience: 'small-team'` | Badge shows "Small Teams" |
| 004 | Renders audience badge for enterprise | `audience: 'enterprise'` | Badge shows "Enterprise" |
| 005 | Renders audience badge for agency | `audience: 'agency'` | Badge shows "Agencies" |
| 006 | Renders budget badge with correct label | `budget: 'free'` | Badge shows "Free" |
| 007 | Renders budget badge for budget | `budget: 'budget'` | Badge shows "Budget" |
| 008 | Renders budget badge for mid-range | `budget: 'mid-range'` | Badge shows "Mid-Range" |
| 009 | Renders budget badge for premium | `budget: 'premium'` | Badge shows "Premium" |

### UC-003: Visual Separation

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| 001 | SEOIndicator column has emerald branding | Valid props | Emerald colored heading/badges |
| 002 | Competitor column has blue branding | Valid props | Blue colored heading/badges |
| 003 | Cards have hover state | Hover on card | Border color changes |
| 004 | Grid layout on desktop | Viewport >= 768px | Two columns side by side |
| 005 | Stacked layout on mobile | Viewport < 768px | Single column stacked |

### UC-004: Accessibility

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| 001 | Section has proper heading hierarchy | Render component | H3 headings for column titles |
| 002 | Cards are accessible | Render component | Cards readable by screen reader |
| 003 | No accessibility violations | jest-axe scan | No WCAG violations |
| 004 | Keyboard navigable | Tab through component | All interactive elements focusable |
| 005 | Proper contrast ratios | Color contrast check | WCAG AA compliance |

### UC-005: Data Handling

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| 001 | Handles multiple use cases per tool | 5 use cases each | All 10 cards rendered |
| 002 | Handles long title | Title 200 chars | Text wraps, no overflow |
| 003 | Handles long description | Description 500 chars | Text wraps, no overflow |
| 004 | Handles special characters | Unicode in text | Renders correctly |
| 005 | Handles duplicate use case titles | Same title twice | Both cards render with unique keys |

### UC-006: Mobile Responsiveness

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| 001 | Mobile viewport < 768px | Small viewport | Single column layout |
| 002 | Tablet viewport 768px+ | Medium viewport | Two column grid |
| 003 | Desktop viewport 1024px+ | Large viewport | Two column grid |
| 004 | Touch targets on mobile | Mobile viewport | Cards have adequate touch targets |

## Mock Data

### Valid UseCase Objects

```typescript
const seoindicatorUseCases: UseCase[] = [
  {
    title: 'Budget-conscious solopreneurs',
    description: 'Perfect for individual content creators who need core SEO tools without the enterprise price tag',
    audience: 'solo',
    budget: 'budget',
  },
  {
    title: 'Small marketing teams',
    description: 'Ideal for small teams starting their SEO journey with limited budget',
    audience: 'small-team',
    budget: 'budget',
  },
  {
    title: 'Freelance SEO consultants',
    description: 'Great for freelancers who need reliable rank tracking on a budget',
    audience: 'solo',
    budget: 'free',
  },
];

const competitorUseCases: UseCase[] = [
  {
    title: 'Agencies managing 50+ client sites',
    description: 'Best for agencies with budget for premium tools and dedicated SEO resources',
    audience: 'agency',
    budget: 'premium',
  },
  {
    title: 'Enterprise SEO teams',
    description: 'Ideal for large organizations with dedicated resources and complex needs',
    audience: 'enterprise',
    budget: 'premium',
  },
  {
    title: 'Link-building specialists',
    description: 'Perfect for specialists who need extensive backlink databases',
    audience: 'small-team',
    budget: 'mid-range',
  },
];
```

### Edge Case Data

```typescript
const longTitleUseCase: UseCase = {
  title: 'A'.repeat(200),
  description: 'Long title test',
  audience: 'solo',
  budget: 'free',
};

const unicodeUseCase: UseCase = {
  title: '适合中文用户 🚀 émojis',
  description: 'Description with émojis 🎉 and unicode: 日本語',
  audience: 'enterprise',
  budget: 'premium',
};
```

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Component Rendering | 100% | 7 |
| UseCase Card Rendering | 100% | 9 |
| Visual Separation | 100% | 5 |
| Accessibility | 100% | 5 |
| Data Handling | 100% | 5 |
| Mobile Responsiveness | 100% | 4 |
| **Total** | **100%** | **35** |

## Acceptance Criteria Mapping

| AC | Test IDs |
|----|----------|
| Both tools' use cases displayed side-by-side | UC-001-001, UC-001-004 |
| Each use case has title, description, audience, and budget tags | UC-002-001 |
| Tags use appropriate badge styling | UC-002-002 to UC-002-009 |
| Mobile responsive layout | UC-006-001 to UC-006-004 |
| Clear visual separation between tools | UC-003-001 to UC-003-005 |
| Honest, balanced recommendations | UC-003-001, UC-003-002 |

## Test Execution

```bash
# Run unit tests
cd frontend
npm test -- __tests__/components/compare/UseCaseSection.test.tsx

# Run accessibility tests
npm test -- __tests__/a11y/UseCaseSection.a11y.test.tsx

# Run with coverage
npm test -- --coverage __tests__/components/compare/UseCaseSection.test.tsx
```

## Dependencies
- TASK-32-1 (Types) - Done ✅

## Notes for Developer
1. Component already exists at `frontend/components/compare/UseCaseSection.tsx`
2. Uses Tailwind CSS for styling
3. Uses existing Card pattern from other comparison components
4. Ensure balanced presentation between both tools
5. Follow existing section styling patterns from other compare components

---

## Comments
### [2026-03-06 20:45 GMT+2] Agent: PreTester
Test documentation created with 35 test scenarios covering all acceptance criteria.
Created unit test file at `frontend/__tests__/components/compare/UseCaseSection.test.tsx`.
Created accessibility test file at `frontend/__tests__/a11y/UseCaseSection.a11y.test.tsx`.
Status updated to `Tests Coverage In Progress`.
