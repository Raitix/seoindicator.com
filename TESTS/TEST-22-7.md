# TEST-22-7: URL Validation and SSRF Protection

## STATUS
`Tests Coverage In Progress`

## Related
- Task: [TASK-22-7](../TASKS/TASK-22-7.md)
- Specification: [SPEC-022](../SPECIFICATIONS/SPEC-022.md)
- Feature: [FEAT-022](../FEATURES/FEAT-022.md)

---

## Test Coverage Summary

| Category | Tests | Assertions | Status |
|----------|-------|------------|--------|
| Protocol Validation | 14 | 14 | 📋 Pending |
| URL Format Validation | 18 | 18 | 📋 Pending |
| IP Address Blocking (IPv4) | 25 | 25 | 📋 Pending |
| IP Address Blocking (IPv6) | 15 | 15 | 📋 Pending |
| DNS Rebinding Protection | 12 | 12 | 📋 Pending |
| Domain Blocking | 10 | 10 | 📋 Pending |
| Redirect Handling | 14 | 14 | 📋 Pending |
| ValidationResult Structure | 10 | 10 | 📋 Pending |
| Configuration | 8 | 8 | 📋 Pending |
| Error Messages | 8 | 8 | 📋 Pending |
| Security Edge Cases | 20 | 20 | 📋 Pending |
| Integration Tests | 10 | 10 | 📋 Pending |
| **Total** | **164** | **164** | 📋 Pending |

---

## Test Scenarios

### Protocol Validation (PV)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| PV-001 | Valid HTTP URL | `http://example.com` | `valid: true` |
| PV-002 | Valid HTTPS URL | `https://example.com` | `valid: true` |
| PV-003 | Block file:// protocol | `file:///etc/passwd` | `valid: false, error: "Protocol not allowed"` |
| PV-004 | Block ftp:// protocol | `ftp://example.com/file` | `valid: false, error: "Protocol not allowed"` |
| PV-005 | Block data: protocol | `data:text/html,<script>alert(1)</script>` | `valid: false, error: "Protocol not allowed"` |
| PV-006 | Block javascript: protocol | `javascript:alert(1)` | `valid: false, error: "Protocol not allowed"` |
| PV-007 | Block mailto: protocol | `mailto:test@example.com` | `valid: false, error: "Protocol not allowed"` |
| PV-008 | Block gopher:// protocol | `gopher://example.com` | `valid: false, error: "Protocol not allowed"` |
| PV-009 | Block custom protocol | `custom://resource` | `valid: false, error: "Protocol not allowed"` |
| PV-010 | Block no protocol | `example.com` | `valid: false, error: "Protocol not allowed"` |
| PV-011 | Case-insensitive protocol block | `FILE:///etc/passwd` | `valid: false, error: "Protocol not allowed"` |
| PV-012 | Mixed case protocol block | `JaVaScRiPt:alert(1)` | `valid: false, error: "Protocol not allowed"` |
| PV-013 | Protocol with whitespace | ` file:///etc/passwd` | `valid: false, error: "Protocol not allowed"` |
| PV-014 | Null byte in protocol | `file%00://example.com` | `valid: false, error: "URL format invalid"` |

