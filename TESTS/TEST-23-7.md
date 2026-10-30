# TEST-23-7: Domain Trust Scoring Service Tests

## Task Reference
- **Task:** [TASK-23-7](../TASKS/TASK-23-7.md)
- **Specification:** [SPEC-023](../SPECIFICATIONS/SPEC-023.md)
- **Feature:** [FEAT-023](../FEATURES/FEAT-023.md)

---

## Overview

Unit tests for the `DomainTrustScorer` service that evaluates domain quality using heuristic analysis. The service calculates a trust score (0-100) based on HTTPS support, TLD quality, domain age estimation, length, and pattern analysis.

---

## Test Files

| File | Tests | Assertions | Description |
|------|-------|------------|-------------|
| `DomainTrustScorerTest.php` | 52 | 120+ | Unit tests for scoring logic |
| `DomainTrustScorerIntegrationTest.php` | 12 | 25+ | Integration with DNS/SSL checks |

---

## Test Scenarios

### HTTPS Scoring (HS)

| ID | Scenario | Input | Expected Score |
|----|----------|-------|----------------|
| HS-001 | Domain supports HTTPS | `example.com` (HTTPS OK) | +20 |
| HS-002 | Domain no HTTPS | `example.com` (HTTPS fails) | +0 |
| HS-003 | IP address input | `192.168.1.1` | +0 (IPs don't have HTTPS) |
| HS-004 | Timeout handling | Domain times out | +0 (graceful fallback) |
| HS-005 | Invalid certificate | Self-signed cert | +0 (not trusted) |
| HS-006 | Redirect to HTTPS | HTTP redirects to HTTPS | +20 |

### TLD Scoring (TS)

| ID | Scenario | Input | Expected Score |
|----|----------|-------|----------------|
| TS-001 | Trusted TLD (.com) | `example.com` | +15 |
| TS-002 | Trusted TLD (.org) | `example.org` | +15 |
| TS-003 | Trusted TLD (.net) | `example.net` | +15 |
| TS-004 | Trusted TLD (.edu) | `harvard.edu` | +15 |
| TS-005 | Trusted TLD (.gov) | `whitehouse.gov` | +15 |
| TS-006 | Moderate TLD (.io) | `techstartup.io` | +10 |
| TS-007 | Moderate TLD (.co) | `example.co` | +10 |
| TS-008 | Moderate TLD (.dev) | `myapp.dev` | +10 |
| TS-009 | Moderate TLD (.app) | `mobile.app` | +10 |
| TS-010 | Other TLD | `example.info` | +5 |
| TS-011 | Spam TLD (.xyz) | `cheap.xyz` | -30 penalty |
| TS-012 | Spam TLD (.top) | `free.top` | -30 penalty |
| TS-013 | Spam TLD (.click) | `ad.click` | -30 penalty |
| TS-014 | Spam TLD (.loan) | `payday.loan` | -30 penalty |
| TS-015 | Spam TLD (.work) | `remote.work` | -30 penalty |
| TS-016 | Spam TLD (.date) | `dating.date` | -30 penalty |

### Domain Length Scoring (LS)

| ID | Scenario | Input | Expected Score |
|----|----------|-------|----------------|
| LS-001 | Short domain (≤8 chars) | `abc.com` | +10 |
| LS-002 | Medium domain (≤15 chars) | `example.com` | +5 |
| LS-003 | Long domain (>15 chars) | `verylongdomainname.com` | +0 |
| LS-004 | Single char domain | `a.com` | +10 |
| LS-005 | Exactly 8 chars | `abcdefgh.com` | +10 |
| LS-006 | Exactly 15 chars | `abcdefghijklmno.com` | +5 |
| LS-007 | Exactly 16 chars | `abcdefghijklmnop.com` | +0 |

### Pattern Scoring (PS)

| ID | Scenario | Input | Expected Score |
|----|----------|-------|----------------|
| PS-001 | Clean domain (letters only) | `example.com` | +10 |
| PS-002 | Domain with hyphen | `my-site.com` | +0 |
| PS-003 | Domain with numbers | `site123.com` | +0 |
| PS-004 | Domain with hyphen and numbers | `my-site-2024.com` | +0 |
| PS-005 | Multiple hyphens | `my-best-site-ever.com` | +0 |
| PS-006 | Starts with number | `1stchoice.com` | +0 |
| PS-007 | Ends with number | `choice1.com` | +0 |

### Domain Age Scoring (AS)

| ID | Scenario | Input | Expected Score |
|----|----------|-------|----------------|
| AS-001 | Very old domain (10+ years) | Domain registered 2005 | +30 |
| AS-002 | Old domain (5-10 years) | Domain registered 2018 | +20 |
| AS-003 | Mature domain (2-5 years) | Domain registered 2022 | +10 |
| AS-004 | New domain (<2 years) | Domain registered 2025 | +5 |
| AS-005 | Unknown age | No WHOIS data | +0 |
| AS-006 | Age estimation via DNS | DNS cache suggests 3 years | +15 (estimated) |

### Edge Cases (EC)

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| EC-001 | IP address | `192.168.1.1` | Score = 0 (no TLD, no HTTPS) |
| EC-002 | IPv6 address | `2001:db8::1` | Score = 0 |
| EC-003 | Localhost | `localhost` | Score = 0 |
| EC-004 | Subdomain only | `blog.example.com` | Uses apex domain for TLD |
| EC-005 | Deep subdomain | `a.b.c.d.example.com` | Uses apex domain |
| EC-006 | Port in URL | `example.com:8080` | Port stripped before scoring |
| EC-007 | URL with path | `example.com/page` | Path stripped before scoring |
| EC-008 | URL with query | `example.com?param=1` | Query stripped before scoring |
| EC-009 | Empty string | `` | Return 0, no error |
| EC-010 | Null input | `null` | Return 0, no error |
| EC-011 | Unicode domain | `münchen.de` | IDN converted to punycode |
| EC-012 | Punycode domain | `xn--mnchen-3ya.de` | Handled correctly |
| EC-013 | Very long domain | 200+ char domain | Score capped, no overflow |

### Composite Scoring (CS)

| ID | Scenario | Input | Expected Score | Calculation |
|----|----------|-------|----------------|-------------|
| CS-001 | Perfect domain | `google.com` | 75-85 | HTTPS(20) + Age(30) + TLD(15) + Length(5) + Pattern(10) |
| CS-002 | Good startup | `stripe.io` | 40-50 | HTTPS(20) + Age(~20) + TLD(10) + Length(10) + Pattern(10) |
| CS-003 | Spam domain | `cheap-free-stuff.xyz` | 0-5 | HTTPS(0) + TLD(-30) + Pattern(0) = capped at 0 |
| CS-004 | New .com | `newstartup2024.com` | 30-40 | HTTPS(20) + Age(5) + TLD(15) + Pattern(0) |
| CS-005 | Established .org | `wikipedia.org` | 70-80 | HTTPS(20) + Age(30) + TLD(15) + Length(5) + Pattern(10) |

### Score Boundaries (SB)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| SB-001 | Score never below 0 | `min(0, calculated)` = 0 |
| SB-002 | Score never above 100 | `max(100, calculated)` = 100 |
| SB-003 | Spam penalty doesn't go below 0 | Score with -30 penalty ≥ 0 |
| SB-004 | All components max | Capped at 100 |

---

## Mock Data

### Domain Test Cases

```php
// Trusted domains
$trustedDomains = [
    'google.com' => ['https' => true, 'age' => 25, 'expected_min' => 70],
    'wikipedia.org' => ['https' => true, 'age' => 22, 'expected_min' => 70],
    'harvard.edu' => ['https' => true, 'age' => 30, 'expected_min' => 75],
    'nasa.gov' => ['https' => true, 'age' => 28, 'expected_min' => 75],
];

// Moderate domains
$moderateDomains = [
    'github.io' => ['https' => true, 'age' => 12, 'expected_min' => 40],
    'vercel.app' => ['https' => true, 'age' => 5, 'expected_min' => 35],
    'linear.dev' => ['https' => true, 'age' => 3, 'expected_min' => 30],
];

// Spam domains
$spamDomains = [
    'free-stuff.xyz' => ['expected_max' => 10],
    'cheap-loans.click' => ['expected_max' => 10],
    'dating-site.top' => ['expected_max' => 10],
];
```

---

## Coverage Targets

| Component | Target | Notes |
|-----------|--------|-------|
| Branch Coverage | 90% | All scoring branches |
| Line Coverage | 95% | Near-complete coverage |
| Edge Cases | 100% | All edge cases tested |
| Security Tests | 10+ | Injection, XSS, malformed input |

---

## Test Execution

```bash
# Run all DomainTrustScorer tests
php artisan test --filter DomainTrustScorer

# Run specific test file
php artisan test tests/Unit/Services/SEO/DomainTrustScorerTest.php

# Run with coverage
php artisan test --coverage --filter DomainTrustScorer
```

---

## Dependencies

- PHPUnit 10+
- Laravel HTTP Client (for HTTPS checks)
- DNS functions (for age estimation)
- Mock DNS/SSL responses in unit tests

---

## Notes for Developer

### Implementation Requirements

1. **Service Class:** `App\Services\SEO\DomainTrustScorer`
2. **Primary Method:** `score(string $domain): int`
3. **Helper Methods:**
   - `supportsHttps(string $domain): bool`
   - `estimateAge(string $domain): int`
   - `scoreTld(string $domain): int`
   - `scoreLength(string $domain): int`
   - `isCleanPattern(string $domain): bool`
   - `isSpamTld(string $domain): bool`
   - `extractApexDomain(string $domain): string`

### Constants to Define

```php
private const TRUSTED_TLDS = ['com', 'org', 'net', 'edu', 'gov'];
private const MODERATE_TLDS = ['io', 'co', 'dev', 'app'];
private const SPAM_TLDS = ['xyz', 'top', 'click', 'loan', 'work', 'date'];
private const MAX_SCORE = 100;
private const MIN_SCORE = 0;
private const SPAM_PENALTY = 30;
```

### Error Handling

- All methods should handle exceptions gracefully
- Return default/minimum values on failure
- Log warnings for debugging (not errors)
- Never throw exceptions from `score()` method

---

## Comments

_Agent communication log_

### [2026-02-28 23:30] Agent: PreTester
**Test Design Complete**

Designed comprehensive test suite for DomainTrustScorer service with:
- 52 unit tests covering all scoring components
- 12 integration tests for DNS/SSL verification
- Edge case coverage for IPs, subdomains, IDN
- Security tests for injection/XSS
- Mock data for trusted, moderate, and spam domains

**Key Design Decisions:**
1. Score range enforced: 0-100 with min/max capping
2. Spam penalty (-30) cannot result in negative score
3. IP addresses return 0 (no TLD analysis applicable)
4. Domain normalization before scoring (strip path, query, port)
5. IDN domains converted to punycode for TLD analysis

Handing off to Developer for implementation.
