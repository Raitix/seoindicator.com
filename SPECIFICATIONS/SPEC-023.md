# SPEC-023: Backlinks Indicator - Technical Specification

## STATUS
`To Break Into Tasks`

## PRIORITY
`High`

## RISK
`High`

## Related
- Feature: [FEAT-023](../FEATURES/FEAT-023.md)
- Tasks: (To be created by SpecSystemAnalyst)
- Integrates with: SPEC-010 (Visibility Scores), SPEC-012 (GSC Integration)

---

## Overview

Introduce a Backlinks indicator that discovers backlinks pointing to the user's site, evaluates quality signals, and integrates with the overall SEO score. Users gain visibility into their backlink profile health and actionable suggestions for improvement.

---

## Architecture

### System Design
```
┌───────────────────────────────────────────────────────────┐
│                    Frontend (Next.js)                     │
├───────────────────────────────────────────────────────────┤
│   Components:                                             │
│   ├── BacklinksDashboard                                │
│   ├── BacklinkList                                      │
│   ├── BacklinkQualityScore                              │
│   ├── ReferringDomainsChart                             │
│   ├── AnchorTextDistribution                            │
│   ├── TopLinkedPagesTable                               │
│   └── BacklinkGuidePage                                 │
└───────────────────────────────────────────────────────────┘
                            │
                            ▼
┌───────────────────────────────────────────────────────────┐
│                   Backend API (Laravel)                   │
├───────────────────────────────────────────────────────────┤
│    Controllers:                                           │
│    ├── BacklinkController                                │
│    ├── BacklinkSyncController                            │
│    └── BacklinkGuideController                           │
├───────────────────────────────────────────────────────────┤
│    Services:                                              │
│    ├── BacklinkFetcherService                            │
│    ├── BacklinkScorerService                             │
│    ├── BacklinkSuggestionService                         │
│    └── GSCBacklinkAdapter (reuses GSC OAuth)             │
└───────────────────────────────────────────────────────────┘
                            │
                            ▼
┌───────────────────────────────────────────────────────────┐
│                    Database (MySQL)                       │
├───────────────────────────────────────────────────────────┤
│   Tables:                                                 │
│   ├── backlinks                                          │
│   ├── referring_domains                                  │
│   ├── anchor_texts                                      │
│   └── backlink_snapshots (historical)                   │
└───────────────────────────────────────────────────────────┘
                            │
                            ▼
┌───────────────────────────────────────────────────────────┐
│               External Data Sources (FREE)                │
├───────────────────────────────────────────────────────────┤
│   Primary: Google Search Console API (links endpoint)    │
│   - Already have OAuth integration (FEAT-012)            │
│   - FREE with existing GSC connection                    │
│   - Shows backlinks Google knows about                   │
│                                                          │
│   Future (optional, if ToS permits):                     │
│   - Mozscape API (free tier: 1 req/10s)                 │
│   - Provides: Domain Authority for quality scoring       │
└───────────────────────────────────────────────────────────┘
```

---

## Data Source Strategy (FREE Tools Only)

### Primary Source: Google Search Console API

**Rationale:**
- Already have OAuth integration from FEAT-012
- Completely FREE with no usage limits beyond standard API quotas
- Official Google data - most accurate for discovered backlinks
- No ToS concerns (official API)

**GSC Links API Endpoints:**
```
GET https://www.googleapis.com/webmasters/v3/sites/{siteUrl}/links
```

**Available Data:**
| Data Point | Description |
|------------|-------------|
| `sites` | Referring domains linking to the site |
| `pages` | Specific URLs linking to the site |
| `internalLinks` | Internal link structure |
| `sampleUrls` | Sample backlink URLs per domain |

**Limitations:**
- Only shows backlinks Google has discovered
- No Domain Authority/Page Authority metrics
- No anchor text for all links (limited sample)
- Cannot see competitor backlinks

### Quality Scoring Approach (No External APIs)

Since we can't use paid APIs for DA/PA, we'll use heuristics:

| Quality Factor | Calculation Method |
|----------------|-------------------|
| **Domain Trust Score** | Based on: domain age (WHOIS), TLD type, HTTPS status |
| **Link Diversity** | Ratio of referring domains to total backlinks |
| **Link Growth Rate** | Month-over-month change in backlink count |
| **Anchor Text Health** | Percentage of branded vs. exact-match anchors |
| **Top Linked Pages** | Distribution of links across pages (not all to homepage) |

---

## Database Schema

### Table: backlinks

```sql
CREATE TABLE backlinks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    source_url VARCHAR(2083) NOT NULL,
    source_domain VARCHAR(255) NOT NULL,
    target_url VARCHAR(2083) NOT NULL,
    anchor_text VARCHAR(500) NULL,
    link_type ENUM('dofollow', 'nofollow', 'unknown') DEFAULT 'unknown',
    first_seen_at TIMESTAMP NULL,
    last_checked_at TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_project_domain (project_id, source_domain),
    INDEX idx_source_url (source_url(255)),
    INDEX idx_target_url (target_url(255)),
    INDEX idx_is_active (is_active)
);
```

