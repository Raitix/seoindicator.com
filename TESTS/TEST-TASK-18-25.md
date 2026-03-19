# TEST-TASK-18-25: PageSpeedChecker Indicator Tests

## Overview
Comprehensive test coverage for PageSpeedChecker indicator that integrates with Google PageSpeed Insights API.

## Test File Location
`backend/tests/Unit/Services/SEO/Checkers/PageSpeedCheckerTest.php`

## Test Summary
| Category | Tests | Description |
|----------|-------|-------------|
| SI | 6 | Service Instantiation |
| API | 15 | Google PageSpeed API Integration |
| SCORE | 12 | Score Mapping (1-3) |
| FALLBACK | 8 | Fallback to Response Time |
| RENDER | 6 | Render-Blocking Resources |
| SIZE | 5 | Page Size Checks |
| CACHE | 6 | Caching Behavior |
| SUGGEST | 8 | Suggestions Generation |
| ERROR | 8 | Error Handling |
| EDGE | 12 | Edge Cases |
| SEC | 5 | Security |
| CONFIG | 4 | Configuration |
| PERF | 3 | Performance |
| AC | 11 | Acceptance Criteria |
| **Total** | **99** | |

## Test Categories

### SI: Service Instantiation (6 tests)
| Test ID | Description |
|---------|-------------|
| SI-001 | Service can be instantiated |
| SI-002 | Implements IndicatorCheckerInterface |
| SI-003 | id() returns 'page_speed' |
| SI-004 | name() returns 'Page Speed' |
| SI-005 | category() returns 'performance' |
| SI-006 | weight() returns 0.10 |

### API: Google PageSpeed API Integration (15 tests)
| Test ID | Description |
|---------|-------------|
| API-001 | Makes request to correct API endpoint |
| API-002 | Includes API key in request |
| API-003 | Extracts performance score from response |
| API-004 | Handles successful API response |
| API-005 | Handles API rate limiting (429) |
| API-006 | Handles API errors (500) |
| API-007 | Handles network timeout |
| API-008 | Handles invalid JSON response |
| API-009 | Extracts FCP (First Contentful Paint) |
| API-010 | Extracts LCP (Largest Contentful Paint) |
| API-011 | Extracts CLS (Cumulative Layout Shift) |
| API-012 | Extracts TBT (Total Blocking Time) |
| API-013 | Extracts SI (Speed Index) |
| API-014 | Handles mobile strategy parameter |
| API-015 | Handles desktop strategy parameter |

### SCORE: Score Mapping (12 tests)
| Test ID | Score Range | Expected Result |
|---------|-------------|-----------------|
| SCORE-001 | 90-100 | Score 3 (Excellent) |
| SCORE-002 | 80-89 | Score 3 (Excellent) |
| SCORE-003 | 60-79 | Score 2 (Good) |
| SCORE-004 | 50-59 | Score 2 (Good) |
| SCORE-005 | 40-49 | Score 1 (Poor) |
| SCORE-006 | 0-39 | Score 1 (Poor) |
| SCORE-007 | Exact 80 boundary | Score 3 |
| SCORE-008 | Exact 60 boundary | Score 2 |
| SCORE-009 | Exact 40 boundary | Score 1 |
| SCORE-010 | Score 100 | Score 3 |
| SCORE-011 | Score 0 | Score 1 |
| SCORE-012 | Score passed=true for >=60 |

### FALLBACK: Fallback to Response Time (8 tests)
| Test ID | Description |
|---------|-------------|
| FALLBACK-001 | API failure falls back to response time |
| FALLBACK-002 | No API key triggers fallback |
| FALLBACK-003 | Response time <1s → Score 3 |
| FALLBACK-004 | Response time 1-2s → Score 2 |
| FALLBACK-005 | Response time >2s → Score 1 |
| FALLBACK-006 | Fallback includes timing details |
| FALLBACK-007 | Fallback logged appropriately |
| FALLBACK-008 | Fallback uses HtmlFetcherService |

### RENDER: Render-Blocking Resources (6 tests)
| Test ID | Description |
|---------|-------------|
| RENDER-001 | Identifies render-blocking CSS |
| RENDER-002 | Identifies render-blocking JavaScript |
| RENDER-003 | Counts render-blocking resources |
| RENDER-004 | Includes render-blocking in details |
| RENDER-005 | Generates suggestion for render-blocking |
| RENDER-006 | Handles no render-blocking resources |

### SIZE: Page Size Checks (5 tests)
| Test ID | Description |
|---------|-------------|
| SIZE-001 | Extracts page size from API |
| SIZE-002 | Large page flagged in details |
| SIZE-003 | Page size >500KB included in suggestions |
| SIZE-004 | Page size calculation correct |
| SIZE-005 | Handles missing size data |

### CACHE: Caching Behavior (6 tests)
| Test ID | Description |
|---------|-------------|
| CACHE-001 | Caches successful API responses |
| CACHE-002 | Cache TTL is 24 hours |
| CACHE-003 | Returns cached result on repeat request |
| CACHE-004 | Cache key includes URL |
| CACHE-005 | Cache invalidation by URL |
| CACHE-006 | Cache bypass with force_refresh option |

### SUGGEST: Suggestions Generation (8 tests)
| Test ID | Description |
|---------|-------------|
| SUGGEST-001 | Generates suggestions from API recommendations |
| SUGGEST-002 | Suggestion includes priority |
| SUGGEST-003 | Suggestion includes description |
| SUGGEST-004 | Render-blocking suggestion generated |
| SUGGEST-005 | Image optimization suggestion |
| SUGGEST-006 | Unused CSS suggestion |
| SUGGEST-007 | Unused JavaScript suggestion |
| SUGGEST-008 | Max 5 suggestions returned |

