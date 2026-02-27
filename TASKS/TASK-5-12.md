# TASK-5-12: Suggestion Card Component

## STATUS
`Tests Coverage In Progress`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Create a reusable card component for displaying individual keyword suggestions with actions.

## Acceptance Criteria
- [ ] Card displays: keyword, source badge, opportunity score
- [ ] Opportunity score shown as progress bar (0-100)
- [ ] Source badge colors: suggest (blue), question (purple)
- [ ] Actions: Track button, Dismiss button, More info expand
- [ ] Expanded view shows: discovered date, tracking status
- [ ] Tracked status indicator (checkmark)
- [ ] Hover state with visual feedback
- [ ] Keyboard accessible (Enter to track)
- [ ] Loading states for action buttons

## Dependencies
- None (standalone component, but needs API context for actions)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Use existing card/button components
- Props: suggestion, onTrack, onDismiss
- Consider memoization for performance in list

---

## Comments
_Agent communication log_

### [2026-02-27 17:52] Agent: PreTester
**Test Design Verified**

Test file already exists with comprehensive coverage:
- **Test File**: `frontend/__tests__/components/suggestions/SuggestionCard.test.tsx`
- **Test Count**: 45+ test scenarios

**Test Coverage:**
- Basic rendering (4 tests): keyword, source badge, score, progress bar
- Source badge colors (3 tests): blue for suggest, purple for question, correct text
- Tracking status (3 tests): checkmark display, no checkmark for untracked, no button for tracked
- Dismissed status (2 tests): indicator display, no action buttons
- Action buttons (4 tests): Track button, Dismiss button, onTrack call, onDismiss call
- Loading states (2 tests): loading text, disabled buttons
- Expanded view (4 tests): more button, expanded content, toggle text, aria-expanded
- Keyboard accessibility (3 tests): focusable, Enter to track, no track when already tracking
- Edge cases (4 tests): null score, zero score, max score, long keyword
- Adversarial (3 tests): XSS handling, special characters, error handling

**TDD Status:** Tests mock the component - waiting for Developer implementation

Status: Tests Coverage In Progress → To Develop (waiting for frontend implementation)

### [2026-02-26 22:42] Agent: ProjectManager
Created task from SPEC-005 breakdown. Priority: Medium (UI component). Risk: Low (presentational component). No hard dependencies - can be developed in parallel with other frontend tasks.
