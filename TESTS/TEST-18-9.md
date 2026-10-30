# TEST-18-9: URLChecker - URL Structure Indicator

## Task Reference
- **Task:** TASK-18-9
- **Feature:** FEAT-018 (Legacy 17-Indicator SEO Analysis Core Engine)
- **Status:** Tests Coverage In Progress

---

## Overview
Tests for the `URLChecker` indicator that analyzes URL structure: HTTPS, length, keywords, special characters, dynamic parameters, and readability.

---

## Test Files

| File | Type | Tests | Assertions |
|------|------|-------|------------|
| `backend/tests/Unit/Services/SEO/Indicators/URLCheckerTest.php` | Unit | 40+ | 80+ |

---

## Test Scenarios

### HTTPS Protocol Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| HT-01 | HTTPS URL | `https://example.com` | isHttps: true, passed |
| HT-02 | HTTP URL | `http://example.com` | isHttps: false, warning |
| HT-03 | Mixed protocol redirect | HTTP redirecting to HTTPS | isHttps: true |
| HT-04 | Invalid protocol | `ftp://example.com` | Error or rejected |

### URL Length Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| UL-01 | Short URL (< 50 chars) | `https://example.com/page` | urlLength: 26, optimal |
| UL-02 | Optimal URL (50-75 chars) | `https://example.com/seo-friendly-url-page` | urlLength: 44, optimal |
| UL-03 | Long URL (76-100 chars) | URL with 80 chars | urlLength: 80, warning |
| UL-04 | Very long URL (> 100 chars) | URL with 150 chars | urlLength: 150, poor |
| UL-05 | Maximum URL (2083 chars) | URL at HTTP limit | Handled gracefully |

### Keywords in URL Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| KU-01 | Keywords present | `https://example.com/seo-services-london` | hasKeywords: true |
| KU-02 | No keywords | `https://example.com/page/12345` | hasKeywords: false |
| KU-03 | Partial keywords | `https://example.com/seo-12345` | hasKeywords: true |
| KU-04 | Multiple keywords | `https://example.com/best-seo-services-london` | hasKeywords: true |
| KU-05 | Brand keywords | `https://example.com/about-us` | hasKeywords: true |

### Special Characters Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SC-01 | Alphanumeric only | `https://example.com/page123` | noSpecialChars: true |
| SC-02 | Hyphens allowed | `https://example.com/my-page` | noSpecialChars: true |
| SC-03 | Underscores allowed | `https://example.com/my_page` | noSpecialChars: true |
| SC-04 | Spaces encoded | `https://example.com/my%20page` | noSpecialChars: true |
| SC-05 | Special chars present | `https://example.com/page@#$` | noSpecialChars: false |
| SC-06 | Unicode characters | `https://example.com/ñ-page` | noSpecialChars: true (Punycode) |

### Dynamic Parameters Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DP-01 | No parameters | `https://example.com/page` | hasDynamicParams: false |
| DP-02 | Single parameter | `https://example.com/page?id=1` | hasDynamicParams: true, paramCount: 1 |
| DP-03 | Multiple parameters | `https://example.com/page?id=1&cat=2` | hasDynamicParams: true, paramCount: 2 |
| DP-04 | Many parameters (> 3) | `https://example.com/page?a=1&b=2&c=3&d=4` | hasDynamicParams: true, warning |
| DP-05 | Hash fragment | `https://example.com/page#section` | hasDynamicParams: false |

### Readability Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RD-01 | Readable structure | `/blog/seo-tips/beginners-guide` | readable: true |
| RD-02 | Numeric IDs | `/page/12345/67890` | readable: false |
| RD-03 | Mixed readable/numeric | `/blog/12345/seo-tips` | readable: partial |
| RD-04 | Deep nesting | `/a/b/c/d/e/f/g/h` | warning: too deep |
| RD-05 | Single segment | `/page` | readable: true |

### Scoring Tests

| ID | Scenario | Input | Expected Score |
|----|----------|-------|----------------|
| SC-01 | Perfect URL | HTTPS + optimal length + keywords + clean | 3 (Excellent) |
| SC-02 | Good URL | HTTPS + good length + minor issues | 2 (Good) |
| SC-03 | Poor URL | HTTP or very long or many params | 1 (Poor) |
| SC-04 | Critical issues | HTTP + long + special chars + many params | 1 (Poor) |

### Suggestion Generation Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SG-01 | HTTP URL | `http://example.com` | Suggest HTTPS |
| SG-02 | Long URL | URL with 100+ chars | Suggest shortening |
| SG-03 | Many parameters | URL with 5+ params | Suggest cleaner URLs |
| SG-04 | Special characters | URL with @#$ | Suggest removing |
| SG-05 | No keywords | URL with numbers only | Suggest adding keywords |

### Interface Compliance Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IC-01 | Implements interface | Class check | Instance of IndicatorCheckerInterface |
| IC-02 | ID returns correct | id() | 'url' |
| IC-03 | Name returns correct | name() | 'URL Structure' |
| IC-04 | Category returns correct | category() | 'technical' |
| IC-05 | Weight returns correct | weight() | 0.08 |

### Edge Case Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-01 | Root URL | `https://example.com/` | Valid analysis |
| EC-02 | Subdomain | `https://blog.example.com/page` | Valid analysis |
| EC-03 | Port in URL | `https://example.com:8080/page` | Valid analysis |
| EC-04 | IP address | `https://192.168.1.1/page` | Valid analysis |
| EC-05 | Punycode domain | `https://xn--example-9ua.com/page` | Valid analysis |
| EC-06 | Empty path | `https://example.com` | Valid analysis |

---

## Mock Data

### Perfect URL
```php
$url = 'https://example.com/seo-services-london';
$crawler = new Crawler('<html><body></body></html>');
```

### URL with Issues
```php
$url = 'http://example.com/page?id=1&cat=2&user=3&session=4&token=abc123@#$';
$crawler = new Crawler('<html><body></body></html>');
```

---

## Expected Result Structure

```php
new IndicatorResult(
    id: 'url',
    name: 'URL Structure',
    category: 'technical',
    score: 3,
    weight: 0.08,
    passed: true,
    details: [
        'urlLength' => 40,
        'hasKeywords' => true,
        'isHttps' => true,
        'noSpecialChars' => true,
        'hasDynamicParams' => false,
        'paramCount' => 0,
        'isReadable' => true,
        'pathSegments' => ['seo-services-london'],
        'depth' => 1,
    ],
    suggestions: []
);
```

---

## Coverage Targets

| Category | Target | Description |
|----------|--------|-------------|
| HTTPS Check | 100% | All protocol scenarios |
| Length Check | 100% | All length ranges |
| Keywords Check | 100% | All keyword scenarios |
| Special Chars | 100% | All character types |
| Dynamic Params | 100% | All parameter scenarios |
| Readability | 100% | All readability cases |
| Scoring | 100% | All score combinations |
| Interface | 100% | All interface methods |

---

## Test Execution

```bash
# Run specific test file
php artisan test --filter=URLCheckerTest

# Run with coverage
php artisan test --filter=URLCheckerTest --coverage
```

---

## Dependencies
- `IndicatorCheckerInterface` (TASK-18-3)
- `IndicatorResult` DTO (TASK-18-3)
- Symfony DomCrawler

---

## Notes for Developer
- URL parsing should use parse_url() for reliability
- Keywords can be extracted from URL path segments
- Consider maintaining a common keyword list for SEO
- Dynamic parameters should differentiate essential vs tracking

---

## Comments

### [2026-02-28] PreTester
- Test documentation created
- 40+ test scenarios covering all URL analysis aspects
- Ready for test implementation
