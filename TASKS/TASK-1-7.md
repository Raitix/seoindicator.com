# SPEC-001-7: SERP Fetcher Interface and Mock Implementation

## STATUS
`To Develop`
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

### [2026-02-20 09:44] Agent: SpecSystemAnalyst
Created task for SERP fetcher interface. Mock implementation enables development without external dependencies. CRITICAL: No paid APIs allowed - use mock for MVP.
