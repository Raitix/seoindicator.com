# TEST-TASK-18-9: URLChecker Indicator Tests

## Overview
Comprehensive test coverage for URLChecker indicator that analyzes URL structure for SEO best practices.

## Test File Location
`backend/tests/Unit/Services/SEO/Checkers/URLCheckerTest.php`

## Test Summary
| Category | Tests | Description |
|----------|-------|-------------|
| SI | 6 | Service Instantiation |
| HTTPS | 8 | HTTPS Protocol Checks |
| LENGTH | 10 | URL Length Validation |
| KEYWORDS | 8 | Keywords in URL Path |
| CHARS | 8 | Special Characters |
| PARAMS | 8 | Dynamic Parameters |
| READABLE | 6 | Path Readability |
| SCORE | 12 | Score Calculation |
| SUGGEST | 10 | Suggestions Generation |
| ERROR | 6 | Error Handling |
| EDGE | 10 | Edge Cases |
| SEC | 4 | Security |
| AC | 11 | Acceptance Criteria |
| **Total** | **107** | |

## Test Categories

### SI: Service Instantiation (6 tests)
| Test ID | Description |
|---------|-------------|
| SI-001 | Service can be instantiated |
| SI-002 | Implements IndicatorCheckerInterface |
| SI-003 | id() returns 'url' |
| SI-004 | name() returns 'URL Structure' |
| SI-005 | category() returns 'technical' |
| SI-006 | weight() returns 0.08 |

### HTTPS: HTTPS Protocol Checks (8 tests)
| Test ID | Description |
|---------|-------------|
| HTTPS-001 | HTTPS URL passes check |
| HTTPS-002 | HTTP URL fails check |
| HTTPS-003 | Protocol detection accurate |
| HTTPS-004 | Mixed content URL handling |
| HTTPS-005 | No protocol URL handling |
| HTTPS-006 | HTTPS in score calculation |
| HTTPS-007 | HTTPS suggestion for HTTP |
| HTTPS-008 | Protocol in details |

### LENGTH: URL Length Validation (10 tests)
| Test ID | Description |
|---------|-------------|
| LENGTH-001 | URL ≤50 chars is optimal |
| LENGTH-002 | URL 51-75 chars is good |
| LENGTH-003 | URL 76-100 chars needs improvement |
| LENGTH-004 | URL >100 chars is poor |
| LENGTH-005 | Exact 75 char boundary |
| LENGTH-006 | Exact 100 char boundary |
| LENGTH-007 | Very long URL (>200 chars) |
| LENGTH-008 | URL length in details |
| LENGTH-009 | Length suggestion generated |
| LENGTH-010 | Query string included in length |

### KEYWORDS: Keywords in URL Path (8 tests)
| Test ID | Description |
|---------|-------------|
| KEYWORDS-001 | Keywords in path detected |
| KEYWORDS-002 | Multiple keywords detected |
| KEYWORDS-003 | No keywords detected |
| KEYWORDS-004 | Keywords from page title matched |
| KEYWORDS-005 | Keyword density calculation |
| KEYWORDS-006 | Hyphen-separated keywords |
| KEYWORDS-007 | Keywords in details |
| KEYWORDS-008 | Keyword suggestion generated |

