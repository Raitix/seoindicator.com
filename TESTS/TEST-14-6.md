# TEST-14-6: RobotsParser Service Tests

## Task Reference
- **Task:** TASK-14-6: Create RobotsParser Service
- **Feature:** FEAT-014: Site Crawler
- **Status:** To Develop

## Overview
Comprehensive unit tests for the RobotsParser service that handles robots.txt parsing, permission checking, and compliant crawling. Tests cover HTTP fetching, directive parsing, wildcard patterns, caching, security (SSRF prevention), and edge cases.

## Test Files

| File | Tests | Assertions | Location |
|------|-------|------------|----------|
| `RobotsParserTest.php` | 116 | 200+ | `backend/tests/Unit/Services/Audit/` |

## Test Categories

### SI: Service Instantiation (5 tests)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| SI-01 | Service resolved from container | RobotsParser instance |
| SI-02 | All required methods exist | fetch, parse, isAllowed, getCrawlDelay, getSitemaps, getDisallowedPaths |
| SI-03 | Default timeout configuration | Configurable timeout |
| SI-04 | Default cache TTL configuration | Configurable TTL (default 3600s) |
| SI-05 | Service is singleton | Same instance on repeated resolution |

### FETCH: Robots.txt Fetching (14 tests)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FETCH-01 | Successful fetch | URL with robots.txt | Content string |
| FETCH-02 | 404 response | URL without robots.txt | null |
| FETCH-03 | 500 error | Server error | null |
| FETCH-04 | Connection timeout | Slow server | null |
| FETCH-05 | URL construction | Base URL with path | Correct robots.txt URL |
| FETCH-06 | Trailing slash URL | URL with / | Correct robots.txt URL |
| FETCH-07 | Query string URL | URL with ?param=value | Correct robots.txt URL |
| FETCH-08 | Port in URL | URL with :8080 | Correct robots.txt URL |
| FETCH-09 | Subdomain URL | blog.example.com | Correct robots.txt URL |
| FETCH-10 | User-Agent header | Any URL | Header sent |
| FETCH-11 | DNS failure | Non-existent domain | null |
| FETCH-12 | SSL error | Invalid certificate | null |
| FETCH-13 | Large file | 50KB+ robots.txt | Content parsed |
| FETCH-14 | Empty robots.txt | Empty response | Empty string |

### PARSE: Directive Parsing (18 tests)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PARSE-01 | Parse returns DTO | Valid robots.txt | ParsedRobots instance |
| PARSE-02 | User-agent groups | Multiple user-agents | Both in userAgents array |
| PARSE-03 | Wildcard user-agent | User-agent: * | '*' in userAgents |
| PARSE-04 | Disallow directives | Disallow: /admin/ | Path in getDisallowedPaths |
| PARSE-05 | Allow directives | Allow: /public/ | Rule with allow=true |
| PARSE-06 | Crawl-delay | Crawl-delay: 10 | crawlDelay = 10 |
| PARSE-07 | Sitemap directives | Sitemap: URL | URL in sitemaps array |
| PARSE-08 | Empty robots.txt | Empty string | isEmpty = true |
| PARSE-09 | Null input | null | isEmpty = true |
| PARSE-10 | Malformed content | Random text | ParsedRobots (graceful) |
| PARSE-11 | Mixed case | USER-AGENT: * | Parsed correctly |
| PARSE-12 | Extra whitespace | User-agent:  * | Trimmed correctly |
| PARSE-13 | Comments | # comment line | Ignored correctly |
| PARSE-14 | URL-encoded paths | %3F in path | Decoded correctly |
| PARSE-15 | Unicode paths | /приватный/ | Preserved correctly |
| PARSE-16 | Multiple user-agents per group | Two User-agent lines | Both captured |
| PARSE-17 | Specific user-agent rules | Googlebot-specific | Only for that agent |
| PARSE-18 | Rules without user-agent | Bare Disallow | Defaults to * |

