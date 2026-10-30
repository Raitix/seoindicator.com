# TEST-32-7: ProsConsCard Component Tests

## Task Reference
- **Task:** TASK-32-7 - ProsConsCard Component
- **Spec:** SPEC-32 (Competitor Comparison Landing Pages)
- **Feature:** FEAT-032 (Competitor Comparison Landing Pages - Programmatic SEO)

## Overview
Comprehensive test suite for the ProsConsCard React component that displays balanced, honest assessment of each tool's strengths and weaknesses in comparison landing pages.

## Test Files

| File | Tests | Purpose |
|------|-------|---------|
| `frontend/__tests__/components/compare/ProsConsCard.test.tsx` | 62 | Component unit tests |

## Test Scenarios

### PC-RENDER: Basic Rendering (4 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PC-RENDER-01 | Renders without crashing | Default props | Card element in document |
| PC-RENDER-02 | Displays tool name in header | tool='Ahrefs' | h3 heading with 'Ahrefs' |
| PC-RENDER-03 | Renders pros and cons sections | Default props | Both 'Pros' and 'Cons' text visible |
| PC-RENDER-04 | Renders card with test id | Default props | data-testid='card' present |

### PC-PROS: Pros Section (7 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PC-PROS-01 | Displays all pros | pros=['A', 'B', 'C'] | All three pros visible |
| PC-PROS-02 | Displays check icons | pros=['Pro 1', 'Pro 2'] | 2 check icons rendered |
| PC-PROS-03 | Check icons have green color | pros=['A pro'] | Icon class contains green/emerald |
| PC-PROS-04 | Renders with semantic structure | Default props | h4 heading + ul list |
| PC-PROS-05 | Renders pros as list items | pros=['Pro A', 'Pro B'] | li elements for each pro |
| PC-PROS-06 | Handles empty pros array | pros=[] | Pros section heading still visible |
| PC-PROS-07 | Correct accessibility attributes | Default props | aria-label='pros-section' |

### PC-CONS: Cons Section (7 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PC-CONS-01 | Displays all cons | cons=['A', 'B', 'C'] | All three cons visible |
| PC-CONS-02 | Displays X icons | cons=['Con 1', 'Con 2'] | 2 X icons rendered |
| PC-CONS-03 | X icons have subtle color | cons=['A con'] | Icon class contains gray/slate/neutral |
| PC-CONS-04 | Renders with semantic structure | Default props | h4 heading + ul list |
| PC-CONS-05 | Renders cons as list items | cons=['Con A', 'Con B'] | li elements for each con |
| PC-CONS-06 | Handles empty cons array | cons=[] | Cons section heading still visible |
| PC-CONS-07 | Correct accessibility attributes | Default props | aria-label='cons-section' |

### PC-RATING: Overall Rating Display (9 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PC-RATING-01 | Displays rating when provided | overallRating=4 | Rating element visible |
| PC-RATING-02 | Hides rating when undefined | overallRating=undefined | No rating element |
| PC-RATING-03 | Correct filled stars count | overallRating=4 | 4 filled stars |
| PC-RATING-04 | Total 5 stars rendered | overallRating=3 | 5 star icons total |
| PC-RATING-05 | Handles rating of 0 | overallRating=0 | 0 filled stars |
| PC-RATING-06 | Handles rating of 5 | overallRating=5 | 5 filled stars |
| PC-RATING-07 | Displays accessible text | overallRating=4.5 | '4.5 out of 5' text |
| PC-RATING-08 | Handles decimal ratings | overallRating=4.5 | Rating text shows decimal |
| PC-RATING-09 | Rating in card header | overallRating=4 | Rating in header section |

### PC-ACCESS: Accessibility (7 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PC-ACCESS-01 | Proper heading hierarchy | Default props | h3 for tool, h4 for sections |
| PC-ACCESS-02 | aria-label on card | tool='SEMrush' | aria-label='Pros and cons for SEMrush' |
| PC-ACCESS-03 | Lists have aria-labels | Default props | pros/cons lists labeled |
| PC-ACCESS-04 | Keyboard navigable | Default props | Tab navigation works |
| PC-ACCESS-05 | Role attribute on card | Default props | role='region' |
| PC-ACCESS-06 | Icons hidden from SR | Default props | aria-hidden='true' on icons |
| PC-ACCESS-07 | Rating aria attributes | overallRating=4.5 | role='img' on rating |

