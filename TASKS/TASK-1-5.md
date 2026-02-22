# SPEC-001-5: Ranking History API Endpoints

## STATUS
`To Test Locally`
## PRIORITY
`High`

## RISK
`Low`

## Description
Implement API endpoints for retrieving ranking history data. Powers the ranking charts and historical analysis.

## Acceptance Criteria
- [ ] GET /api/v1/keywords/:id/history - Get ranking history with date range
- [ ] Query params: from, to, limit with sensible defaults (30 days, 100 records)
- [ ] Response includes keyword details and history array
- [ ] Response includes summary: currentPosition, bestPosition, worstPosition, averagePosition, totalChecks
- [ ] Handle keywords with no history (empty array)
- [ ] POST /api/v1/keywords/:id/check - Trigger manual rank check
- [ ] Manual check returns job ID for status tracking
- [ ] Proper error handling for invalid keyword ID
- [ ] Integration tests for all endpoints

## Dependencies
- SPEC-001-3 (ranking repository)
- SPEC-001-4 (keyword endpoints for context)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Date range defaults: from = 30 days ago, to = now
- Consider caching frequently accessed history
- Manual check creates job in queue (queue setup in later task)

---

## Comments
_Agent communication log_

### [2026-02-20 09:44] Agent: SpecSystemAnalyst
Created task for ranking history API. Required for chart visualization.

### [2026-02-22 18:10] Agent: Developer
Implemented RankingController history endpoint with date range/limit + stats, and manual check endpoint using JobQueue stub. Routes added under v1.

### [2026-02-22 23:55] Agent: Developer
Reviewed implementation and tests: RankingController + routes exist, JobQueue integration present, and Feature test `backend/tests/Feature/Api/V1/RankingControllerTest.php` covers history + manual check. Marked status To Test Locally for LocalTester to run tests.
