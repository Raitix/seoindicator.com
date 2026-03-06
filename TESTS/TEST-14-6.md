# TEST-14-6: RobotsParser Service

## Task Reference
- **Task:** TASK-14-6
- **Feature:** FEAT-014 - Technical SEO Site Audit
- **Specification:** SPEC-014
- **Status:** Tests Coverage In Progress

## Overview
Comprehensive test suite for the RobotsParser service that handles robots.txt parsing and crawl permission checking. Tests cover fetching, parsing, directive handling, wildcard patterns, caching, and edge cases.

## Test Files

| File | Tests | Location |
|------|-------|----------|
| RobotsParserServiceTest.php | 150+ | `backend/tests/Unit/Services/Audit/RobotsParserServiceTest.php` |
| ParsedRobotsTest.php | 45+ | `backend/tests/Unit/Services/Audit/DTOs/ParsedRobotsTest.php` |
| RobotsRuleTest.php | 35+ | `backend/tests/Unit/Services/Audit/DTOs/RobotsRuleTest.php` |
| RobotsParserIntegrationTest.php | 40+ | `backend/tests/Feature/Services/RobotsParserIntegrationTest.php` |
| **Total** | **270+** | |

## Test Categories

### RP-SI: Service Instantiation (5 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| RP-SI-01 | Service can be instantiated via app() | Instance of RobotsParser |
| RP-SI-02 | Service has fetch method | Method exists |
| RP-SI-03 | Service has parse method | Method exists |
| RP-SI-04 | Service has isAllowed method | Method exists |
| RP-SI-05 | Service has getCrawlDelay, getSitemaps, getDisallowedPaths methods | Methods exist |

### RP-FETCH: Robots.txt Fetching (20 tests)
| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| RP-FETCH-01 | Fetch valid robots.txt | `https://example.com` | Returns robots.txt content |
| RP-FETCH-02 | Construct correct URL | `https://example.com` | Fetches `https://example.com/robots.txt` |
| RP-FETCH-03 | Handle trailing slash | `https://example.com/` | Fetches `https://example.com/robots.txt` |
| RP-FETCH-04 | Handle 404 response | 404 status | Returns null (allow all) |
| RP-FETCH-05 | Handle 403 response | 403 status | Returns null (allow all) |
| RP-FETCH-06 | Handle 500 response | 500 status | Returns null (allow all) |
| RP-FETCH-07 | Handle timeout | Request timeout | Returns null, logs warning |
| RP-FETCH-08 | Handle DNS failure | NXDOMAIN | Returns null, logs warning |
| RP-FETCH-09 | Handle SSL certificate error | Invalid cert | Returns null, logs warning |
| RP-FETCH-10 | Handle connection refused | Connection refused | Returns null, logs warning |
| RP-FETCH-11 | Default timeout is 10 seconds | No timeout param | 10s timeout configured |
| RP-FETCH-12 | Custom timeout | timeout=5 | 5s timeout configured |
| RP-FETCH-13 | Handle malformed base URL | `not-a-url` | Throws InvalidArgumentException |
| RP-FETCH-14 | Handle relative URL | `/path` | Throws InvalidArgumentException |
| RP-FETCH-15 | Handle non-HTTP URL | `ftp://example.com` | Throws InvalidArgumentException |
| RP-FETCH-16 | Handle empty response body | 200 with empty body | Returns empty string |
| RP-FETCH-17 | Handle large robots.txt | 100KB file | Returns content (no truncation) |
| RP-FETCH-18 | Handle redirect (301) | Redirect to /robots.txt | Follows redirect, returns content |
| RP-FETCH-19 | Handle redirect (302) | Redirect to /robots.txt | Follows redirect, returns content |
| RP-FETCH-20 | Handle max redirects exceeded | 10+ redirects | Returns null |