### PERM: Permission Checking (16 tests)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PERM-01 | Allowed path | /public/page | true |
| PERM-02 | Disallowed path | /admin/settings | false |
| PERM-03 | Empty robots.txt | Any path | true |
| PERM-04 | No matching rules | Different user-agent | true |
| PERM-05 | Exact path match | /private | false for /private only |
| PERM-06 | Directory disallow | /admin/ | false for /admin/* |
| PERM-07 | Root disallow | Disallow: / | false for all |
| PERM-08 | Allow override | Allow in Disallow / | true for allowed |
| PERM-09 | Specific user-agent | Googlebot rules | Only for Googlebot |
| PERM-10 | Wildcard + specific rules | Both types | Both checked |
| PERM-11 | Query string in URL | /search?q=test | Checked correctly |
| PERM-12 | Fragment in URL | /page#section | Checked correctly |
| PERM-13 | Empty Disallow | Disallow: | Allow all |
| PERM-14 | Encoded characters | %3F in path | Matched correctly |
| PERM-15 | Longest rule match | Multiple rules | Most specific wins |
| PERM-16 | Case sensitivity | /Admin/ vs /admin/ | Different paths |

### WILDCARD: Pattern Matching (12 tests)
| ID | Scenario | Pattern | Test Path | Expected |
|----|----------|---------|-----------|----------|
| WILDCARD-01 | Trailing asterisk | /*.pdf | /doc.pdf | false |
| WILDCARD-02 | Middle asterisk | /admin/*/settings | /admin/users/settings | false |
| WILDCARD-03 | Dollar sign marker | /print$ | /print | false |
| WILDCARD-04 | Combined wildcards | /*.pdf$ | /doc.pdf | false |
| WILDCARD-05 | Multiple asterisks | /*/admin/* | /site/admin/users | false |
| WILDCARD-06 | Zero-character match | /test*page | /testpage | false |
| WILDCARD-07 | Wildcard in user-agent | User-agent: * | Any bot | Matches |
| WILDCARD-08 | Path ending asterisk | /private/* | /private/secret | false |
| WILDCARD-09 | Path starting asterisk | */search | /api/search | false |
| WILDCARD-10 | Case sensitivity | /*.PDF | /doc.pdf | true (no match) |
| WILDCARD-11 | Escaped characters | /path%20spaces | /path spaces | false |
| WILDCARD-12 | Complex pattern | /*?*print* | /page?print=1 | false |

### DELAY: Crawl Delay (8 tests)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DELAY-01 | Extract delay | Crawl-delay: 5 | 5 |
| DELAY-02 | Missing directive | No Crawl-delay | null |
| DELAY-03 | User-agent specific | Different for bots | Per-agent value |
| DELAY-04 | Decimal value | Crawl-delay: 0.5 | 0.5 |
| DELAY-05 | Invalid value | Crawl-delay: invalid | null |
| DELAY-06 | Negative value | Crawl-delay: -5 | null |
| DELAY-07 | Zero value | Crawl-delay: 0 | 0 |
| DELAY-08 | Wildcard default | No specific rule | Wildcard value |

### SITEMAP: Sitemap Extraction (6 tests)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SITEMAP-01 | Single sitemap | Sitemap: URL | Array with URL |
| SITEMAP-02 | Multiple sitemaps | Two Sitemap lines | Array with both |
| SITEMAP-03 | No sitemaps | Only Disallow rules | Empty array |
| SITEMAP-04 | Relative URL | /sitemap.xml | Included in array |
| SITEMAP-05 | Sitemap index | sitemap-index.xml | Included correctly |
| SITEMAP-06 | Gzip sitemap | .xml.gz URL | Included correctly |

### CACHE: Caching Behavior (10 tests)
| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| CACHE-01 | Successful response cached | Cache::remember called |
| CACHE-02 | Second request uses cache | No HTTP call |
| CACHE-03 | Cache key format | Prefix 'robots:' |
| CACHE-04 | Cache TTL | 3600 seconds (1 hour) |
| CACHE-05 | Force refresh option | Cache bypassed |
| CACHE-06 | Different domains | Different cache keys |
| CACHE-07 | 404 response caching | Cached as null |
| CACHE-08 | Parse error clears cache | Cache::forget called |
| CACHE-09 | Custom TTL from config | Respects config value |
| CACHE-10 | ParsedRobots cached | Parsed result cached |

### ERROR: Error Handling (10 tests)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ERROR-01 | Malformed URL | not-a-url | null |
| ERROR-02 | Empty URL | '' | null |
| ERROR-03 | Connection reset | Connection dropped | null |
| ERROR-04 | Invalid response body | Binary data | Handled gracefully |
| ERROR-05 | Request timeout | Slow server | null |
| ERROR-06 | Binary content parse | \x00\x01\x02 | ParsedRobots |
| ERROR-07 | Very long lines | 10000 char line | Handled gracefully |
| ERROR-08 | Invalid URL in isAllowed | not-a-url | true (default allow) |
| ERROR-09 | Recursion limit | Many wildcards | No crash |
| ERROR-10 | Memory limit | 100K+ lines | Parsed successfully |

### SEC: Security (SSRF Prevention) (10 tests)
| ID | Scenario | URL | Expected |
|----|----------|-----|----------|
| SEC-01 | Localhost blocked | http://localhost/ | null |
| SEC-02 | 127.0.0.1 blocked | http://127.0.0.1/ | null |
| SEC-03 | 10.x.x.x blocked | http://10.0.0.1/ | null |
| SEC-04 | 172.16.x.x blocked | http://172.16.0.1/ | null |
| SEC-05 | 192.168.x.x blocked | http://192.168.1.1/ | null |
| SEC-06 | Cloud metadata blocked | http://169.254.169.254/ | null |
| SEC-07 | 0.0.0.0 blocked | http://0.0.0.0/ | null |
| SEC-08 | IPv6 localhost blocked | http://[::1]/ | null |
| SEC-09 | file:// blocked | file:///etc/passwd | null |
| SEC-10 | Internal hostnames blocked | http://internal.local/ | null |

### EDGE: Edge Cases (12 tests)
| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| EDGE-01 | UTF-8 BOM | \xEF\xBB\xBF... | Parsed correctly |
| EDGE-02 | Windows line endings | \r\n | Parsed correctly |
| EDGE-03 | Mixed line endings | \n and \r\n | Parsed correctly |
| EDGE-04 | URLs with credentials | user:pass@domain | Credentials stripped |
| EDGE-05 | Non-standard ports | :3000 | Handled correctly |
| EDGE-06 | International domains | пример.org | Punycode handled |
| EDGE-07 | Only comments | # comments only | isEmpty = true |
| EDGE-08 | Duplicate directives | Same Disallow twice | Deduplicated |
| EDGE-09 | Long sitemap URL | 2000+ char URL | Included |
| EDGE-10 | Null bytes | \x00 in path | Handled gracefully |
| EDGE-11 | Unicode user-agent | Бот | Preserved |
| EDGE-12 | Emoji in paths | /🎉/ | Preserved |

### INT: Integration (5 tests)
| ID | Scenario | Description |
|----|----------|-------------|
| INT-01 | Full workflow | fetch → parse → isAllowed |
| INT-02 | Cache integration | Verify cache hit/miss |
| INT-03 | Multi user-agent check | Different bots, different rules |
| INT-04 | Real-world robots.txt | Common patterns from production |
| INT-05 | Performance | 1000 rules parsed < 1 second |

## Mock Data Samples

### Standard robots.txt
```
User-agent: *
Disallow: /admin/
Disallow: /private/
Allow: /public/
Crawl-delay: 5
Sitemap: https://example.com/sitemap.xml
```

### Complex robots.txt
```
# Robots.txt for example.com
User-agent: *
Disallow: /search?q=
Disallow: /*.pdf$
Disallow: /admin/*/