### URL Format Validation (UFV)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| UFV-001 | Valid URL with path | `https://example.com/path/to/page` | `valid: true` |
| UFV-002 | Valid URL with query | `https://example.com?page=1&sort=asc` | `valid: true` |
| UFV-003 | Valid URL with fragment | `https://example.com#section` | `valid: true` |
| UFV-004 | Valid URL with port | `https://example.com:8443/path` | `valid: true` |
| UFV-005 | Invalid URL - no host | `https:///path` | `valid: false, error: "URL format invalid"` |
| UFV-006 | Invalid URL - malformed | `https://[invalid` | `valid: false, error: "URL format invalid"` |
| UFV-007 | Empty string | `` | `valid: false, error: "URL format invalid"` |
| UFV-008 | Null value | `null` | `valid: false, error: "URL format invalid"` |
| UFV-009 | IPv4 address URL | `https://192.0.2.1/path` | `valid: true` (if not blocked IP) |
| UFV-010 | IPv6 address URL | `https://[2001:db8::1]/path` | `valid: true` (if not blocked IP) |
| UFV-011 | IPv6 with zone ID | `https://[fe80::1%eth0]/` | `valid: false, error: "URL format invalid"` |
| UFV-012 | IDN domain (punycode) | `https://xn--r8jz45g.jp` | `valid: true` |
| UFV-013 | IDN domain (unicode) | `https://例え.jp` | `valid: true` |
| UFV-014 | URL with credentials | `https://user:pass@example.com` | `valid: true` |
| UFV-015 | Very long URL (>2083 chars) | 2100 char URL | `valid: false, error: "URL format invalid"` |
| UFV-016 | URL with special chars | `https://example.com/path?q=a+b&x=y%20z` | `valid: true` |
| UFV-017 | URL normalization - lowercase host | `https://Example.COM/Path` | `valid: true, url: "https://example.com/Path"` |
| UFV-018 | URL normalization - remove default port | `https://example.com:443/` | `valid: true, url: "https://example.com/"` |

### IP Address Blocking - IPv4 (IPV4)

| ID | Scenario | IP | Expected Block Reason |
|----|----------|-------|----------|
| IPV4-001 | Localhost 127.0.0.1 | `http://127.0.0.1/` | `IP address blocked: localhost` |
| IPV4-002 | Localhost 127.0.0.2 | `http://127.0.0.2/` | `IP address blocked: localhost` |
| IPV4-003 | 127.0.0.0/8 range start | `http://127.0.0.0/` | `IP address blocked: localhost` |
| IPV4-004 | 127.0.0.0/8 range end | `http://127.255.255.255/` | `IP address blocked: localhost` |
| IPV4-005 | Private 10.0.0.1 | `http://10.0.0.1/` | `IP address blocked: private network` |
| IPV4-006 | Private 10.255.255.255 | `http://10.255.255.255/` | `IP address blocked: private network` |
| IPV4-007 | Private 172.16.0.1 | `http://172.16.0.1/` | `IP address blocked: private network` |
| IPV4-008 | Private 172.31.255.255 | `http://172.31.255.255/` | `IP address blocked: private network` |
| IPV4-009 | Private 192.168.0.1 | `http://192.168.0.1/` | `IP address blocked: private network` |
| IPV4-010 | Private 192.168.255.255 | `http://192.168.255.255/` | `IP address blocked: private network` |
| IPV4-011 | AWS/GCP metadata 169.254.169.254 | `http://169.254.169.254/latest/meta-data/` | `IP address blocked: metadata endpoint` |
| IPV4-012 | Link-local 169.254.0.1 | `http://169.254.0.1/` | `IP address blocked: link-local` |
| IPV4-013 | Link-local 169.254.255.255 | `http://169.254.255.255/` | `IP address blocked: link-local` |
| IPV4-014 | Current network 0.0.0.0 | `http://0.0.0.0/` | `IP address blocked: unspecified` |
| IPV4-015 | 0.0.0.0/8 range | `http://0.0.0.5/` | `IP address blocked: unspecified` |
| IPV4-016 | Multicast 224.0.0.1 | `http://224.0.0.1/` | `IP address blocked: multicast` |
| IPV4-017 | Multicast 239.255.255.255 | `http://239.255.255.255/` | `IP address blocked: multicast` |
| IPV4-018 | Reserved 240.0.0.1 | `http://240.0.0.1/` | `IP address blocked: reserved` |
| IPV4-019 | Reserved 255.255.255.255 | `http://255.255.255.255/` | `IP address blocked: reserved` |
| IPV4-020 | Broadcast 255.255.255.255 | `http://255.255.255.255/` | `IP address blocked: broadcast` |
| IPV4-021 | Valid public IP | `http://93.184.216.34/` | `valid: true` (example.com IP) |
| IPV4-022 | DNS resolves to blocked IP | `http://localhost/` (resolves to 127.0.0.1) | `IP address blocked: localhost` |
| IPV4-023 | Decimal IP notation | `http://2130706433/` (127.0.0.1 in decimal) | `IP address blocked: localhost` |
| IPV4-024 | Hex IP notation | `http://0x7f000001/` (127.0.0.1 in hex) | `IP address blocked: localhost` |
| IPV4-025 | Octal IP notation | `http://0177.0.0.1/` (127.0.0.1 in octal) | `IP address blocked: localhost` |

