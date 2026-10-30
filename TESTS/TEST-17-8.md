# TEST-17-8: Step 3 - Ranking Preview Component

## Task Reference
- **Task**: [TASK-17-8](../TASKS/TASK-17-8.md)
- **Specification**: SPEC-017 (User Onboarding & Activation Flow)
- **Feature**: FEAT-017 (User Onboarding)

## Overview

Test suite for the Step 3 Ranking Preview Component - the third step of the onboarding wizard that shows users an immediate preview of their keyword ranking using MockSERPFetcher.

---

## Test Files

| File | Type | Tests | Description |
|------|------|-------|-------------|
| `frontend/tests/components/onboarding/Step3RankingPreview.test.tsx` | Unit | 45 | Component rendering, states, interactions |
| `frontend/tests/integration/onboarding/Step3RankingPreview.integration.test.tsx` | Integration | 15 | MockSERPFetcher integration, full flows |

---

## Test Scenarios

### RP-001: Component Rendering

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RP-001-01 | Component renders with required props | keyword="seo tools", websiteUrl="example.com" | Component renders without crash |
| RP-001-02 | Component displays keyword | keyword="seo tools" | Shows "Checking ranking for: seo tools" |
| RP-001-03 | Component displays website URL | websiteUrl="example.com" | Shows website URL in context |
| RP-001-04 | Component has preview indicator | - | Shows "Preview based on sample data" badge |
| RP-001-05 | Component has correct data-testid | - | data-testid="step3-ranking-preview" |

### RP-002: Loading State

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RP-002-01 | Shows loading spinner initially | - | Spinner element visible |
| RP-002-02 | Shows loading text | - | "Checking your ranking..." text |
| RP-002-03 | Loading has aria-live | - | aria-live="polite" on loading container |
| RP-002-04 | Buttons disabled during loading | - | All buttons have disabled attribute |
| RP-002-05 | Loading state transitions | MockSERPFetcher resolves | Loading disappears, results shown |

### RP-003: Position Display (Success State)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RP-003-01 | Shows position number | position: 15 | "Position 15" displayed |
| RP-003-02 | Shows position explanation | position: 15 | Brief explanation text |
| RP-003-03 | Shows position icon | position: 15 | TrendingUp or similar icon |
| RP-003-04 | Position 1 displays correctly | position: 1 | "Position 1" with special styling |
| RP-003-05 | Position in top 10 styling | position: 5 | Green/success color styling |
| RP-003-06 | Position in top 20 styling | position: 15 | Yellow/warning color styling |
| RP-003-07 | Position in top 50 styling | position: 45 | Orange styling |
| RP-003-08 | Position in top 100 styling | position: 95 | Gray/neutral styling |

### RP-004: Not Ranking Display

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RP-004-01 | Shows "Not in top 100" | position: null or >100 | "Not in top 100" displayed |
| RP-004-02 | Shows encouragement message | position: null | Encouragement text like "Keep optimizing!" |
| RP-004-03 | Shows info icon | position: null | Info icon displayed |
| RP-004-04 | Not ranking has neutral styling | position: null | Neutral/gray color scheme |

### RP-005: Error Handling

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RP-005-01 | Shows error message | MockSERPFetcher throws error | Error message displayed |
| RP-005-02 | Shows retry button on error | MockSERPFetcher throws error | "Retry" button visible |
| RP-005-03 | Shows continue on error | MockSERPFetcher throws error | "Continue anyway" option |
| RP-005-04 | Error has alert role | MockSERPFetcher throws error | role="alert" on error message |
| RP-005-05 | Retry calls fetcher again | Click retry | MockSERPFetcher.fetch called again |
| RP-005-06 | Network error handled | Network error | Graceful error message |
| RP-005-07 | Timeout error handled | Timeout error | Graceful error message |

### RP-006: Continue Button

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RP-006-01 | Continue button renders | - | "Continue" button visible |
| RP-006-02 | Continue button triggers callback | Click continue | onComplete() called |
| RP-006-03 | Continue button has aria-label | - | aria-label="Continue to next step" |
| RP-006-04 | Continue disabled during loading | Loading state | Button has disabled attribute |
| RP-006-05 | Continue enabled after fetch | Fetch complete | Button not disabled |

### RP-007: Skip Functionality

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RP-007-01 | Skip link renders | - | "Skip for now" link visible |
| RP-007-02 | Skip triggers callback | Click skip | onSkip() called |
| RP-007-03 | Skip has button role | - | role="button" on skip link |
| RP-007-04 | Skip has accessible name | - | aria-label or text "Skip ranking preview" |
| RP-007-05 | Skip works during loading | Loading state | Skip still clickable |
| RP-007-06 | Skip works during error | Error state | Skip still clickable |

### RP-008: Add More Keywords Button

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RP-008-01 | Button renders | - | "Add more keywords" button visible |
| RP-008-02 | Button has correct href | - | href="/keywords" (post-MVP) |
| RP-008-03 | Button has icon | - | Plus icon displayed |
| RP-008-04 | Button aria-label | - | aria-label="Add more keywords to track" |
| RP-008-05 | Button disabled during loading | Loading state | disabled attribute |

