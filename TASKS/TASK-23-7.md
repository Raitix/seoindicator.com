# TASK-23-7: Domain Trust Scoring Service

## STATUS
`To Prioritize`

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
