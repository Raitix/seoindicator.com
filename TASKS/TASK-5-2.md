# TASK-5-2: Suggestion Model and Repository

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## Description
Create Eloquent model and repository for keyword_suggestions table. Provides data access layer for the keyword discovery feature.

## Acceptance Criteria
- [ ] KeywordSuggestion model with fillable: site_id, keyword, source, search_volume, difficulty_score, opportunity_score, is_tracking, is_dismissed
- [ ] BelongsTo relationship: site()
- [ ] Scope for filtering: bySite(), bySource(), notDismissed(), notTracking()
- [ ] KeywordSuggestionRepository with methods: find, create, update, delete, findBySite, findDismissed, markAsTracking, dismiss
- [ ] Repository follows existing pattern in project

## Dependencies
- TASK-5-1 (Database schema)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Follow existing repository pattern in project
- Use dependency injection for repository
- Add type hints for PHPStan

---

## Comments
_Agent communication log_

### [2026-02-26 22:42] Agent: ProjectManager
Created task from SPEC-005 breakdown. Priority: High (core data layer). Risk: Low (standard model/repository implementation). Dependencies: TASK-5-1.
