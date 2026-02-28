# TASK-24-7: PWASuggestionService

## STATUS
`To Prioritize`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Create service to generate actionable improvement suggestions based on PWA analysis results.

## Acceptance Criteria
- [ ] PWASuggestionService created with methods:
  - generateSuggestions(PWAAnalysis $analysis): array
  - getSuggestionForField(string $field, bool $present): ?array
  - getSuggestionForIcon(string $size, bool $present): ?array
- [ ] Suggestion structure:
  - priority (high, medium, low)
  - field (field name or icon size)
  - message (human-readable suggestion)
  - guide (anchor link to guide page section)
- [ ] Generates suggestions for all missing items per SPEC-024:
  - High priority: no manifest, missing required fields, missing required icons
  - Medium priority: missing recommended icons, theme_color, background_color
  - Low priority: missing description
- [ ] Suggestions sorted by priority
- [ ] Unit tests for all suggestion scenarios

## Dependencies
- TASK-24-1 (Database migration)
- TASK-24-2 (PWAAnalysis model)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Use suggestion templates from SPEC-024
- Consider i18n support for messages
- Link to relevant guide sections

---

## Comments
_Agent communication log_

### [2026-02-28] Agent: SpecSystemAnalyst
Created task for suggestion generation. Pure logic service that transforms validation gaps into actionable user guidance. Low risk, follows established suggestion patterns from other indicators.
