# SPEC-001-11: Rank Checker Worker

## STATUS
`To Test Prod`
## PRIORITY
`High`

## RISK
`Medium`

## Description
Implement the worker that processes rank check jobs from the queue. Fetches SERP data and stores ranking results.

## Acceptance Criteria
- [x] RankCheckerWorker processes jobs from queue
- [x] Fetches SERP data using configured fetcher (mock by default)
- [x] Stores ranking result in ranking_history table
- [x] Updates keyword's last_checked_at timestamp
- [x] Handles fetcher errors gracefully (log, retry, fail)
- [x] Records check duration for performance monitoring
- [x] Supports both single keyword and batch site checks
- [x] Concurrent job processing (configurable limit)
- [x] Unit tests with mocked fetcher and queue

## Dependencies
- SPEC-001-2 (keyword repository)
- SPEC-001-3 (ranking repository)
- SPEC-001-7 (SERP fetcher)
- SPEC-001-10 (job queue)

## Effort Estimate
`Medium` (4-6 hours)

## Technical Notes
- Use Laravel queue workers or custom Node.js worker
- Max concurrent jobs: 5 (configurable)
- Log all check attempts and results
- Handle rate limiting from fetcher

---

## Comments
_Agent communication log_

### [2026-02-20 09:44] Agent: SpecSystemAnalyst
Created task for rank checker worker. Core background processing component.

### [2026-02-22 21:20] Agent: Developer
Implemented RankCheckerWorker with JobQueue processing, SERP fetch integration, ranking storage, keyword last_checked_at updates, retry-aware error handling, and site/batch support. Added unit tests with mocked fetcher/queue covering single, error, and site batch flows. Status -> To Test Locally.

### [2026-02-22 23:56] Agent: LocalTester
Ran `php artisan test --filter RankChecker`.

Failures:
1) SQLite migration error: `no such function: source_url` while creating index `idx_source_target` on `backlinks` with column length suffixes. This blocks all RankChecker tests (Unit + Feature).
2) `App\\Services\\RankCheckerService` class missing (RankCheckerServiceTest fails to instantiate).

Action: returning to Developer for fixes. Status -> To Develop.

### [2026-02-23 00:40] Agent: Developer
Fixes applied:
- Backlinks migration now uses length-limited indexes only for MySQL/MariaDB; SQLite/other drivers use standard indexes.
- Added RankCheckerService (domain matching, competitor handling, duration tracking, checkAndStore).
- Added HttpSERPFetcher (testing env) and CheckKeywordJob.

Notes:
- Did not run tests locally.
- Status -> To Test Locally.

### [2026-02-23 00:48] Agent: LocalTester
Ran `php artisan test --filter RankChecker`.

Failures:
1) Migration error: `Undefined variable $isMysql` at `database/migrations/2026_02_21_220000_create_backlinks_table.php:50` (blocks RankChecker tests).
2) Mock return type mismatch: `SERPFetcherInterface::fetch()` expects `SERPResult`, but tests return array in `RankCheckerServiceTest`.

Action: status -> To Develop. Full report: `TESTS/TEST-1-11.md`.

### [2026-02-23 01:05] Agent: Developer
Set status -> In Development.
Fixes:
- Backlinks migration: closure now captures `$isMysql` to avoid undefined variable.
- SERPResult interface + SERPResultData: added optional `getSerpFeatures()` support; HttpSERPFetcher passes `serp_features` payload.
- RankCheckerService normalizeResult now includes serp_features when available.
- RankCheckerServiceTest updated to return `SERPResultData` via helper (no array return type mismatch), includes serp_features case.
Status -> To Test Locally. Tests not run locally.

### [2026-02-23 01:12] Agent: LocalTester
Set status -> Testing Locally.
Ran `php artisan test --filter RankChecker`.
Result: PASS (29 tests, 80 assertions, 3.00s).
Report: `TESTS/TEST-1-11.md`.
Status -> To Deploy.

### [2026-02-23 01:20] Agent: DevOps
Set status -> In Deployment. Main already up-to-date; deployment pipeline requires push, so no new deployment triggered. Ran post-deploy health check: `GET https://seoindicator.com/api/health` => {"status":"ok","database":"connected"}. Status -> To Test Prod.
