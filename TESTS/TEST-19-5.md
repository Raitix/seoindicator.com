# TEST-19-5: IndicatorProcessor Base Class

## Overview
Test suite for abstract IndicatorProcessor base class with common functionality for all indicator checkers.

---

## Test File
`tests/Unit/Services/IndicatorProcessorTest.php`

---

## Test Scenarios

### 1. Abstract Method Tests

#### 1.1 slug() Method
- [ ] Returns unique identifier string
- [ ] Uses snake_case format
- [ ] Maximum 100 characters

#### 1.2 name() Method
- [ ] Returns human-readable name
- [ ] Properly formatted (Title Case)
- [ ] Maximum 255 characters

#### 1.3 maxScore() Method
- [ ] Returns float value
- [ ] Default: 100.0
- [ ] Overrideable per processor

#### 1.4 process() Method
- [ ] Accepts URL string
- [ ] Returns IndicatorResult DTO
- [ ] Handles valid URLs
- [ ] Handles invalid URLs

### 2. Helper Method Tests

#### 2.1 fetchUrl() Method
- [ ] Fetches HTML content from URL
- [ ] Respects timeout configuration (30s default)
- [ ] Respects max response size (10MB default)
- [ ] Handles HTTP errors (4xx, 5xx)
- [ ] Handles network timeouts
- [ ] Applies SSRF protection

#### 2.2 parseHtml() Method
- [ ] Returns DomCrawler instance
- [ ] Handles valid HTML
- [ ] Handles malformed HTML gracefully
- [ ] Handles empty HTML

### 3. SSRF Protection Tests

#### 3.1 Blocked IPs
- [ ] Blocks localhost (127.0.0.1, ::1, localhost)
- [ ] Blocks private IP ranges (10.x, 172.16-31.x, 192.168.x)
- [ ] Blocks link-local (169.254.x.x)
- [ ] Blocks cloud metadata (169.254.169.254)

#### 3.2 Blocked Protocols
- [ ] Allows HTTP
- [ ] Allows HTTPS
- [ ] Blocks file://
- [ ] Blocks ftp://
- [ ] Blocks other protocols

#### 3.3 DNS Rebinding Protection
- [ ] Resolves DNS before connection
- [ ] Verifies resolved IP not blocked
- [ ] Re-checks after redirects

### 4. IndicatorResult DTO Tests

#### 4.1 Structure
- [ ] Has score property (float)
- [ ] Has maxScore property (float)
- [ ] Has percentage property (int 0-100)
- [ ] Has data property (array)
- [ ] Has recommendations property (array)
- [ ] Has processingTimeMs property (int)
- [ ] Has error property (string|null)

#### 4.2 Calculated Properties
- [ ] percentage calculated from score/maxScore
- [ ] processingTimeMs set automatically

### 5. Configuration Tests

#### 5.1 Timeout Configuration
- [ ] Default timeout 30 seconds
- [ ] Configurable via constructor
- [ ] Configurable via config file

#### 5.2 Max Response Size
- [ ] Default 10MB
- [ ] Configurable
- [ ] Throws exception when exceeded

### 6. Error Handling Tests

#### 6.1 Network Errors
- [ ] Handles connection timeout
- [ ] Handles connection refused
- [ ] Handles DNS resolution failure
- [ ] Handles SSL certificate errors

#### 6.2 HTTP Errors
- [ ] Handles 404 Not Found
- [ ] Handles 403 Forbidden
- [ ] Handles 500 Server Error
- [ ] Handles redirects (>5)

### 7. Edge Cases

#### 7.1 Large Responses
- [ ] Handles responses up to max size
- [ ] Rejects responses over max size

#### 7.2 Slow Responses
- [ ] Respects timeout setting
- [ ] Provides meaningful error on timeout

#### 7.3 Empty Responses
- [ ] Handles empty HTML
- [ ] Handles 204 No Content

### 8. Security Tests

#### 8.1 SSRF via Redirect
- [ ] Blocks redirect to localhost
- [ ] Blocks redirect to private IPs
- [ ] Follows max redirect limit (5)

#### 8.2 DNS Rebinding
- [ ] Second DNS lookup after redirect
- [ ] Blocks if redirected to blocked IP

---

## Test Implementation Notes

- Use concrete test implementation extending IndicatorProcessor
- Mock HTTP client for network tests
- Use DomCrawler for HTML parsing tests
- Test SSRF protection comprehensively (HIGH RISK)

---

## Dependencies
- TASK-16-1 (Tools infrastructure - SSRF protection)

---

## Coverage Target
- 60+ test methods
- 90%+ code coverage

---

*Created by PreTester for TASK-19-5*
