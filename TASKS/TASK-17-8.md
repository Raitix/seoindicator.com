# TASK-17-8: Step 3 - Ranking Preview Component

## STATUS
`To Write Tests`

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

*Created by SpecSystemAnalyst on 2026-02-28*