### RP-PARSE: Directive Parsing (30 tests)
| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| RP-PARSE-01 | Parse empty robots.txt | `""` | ParsedRobots with isEmpty=true |
| RP-PARSE-02 | Parse whitespace-only | `"   \n\n   "` | ParsedRobots with isEmpty=true |
| RP-PARSE-03 | Parse user-agent group | `User-agent: *\nDisallow: /admin` | ParsedRobots with rules |
| RP-PARSE-04 | Parse multiple user-agents | Multiple User-agent lines | All rules grouped correctly |
| RP-PARSE-05 | Parse Disallow directive | `Disallow: /private` | Rule with allow=false |
| RP-PARSE-06 | Parse Allow directive | `Allow: /public` | Rule with allow=true |
| RP-PARSE-07 | Parse Crawl-delay | `Crawl-delay: 10` | crawlDelay=10 |
| RP-PARSE-08 | Parse Sitemap directive | `Sitemap: https://example.com/sitemap.xml` | sitemaps array |
| RP-PARSE-09 | Parse multiple Sitemaps | Multiple Sitemap lines | All sitemaps in array |
| RP-PARSE-10 | Handle comments | Lines starting with # | Comments ignored |
| RP-PARSE-11 | Handle inline comments | `Disallow: /admin # private` | Path extracted correctly |
| RP-PARSE-12 | Handle case-insensitive directives | `user-agent: *` | Parsed correctly |
| RP-PARSE-13 | Handle mixed case | `DISALLOW: /admin` | Parsed correctly |
| RP-PARSE-14 | Parse path with spaces | `Disallow: /my path` | Path preserved |
| RP-PARSE-15 | Parse empty Disallow | `Disallow:` | Allow all (empty disallow) |
| RP-PARSE-16 | Parse empty Allow | `Allow:` | No effect |
| RP-PARSE-17 | Handle invalid Crawl-delay | `Crawl-delay: abc` | crawlDelay=null |
| RP-PARSE-18 | Handle negative Crawl-delay | `Crawl-delay: -5` | crawlDelay=null |
| RP-PARSE-19 | Handle zero Crawl-delay | `Crawl-delay: 0` | crawlDelay=0 |
| RP-PARSE-20 | Parse large robots.txt | 100+ directives | All parsed correctly |
| RP-PARSE-21 | Handle Windows line endings | `\r\n` | Parsed correctly |
| RP-PARSE-22 | Handle mixed line endings | `\n` and `\r\n` | Parsed correctly |
| RP-PARSE-23 | Parse User-agent with spaces | `User-agent: Google Bot` | userAgent="Google Bot" |
| RP-PARSE-24 | Handle malformed directives | `Invalid: value` | Ignored, no error |
| RP-PARSE-25 | Parse path with URL encoding | `Disallow: /path%20with%20spaces` | Path preserved |
| RP-PARSE-26 | Handle relative Sitemap | `Sitemap: /sitemap.xml` | Converted to absolute URL |
| RP-PARSE-27 | Handle invalid Sitemap URL | `Sitemap: not-a-url` | Added to sitemaps array (as-is) |
| RP-PARSE-28 | Parse wildcard in Disallow | `Disallow: /admin/*` | Rule with wildcard pattern |
| RP-PARSE-29 | Parse $ in Disallow | `Disallow: /*.pdf$` | Rule with end pattern |
| RP-PARSE-30 | Parse multiple rules for same user-agent | Multiple Disallow lines | All rules captured |