### Table: referring_domains

```sql
CREATE TABLE referring_domains (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    domain VARCHAR(255) NOT NULL,
    backlinks_count INT UNSIGNED DEFAULT 0,
    domain_trust_score TINYINT UNSIGNED DEFAULT 0, -- 0-100 heuristic
    first_seen_at TIMESTAMP NULL,
    last_seen_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    UNIQUE KEY idx_project_domain (project_id, domain),
    INDEX idx_backlinks_count (backlinks_count DESC)
);
```

### Table: backlink_snapshots

```sql
CREATE TABLE backlink_snapshots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    total_backlinks INT UNSIGNED DEFAULT 0,
    referring_domains_count INT UNSIGNED DEFAULT 0,
    dofollow_count INT UNSIGNED DEFAULT 0,
    nofollow_count INT UNSIGNED DEFAULT 0,
    avg_domain_trust DECIMAL(5,2) DEFAULT 0,
    anchor_diversity_score DECIMAL(5,2) DEFAULT 0,
    snapshot_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    UNIQUE KEY idx_project_date (project_id, snapshot_date),
    INDEX idx_snapshot_date (snapshot_date)
);
```

### Table: anchor_texts

```sql
CREATE TABLE anchor_texts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    anchor_text VARCHAR(500) NOT NULL,
    backlinks_count INT UNSIGNED DEFAULT 0,
    is_branded TINYINT(1) DEFAULT 0,
    is_exact_match TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_project_anchor (project_id, anchor_text(255)),
    INDEX idx_branded (is_branded)
);
```

---

## APIs

### Backlink Endpoints

```yaml
# Get backlinks overview
GET /api/v1/projects/{projectId}/backlinks
Response:
{
  "success": true,
  "data": {
    "summary": {
      "totalBacklinks": 1250,
      "referringDomains": 89,
      "dofollowRatio": 0.72,
      "avgDomainTrust": 45.2,
      "growthRate": 12.5
    },
    "recentBacklinks": [...],
    "topReferringDomains": [...]
  }
}

# Get referring domains
GET /api/v1/projects/{projectId}/backlinks/domains
Query Params: { "page": 1, "perPage": 20, "sort": "backlinks_count|domain_trust_score" }
Response:
{
  "data": [
    {
      "domain": "example.com",
      "backlinksCount": 45,
      "domainTrustScore": 72,
      "firstSeenAt": "2026-01-15T10:00:00Z"
    }
  ],
  "meta": { "total": 89, "page": 1, "perPage": 20 }
}

# Get anchor text distribution
GET /api/v1/projects/{projectId}/backlinks/anchors
Response:
{
  "data": [
    { "anchorText": "seo tools", "count": 150, "percentage": 12.0, "isBranded": false },
    { "anchorText": "Brand Name", "count": 280, "percentage": 22.4, "isBranded": true }
  ]
}

# Get backlink history (for charts)
GET /api/v1/projects/{projectId}/backlinks/history
Query Params: { "from": "2026-01-01", "to": "2026-02-28" }
Response:
{
  "data": [
    { "date": "2026-01-01", "backlinks": 1100, "domains": 75 },
    { "date": "2026-02-01", "backlinks": 1250, "domains": 89 }
  ]
}

# Trigger backlink sync
POST /api/v1/projects/{projectId}/backlinks/sync
Response: { "jobId": "backlink-sync-abc123", "status": "queued" }

# Get guide page content
GET /api/v1/backlinks/guide
Response: { "content": "...markdown content..." }
```

---

## Backlink Quality Scoring Algorithm

### Domain Trust Score (Heuristic)

```php
/**
 * Calculate domain trust score (0-100) based on heuristics
 * No external APIs required
 */
function calculateDomainTrustScore(string $domain): int
{
    $score = 0;
    
    // HTTPS (0-20 points)
    if (str_starts_with($domain, 'https://')) $score += 20;
    
    // Domain age estimation via pattern (0-30 points)
    // Older domains tend to be more trustworthy
    $ageScore = estimateDomainAge($domain); // Uses DNS/cache lookup
    $score += min(30, $ageScore);
    
    // TLD quality (0-15 points)
    $tld = extractTld($domain);
    if (in_array($tld, ['com', 'org', 'net', 'edu', 'gov'])) $score += 15;
    elseif (in_array($tld, ['io', 'co'])) $score += 10;
    
    // Domain length (0-10 points) - shorter often more established
    $domainLength = strlen(preg_replace('/\.(com|org|net).*$/', '', $domain));
    if ($domainLength <= 8) $score += 10;
    elseif ($domainLength <= 15) $score += 5;
    
    // No hyphens/numbers (0-10 points)
    if (!preg_match('/[-0-9]/', $domain)) $score += 10;
    
    // Known spam TLDs penalty
    if (in_array($tld, ['xyz', 'top', 'click', 'loan'])) $score = max(0, $score - 30);
    
    return min(100, max(0, $score));
}
```