### ERROR: Error Handling (8 tests)
| Test ID | Description |
|---------|-------------|
| ERROR-001 | Invalid URL handled gracefully |
| ERROR-002 | Empty URL returns error result |
| ERROR-003 | Malformed URL returns error result |
| ERROR-004 | Non-HTTP URL handled |
| ERROR-005 | API timeout handled |
| ERROR-006 | Network error handled |
| ERROR-007 | Invalid response handled |
| ERROR-008 | Never throws exception |

### EDGE: Edge Cases (12 tests)
| Test ID | Description |
|---------|-------------|
| EDGE-001 | Unicode URL handled |
| EDGE-002 | Very long URL handled |
| EDGE-003 | Special characters in URL |
| EDGE-004 | HTTP vs HTTPS |
| EDGE-005 | URL with query parameters |
| EDGE-006 | URL with fragment |
| EDGE-007 | Redirect URL handled |
| EDGE-008 | Internationalized domain |
| EDGE-009 | Concurrent requests |
| EDGE-010 | Same URL different strategies (mobile/desktop) |
| EDGE-011 | Very low API score (0) |
| EDGE-012 | Very high API score (100) |

### SEC: Security (5 tests)
| Test ID | Description |
|---------|-------------|
| SEC-001 | API key not exposed in logs |
| SEC-002 | API key not exposed in results |
| SEC-003 | SSRF protection via HtmlFetcherService |
| SEC-004 | No sensitive data in suggestions |
| SEC-005 | Input validation on URL |

### CONFIG: Configuration (4 tests)
| Test ID | Description |
|---------|-------------|
| CONFIG-001 | Custom API key from config |
| CONFIG-002 | Custom timeout from config |
| CONFIG-003 | Custom cache TTL |
| CONFIG-004 | Missing API key triggers fallback |

### PERF: Performance (3 tests)
| Test ID | Description |
|---------|-------------|
| PERF-001 | Cached response < 10ms |
| PERF-002 | Fallback response < 100ms |
| PERF-003 | No memory leak on repeated checks |

### AC: Acceptance Criteria Verification (11 tests)
| Test ID | Criteria |
|---------|----------|
| AC-001 | Implements IndicatorCheckerInterface |
| AC-002 | Integrates with Google PageSpeed Insights API |
| AC-003 | Handles API failures gracefully (fallback) |
| AC-004 | Extracts performance score from API response |
| AC-005 | Identifies render-blocking resources |
| AC-006 | Checks page size from response |
| AC-007 | Caches API results (24 hours) |
| AC-008 | Returns score 1-3 based on PageSpeed score |
| AC-009 | Generates suggestions from API recommendations |
| AC-010 | Weight: 0.10, Category: performance |
| AC-011 | Unit tests with mocked API responses |

## Mock Patterns

### Google PageSpeed API Response Mock
```json
{
  "lighthouseResult": {
    "categories": {
      "performance": {
        "score": 0.85
      }
    },
    "audits": {
      "first-contentful-paint": {
        "numericValue": 1200,
        "score": 0.92
      },
      "largest-contentful-paint": {
        "numericValue": 2500,
        "score": 0.78
      },
      "cumulative-layout-shift": {
        "numericValue": 0.05,
        "score": 0.95
      },
      "total-blocking-time": {
        "numericValue": 150,
        "score": 0.88
      },
      "speed-index": {
        "numericValue": 2000,
        "score": 0.85
      },
      "render-blocking-resources": {
        "details": {
          "items": [
            { "url": "https://example.com/style.css", "wastedMs": 500 },
            { "url": "https://example.com/script.js", "wastedMs": 300 }
          ]
        }
      }
    }
  }
}
```

### Fallback Response Time Mock
```php
// When API fails, use HtmlFetcherService for timing
$mockFetcherResult = new HtmlFetcherResult(
    success: true,
    content: '<html><body>Test</body></html>',
    timingMs: 1500
);
```

## Dependencies
- TASK-18-3 (IndicatorCheckerInterface) - Done ✅
- TASK-18-5 (HtmlFetcherService) - Done ✅ (for fallback timing)

## Implementation Notes for Developer

### Expected Service Interface
```php
class PageSpeedChecker implements IndicatorCheckerInterface
{
    public function __construct(
        private HtmlFetcherService $htmlFetcher,
        private string $apiKey
    ) {}
    
    public function id(): string { return 'page_speed'; }
    public function name(): string { return 'Page Speed'; }
    public function category(): string { return 'performance'; }
    public function weight(): float { return 0.10; }
    
    public function check(Crawler $crawler, string $url, array $options = []): IndicatorResult;
    public function getSuggestions(IndicatorResult $result): array;
}
```

### API Endpoint
```
GET https://www.googleapis.com/pagespeedonline/v5/runPagespeed
    ?url={url}
    &key={api_key}
    &category=performance
    &strategy={mobile|desktop}
```

### Score Mapping
| PageSpeed Score | Our Score | Label |
|-----------------|-----------|-------|
| 80-100 | 3 | Excellent |
| 60-79 | 2 | Good |
| 0-59 | 1 | Poor |

### Fallback Timing Score
| Response Time | Score |
|---------------|-------|
| < 1000ms | 3 |
| 1000-2000ms | 2 |
| > 2000ms | 1 |

## Status
Tests designed. Ready for implementation.