### PC-RESPONSIVE: Responsive Design (3 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PC-RESPONSIVE-01 | Responsive grid classes | Default props | grid/flex classes present |
| PC-RESPONSIVE-02 | Equal width on desktop | Default props | grid-cols-2 class |
| PC-RESPONSIVE-03 | Stacks vertically on mobile | Default props | grid-cols-1 or flex-col |

### PC-PROPS: Props Validation (6 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PC-PROPS-01 | Accepts custom className | className='custom-class' | Class applied to card |
| PC-PROPS-02 | Merges custom with default | className='my-custom' | Both classes present |
| PC-PROPS-03 | Handles undefined pros | pros=undefined | Card still renders |
| PC-PROPS-04 | Handles undefined cons | cons=undefined | Card still renders |
| PC-PROPS-05 | Clamps negative rating | overallRating=-1 | Shows 0 rating |
| PC-PROPS-06 | Clamps rating > 5 | overallRating=10 | Shows 5 rating |

### PC-EDGE: Edge Cases (10 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PC-EDGE-01 | Very long pro text | pros=['A'.repeat(500)] | Text displayed |
| PC-EDGE-02 | Very long con text | cons=['B'.repeat(500)] | Text displayed |
| PC-EDGE-03 | Many pros (15 items) | pros=[1..15] | All 15 pros visible |
| PC-EDGE-04 | Many cons (15 items) | cons=[1..15] | All 15 cons visible |
| PC-EDGE-05 | Special chars in tool name | tool='A & B <Test>' | Rendered escaped |
| PC-EDGE-06 | Special chars in pros | pros=['<script>alert(1)</script>'] | Rendered as text |
| PC-EDGE-07 | Unicode in tool name | tool='工具名称 🛠️' | Unicode displayed |
| PC-EDGE-08 | Unicode in pros/cons | pros=['😀 Great', '中文'] | Unicode displayed |
| PC-EDGE-09 | Empty tool name | tool='' | Card still renders |
| PC-EDGE-10 | Null overallRating | overallRating=null | No rating displayed |

### PC-SEC: Security (5 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PC-SEC-01 | Prevents XSS via tool name | tool='<img onerror=alert(1)>' | No img rendered |
| PC-SEC-02 | Prevents XSS via pros | pros=['<script>...</script>'] | No script executed |
| PC-SEC-03 | Prevents XSS via cons | cons=['<a href="javascript:...">'] | No JS links |
| PC-SEC-04 | Sanitizes HTML entities | tool='A & B < C' | Entities escaped |
| PC-SEC-05 | No event handlers executed | pros=['<div onclick="...">'] | No onclick handlers |

### AC: Acceptance Criteria (7 tests)

| ID | Criteria | Status |
|----|----------|--------|
| AC-01 | Tool name displayed in header | ✅ Tested |
| AC-02 | Pros listed with green check icons | ✅ Tested |
| AC-03 | Cons listed with X icons (subtle color) | ✅ Tested |
| AC-04 | Overall rating displayed when provided | ✅ Tested |
| AC-05 | Mobile responsive (stacked or scrollable) | ✅ Tested |
| AC-06 | Balanced presentation (no bias in layout) | ✅ Tested |
| AC-07 | Accessible (proper heading structure) | ✅ Tested |

## Mock Data

### Default Props
```typescript
const defaultProps: ProsConsCardProps = {
  tool: 'SEOIndicator',
  pros: ['Easy to use', 'Affordable pricing', 'Great support'],
  cons: ['Limited integrations', 'No mobile app'],
};
```

### With Rating
```typescript
const withRatingProps: ProsConsCardProps = {
  tool: 'Ahrefs',
  pros: ['Comprehensive backlink data', 'Powerful site explorer'],
  cons: ['Expensive pricing', 'Steep learning curve'],
  overallRating: 4.5,
};
```

