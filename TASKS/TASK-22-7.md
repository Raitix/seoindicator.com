# TASK-22-7: URL Validation and SSRF Protection

## STATUS
`Prioritization In Progress`

## PRIORITY
`High`

## RISK
`High`

## Related Specification
SPEC-022: Headless Crawler Service (JS-Rendered HTML)

## Related Feature
FEAT-022: Headless Crawler Service (JS-Rendered HTML)

---

## Description
Implement comprehensive URL validation and SSRF (Server-Side Request Forgery) protection to prevent malicious requests to internal resources.

## Acceptance Criteria

### Service Structure
- [ ] Service created at `app/Services/Crawler/UrlValidatorService.php`
- [ ] Method `validate(string $url): ValidationResult` - Main validation entry

### Protocol Validation
- [ ] Allow only http:// and https:// protocols
- [ ] Block file:// protocol
- [ ] Block ftp:// protocol
- [ ] Block data: protocol
- [ ] Block javascript: protocol
- [ ] Block mailto: protocol
- [ ] Block custom protocols

### URL Format Validation
- [ ] Validate URL structure (parse_url)
- [ ] Require host component
- [ ] Validate port (allow 80, 443, custom ports within range)
- [ ] Handle IPv6 URLs
- [ ] Handle IDN (Internationalized Domain Names)
- [ ] Normalize URL (lowercase host, remove default port)

### IP Address Blocking (SSRF Protection)
- [ ] Resolve hostname to IP address(es) before request
- [ ] Block localhost (127.0.0.1, ::1)
- [ ] Block 127.0.0.0/8 range
- [ ] Block 10.0.0.0/8 (Private Class A)
- [ ] Block 172.16.0.0/12 (Private Class B)
- [ ] Block 192.168.0.0/16 (Private Class C)
- [ ] Block 169.254.169.254 (AWS/GCP metadata)
- [ ] Block 169.254.0.0/16 (Link-local)
- [ ] Block 0.0.0.0/8 (Current network)
- [ ] Block 224.0.0.0/4 (Multicast)
- [ ] Block 240.0.0.0/4 (Reserved)
- [ ] Block ::/128 (Unspecified IPv6)
- [ ] Block fc00::/7 (Unique local IPv6)
- [ ] Block fe80::/10 (Link-local IPv6)
- [ ] Block ff00::/8 (Multicast IPv6)

### DNS Rebinding Protection
- [ ] Resolve DNS before browser navigation
- [ ] Cache DNS resolution for short duration
- [ ] Re-validate IP after redirects
- [ ] Block domains that resolve to blocked IPs

### Domain Blocking
- [ ] Block *.internal domains
- [ ] Block *.local domains
- [ ] Block *.localhost domains
- [ ] Block *.internal.* wildcard
- [ ] Configurable allowlist/blocklist

### Redirect Handling
- [ ] Validate redirect targets
- [ ] Block redirects to blocked IPs
- [ ] Block redirects to blocked domains
- [ ] Limit redirect chain (max 5)
- [ ] Track redirect history

### ValidationResult Structure
```php
[
    'valid' => bool,
    'url' => string, // Normalized URL
    'originalUrl' => string,
    'host' => string,
    'ip' => string|null,
    'error' => string|null,
    'blocked' => bool,
    'blockReason' => string|null,
]
```

### Configuration
- [ ] Config values in config/crawler.php:
  - [ ] `allowed_protocols` - ['http', 'https']
  - [ ] `blocked_ips` - array of CIDR ranges
  - [ ] `blocked_domains` - array of domain patterns
  - [ ] `allowed_domains` - array (empty = all allowed)
  - [ ] `max_redirects` - int (default 5)
  - [ ] `dns_cache_ttl` - int (seconds, default 60)

### Error Messages
- [ ] "Protocol not allowed"
- [ ] "URL format invalid"
- [ ] "IP address blocked: private network"
- [ ] "IP address blocked: localhost"
- [ ] "IP address blocked: metadata endpoint"
- [ ] "Domain blocked"
- [ ] "DNS resolution failed"
- [ ] "Redirect target blocked"

### Tests
- [ ] Test valid URLs pass
- [ ] Test blocked protocols fail
- [ ] Test blocked IPs fail
- [ ] Test DNS rebinding protection
- [ ] Test domain blocking
- [ ] Test redirect validation
- [ ] Test IDN URLs
- [ ] Test IPv6 URLs

## Dependencies
- None (reuses patterns from TASK-16-1 if available)

## Effort Estimate
`Medium` (3-4 hours)

## Notes
- This is CRITICAL for security - do not skip any checks
- Consider using existing SSRF protection from TASK-16-1
- Log all blocked requests for security monitoring
- Consider rate limiting validation failures

## Security Note
This task is marked HIGH risk because SSRF vulnerabilities can lead to:
- Access to internal services
- Cloud metadata exposure
- Port scanning
- Data exfiltration

## Comments
_Leave notes for the next agent here_

### ProjectManager Prioritization (2026-03-02)

**Priority Assessment:**
- **Priority: High** (confirmed) - Security-critical infrastructure for SSRF protection
- **Risk: High** (confirmed) - SSRF vulnerabilities can lead to data breaches, internal network access

**Rationale:**
1. SSRF protection is mandatory before any external URL requests can be made safely
2. No dependencies - can be developed independently (TASK-16-1 patterns available but not required)
3. Foundation security service that enables TASK-22-11 (API), TASK-22-12 (Validation), TASK-22-15 (Worker)
4. Comprehensive scope: 50+ acceptance criteria covering protocol validation, IP blocking, DNS rebinding, redirect handling

**Dependencies Check:**
- TASK-16-1 (SSRF patterns): Done ✅ - Reference only, not blocking
- No hard dependencies - standalone security service

**Execution Order:**
- This task can proceed immediately
- Downstream tasks (22-11, 22-12, 22-15) depend on this for URL validation

**Handoff to PreTester:**
- Design comprehensive test suite for SSRF protection
- Test all blocked IP ranges (localhost, private networks, metadata endpoints)
- Test protocol blocking, DNS rebinding protection, redirect validation
- Security-focused tests are critical