### IP Address Blocking - IPv6 (IPV6)

| ID | Scenario | IP | Expected Block Reason |
|----|----------|-------|----------|
| IPV6-001 | IPv6 localhost ::1 | `http://[::1]/` | `IP address blocked: localhost` |
| IPV6-002 | IPv6 unspecified :: | `http://[::]/` | `IP address blocked: unspecified` |
| IPV6-003 | IPv6 unique local fc00::1 | `http://[fc00::1]/` | `IP address blocked: unique local` |
| IPV6-004 | IPv6 unique local fdff:ffff::1 | `http://[fdff:ffff::1]/` | `IP address blocked: unique local` |
| IPV6-005 | IPv6 link-local fe80::1 | `http://[fe80::1]/` | `IP address blocked: link-local` |
| IPV6-006 | IPv6 link-local febf::1 | `http://[febf::1]/` | `IP address blocked: link-local` |
| IPV6-007 | IPv6 multicast ff00::1 | `http://[ff00::1]/` | `IP address blocked: multicast` |
| IPV6-008 | IPv6 multicast ffff::1 | `http://[ffff::1]/` | `IP address blocked: multicast` |
| IPV6-009 | IPv6 IPv4-mapped ::ffff:127.0.0.1 | `http://[::ffff:127.0.0.1]/` | `IP address blocked: localhost` |
| IPV6-010 | IPv6 IPv4-mapped ::ffff:192.168.1.1 | `http://[::ffff:192.168.1.1]/` | `IP address blocked: private network` |
| IPV6-011 | IPv6 full notation | `http://[0000:0000:0000:0000:0000:0000:0000:0001]/` | `IP address blocked: localhost` |
| IPV6-012 | IPv6 compressed notation | `http://[::ffff:7f00:1]/` | `IP address blocked: localhost` |
| IPV6-013 | Valid IPv6 public | `http://[2001:4860:4860::8888]/` | `valid: true` (Google DNS) |
| IPV6-014 | IPv6 with zone ID blocked | `http://[fe80::1%25eth0]/` | `valid: false, error: "URL format invalid"` |
| IPV6-015 | IPv6 resolves via DNS | `http://ipv6.example.com/` (resolves to ::1) | `IP address blocked: localhost` |

### DNS Rebinding Protection (DRP)

| ID | Scenario | Description | Expected |
|----|----------|-------------|----------|
| DRP-001 | DNS resolves before request | Hostname resolves to IP | IP validated against blocklist |
| DRP-002 | DNS cache TTL | Second request within TTL | Uses cached IP resolution |
| DRP-003 | DNS cache expiry | Request after TTL expiry | Re-resolves DNS |
| DRP-004 | First resolution blocked | DNS resolves to 127.0.0.1 | `IP address blocked: localhost` |
| DRP-005 | Redirect to blocked IP | 301 redirect to http://127.0.0.1/ | `Redirect target blocked` |
| DRP-006 | Redirect to blocked domain | 302 redirect to http://localhost/ | `Redirect target blocked` |
| DRP-007 | DNS rebinding attack | First resolve: public IP, second: private | Second request blocked |
| DRP-008 | Multiple A records | Domain resolves to multiple IPs | All IPs checked |
| DRP-009 | CNAME chain | Domain CNAME to blocked domain | `Domain blocked` |
| DRP-010 | Re-validate after redirect | Redirect to new domain | DNS resolution for new domain |
| DRP-011 | DNS resolution failure | Invalid domain (NXDOMAIN) | `DNS resolution failed` |
| DRP-012 | DNS timeout | DNS server timeout | `DNS resolution failed` |