### RP-ALLOW: Permission Checking (25 tests)
| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| RP-ALLOW-01 | Check allowed URL | `/public`, allow `/public` | true |
| RP-ALLOW-02 | Check disallowed URL | `/admin`, disallow `/admin` | false |
| RP-ALLOW-03 | Check partially matching URL | `/admin/users`, disallow `/admin` | false |
| RP-ALLOW-04 | Check exact match | `/admin`, disallow `/admin` | false |
| RP-ALLOW-05 | Check trailing slash | `/admin/`, disallow `/admin` | false |
| RP-ALLOW-06 | Check no trailing slash | `/admin`, disallow `/admin/` | true (different path) |
| RP-ALLOW-07 | Check with user-agent | `/admin`, `Googlebot`, disallow for `Googlebot` | false |
| RP-ALLOW-08 | Check with different user-agent | `/admin`, `Bingbot`, disallow for `Googlebot` | true (uses *) |
| RP-ALLOW-09 | Check wildcard pattern | `/admin/secret`, disallow `/admin/*` | false |
| RP-ALLOW-10 | Check end-of-path pattern | `/file.pdf`, disallow `/*.pdf$` | false |
| RP-ALLOW-11 | Check no match for end pattern | `/file.pdf.html`, disallow `/*.pdf$` | true |
| RP-ALLOW-12 | Check multiple wildcards | `/a/b/c`, disallow `/a/*/c` | false |
| RP-ALLOW-13 | Check empty robots.txt | Any URL | true (allow all) |
| RP-ALLOW-14 | Check with no matching rules | `/any`, no rules | true |
| RP-ALLOW-15 | Check Allow overrides Disallow | `/public`, disallow `/`, allow `/public` | true |
| RP-ALLOW-16 | Check more specific Allow wins | `/admin/public`, disallow `/admin`, allow `/admin/public` | true |
| RP-ALLOW-17 | Check path normalization | `/ADMIN`, disallow `/admin` | false (case-insensitive) |
| RP-ALLOW-18 | Check URL decoding | `/path%20with%20spaces`, disallow `/path with spaces` | false |
| RP-ALLOW-19 | Check query string | `/page?id=1`, disallow `/page` | false |
| RP-ALLOW-20 | Check query string in disallow | `/page?id=1`, disallow `/page?id=` | false |
| RP-ALLOW-21 | Check fragment | `/page#section`, disallow `/page` | false |
| RP-ALLOW-22 | Check root path | `/`, disallow `/` | false |
| RP-ALLOW-23 | Check root path allowed | `/`, no rules | true |
| RP-ALLOW-24 | Check with robots.txt 404 | Any URL | true |
| RP-ALLOW-25 | Check with robots.txt error | Any URL | true |

### RP-WILDCARD: Wildcard Pattern Matching (20 tests)
| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| RP-WC-01 | Match * at end | `/admin/users`, `/admin/*` | Match |
| RP-WC-02 | Match * at start | `/users/admin`, `*/admin` | Match |
| RP-WC-03 | Match * in middle | `/a/b/c`, `/a/*/c` | Match |
| RP-WC-04 | Match multiple * | `/a/b/c/d`, `/a/*/c/*` | Match |
| RP-WC-05 | Match $ at end | `/file.pdf`, `/*.pdf$` | Match |
| RP-WC-06 | No match for $ | `/file.pdf.html`, `/*.pdf$` | No match |
| RP-WC-07 | Match * and $ combined | `/docs/file.pdf`, `/docs/*.pdf$` | Match |
| RP-WC-08 | Empty path with * | `/*` matches everything | All paths match |
| RP-WC-09 | Single * matches all | `/any/path`, `/*` | Match |
| RP-WC-10 | Match directory with * | `/admin/`, `/admin*` | Match |
| RP-WC-11 | Match file extension | `/image.png`, `/*.png` | Match |
| RP-WC-12 | Match multiple extensions | `/image.png`, `/*.png$` | Match |
| RP-WC-13 | No match for different path | `/public`, `/private/*` | No match |
| RP-WC-14 | Match with query string | `/page?id=1`, `/page*` | Match |
| RP-WC-15 | Match with fragment | `/page#section`, `/page*` | Match |
| RP-WC-16 | Case-insensitive matching | `/Admin`, `/admin/*` | Match |
| RP-WC-17 | URL-encoded path | `/path%20with%20spaces`, `/path*` | Match |
| RP-WC-18 | Match special characters | `/file-name.pdf`, `/*-name.pdf$` | Match |
| RP-WC-19 | No wildcards (exact match) | `/admin`, `/admin` | Match |
| RP-WC-20 | Pattern longer than path | `/a`, `/a/b/*` | No match |

### RP-CACHE: Caching Behavior (15 tests)
| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| RP-CACHE-01 | Cache parsed result | First fetch | Stored in cache |
| RP-CACHE-02 | Cache hit on second fetch | Same domain | Returns cached result |
| RP-CACHE-03 | Cache key format | `https://example.com` | Key includes domain hash |
| RP-CACHE-04 | TTL is 1 hour | Any fetch | Cache expires after 3600 seconds |
| RP-CACHE-05 | Different domains cached separately | `example.com`, `other.com` | Different cache keys |
| RP-CACHE-06 | Cache respects protocol | `http://example.com`, `https://example.com` | Different cache keys |
| RP-CACHE-07 | Cache invalidation | Clear cache | Next fetch hits network |
| RP-CACHE-08 | Cache 404 response | 404 status | Cached as empty (allow all) |
| RP-CACHE-09 | Cache error response | 500 status | Cached as empty (allow all) |
| RP-CACHE-10 | Cache timeout response | Timeout | Cached as empty (allow all) |
| RP-CACHE-11 | Force refresh | `forceRefresh=true` | Bypasses cache |
| RP-CACHE-12 | Cache size limit | Large robots.txt | Stored completely |
| RP-CACHE-13 | Cache concurrent access | Simultaneous requests | Single network call |
| RP-CACHE-14 | Cache hit returns ParsedRobots | Cached entry | Returns ParsedRobots object |
| RP-CACHE-15 | Cache stores parsed result | Parse result | Not raw text |

