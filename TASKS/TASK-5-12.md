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

### [2026-02-26 22:42] Agent: ProjectManager
Created task from SPEC-005 breakdown. Priority: Medium (UI component). Risk: Low (presentational component). No hard dependencies - can be developed in parallel with other frontend tasks.