### RP-009: Preview Indicator

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RP-009-01 | Preview badge renders | - | "Preview" badge visible |
| RP-009-02 | Preview text explains data | - | "based on sample data" text |
| RP-009-03 | Preview has info icon | - | Info icon in badge |
| RP-009-04 | Preview has tooltip | Hover preview | Explains mock data |

### RP-010: Accessibility

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RP-010-01 | Component has accessible name | - | aria-label or aria-labelledby |
| RP-010-02 | Position has live region | - | aria-live="polite" on result |
| RP-010-03 | Focus management on state change | Loading → Success | Focus moves to result |
| RP-010-04 | Keyboard navigation | Tab through | All interactive elements focusable |
| RP-010-05 | Screen reader announces position | position: 15 | "Your site is in position 15" announced |
| RP-010-06 | Screen reader announces not ranking | position: null | "Your site is not in top 100" announced |
| RP-010-07 | Color contrast sufficient | All states | WCAG AA contrast ratio |

### RP-011: Integration with MockSERPFetcher

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RP-011-01 | Calls MockSERPFetcher.fetch | Component mounts | fetch called with keyword and URL |
| RP-011-02 | Passes correct keyword | keyword="seo tools" | fetch called with "seo tools" |
| RP-011-03 | Passes correct targetUrl | websiteUrl="example.com" | fetch options.targetUrl = "example.com" |
| RP-011-04 | Displays position from result | result.position = 25 | "Position 25" shown |
| RP-011-05 | Handles null position | result.position = null | "Not in top 100" shown |
| RP-011-06 | Handles fetch error | fetch rejects | Error state shown |

### RP-012: Edge Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RP-012-01 | Empty keyword | keyword="" | Graceful handling, no crash |
| RP-012-02 | Empty website URL | websiteUrl="" | Graceful handling, no crash |
| RP-012-03 | Very long keyword | keyword with 200+ chars | Text truncated gracefully |
| RP-012-04 | Special characters in keyword | keyword="seo & marketing" | Displayed correctly (escaped) |
| RP-012-05 | Unicode in keyword | keyword="seo ñoño" | Displayed correctly |
| RP-012-06 | Position 0 (featured snippet) | position: 0 | "Position 0 - Featured Snippet!" |
| RP-012-07 | Position > 100 | position: 150 | "Not in top 100" |

---

## Mock Data

### MockSERPFetcher Response (Success)
```typescript
const mockSERPResponse = {
  position: 15,
  url: 'https://example.com',
  title: 'Example Page Title',
  snippet: 'This is a search result snippet...',
};
```

### MockSERPFetcher Response (Not Ranking)
```typescript
const mockSERPNotRankingResponse = {
  position: null,
  url: null,
  title: null,
  snippet: null,
};
```

### MockSERPFetcher Response (Featured Snippet)
```typescript
const mockSERPFeaturedResponse = {
  position: 0,
  url: 'https://example.com',
  title: 'Example Page Title',
  snippet: 'Featured snippet content...',
  isFeatured: true,
};
```

### Component Props
```typescript
const defaultProps = {
  keyword: 'seo tools',
  websiteUrl: 'example.com',
  onComplete: vi.fn(),
  onSkip: vi.fn(),
};
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
npm test -- --testPathPattern="Step3RankingPreview"

# Integration tests
npm test -- --testPathPattern="Step3RankingPreview.integration"

# Coverage
npm test -- --coverage --testPathPattern="Step3RankingPreview"
```

---

## Dependencies

- MockSERPFetcher (from TASK-1-7)
- OnboardingProvider (from TASK-17-3)
- Step 2 component must pass keyword/websiteUrl props

---

## Notes for Developer

1. **Component Location**: `frontend/components/onboarding/Step3RankingPreview.tsx`

2. **Props Interface**:
```typescript
interface Step3RankingPreviewProps {
  keyword: string;
  websiteUrl: string;
  onComplete: () => void;
  onSkip: () => void;
}
```

3. **State Machine**:
```
Initial → Loading → Success (position shown)
                 → NotRanking ("Not in top 100")
                 → Error (retry option)
```

4. **UI Elements Required**:
   - Loading spinner with text
   - Position display (number + explanation)
   - "Not in top 100" display (encouragement)
   - Error message with retry button
   - Continue button
   - Skip link
   - "Add more keywords" button
   - Preview indicator badge

5. **MockSERPFetcher Usage**:
```typescript
const result = await mockSERPFetcher.fetch(keyword, {
  targetUrl: websiteUrl
});
```

6. **Accessibility Requirements**:
   - aria-live regions for dynamic content
   - Focus management on state changes
   - All buttons have accessible names
   - Color contrast WCAG AA

---

## Comments

### [2026-03-18 18:35 GMT+2] Agent: PreTester
- Test documentation created
- 60 test scenarios defined across 12 categories
- Ready for Developer implementation
- Status: To Write Tests → To Develop