User-agent: Googlebot
Disallow: /google-specific/
Crawl-delay: 2

User-agent: Bingbot
Disallow: /bing-specific/

Sitemap: https://example.com/sitemap.xml
Sitemap: https://example.com/news-sitemap.xml
```

### Wildcard patterns
```
User-agent: *
Disallow: /*.pdf$
Disallow: /*.doc$
Disallow: /private/*/
Disallow: /*?print=
Allow: /public/*.html$
```

## Coverage Targets
- **Line Coverage:** 95%+
- **Branch Coverage:** 90%+
- **Method Coverage:** 100%

## Test Execution
```bash
# Run all RobotsParser tests
php artisan test --filter=RobotsParserTest

# Run with coverage
php artisan test --filter=RobotsParserTest --coverage

# Run specific category
php artisan test --filter="test_fetch_"
php artisan test --filter="test_perm_"
php artisan test --filter="test_wildcard_"
```

## Dependencies
- None (standalone service)

## Notes for Developer
1. Implement `RobotsParser` service at `backend/app/Services/Audit/RobotsParser.php`
2. Create DTOs: `ParsedRobots` and `RobotsRule`
3. Implement wildcard pattern matching for `*` and `$` characters
4. Add SSRF protection to block internal/private IPs
5. Use Laravel Cache for caching with 1-hour TTL
6. Handle all edge cases gracefully (no exceptions thrown)
7. Default to "allow" when in doubt

## Acceptance Criteria Coverage
- [x] Tests for RobotsParser service with all methods
- [x] Tests for standard robots.txt format parsing
- [x] Tests for wildcards in Allow/Disallow paths
- [x] Tests for crawl-delay extraction
- [x] Tests for sitemap URL extraction
- [x] Tests for graceful handling of missing/invalid robots.txt
- [x] Tests for caching with correct TTL
- [x] Tests for unit coverage including edge cases

---

## Comments
_Agent communication log_

### PreTester Session (2026-03-07 06:48 UTC)
**Test Design Complete**
- 116 tests designed covering all service functionality
- Test categories: SI(5), FETCH(14), PARSE(18), PERM(16), WILDCARD(12), DELAY(8), SITEMAP(6), CACHE(10), ERROR(10), SEC(10), EDGE(12), INT(5)
- DTOs created: ParsedRobots, RobotsRule
- Security tests for SSRF prevention (10 tests)
- Performance test for large files (< 1s for 1000 rules)
- All acceptance criteria covered by tests
- Status: `To Write Tests` → `To Develop`
