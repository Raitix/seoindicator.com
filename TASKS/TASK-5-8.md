# TASK-5-8: Generate Suggestions Endpoint

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create API endpoint for generating new keyword suggestions. This triggers the suggestion aggregation service.

## Acceptance Criteria
- [ ] POST /api/v1/sites/:siteId/suggestions/generate - Generate new suggestions
- [ ] Request body: { seedKeyword: string, sources: array, maxResults: int }
- [ ] Validates: seedKeyword required, maxResults min 1 max 100
- [ ] Dispatches SuggestionService asynchronously (queue job)
- [ ] Returns 202 with { jobId, status: 'queued' }
- [ ] Authorization: user must own the site
- [ ] Rate limit: max 10 requests per hour per site

## Dependencies
- TASK-5-6 (Suggestion aggregation service)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Use Laravel queue for async processing
- Consider websockets for progress updates (post-MVP)
- Track job status for user feedback

---

## Comments
_Agent communication log_

### [2026-02-26 22:42] Agent: ProjectManager
Created task from SPEC-005 breakdown. Priority: High (MVP feature). Risk: Medium (queue-based async processing). Dependencies: TASK-5-6.
