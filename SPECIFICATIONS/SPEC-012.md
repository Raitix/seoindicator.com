# SPEC-012: Google Search Console Integration - Technical Specification

## STATUS
`To Break Into Tasks`

## PRIORITY
`Medium`

## RISK
`High`

## Related
- Feature: [FEAT-012](../FEATURES/FEAT-012.md)
- Tasks: (To be created by SpecSystemAnalyst)
- Enhances: SPEC-001 (add traffic context), SPEC-004 (reports with GSC data)

---

## Overview

Integrate Google Search Console (GSC) to import key metrics like clicks, impressions, CTR, and average position. Users gain actionable traffic insights, connecting ranking data to business outcomes.

---

## Architecture

### System Design
```
┌───────────────────────────────────────────┐
│       Frontend (Next.js)                  │
├───────────────────────────────────────────┤
│   Components:                            │
│   ├── GSCConnectionPage                 │
│   ├── OAuthFlowHandler                  │
│   ├── GSCDataDashboard                  │
│   ├── OpportunityKeywordsView           │
│   └── SearchConsoleTrendChart           │
└───────────────────────────────────────────┘
          │
          ▼
┌───────────────────────────────────────────┐
│        Backend API (Laravel)              │
├───────────────────────────────────────────┤
│    Controllers:                          │
│    ├── GSCConnectionController           │
│    ├── GSCDataController                 │
│    └── OpportunityController             │
├───────────────────────────────────────────┤
│    Services:                             │
│    ├── GSCClient                          │
│    ├── GSCDataSync                        │
│    ├── OpportunityScoreService            │
│    └── OAuthService                       │
└───────────────────────────────────────────┘
          │
          ▼
┌───────────────────────────────────────────┐
│        Database (MySQL)                   │
├───────────────────────────────────────────┤
│   Tables:                                │
│   ├── gsc_connections                     │
│   ├── gsc_data                            │
│   └── keywords (updated for query match)  │
└───────────────────────────────────────────┘
```

---

## Database Schema

### Tables

#### Table: gsc_connections

```sql
CREATE TABLE gsc_connections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    site_url VARCHAR(500) NOT NULL,
    property_url VARCHAR(500) NOT NULL,
    access_token VARCHAR(2000) NOT NULL,
    refresh_token VARCHAR(2000) NOT NULL,
    token_expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY idx_user_property (user_id, property_url)
);
```

#### Table: gsc_data

```sql
CREATE TABLE gsc_data (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    query VARCHAR(255) NOT NULL,
    page VARCHAR(255) NULL,
    clicks INT UNSIGNED NOT NULL DEFAULT 0,
    impressions INT UNSIGNED NOT NULL DEFAULT 0,
    ctr FLOAT UNSIGNED NOT NULL DEFAULT 0,
    avg_position FLOAT UNSIGNED NOT NULL DEFAULT 0,
    data_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_query_date (query, data_date),
    INDEX idx_project_date (project_id, data_date)
);
```

-- Add to `keywords` table:
ALTER TABLE keywords ADD COLUMN gsc_query_match VARCHAR(255) NULL COMMENT 'Fuzzy match to GSC queries';

---

## APIs

### GSC Connection Management

```yaml
# Add GSC connection
POST /api/gsc/connect
Body: { "authorization_code": "abc123" }
Response: { "success": true, "property_url": "https://example.com" }

# Remove GSC connection
DELETE /api/gsc/disconnect/{propertyId}
Response: { "success": true }

# Get connected properties
GET /api/gsc/properties
Response: { "data": [ { "property_url": "https://example.com", "site_url": "example.com" } ] }
```

### GSC Data Retrieval

```yaml
# Get GSC data for a project
GET /api/projects/{projectId}/gsc
Query Params: { "from": "2026-02-01", "to": "2026-02-15" }
Response:
{
    "data": [
        {
            "query": "seo tools",
            "clicks": 100,
            "impressions": 2000,
            "ctr": 5.0,
            "avg_position": 3.2
        }
    ],
    "meta": { "total": 30 }
}

# Get opportunity keywords
GET /api/projects/{projectId}/gsc/opportunities
Response:
{
    "data": [
        {
            "query": "keyword research tool",
            "opportunity_score": 78.5,
            "clicks": 50,
            "impressions": 800,
            "ctr": 6.25,
            "expected_ctr": 8.5
        }
    ]
}
```

---

## Security Considerations

- **OAuth Tokens**: Encrypt tokens and store securely with auto-expiration.
- **Rate Limits**: Respect GSC API rate limits with efficient query batching.
- **User Privacy**: Only fetch data for authenticated and authorized properties.
- **Data Ownership**: User retains ownership; allow deletion of synced data.

---

## Tests

### Unit Tests
- Validate OAuth token handling.
- Validate API query batching logic.

### Integration Tests
- Verify OAuth connection/disconnection flows.
- Test data sync for various properties and rate limits.

### Feature Tests
- Data visibility in dashboards.
- Accurate fuzzy query matching.

---

## Rollout Plan

1. Implement database schemas for GSC connections and data.
2. Develop OAuth connection flow.
3. Implement daily sync jobs for GSC data.
4. Create dashboards to display GSC metrics alongside keyword rankings.
5. Thorough testing for API quotas, performance, and security.

---

## Comments
_Agent communication log_

### [2026-02-20 17:27] Agent: Architect
Created specification SPEC-012 for Google Search Console integration (FEAT-012). Key architectural decisions:
- Secure OAuth token handling with refresh tokens.
- Fuzzy query matching to map GSC data to tracked keywords.
- Opportunity scoring deferred (Phase 2).
Ready for SpecSystemAnalyst to break into tasks.
