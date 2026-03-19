# TASK-17-8: Step 3 - Ranking Preview Component

## STATUS
`To Develop`

## PRIORITY
`Medium`

## RISK
`Low`

## Specification
- **SPEC-017**: User Onboarding & Activation Flow

## Dependencies
- TASK-17-3 (Frontend - OnboardingProvider, Modal, and Wizard Shell)
- TASK-17-5 (Step 2 - First Keyword Input Component)
- MockSERPFetcher (existing from TASK-1-7)

## Description
Create the third step of the onboarding wizard: Ranking preview. This component shows the user an immediate preview of their keyword ranking using the MockSERPFetcher, providing instant value and demonstrating the tool's core functionality.

## Acceptance Criteria
- [ ] Loading state displayed while fetching rank
- [ ] Ranking display shows:
  - [ ] Position number (e.g., "Position 15")
  - [ ] Or "Not in top 100" if not ranking
  - [ ] Brief explanation of what the position means
- [ ] Mock data used for MVP (MockSERPFetcher)
- [ ] "Add more keywords" button (goes to keywords page post-MVP)
- [ ] "Continue" button advances to step 4
- [ ] "Skip for now" link available
- [ ] Error handling for failed rank check:
  - [ ] Graceful error message
  - [ ] Option to retry or continue
- [ ] Visual indicator that this is a preview (e.g., "Preview based on sample data")
- [ ] Post-MVP: Integration with real SERP fetcher

## Effort Estimate
`S` (4-6 hours)

## Technical Details

### Component Props
```typescript
interface Step3PreviewProps {
  keyword: string;
  websiteUrl: string;
  onComplete: () => void;
  onSkip: () => void;
}
```

### Mock Ranking Display
```typescript
// Use MockSERPFetcher for MVP
const mockResult = await mockSERPFetcher.fetch(keyword, {
  targetUrl: websiteUrl
});
// Display position from result
```

### UI States
1. **Loading**: Spinner with "Checking your ranking..."
2. **Success**: Position number with explanation
3. **Not Ranking**: "Not in top 100" with encouragement
4. **Error**: Error message with retry option

## Testing Requirements
- Unit test for loading state
- Unit test for position display
- Unit test for "not ranking" display
- Unit test for error handling
- Unit test for skip functionality
- Integration test with MockSERPFetcher

## Notes
MVP uses MockSERPFetcher. Post-MVP, integrate with real SERP API. The preview provides immediate value to the user, encouraging completion.

---

## Comments
_Agent communication log_

### [2026-03-18 18:40 GMT+2] Agent: PreTester
**Test Design Complete**

Created comprehensive test suite for Step 3 Ranking Preview Component:

**Files Created:**
- `TESTS/TEST-17-8.md` - Test documentation with 60 test scenarios
- `frontend/tests/components/onboarding/Step3RankingPreview.test.tsx` - 45 unit tests

**Test Categories:**
- RP-001: Component Rendering (5 tests)
- RP-002: Loading State (5 tests)
- RP-003: Position Display (8 tests)
- RP-004: Not Ranking Display (4 tests)
- RP-005: Error Handling (7 tests)
- RP-006: Continue Button (5 tests)
- RP-007: Skip Functionality (6 tests)
- RP-008: Add More Keywords Button (5 tests)
- RP-009: Preview Indicator (4 tests)
- RP-010: Accessibility (7 tests)
- RP-011: MockSERPFetcher Integration (6 tests)
- RP-012: Edge Cases (7 tests)

**Coverage Targets:**
- Statements: 90%
- Branches: 85%
- Functions: 95%
- Lines: 90%

**Key Implementation Notes:**
1. Component at `frontend/components/onboarding/Step3RankingPreview.tsx`
2. Uses MockSERPFetcher.fetch(keyword, { targetUrl })
3. State machine: Initial → Loading → Success/NotRanking/Error
4. Must have aria-live regions for accessibility
5. Position styling: green (1-10), yellow (11-20), orange (21-50), gray (51-100)

**Status: To Write Tests → To Develop**

---

*Created by SpecSystemAnalyst on 2026-02-28*

---

## Comments
_Agent communication log_

### [2026-03-18 18:20 GMT+2] Agent: PreTester
**Tests Written**

Created comprehensive test suite for Ranking Preview Component.

**Files Created:**
1. `TESTS/TEST-17-8.md` - Test documentation (37 test scenarios across 10 categories)
2. `frontend/components/onboarding/steps/__tests__/Step3_Preview.test.tsx` - 37 unit tests

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| RP-001: Loading State | 4 | Spinner, aria-busy, disabled buttons |
| RP-002: Position Display | 5 | Position numbers, featured snippet, emphasis |
| RP-003: Not Ranking | 3 | "Not in top 100", encouragement |
| RP-004: Error Handling | 5 | Error messages, retry, continue |
| RP-005: Navigation | 4 | Continue, skip, back buttons |
| RP-006: Preview Indicator | 3 | Sample data indicator |
| RP-007: Add More Keywords | 3 | Navigation to keywords page |
| RP-008: Accessibility | 5 | ARIA attributes, keyboard nav |
| RP-009: MockSERPFetcher | 4 | Integration with fetcher |
| RP-010: Edge Cases | 4 | Empty inputs, high positions |

**Key Implementation Notes:**
1. Component should use MockSERPFetcher for MVP (not real SERP API)
2. Preview indicator should be visible: "Preview based on sample data"
3. Position 0 = Featured Snippet (special handling)
4. Position display should explain what the position means (e.g., "page 2")
5. Error handling should allow users to continue even if preview fails

**Mock SERPFetcher Interface:**
```typescript
mockSERPFetcher.fetch({
  keyword: string,
  targetUrl: string
}): Promise<{ position: number | null, url: string, keyword: string }>
```

**Status: To Write Tests → To Develop**