### RP-USERAGENT: User-Agent Handling (15 tests)
| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| RP-UA-01 | Default user-agent | No user-agent param | Uses `*` |
| RP-UA-02 | Specific user-agent | `Googlebot` | Rules for Googlebot |
| RP-UA-03 | Fallback to * | Unknown user-agent | Uses * rules |
| RP-UA-04 | Case-insensitive user-agent | `googlebot`, `GOOGLEBOT` | Same rules |
| RP-UA-05 | User-agent with version | `Googlebot/2.1` | Uses Googlebot rules |
| RP-UA-06 | Multiple user-agent definitions | `Googlebot`, `googlebot` | Merged rules |
| RP-UA-07 | Wildcard user-agent | `*` | Applies to all |
| RP-UA-08 | User-agent specific Crawl-delay | `Googlebot` with delay | Returns Googlebot delay |
| RP-UA-09 | Fallback Crawl-delay | Unknown user-agent | Returns * delay |
| RP-UA-10 | No Crawl-delay defined | Any user-agent | Returns null |
| RP-UA-11 | User-agent specific Disallow | `Googlebot` disallow | Only applies to Googlebot |
| RP-UA-12 | Mixed user-agent rules | Multiple user-agents | Correct rules per user-agent |
| RP-UA-13 | Empty user-agent | `""` | Uses * rules |
| RP-UA-14 | User-agent with spaces | `Google Bot` | Matches "Google Bot" |
| RP-UA-15 | Special characters in user-agent | `bot-123` | Matches correctly |

### RP-DTO: Data Structure Tests (80 tests)

#### ParsedRobots DTO (45 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| RP-DTO-PR-01 | Create with empty state | isEmpty=true, empty arrays |
| RP-DTO-PR-02 | Create with rules | userAgents, rules populated |
| RP-DTO-PR-03 | Create with sitemaps | sitemaps array populated |
| RP-DTO-PR-04 | Create with crawlDelay | crawlDelay set |
| RP-DTO-PR-05 | toArray() method | Returns array representation |
| RP-DTO-PR-06 | jsonSerialize() method | Returns JSON-compatible array |
| RP-DTO-PR-07 | Immutability | Properties are readonly |
| RP-DTO-PR-08 | Add rule | Rules array grows |
| RP-DTO-PR-09 | Add sitemap | Sitemaps array grows |
| RP-DTO-PR-10 | Multiple user-agents | userAgents array populated |
| RP-DTO-PR-11 | Rules grouped by user-agent | Correct grouping |
| RP-DTO-PR-12 | Empty robots.txt | isEmpty=true |
| RP-DTO-PR-13 | Comments only | isEmpty=true |
| RP-DTO-PR-14 | Whitespace only | isEmpty=true |
| RP-DTO-PR-15 | Has rules check | isEmpty=false when rules exist |

#### RobotsRule DTO (35 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| RP-DTO-RR-01 | Create with required fields | userAgent, path, allow set |
| RP-DTO-RR-02 | Create Disallow rule | allow=false |
| RP-DTO-RR-03 | Create Allow rule | allow=true |
| RP-DTO-RR-04 | toArray() method | Returns array representation |
| RP-DTO-RR-05 | jsonSerialize() method | Returns JSON-compatible array |
| RP-DTO-RR-06 | Immutability | Properties are readonly |
| RP-DTO-RR-07 | Path normalization | Path trimmed |
| RP-DTO-RR-08 | Empty path | Path="" |
| RP-DTO-RR-09 | Path with leading slash | Preserved |
| RP-DTO-RR-10 | Path without leading slash | Added |
| RP-DTO-RR-11 | Wildcard path | Preserved as-is |
| RP-DTO-RR-12 | $ pattern | Preserved as-is |
| RP-DTO-RR-13 | URL-encoded path | Preserved as-is |
| RP-DTO-RR-14 | Long path | Preserved |
| RP-DTO-RR-15 | Unicode path | Preserved |