### Edge Case Props
```typescript
const edgeCaseProps: ProsConsCardProps = {
  tool: '工具名称 🛠️',
  pros: Array.from({ length: 15 }, (_, i) => `Pro ${i + 1}`),
  cons: Array.from({ length: 15 }, (_, i) => `Con ${i + 1}`),
  overallRating: 4.5,
};
```

## Test Patterns

### Mocking Lucide Icons
```typescript
jest.mock('lucide-react', () => ({
  Check: ({ className }) => <span data-testid="check-icon" className={className}>✓</span>,
  X: ({ className }) => <span data-testid="x-icon" className={className}>✗</span>,
  Star: ({ className, filled }) => (
    <span data-testid="star-icon" className={className} data-filled={filled}>
      {filled ? '★' : '☆'}
    </span>
  ),
}));
```

### Mocking shadcn/ui Components
```typescript
jest.mock('@/components/ui/card', () => ({
  Card: ({ children, className }) => (
    <div data-testid="card" className={className}>{children}</div>
  ),
  CardHeader: ({ children, className }) => (
    <div data-testid="card-header" className={className}>{children}</div>
  ),
  CardContent: ({ children, className }) => (
    <div data-testid="card-content" className={className}>{children}</div>
  ),
}));
```

### Testing Color Classes
```typescript
// Pros should have green/emerald color
const checkIcon = screen.getByTestId('check-icon');
expect(checkIcon.className).toMatch(/green|emerald/);

// Cons should have subtle gray color
const xIcon = screen.getByTestId('x-icon');
expect(xIcon.className).toMatch(/gray|slate|neutral|muted/);
```

### Testing Rating Stars
```typescript
// Count filled stars
const filledStars = screen.getAllByTestId('star-icon').filter(
  star => star.getAttribute('data-filled') === 'true'
);
expect(filledStars).toHaveLength(expectedRating);
```

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Basic Rendering | 100% | 4 |
| Pros Section | 100% | 7 |
| Cons Section | 100% | 7 |
| Rating Display | 100% | 9 |
| Accessibility | 100% | 7 |
| Responsive | 100% | 3 |
| Props Validation | 100% | 6 |
| Edge Cases | 100% | 10 |
| Security | 100% | 5 |
| Acceptance Criteria | 100% | 7 |
| **Total** | **100%** | **62** |

## Test Execution

```bash
# Run all ProsConsCard tests
npm test -- ProsConsCard.test.tsx

# Run with coverage
npm run test:coverage -- ProsConsCard.test.tsx

# Run specific test category
npm test -- ProsConsCard.test.tsx -t "PC-RATING"
```

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-32-1 (Types) | Done ✅ | ProsConsCardProps interface defined |
| shadcn/ui Card | Available | Card, CardHeader, CardContent |
| Lucide React | Available | Check, X, Star icons |
| Jest | Configured | Test runner |
| React Testing Library | Configured | Component testing |

## TDD Reference Implementation

The test file includes a commented reference implementation showing the expected component interface. The actual implementation should be created in:

```
frontend/components/compare/ProsConsCard.tsx
```

---

## Comments

### [2026-03-06 09:48 UTC] Agent: PreTester
**Test suite created for ProsConsCard component.**

**Test Summary:**
- 62 test scenarios across 10 categories
- All 7 acceptance criteria covered
- TDD reference implementation included
- Mock patterns for Lucide icons and shadcn/ui components

**Key Test Patterns:**
- Mock Lucide icons with data-testid for reliable selection
- Test color classes with regex matching (green|emerald, gray|slate)
- Test rating stars with data-filled attribute
- Test accessibility with aria-label and role attributes

**Dependencies Verified:**
- TASK-32-1 (Types) - Done ✅
- ProsConsCardProps interface available

**Handoff to Developer:**
- Ready for implementation
- Component should follow reference implementation structure
- Ensure proper Tailwind classes for colors and responsive layout