### Domain Blocking (DB)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| DB-001 | Block *.internal | `http://app.internal/` | `Domain blocked` |
| DB-002 | Block *.internal subdomain | `http://api.service.internal/` | `Domain blocked` |
| DB-003 | Block *.local | `http://test.local/` | `Domain blocked` |
| DB-004 | Block *.localhost | `http://api.localhost/` | `Domain blocked` |
| DB-005 | Block wildcard internal.* | `http://internal.company.com/` | `Domain blocked` |
| DB-006 | Allowlist overrides blocklist | Config: allow `['trusted.internal']` | `valid: true` for allowed domain |
| DB-007 | Custom blocklist | Config: block `['blocked.example.com']` | `Domain blocked` |
| DB-008 | Case-insensitive domain block | `http://Internal.Company.COM/` | `Domain blocked` |
| DB-009 | Domain with port | `http://internal:8080/` | `Domain blocked` |
| DB-010 | Valid external domain | `https://google.com/` | `valid: true` |

### Redirect Handling (RH)

| ID | Scenario | Description | Expected |
|----|----------|-------------|----------|
| RH-001 | Valid redirect | 301 to https://example.com/new | Follow, validate new URL |
| RH-002 | Redirect to blocked IP | 302 to http://127.0.0.1/ | `Redirect target blocked` |
| RH-003 | Redirect to blocked domain | 301 to http://internal/ | `Redirect target blocked` |
| RH-004 | Redirect chain limit (5) | 6 consecutive redirects | `Redirect chain too long` |
| RH-005 | Circular redirect | A → B → A | `Redirect loop detected` |
| RH-006 | Meta refresh redirect | `<meta http-equiv="refresh"...>` | Validate redirect URL |
| RH-007 | JavaScript redirect | `window.location = "..."` | Validate redirect URL |
| RH-008 | HTTP to HTTPS redirect | http://example.com → https://example.com | `valid: true` |
| RH-009 | Relative redirect | Location: /new-path | Validate against original host |
| RH-010 | Redirect with credentials | Location: http://user:pass@host/ | `valid: true` (after validation) |
| RH-011 | Redirect tracking | History of all redirects | Included in result |
| RH-012 | Redirect to different protocol | https:// → http:// | `valid: true` (downgrade warning) |
| RH-013 | Cross-domain redirect | example.com → other.com | Validate new domain |
| RH-014 | Redirect with fragment | Location: /new#section | `valid: true` |

### ValidationResult Structure (VRS)

| ID | Scenario | Property | Expected |
|----|----------|----------|----------|
| VRS-001 | Valid URL response | `valid` | `true` |
| VRS-002 | Valid URL response | `url` | Normalized URL |
| VRS-003 | Valid URL response | `originalUrl` | Original input URL |
| VRS-004 | Valid URL response | `host` | Extracted hostname |
| VRS-005 | Valid URL response | `ip` | Resolved IP address |
| VRS-006 | Invalid URL response | `valid` | `false` |
| VRS-007 | Invalid URL response | `error` | Error message string |
| VRS-008 | Blocked URL response | `blocked` | `true` |
| VRS-009 | Blocked URL response | `blockReason` | Block reason string |
| VRS-010 | All fields present | Full response | All required fields populated |

### Configuration (CFG)

| ID | Scenario | Config Key | Expected Default |
|----|----------|------------|------------------|
| CFG-001 | Allowed protocols | `allowed_protocols` | `['http', 'https']` |
| CFG-002 | Blocked IPs | `blocked_ips` | Array of CIDR ranges |
| CFG-003 | Blocked domains | `blocked_domains` | `['*.internal', '*.local', '*.localhost']` |
| CFG-004 | Allowed domains | `allowed_domains` | `[]` (all allowed if empty) |
| CFG-005 | Max redirects | `max_redirects` | `5` |
| CFG-006 | DNS cache TTL | `dns_cache_ttl` | `60` (seconds) |
| CFG-007 | Custom allowed protocols | `allowed_protocols: ['http', 'https', 'ftp']` | FTP allowed |
| CFG-008 | Custom blocked domains | `blocked_domains: ['evil.com']` | Custom domain blocked |

