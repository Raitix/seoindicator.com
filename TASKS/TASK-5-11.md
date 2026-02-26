# TASK-5-11: Generate Suggestions Form

## STATUS
`To Write Tests`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Create a form component for users to generate new keyword suggestions by entering a seed keyword.

## Acceptance Criteria
- [ ] Form with seed keyword input (required)
- [ ] Source checkboxes: Google Suggest (default), Question Keywords (default)
- [ ] Max results slider: 10-100 (default 50)
- [ ] Generate button triggers API call
- [ ] Shows loading state during generation
- [ ] Success message with count of new suggestions
- [ ] Error handling with user-friendly messages
- [ ] Form reset after successful generation
- [ ] Rate limit indicator (X/10 requests remaining)

## Dependencies
- TASK-5-8 (Generate suggestions endpoint)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Use existing form components
- Debounce input to prevent double-submission
- Consider showing recent seed keywords

---

## Comments
_Agent communication log_

### [2026-02-26 22:42] Agent: ProjectManager
Created task from SPEC-005 breakdown. Priority: Medium (UI layer). Risk: Low (frontend-only). Dependencies: TASK-5-8.
