# TEST-32-9: ComparisonCTA Component Tests

## Task Reference
- **Task:** TASK-32-9
- **Spec:** SPEC-32 (Competitor Comparison Landing Pages)
- **Status:** Tests Coverage In Progress

## Overview
Comprehensive unit tests for the ComparisonCTA component - a call-to-action component for comparison pages with support for multiple variants, A/B testing, and sticky mobile behavior.

## Test File
`frontend/__tests__/components/compare/ComparisonCTA.test.tsx`

## Test Categories

### 1. Primary Variant Tests (CTA-PRI-*)

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| CTA-PRI-001 | Renders primary variant container | `<section>` with correct classes |
| CTA-PRI-002 | Displays heading "Ready to Try SEOIndicator?" | h3 element with heading text |
| CTA-PRI-003 | Displays description text | Paragraph with "Start with our free tier - no credit card required" |
| CTA-PRI-004 | Renders TWO buttons in container | Both "Try SEOIndicator Free" and "View Features" buttons |
| CTA-PRI-005 | Primary button links to href prop | Links to custom href or /register |
| CTA-PRI-006 | Secondary button links to /features | "View Features" button links to /features |
| CTA-PRI-007 | Primary button has primary styling | bg-brand-primary classes |
| CTA-PRI-008 | Secondary button has outline styling | border, variant="outline" classes |
| CTA-PRI-009 | Full-width section layout | Container spans full width |

### 2. Secondary Variant Tests (CTA-SEC-*)

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| CTA-SEC-001 | Renders secondary variant | Inline anchor element |
| CTA-SEC-002 | Displays custom text | Text prop rendered |
| CTA-SEC-003 | Links to href prop | Links to custom href |
| CTA-SEC-004 | Has outline/border styling | border, text-slate classes |
| CTA-SEC-005 | Compact inline layout | No section wrapper |
| CTA-SEC-006 | Displays arrow icon | Arrow icon visible |

### 3. Sticky Variant Tests (CTA-STK-*)

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| CTA-STK-001 | Initially hidden (scrollY < 500) | Returns null, not in DOM |
| CTA-STK-002 | Appears after 500px scroll | Visible when scrollY > 500 |
| CTA-STK-003 | Fixed at bottom of viewport | position: fixed, bottom: 0 |
| CTA-STK-004 | Only visible on mobile | md:hidden class |
| CTA-STK-005 | Full-width button | block w-full classes |
| CTA-STK-006 | Has border-top | border-t class |
| CTA-STK-007 | Hides again when scrollY < 500 | Returns null when scrolling back up |
| CTA-STK-008 | Uses intersection observer pattern | Proper cleanup on unmount |
| CTA-STK-009 | Cleanup removes scroll listener | No memory leaks |

### 4. Text & Link Tests (CTA-TXT-*)

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| CTA-TXT-001 | Custom text displayed | Text prop rendered in button |
| CTA-TXT-002 | Default text "Try SEOIndicator Free" | Used when no text prop |
| CTA-TXT-003 | Default href "/register" | Used when no href prop |
| CTA-TXT-004 | Custom href used | Links to provided href |
| CTA-TXT-005 | Query params in href preserved | ?ref=compare works |
| CTA-TXT-006 | Hash links work | #pricing works |

### 5. A/B Test Tracking Tests (CTA-TRK-*)

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| CTA-TRK-001 | Click tracked in analytics | trackEvent called on click |
| CTA-TRK-002 | Event name is "cta_click" | Correct event name |
| CTA-TRK-003 | Variant included in event | variant: "primary"|"secondary"|"sticky" |
| CTA-TRK-004 | Custom text included | text prop in payload |
| CTA-TRK-005 | A/B test group included | abTestGroup prop in payload |
| CTA-TRK-006 | Page URL included | pathname in payload |
| CTA-TRK-007 | Multiple clicks tracked | Each click fires event |
| CTA-TRK-008 | Primary button click tracked | Analytics fires for primary button |
| CTA-TRK-009 | Secondary button NOT tracked | "View Features" doesn't fire CTA event |