### Error Messages (ERR)

| ID | Scenario | Error Type | Expected Message |
|----|----------|------------|------------------|
| ERR-001 | Blocked protocol | Protocol | "Protocol not allowed" |
| ERR-002 | Malformed URL | Format | "URL format invalid" |
| ERR-003 | Private IP | Private | "IP address blocked: private network" |
| ERR-004 | Localhost | Localhost | "IP address blocked: localhost" |
| ERR-005 | Metadata endpoint | Metadata | "IP address blocked: metadata endpoint" |
| ERR-006 | Blocked domain | Domain | "Domain blocked" |
| ERR-007 | DNS failure | DNS | "DNS resolution failed" |
| ERR-008 | Redirect blocked | Redirect | "Redirect target blocked" |

### Security Edge Cases (SEC)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| SEC-001 | SSRF via localhost hostname | `http://localhost/` | `IP address blocked: localhost` |
| SEC-002 | SSRF via 127.0.0.1 | `http://127.0.0.1/` | `IP address blocked: localhost` |
| SEC-003 | SSRF via [::1] | `http://[::1]/` | `IP address blocked: localhost` |
| SEC-004 | SSRF via 0.0.0.0 | `http://0.0.0.0/` | `IP address blocked: unspecified` |
| SEC-005 | SSRF via cloud metadata | `http://169.254.169.254/latest/meta-data/` | `IP address blocked: metadata endpoint` |
| SEC-006 | SSRF via AWS IMDSv2 | `http://169.254.169.254/latest/api/token` | `IP address blocked: metadata endpoint` |
| SEC-007 | SSRF via GCP metadata | `http://metadata.google.internal/` | `Domain blocked` |
| SEC-008 | SSRF via Azure metadata | `http://169.254.169.254/metadata/instance` | `IP address blocked: metadata endpoint` |
| SEC-009 | SSRF via Kubernetes service | `http://kubernetes.default.svc/` | `Domain blocked` |
| SEC-010 | SSRF via internal service | `http://internal-service/` | `Domain blocked` |
| SEC-011 | SSRF via decimal IP | `http://2130706433/` | `IP address blocked: localhost` |
| SEC-012 | SSRF via hex IP | `http://0x7f000001/` | `IP address blocked: localhost` |
| SEC-013 | SSRF via octal IP | `http://0177.0.0.1/` | `IP address blocked: localhost` |
| SEC-014 | SSRF via mixed notation | `http://0x7f.0.0.1/` | `IP address blocked: localhost` |
| SEC-015 | SSRF via IPv4-mapped IPv6 | `http://[::ffff:127.0.0.1]/` | `IP address blocked: localhost` |
| SEC-016 | SSRF via URL encoding | `http://%31%32%37%2e%30%2e%30%2e%31/` | `IP address blocked: localhost` |
| SEC-017 | SSRF via double encoding | `http://%25%33%31%25%33%32...` | `IP address blocked: localhost` |
| SEC-018 | SSRF via redirect | Initial: valid, Redirect: 127.0.0.1 | `Redirect target blocked` |
| SEC-019 | SSRF via DNS rebinding | First: public, Second: private | Second blocked |
| SEC-020 | Null byte injection | `http://example.com%00@127.0.0.1/` | `URL format invalid` |

### Integration Tests (INT)

| ID | Scenario | Description | Expected |
|----|----------|-------------|----------|
| INT-001 | Full validation pipeline | Valid public URL | All checks pass, result valid |
| INT-002 | Full validation with DNS | Domain resolves to public IP | IP stored in result |
| INT-003 | Multi-IP resolution | Domain has multiple A records | All IPs checked, valid if none blocked |
| INT-004 | Redirect chain following | 3 redirects to final URL | All intermediate URLs validated |
| INT-005 | Config hot-reload | Change config mid-request | New config applies to next request |
| INT-006 | Concurrent requests | 10 parallel validations | No race conditions, all correct |
| INT-007 | Large batch validation | 100 URLs in sequence | Consistent performance |
| INT-008 | Mixed valid/invalid batch | 50 valid, 50 blocked URLs | Correct classification for all |
| INT-009 | Performance baseline | 1000 validations | Complete in < 5 seconds |
| INT-010 | Memory usage | 1000 validations | No memory leaks |

