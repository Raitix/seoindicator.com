# TASK-23-7: Domain Trust Scoring Service

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Medium`

## Type
Backend

## Description
Create heuristic domain trust scoring service that evaluates backlink quality without paid APIs. Uses domain characteristics to estimate authority score (0-100).

## Related
- Specification: [SPEC-023](../SPECIFICATIONS/SPEC-023.md)
- Feature: [FEAT-023](../FEATURES/FEAT-023.md)
- Depends on: TASK-23-2

---

## Acceptance Criteria
- [ ] `DomainTrustScorer` service class created
- [ ] Calculates score based on HTTPS, TLD, domain age estimation
- [ ] Penalizes known spam TLDs
- [ ] Domain length and pattern analysis
- [ ] Score range: 0-100
- [ ] Handles edge cases (IPs, subdomains, etc.)
- [ ] Unit tests for scoring logic

---

## Technical Details

### Scoring Algorithm
```php
class DomainTrustScorer
{
    /**
     * Calculate domain trust score (0-100)
     */
    public function score(string $domain): int
    {
        $score = 0;
        
        // HTTPS (0-20 points)
        if ($this->supportsHttps($domain)) $score += 20;
        
        // Domain age estimation (0-30 points)
        $score += min(30, $this->estimateAge($domain));
        
        // TLD quality (0-15 points)
        $score += $this->scoreTld($domain);
        
        // Domain length (0-10 points)
        $score += $this->scoreLength($domain);
        
        // Clean pattern (0-10 points)
        if ($this->isCleanPattern($domain)) $score += 10;
        
        // Spam TLD penalty
        if ($this->isSpamTld($domain)) $score = max(0, $score - 30);
        
        return min(100, max(0, $score));
    }
}
```

### Scoring Components
| Component | Points | Criteria |
|-----------|--------|----------|
| HTTPS | 0-20 | Supports HTTPS protocol |
| Age | 0-30 | Estimated domain age (WHOIS, DNS) |
| TLD | 0-15 | .com, .org, .edu, .gov = 15; .io, .co = 10 |
| Length | 0-10 | Shorter = better (≤8 = 10, ≤15 = 5) |
| Pattern | 0-10 | No hyphens, numbers = 10 |
| Spam Penalty | -30 | .xyz, .top, .click, .loan |

### TLD Categories
```php
private const TRUSTED_TLDS = ['com', 'org', 'net', 'edu', 'gov'];
private const MODERATE_TLDS = ['io', 'co', 'dev', 'app'];
private const SPAM_TLDS = ['xyz', 'top', 'click', 'loan', 'work', 'date'];
```

### Domain Age Estimation
- Uses DNS cache lookup for creation date
- Falls back to heuristic (older domains tend to have more backlinks)
- Conservative scoring for unknown domains

---

## Dependencies
- TASK-23-2 (Backlink Models)

## Effort Estimate
`Medium` (3-4 hours)

---

## Comments
_Agent communication log_

### [2026-02-28 23:35] Agent: PreTester
**Test Design Complete**

Created comprehensive test suite for DomainTrustScorer service:

**Test Files Created:**
- `TESTS/TEST-23-7.md` — Test documentation with 52 unit tests + 12 integration tests
- `tests/Unit/Services/SEO/DomainTrustScorerTest.php` — Unit tests (52 tests, 120+ assertions)
- `tests/Integration/Services/SEO/DomainTrustScorerIntegrationTest.php` — Integration tests (12 tests)

**Test Coverage:**
| Category | Tests | Description |
|----------|-------|-------------|
| HTTPS Scoring (HS) | 6 | HTTPS support verification |
| TLD Scoring (TS) | 16 | Trusted/moderate/spam TLDs |
| Length Scoring (LS) | 7 | Domain length analysis |
| Pattern Scoring (PS) | 7 | Clean pattern detection |
| Edge Cases (EC) | 13 | IPs, subdomains, IDN, etc. |
| Score Boundaries (SB) | 3 | 0-100 range enforcement |
| Security (SC) | 5 | Injection/XSS protection |
| Integration | 12 | Real-world scenarios, caching |

**Key Design Decisions:**
1. Score range: 0-100 with min/max capping
2. Spam penalty (-30) cannot result in negative score
3. IP addresses return 0 (no TLD analysis)
4. Domain normalization before scoring (strip path, query, port)
5. IDN domains converted to punycode for analysis

**Status:** Tests ready for implementation. Handing off to Developer.

### [2026-02-28 21:38] Agent: ProjectManager
**Prioritization Complete**

- **Priority Confirmed**: `High` — Core scoring service for backlink quality assessment, critical for feature value
- **Risk**: `Medium` — New algorithm implementation, no external API dependencies (uses heuristics only)

**Dependency Analysis:**
- Depends on: TASK-23-2 (Backlink Models) — Status: `To Write Tests`
- The `ReferringDomain` model stores `domain_trust_score` field that this service populates
- Dependency chain: TASK-23-1 (Done) → TASK-23-2 (To Write Tests) → TASK-23-7 (Trust Scoring)

**Execution Order:**
```
TASK-23-2 (Models) ──→ TASK-23-7 (Trust Scoring) ──→ TASK-23-8 (Profile Health)
```

**Algorithm Components:**
| Component | Points | Implementation |
|-----------|--------|----------------|
| HTTPS | 0-20 | Check SSL certificate |
| Age Estimation | 0-30 | DNS/WHOIS lookup (free) |
| TLD Quality | 0-15 | Static mapping |
| Length | 0-10 | String analysis |
| Pattern | 0-10 | Regex validation |
| Spam Penalty | -30 | Known spam TLD list |

**Free Tools Compliance:** ✅ Uses only heuristic analysis, no paid APIs required.

Handing off to PreTester for test design.
