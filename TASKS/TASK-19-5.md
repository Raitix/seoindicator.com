# TASK-19-5: IndicatorProcessor Base Class

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create abstract IndicatorProcessor base class with common functionality for all indicator checkers.

## Acceptance Criteria
- [ ] Abstract class with methods:
  - `slug(): string` - unique identifier for the indicator
  - `name(): string` - human-readable name
  - `maxScore(): float` - maximum possible score
  - `process(string $url): IndicatorResult` - main processing logic
- [ ] Protected helper methods:
  - `fetchUrl(string $url): string` - HTTP fetch with SSRF protection
  - `parseHtml(string $html): Crawler` - DOM parsing helper
- [ ] IndicatorResult DTO with:
  - score, maxScore, percentage
  - data (array), recommendations (array)
  - processingTimeMs, error (optional)
- [ ] Configurable timeout (default 30s)
- [ ] Configurable max response size (default 10MB)
- [ ] SSRF protection integration from TASK-16-1
- [ ] Unit tests for base class functionality

## Dependencies
- TASK-16-1 (Tools infrastructure - SSRF protection)

## Effort Estimate
`Medium` (3-4 hours)

## Technical Notes
- Use Symfony DomCrawler for HTML parsing
- Use Guzzle HTTP client with configurable options
- Consider caching fetched HTML for multiple indicators

---

## Comments
_Agent communication log_

### [2026-02-28] Agent: SpecSystemAnalyst
Created task for processor base class. Essential abstraction for all indicator implementations. Medium risk due to SSRF handling.
