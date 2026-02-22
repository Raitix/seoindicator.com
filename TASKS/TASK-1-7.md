# SPEC-001-7: SERP Fetcher Interface and Mock Implementation

## STATUS
`In Deployment`
## PRIORITY
`High`

## RISK
`Low`

## Description
Create the SERP fetcher interface and a mock implementation for development/testing. This allows development to proceed without external API dependencies.

## Acceptance Criteria
- [ ] SERPFetcher interface defined with fetch() method
- [ ] SERPResult interface: keyword, searchEngine, location, device, results[], checkedAt, error
- [ ] SERPEntry interface: position, url, title, description, isOurSite
- [ ] SERPOptions interface: searchEngine, location, device, targetUrl
- [ ] MockSERPFetcher implementation returns simulated results
- [ ] Mock results include randomized positions within realistic ranges
- [ ] Mock fetcher simulates network delay (100-500ms)
- [ ] Mock fetcher can simulate errors for testing
- [ ] Unit tests for mock fetcher

## Dependencies
- None

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Use dependency injection pattern for fetcher selection
- Mock should be deterministic for tests (seed random)
- Return 100 results to match "top 100" requirement
- Include isOurSite flag for target URL matching

---

## Comments
_Agent communication log_

### [2026-02-23 00:43] Agent: DevOps
Set status to In Deployment. Preparing deployment via GitHub Actions (push to main) and post-deploy health checks.

### [2026-02-22 23:55] Agent: LocalTester
Ran `php artisan test --filter=MockSERPFetcherTest`.
Result: PASS (5 tests, 18 assertions).
No discrepancies found.
Status -> To Deploy.

### [2026-02-22 23:45] Agent: Developer
Fixed MockSERPFetcher to respect includeTargetUrl for isOurSite and made URLs seed-dependent (randomized domain token). Re-ran: `php artisan test --filter=MockSERPFetcherTest` ✅
Status -> To Test Locally.

### [2026-02-22 21:09] Agent: LocalTester
Set status to Testing Locally and ran: `php artisan test --filter=MockSERPFetcherTest`.
Failures:
- `target url not in results when disabled` (expected 0 matches, got 1)
- `deterministic results with seed` (different seed produced identical URL at index 10)

Please adjust MockSERPFetcher logic so disabling targetUrl doesn't mark isOurSite, and seed affects URL generation deterministically.
Status -> To Develop.

### [2026-02-22 20:46] Agent: Developer
Implemented SERPFetcherInterface + SERP interfaces/data classes, MockSERPFetcher (deterministic seed, delay, error simulation, target URL injection), tests for interfaces and mock behavior, and DI binding in AppServiceProvider. Status -> To Test Locally.

### [2026-02-20 09:44] Agent: SpecSystemAnalyst
Created task for SERP fetcher interface. Mock implementation enables development without external dependencies. CRITICAL: No paid APIs allowed - use mock for MVP.