### 6. Accessibility Tests (CTA-ACC-*)

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| CTA-ACC-001 | Links have valid href | Not empty or javascript: |
| CTA-ACC-002 | Links are focusable | Tab focuses on links |
| CTA-ACC-003 | Descriptive link text | Text describes action |
| CTA-ACC-004 | Keyboard navigation works | Enter activates links |
| CTA-ACC-005 | Screen reader readable | No aria-hidden on text |
| CTA-ACC-006 | Icons are decorative | aria-hidden on icons |
| CTA-ACC-007 | Heading hierarchy correct | h3 for section heading |

### 7. Responsive Design Tests (CTA-RES-*)

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| CTA-RES-001 | Primary: stacked on mobile | Buttons stack vertically |
| CTA-RES-002 | Primary: side-by-side on desktop | Buttons in row |
| CTA-RES-003 | Sticky: hidden on desktop | md:hidden class |
| CTA-RES-004 | Sticky: visible on mobile | Shows on small screens |
| CTA-RES-005 | Touch targets 44x44 min | Large enough for mobile |

### 8. Edge Cases (CTA-EDG-*)

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| CTA-EDG-001 | Long custom text | Text wraps, no overflow |
| CTA-EDG-002 | Empty text prop | Falls back to default |
| CTA-EDG-003 | Empty href prop | Falls back to /register |
| CTA-EDG-004 | Special chars in href | Escaped properly |
| CTA-EDG-005 | Undefined abTestGroup | Not included in payload |
| CTA-EDG-006 | Unicode in text | Renders correctly |
| CTA-EDG-007 | No props at all | Uses all defaults |

## Acceptance Criteria Coverage

| AC | Description | Test IDs |
|----|-------------|----------|
| AC-1 | Primary variant renders with both buttons | CTA-PRI-001 to CTA-PRI-009 |
| AC-2 | Secondary variant renders as inline CTA | CTA-SEC-001 to CTA-SEC-006 |
| AC-3 | Sticky variant appears on mobile after scroll | CTA-STK-001 to CTA-STK-009 |
| AC-4 | A/B test group prop tracked in analytics | CTA-TRK-001 to CTA-TRK-009 |
| AC-5 | Custom text and href supported | CTA-TXT-001 to CTA-TXT-006 |
| AC-6 | Responsive design (different layouts) | CTA-RES-001 to CTA-RES-005 |
| AC-7 | Accessible (proper button/link roles) | CTA-ACC-001 to CTA-ACC-007 |

## Running Tests

```bash
# Run ComparisonCTA tests
npm test -- ComparisonCTA.test.tsx

# Run with coverage
npm run test:coverage -- --testPathPattern="ComparisonCTA"

# Run in watch mode
npm test -- ComparisonCTA.test.tsx --watch
```

## Test Implementation Notes

### Intersection Observer Mock
For sticky variant tests, mock IntersectionObserver:

```typescript
const mockIntersectionObserver = jest.fn();
mockIntersectionObserver.mockReturnValue({
  observe: () => null,
  unobserve: () => null,
  disconnect: () => null,
});
window.IntersectionObserver = mockIntersectionObserver;
```

### Scroll Event Simulation
```typescript
// Simulate scroll
fireEvent.scroll(window, { target: { scrollY: 600 } });
```

### Analytics Mock
```typescript
jest.mock('@/lib/analytics', () => ({
  trackEvent: jest.fn(),
}));
```

## Dependencies

| Dependency | Status |
|------------|--------|
| TASK-32-1 (Types) | Done ✅ |
| ComparisonCTAProps interface | Available |
| Button component | Available (shadcn/ui) |

## Comments

### [2026-03-05 18:15 GMT+2] Agent: PreTester
**Test Design Started:**
- Created TEST-32-9.md documentation
- Enhanced existing tests from TASK-32-19 to match actual requirements
- Key differences from TASK-32-19 tests:
  1. Primary variant now tests TWO buttons (main CTA + View Features)
  2. Sticky variant tests scroll behavior (500px threshold)
  3. Added responsive design tests
  4. Added intersection observer mock pattern

---

Last updated: 2026-03-05