### Backlink Profile Health Score

```php
/**
 * Overall backlink profile health score (0-100)
 */
function calculateBacklinkProfileScore(Project $project): float
{
    $metrics = $project->backlinkMetrics;
    
    // Diversity Score (0-25 points)
    $diversityScore = min(25, 
        ($metrics->referring_domains / max(1, $metrics->total_backlinks)) * 100
    );
    
    // Trust Score (0-25 points)
    $trustScore = min(25, $metrics->avg_domain_trust / 4);
    
    // Growth Score (0-25 points)
    $growthScore = min(25, max(0, 12.5 + ($metrics->growth_rate / 2)));
    
    // Anchor Health (0-25 points)
    $anchorHealth = min(25, 
        25 - ($metrics->exact_match_anchor_ratio * 50)
    );
    
    return $diversityScore + $trustScore + $growthScore + $anchorHealth;
}
```

---

## Integration with Visibility Scores (FEAT-010)

The backlink indicator will contribute to the overall visibility score:

```php
// In VisibilityScoreService
protected function calculateBacklinkComponent(Project $project): float
{
    $backlinkScore = $project->backlinkProfileScore(); // 0-100
    
    // Weight: 20% of overall visibility score
    return $backlinkScore * 0.20;
}
```

---

## Suggestions Engine

### Quality Improvement Suggestions

| Trigger | Suggestion |
|---------|------------|
| Low referring domain count | "Build more referring domains. Guest posting and content marketing can help." |
| High exact-match anchor % | "Diversify anchor text. Too many exact-match anchors can appear manipulative." |
| Low domain trust average | "Focus on earning links from higher-authority sites in your niche." |
| Declining backlink count | "Check for lost backlinks. Some may be recoverable with outreach." |
| Homepage-only links | "Create link-worthy content beyond your homepage to diversify link targets." |

---

## Security Considerations

| Concern | Mitigation |
|---------|------------|
| **OAuth Token Security** | Reuses existing GSC OAuth from FEAT-012, already encrypted |
| **API Rate Limits** | GSC API has generous limits; implement request throttling |
| **Data Privacy** | Backlink data is public; no PII concerns |
| **Malicious URLs** | Sanitize all source/target URLs before storage |
| **SQL Injection** | Eloquent ORM with parameterized queries |

---

## Risk Assessment

| Risk | Level | Mitigation |
|------|-------|------------|
| **GSC API Changes** | Medium | Abstract backlink fetching behind interface |
| **Limited Backlink Data** | Medium | GSC shows discovered links only; educate users |
| **No DA/PA Metrics** | Low | Heuristic scoring provides alternative quality signals |
| **Rate Limiting** | Low | GSC API has generous quotas; implement caching |

---

## Tests

### Unit Tests
- Domain trust score calculation accuracy
- Backlink profile health score calculation
- Anchor text classification (branded/exact-match)
- Growth rate calculation

### Integration Tests
- GSC backlink sync from connected properties
- Snapshot creation and historical tracking
- Visibility score integration

### Feature Tests
- API endpoint responses
- Authorization (user owns project)
- Guide page content delivery

---

## Rollout Plan

### Phase 1: Core Infrastructure
1. Database migrations (backlinks, referring_domains, anchor_texts, backlink_snapshots)
2. Model and repository classes
3. GSC backlink fetcher service

### Phase 2: Data Sync
4. Backlink sync command/job
5. Scheduled daily sync
6. Historical snapshot creation

### Phase 3: Scoring & API
7. Domain trust scoring service
8. Backlink profile health scoring
9. REST API endpoints

### Phase 4: Frontend & Integration
10. Backlink dashboard components
11. Charts and visualizations
12. Integration with visibility score
13. Guide page creation

---

## Comments
_Agent communication log_

### [2026-02-28 10:55] Agent: Architect
Created specification SPEC-023 for Backlinks Indicator (FEAT-023).

**Key Architectural Decisions:**
1. **FREE Tools Only**: Uses Google Search Console API (already integrated) as primary data source - no paid APIs required.
2. **Heuristic Quality Scoring**: Since we can't use paid DA/PA APIs, developed domain trust scoring algorithm based on domain characteristics.
3. **Reuses Existing OAuth**: Leverages GSC connection from FEAT-012, no new OAuth flow needed.
4. **Visibility Score Integration**: Backlink component contributes 20% to overall SEO visibility score.

**Risk Reassessment**: Confirmed HIGH risk due to:
- External API dependency (GSC)
- Data completeness limitations (only discovered backlinks)
- No authority metrics from paid tools

Ready for SpecSystemAnalyst to break into tasks.