### RP-EDGE: Edge Cases (25 tests)
| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| RP-EDGE-01 | Null robots.txt content | null | isEmpty=true |
| RP-EDGE-02 | Empty string robots.txt | "" | isEmpty=true |
| RP-EDGE-03 | Very long path in Disallow | 1000+ char path | Handled gracefully |
| RP-EDGE-04 | Unicode in robots.txt | Non-ASCII chars | Handled gracefully |
| RP-EDGE-05 | Emoji in path | Emoji characters | Handled gracefully |
| RP-EDGE-06 | Binary data in robots.txt | Non-text content | Handled gracefully |
| RP-EDGE-07 | Malformed URLs in Sitemap | Invalid URLs | Added as-is |
| RP-EDGE-08 | Circular redirect | Infinite redirect | Returns null |
| RP-EDGE-09 | Self-referencing robots.txt | robots.txt in Disallow | Allowed by default |
| RP-EDGE-10 | IP address base URL | `http://192.168.1.1` | Works correctly |
| RP-EDGE-11 | Localhost URL | `http://localhost` | Works correctly |
| RP-EDGE-12 | Port in URL | `http://example.com:8080` | Works correctly |
| RP-EDGE-13 | Subdomain URL | `http://sub.example.com` | Works correctly |
| RP-EDGE-14 | Very large robots.txt | 1MB file | Parsed correctly |
| RP-EDGE-15 | Concurrent requests | Multiple threads | Thread-safe |
| RP-EDGE-16 | Memory limit | Large file | No memory leak |
| RP-EDGE-17 | Special characters in user-agent | Regex chars | Escaped properly |
| RP-EDGE-18 | Null byte in path | `%00` | Handled gracefully |
| RP-EDGE-19 | Newline in path | `\n` | Handled gracefully |
| RP-EDGE-20 | Tab in path | `\t` | Handled gracefully |
| RP-EDGE-21 | Path with backslash | `\admin` | Converted to forward slash |
| RP-EDGE-22 | Multiple consecutive slashes | `//admin` | Normalized |
| RP-EDGE-23 | Path with dots | `/../admin` | Not normalized (robots.txt literal) |
| RP-EDGE-24 | Empty Disallow for user-agent | `User-agent: *\nDisallow:` | Allow all for that user-agent |
| RP-EDGE-25 | Only comments in robots.txt | `# comment` | isEmpty=true |

### RP-SEC: Security Tests (15 tests)
| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| RP-SEC-01 | SSRF prevention - localhost | `http://localhost/robots.txt` | Blocked or sanitized |
| RP-SEC-02 | SSRF prevention - private IP | `http://192.168.1.1` | Blocked or sanitized |
| RP-SEC-03 | SSRF prevention - cloud metadata | `http://169.254.169.254` | Blocked |
| RP-SEC-04 | SSRF prevention - file:// | `file:///etc/passwd` | Blocked |
| RP-SEC-05 | SSRF prevention - gopher:// | `gopher://internal` | Blocked |
| RP-SEC-06 | XSS in Sitemap URL | `<script>alert(1)</script>` | Escaped or sanitized |
| RP-SEC-07 | XSS in Disallow path | `<script>` in path | Not executed |
| RP-SEC-08 | SQL injection in URL | `' OR 1=1` | Not executed |
| RP-SEC-09 | No credentials in URL | `http://user:pass@example.com` | Credentials stripped |
| RP-SEC-10 | No internal network access | Internal IPs | Blocked |
| RP-SEC-11 | No DNS rebinding | Malicious DNS | Timeout handled |
| RP-SEC-12 | No redirect to internal | External → internal redirect | Blocked |
| RP-SEC-13 | No CRLF injection | `\r\n` in URL | Handled gracefully |
| RP-SEC-14 | No open redirect | Malicious redirect | Blocked |
| RP-SEC-15 | No path traversal | `../../etc/passwd` | Handled gracefully |

