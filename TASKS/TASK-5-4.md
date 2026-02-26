# TASK-5-4: Google Suggest Fetcher (MVP)

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Implement a fetcher for Google Suggest API to retrieve keyword suggestions. This is the primary FREE source for keyword discovery in MVP.

## Acceptance Criteria
- [ ] SuggestFetcher interface with fetch(keyword: string): array method
- [ ] GoogleSuggestFetcher implementation
- [ ] API endpoint: http://suggestqueries.google.com/complete/search?client=firefox&q={keyword}
- [ ] Parse JSON response and return array of suggestion strings
- [ ] Handle rate limiting and errors gracefully
- [ ] Return empty array on failure (no exceptions)
- [ ] Cache results for 24 hours (optional optimization)

## Dependencies
- None (standalone fetcher)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- FREE API - no API key required
- Respect rate limits to avoid IP blocking
- Consider adding retry logic with exponential backoff
- Mock for testing to avoid external API calls

---

## Comments
_Agent communication log_

### [2026-02-26 22:42] Agent: ProjectManager
Created task from SPEC-005 breakdown. Priority: High (MVP core functionality). Risk: Medium (external API dependency, rate limiting). No hard dependencies - can be developed in parallel with schema.
