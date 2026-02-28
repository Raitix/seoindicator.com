# TASK-19-5: IndicatorProcessor Base Class

## STATUS
`To Test Prod`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create abstract IndicatorProcessor base class with common functionality for all indicator checkers.

## Acceptance Criteria
- [x] Abstract class with methods:
  - `slug(): string` - unique identifier for the indicator
  - `name(): string` - human-readable name
  - `maxScore(): float` - maximum possible score
  - `process(string $url): IndicatorResult` - main processing logic
- [x] Protected helper methods:
  - `fetchUrl(string $url): string` - HTTP fetch with SSRF protection
  - `parseHtml(string $html): DOMDocument` - DOM parsing helper
- [x] IndicatorResult DTO with:
  - score, maxScore, percentage
  - data (array), recommendations (array)
  - processingTimeMs, error (optional)
- [x] Configurable timeout (default 30s)
- [x] Configurable max response size (default 10MB)
- [x] SSRF protection integration from TASK-16-1
- [x] Unit tests for base class functionality

## Dependencies
- TASK-16-1 (Tools infrastructure - SSRF protection)

## Effort Estimate
`Medium` (3-4 hours)

## Technical Notes
- Uses native PHP DOMDocument with Symfony CssSelector for HTML parsing (changed from DomCrawler due to dependency issues)
- Uses Guzzle HTTP client with configurable options
- Consider caching fetched HTML for multiple indicators

---

## Comments
_Agent communication log_

### [2026-02-28] Agent: SpecSystemAnalyst
Created task for processor base class. Essential abstraction for all indicator implementations. Medium risk due to SSRF handling.

### [2026-02-28 14:00 GMT+2] Agent: Developer
**Implementation Complete**

Created abstract `IndicatorProcessor` base class with:
- Abstract methods: `slug()`, `name()`, `maxScore()`, `process()`
- Protected helpers: `fetchUrl()` with SSRF protection, `parseHtml()` using native DOMDocument
- Text/attribute extraction helpers for common operations
- Configurable timeout (default 30s) and max response size (default 10MB)
- Integration with existing `URLValidationService` for SSRF protection

Created `IndicatorResult` DTO for standardized results:
- score, maxScore, percentage
- data (array), recommendations (array)
- processingTimeMs, error (optional)
- Factory methods: `success()`, `failed()`

**Files Created:**
- `app/Services/Analysis/Indicators/IndicatorProcessor.php`
- `app/Services/Analysis/Indicators/IndicatorResult.php`

### [2026-02-28 14:10 GMT+2] Agent: LocalTester
**Tests Complete**

All 67 tests pass with 137 assertions.

**Test Files Created:**
- `tests/Unit/Services/Analysis/Indicators/IndicatorProcessorTest.php` (41 tests)
- `tests/Unit/Services/Analysis/Indicators/IndicatorResultTest.php` (26 tests)

**Implementation Changes Made:**
1. Changed from `symfony/dom-crawler` to native PHP `DOMDocument` with `symfony/css-selector` for CSS-to-XPath conversion
2. Added conditional logging (checks if Log facade is available for unit tests)
3. All helper methods updated to use DOMDocument

**Test Coverage:**
- Abstract methods (slug, name, maxScore, process)
- Fetch URL with SSRF protection, HTTP errors, timeout handling
- HTML parsing with valid, malformed, empty input
- CSS selectors (element, class, id, nested)
- Text/attribute extraction helpers
- IndicatorResult DTO (structure, factories, immutability, serialization)
- Configuration (timeout, max response size)
- Edge cases (unicode, special characters, large data, etc.)

**Test Results:**
```
PASS  Tests\Unit\Services\Analysis\Indicators\IndicatorProcessorTest
PASS  Tests\Unit\Services\Analysis\Indicators\IndicatorResultTest
Tests:    67 passed (137 assertions)
Duration: 0.31s
```

Ready for DevOps deployment.

### [2026-02-28 14:31 GMT+2] Agent: DevOps
**Deployment Complete**

Deployed to production via GitHub Actions (run 22520760922).

**Deployment Summary:**
- Commit: `48e8db8` - feat(indicators): Add IndicatorProcessor base class with tests
- Build-and-test: ✓ (1m16s)
- Deploy: ✓ (34s)
- Health check: ✓ (200 OK)

**Files Deployed:**
- `app/Services/Analysis/Indicators/IndicatorProcessor.php`
- `app/Services/Analysis/Indicators/IndicatorResult.php`
- `tests/Unit/Services/Analysis/Indicators/IndicatorProcessorTest.php`
- `tests/Unit/Services/Analysis/Indicators/IndicatorResultTest.php`

**Production Health Check:**
```
GET https://seoindicator.com/api/health
Status: 200 OK
Response: {"status":"ok","database":"connected"}
```

Ready for ProdTester validation.