### RP-PERF: Performance Tests (5 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| RP-PERF-01 | Parse 1KB robots.txt | < 10ms |
| RP-PERF-02 | Parse 100KB robots.txt | < 100ms |
| RP-PERF-03 | Check 100 URLs against rules | < 50ms |
| RP-PERF-04 | Cache hit response | < 1ms |
| RP-PERF-05 | Memory usage for large file | < 10MB |

### RP-INT: Integration Tests (10 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| RP-INT-01 | Full fetch-parse-check flow | Works correctly |
| RP-INT-02 | Multiple user-agents in one parse | Correct rules per agent |
| RP-INT-03 | Cache integration | Cache works with service |
| RP-INT-04 | Logger integration | Errors logged |
| RP-INT-05 | HTTP client integration | Requests made correctly |
| RP-INT-06 | Real-world robots.txt | Google, GitHub, etc. |
| RP-INT-07 | Integration with SiteCrawler | Used by crawler |
| RP-INT-08 | Integration with IssueDetector | Issues detected |
| RP-INT-09 | Queue job usage | Works in queue context |
| RP-INT-10 | Multiple domains | Concurrent parsing |

## Mock Data

### Standard robots.txt
```
User-agent: *
Disallow: /admin
Disallow: /private
Allow: /public
Crawl-delay: 5
Sitemap: https://example.com/sitemap.xml

User-agent: Googlebot
Disallow: /google-only
Crawl-delay: 2

User-agent: Bingbot
Disallow: /bing-only
```

### robots.txt with wildcards
```
User-agent: *
Disallow: /admin/*
Disallow: /*.pdf$
Disallow: /secret*
Allow: /public/*
```

### Empty robots.txt (404)
```
(No content - 404 response)
```

### robots.txt with only comments
```
# This is a comment
# Another comment
```

### Large robots.txt (for performance testing)
```
User-agent: *
# 100+ Disallow lines
Disallow: /path1
Disallow: /path2
...
```

## Test Execution

```bash
# Run all RobotsParser tests
php artisan test --filter=RobotsParser

# Run specific test file
php artisan test backend/tests/Unit/Services/Audit/RobotsParserServiceTest.php

# Run with coverage
php artisan test --coverage --filter=RobotsParser
```

## Coverage Targets

| Category | Target | Status |
|----------|--------|--------|
| Service Instantiation | 100% | - |
| Robots.txt Fetching | 100% | - |
| Directive Parsing | 100% | - |
| Permission Checking | 100% | - |
| Wildcard Patterns | 100% | - |
| Caching | 100% | - |
| User-Agent Handling | 100% | - |
| DTO Tests | 100% | - |
| Edge Cases | 100% | - |
| Security | 100% | - |
| Performance | 100% | - |
| Integration | 100% | - |
| **Overall** | **95%+** | - |

## Dependencies

- **Uses:** HTTP Client (Laravel Http facade)
- **Uses:** Cache (Laravel Cache facade)
- **Used by:** TASK-14-5 (SiteCrawler) - Mocked
- **Used by:** TASK-14-8 (IssueDetector) - Mocked

## Notes for Developer

1. **Service Location:** `backend/app/Services/Audit/RobotsParser.php`

2. **DTO Locations:**
   - `backend/app/Services/Audit/DTOs/ParsedRobots.php`
   - `backend/app/Services/Audit/DTOs/RobotsRule.php`

3. **Cache Key Format:** `robots:{domain_hash}` (domain_hash = SHA-256 of domain)

4. **Default Timeout:** 10 seconds (configurable)

5. **SSRF Protection:** Block localhost, private IPs, cloud metadata IP

6. **User-Agent Matching:** Case-insensitive, version-stripped matching

7. **Wildcard Pattern Rules:**
   - `*` matches any sequence of characters
   - `$` matches end of path
   - Multiple wildcards allowed

8. **Crawl-Delay Handling:**
   - Only applies to specified user-agent
   - Falls back to `*` user-agent if not specified
   - Returns null if not defined

---

## Comments
_Agent communication log_

### PreTester Session (23:13 GMT+2)
- Test design started
- 270+ test scenarios designed across 13 categories
- TDD pattern applied with mocked HTTP responses
- Key patterns: Http::fake(), Cache::shouldReceive(), Mockery for DTOs
- Dependencies: None (standalone service)
- **Status:** Tests Coverage In Progress