### CHARS: Special Characters (8 tests)
| Test ID | Description |
|---------|-------------|
| CHARS-001 | Alphanumeric-only URL passes |
| CHARS-002 | Hyphens allowed |
| CHARS-003 | Underscores allowed |
| CHARS-004 | Special chars detected (!@#$%) |
| CHARS-005 | Spaces detected (encoded or not) |
| CHARS-006 | Unicode characters handled |
| CHARS-007 | Character issues in details |
| CHARS-008 | Character suggestion generated |

### PARAMS: Dynamic Parameters (8 tests)
| Test ID | Description |
|---------|-------------|
| PARAMS-001 | No parameters is optimal |
| PARAMS-002 | 1-2 parameters acceptable |
| PARAMS-003 | 3+ parameters flagged |
| PARAMS-004 | Parameter count in details |
| PARAMS-005 | Session IDs detected |
| PARAMS-006 | UTM parameters excluded from count |
| PARAMS-007 | Parameter suggestion generated |
| PARAMS-008 | Complex query strings |

### READABLE: Path Readability (6 tests)
| Test ID | Description |
|---------|-------------|
| READABLE-001 | Short readable paths score well |
| READABLE-002 | Long cryptic paths flagged |
| READABLE-003 | Path segment count |
| READABLE-004 | ID-based paths flagged |
| READABLE-005 | Readability in details |
| READABLE-006 | Readability suggestion |

### SCORE: Score Calculation (12 tests)
| Test ID | Description |
|---------|-------------|
| SCORE-001 | Perfect URL = score 3 |
| SCORE-002 | Missing HTTPS = score ≤2 |
| SCORE-003 | Long URL = score reduction |
| SCORE-004 | Many params = score reduction |
| SCORE-005 | Special chars = score reduction |
| SCORE-006 | Multiple issues = score 1 |
| SCORE-007 | Good URL with minor issues = score 2 |
| SCORE-008 | Score always 1-3 |
| SCORE-009 | Passed status calculation |
| SCORE-010 | Score breakdown in details |
| SCORE-011 | Weight applied correctly |
| SCORE-012 | Category correct |

### SUGGEST: Suggestions Generation (10 tests)
| Test ID | Description |
|---------|-------------|
| SUGGEST-001 | HTTPS suggestion for HTTP |
| SUGGEST-002 | Length suggestion for long URLs |
| SUGGEST-003 | Parameter reduction suggestion |
| SUGGEST-004 | Keyword suggestion |
| SUGGEST-005 | Special character suggestion |
| SUGGEST-006 | Readability suggestion |
| SUGGEST-007 | Combined suggestions |
| SUGGEST-008 | Max 5 suggestions |
| SUGGEST-009 | Suggestion priority |
| SUGGEST-010 | Suggestion description quality |

### ERROR: Error Handling (6 tests)
| Test ID | Description |
|---------|-------------|
| ERROR-001 | Empty URL handled |
| ERROR-002 | Invalid URL handled |
| ERROR-003 | Null URL handled |
| ERROR-004 | Malformed URL handled |
| ERROR-005 | Never throws exception |
| ERROR-006 | Returns valid IndicatorResult |

### EDGE: Edge Cases (10 tests)
| Test ID | Description |
|---------|-------------|
| EDGE-001 | URL with fragment |
| EDGE-002 | URL with subdomain |
| EDGE-003 | URL with port |
| EDGE-004 | Internationalized domain |
| EDGE-005 | Punycode domain |
| EDGE-006 | URL with trailing slash |
| EDGE-007 | Root URL (domain only) |
| EDGE-008 | URL with auth credentials |
| EDGE-009 | Very short URL |
| EDGE-010 | IP address URL |

### SEC: Security (4 tests)
| Test ID | Description |
|---------|-------------|
| SEC-001 | No XSS in suggestions |
| SEC-002 | No sensitive data exposed |
| SEC-003 | Injection prevention |
| SEC-004 | URL encoding handled |

### AC: Acceptance Criteria Verification (11 tests)
| Test ID | Criteria |
|--------|----------|
| AC-001 | Implements IndicatorCheckerInterface |
| AC-002 | Checks HTTPS protocol usage |
| AC-003 | Validates URL length (≤75 chars optimal) |
| AC-004 | Detects keywords in URL path |
| AC-005 | Checks for special characters |
| AC-006 | Identifies dynamic parameters |
| AC-007 | Evaluates path segment readability |
| AC-008 | Returns score 1-3 based on criteria |
| AC-009 | Generates actionable suggestions |
| AC-010 | Weight: 0.08, Category: technical |
| AC-011 | Unit tests covering all scenarios |

## Score Matrix

| HTTPS | Length | Params | Chars | Keywords | Score |
|-------|--------|--------|-------|----------|-------|
| ✅ | ≤75 | 0-2 | Clean | Present | 3 |
| ✅ | ≤75 | 0-2 | Clean | Missing | 2-3 |
| ❌ | Any | Any | Any | Any | ≤2 |
| ✅ | 76-100 | 3+ | Issues | Any | 1-2 |
| ✅ | >100 | Many | Issues | Any | 1 |

## Expected Service Interface

```php
class URLChecker implements IndicatorCheckerInterface
{
    public function id(): string { return 'url'; }
    public function name(): string { return 'URL Structure'; }
    public function category(): string { return 'technical'; }
    public function weight(): float { return 0.08; }
    
    public function check(Crawler $crawler, string $url, array $options = []): IndicatorResult;
    public function getSuggestions(IndicatorResult $result): array;
}
```

## Dependencies
- TASK-18-3 (IndicatorCheckerInterface) - Done ✅

## Status
Tests designed. Ready for implementation.