---

## Test Files

| File | Location | Tests |
|------|----------|-------|
| UrlValidatorServiceTest.php | `backend/tests/Unit/Services/Crawler/` | 120 |
| UrlValidatorIntegrationTest.php | `backend/tests/Feature/Services/Crawler/` | 44 |

---

## Mock Data

### Blocked IP Ranges (IPv4)
```php
$blockedRanges = [
    '127.0.0.0/8',      // Localhost
    '10.0.0.0/8',       // Private Class A
    '172.16.0.0/12',    // Private Class B
    '192.168.0.0/16',   // Private Class C
    '169.254.0.0/16',   // Link-local
    '0.0.0.0/8',        // Current network
    '224.0.0.0/4',      // Multicast
    '240.0.0.0/4',      // Reserved
];
```

### Blocked IP Ranges (IPv6)
```php
$blockedRangesV6 = [
    '::1/128',          // Localhost
    '::/128',           // Unspecified
    'fc00::/7',         // Unique local
    'fe80::/10',        // Link-local
    'ff00::/8',         // Multicast
];
```

### Blocked Domains
```php
$blockedDomains = [
    '*.internal',
    '*.local',
    '*.localhost',
    '*.internal.*',
    'metadata.google.internal',
    'kubernetes.default.svc',
];
```

### Valid Test URLs
```php
$validUrls = [
    'https://example.com',
    'https://www.google.com/search?q=test',
    'https://github.com/user/repo',
    'http://93.184.216.34/',  // example.com IP
    'https://xn--r8jz45g.jp', // IDN (punycode)
];
```

### Invalid Test URLs
```php
$invalidUrls = [
    'http://localhost/',
    'http://127.0.0.1/',
    'http://10.0.0.1/',
    'http://192.168.1.1/',
    'http://169.254.169.254/',
    'file:///etc/passwd',
    'ftp://example.com/',
    'javascript:alert(1)',
];
```

---

## Coverage Targets

| Metric | Target | Notes |
|--------|--------|-------|
| Line Coverage | 95%+ | Security-critical code |
| Branch Coverage | 90%+ | All code paths |
| Method Coverage | 100% | All public methods |
| Security Tests | 20+ | All SSRF vectors |

---

## Test Execution

```bash
# Run all URL validator tests
php artisan test --filter=UrlValidator

# Run unit tests only
php artisan test --filter=UrlValidatorServiceTest

# Run integration tests only
php artisan test --filter=UrlValidatorIntegrationTest

# Run with coverage
php artisan test --filter=UrlValidator --coverage
```

---

## Security Considerations

- All SSRF vectors must be tested
- DNS rebinding protection is critical
- IP normalization must handle all formats
- Redirect validation prevents bypass
- Config changes should not allow bypass
- Rate limiting should prevent enumeration

---

## Dependencies

- PHP 8.2+
- Laravel 11.x
- DNS resolution service
- Cache for DNS results

---

## Notes for Developer

1. **Service Location:** `app/Services/Crawler/UrlValidatorService.php`
2. **Config Location:** `config/crawler.php`
3. **Return Type:** `ValidationResult` DTO (array-like)
4. **DNS Resolution:** Must resolve before HTTP request
5. **Redirect Validation:** Validate each redirect target
6. **Logging:** Log all blocked requests for security monitoring
7. **Performance:** DNS cache prevents repeated lookups

---

## Comments
_Leave notes for the next agent here_

### PreTester Start (2026-03-02)

**Test Coverage In Progress**
- Created comprehensive test documentation
- 164 test scenarios covering all acceptance criteria
- Security-focused tests for SSRF protection
- Integration tests for full pipeline validation

**Handoff to Developer:**
- Implement `UrlValidatorService` at `app/Services/Crawler/UrlValidatorService.php`
- Create `ValidationResult` DTO
- Add config to `config/crawler.php`
- Focus on security - this is HIGH risk
