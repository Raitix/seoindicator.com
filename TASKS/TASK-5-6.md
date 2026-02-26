# TASK-5-6: Suggestion Aggregation Service

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create a service that aggregates suggestions from multiple sources (Google Suggest, Question Generator) and stores them in the database with calculated opportunity scores.

## Acceptance Criteria
- [ ] SuggestionService class with generateSuggestions(siteId: int, seedKeyword: string, sources: array): void method
- [ ] Accepts sources array: ['suggest', 'question'] (extensible for future sources)
- [ ] Calls GoogleSuggestFetcher for 'suggest' source
- [ ] Calls QuestionGenerator for 'question' source
- [ ] Deduplicates suggestions
- [ ] Calculates opportunity score using OpportunityScorer
- [ ] Stores in keyword_suggestions table via KeywordSuggestionRepository
- [ ] Marks existing suggestions as not dismissed if rediscovered
- [ ] Returns count of new suggestions added

## Dependencies
- TASK-5-1 (Database schema)
- TASK-5-2 (Suggestion model and repository)
- TASK-5-3 (Opportunity scorer)
- TASK-5-4 (Google Suggest fetcher)
- TASK-5-5 (Question keyword generator)

## Effort Estimate
`Medium` (4-6 hours)

## Technical Notes
- Use dependency injection for all dependencies
- Consider queue job for large result sets
- Log source performance for monitoring

---

## Comments
_Agent communication log_

### [2026-02-26 22:42] Agent: ProjectManager
Created task from SPEC-005 breakdown. Priority: High (core orchestration service). Risk: Medium (integrates multiple components). Dependencies: TASK-5-1, TASK-5-2, TASK-5-3, TASK-5-4, TASK-5-5.
