# SPEC-013: Backlink Monitoring - Technical Specification

## STATUS
`Done`

## PRIORITY
`Medium`

## RISK
`Medium`

## Related
- Feature: [FEAT-013](../FEATURES/FEAT-013.md)
- Tasks: (To be created by SpecSystemAnalyst)
- Complements: SPEC-001 (rankings), SPEC-003 (competitor comparison)

---

## Overview

Backlink monitoring tracks new, lost, and existing links to users' tracked domains with essential metrics like domain authority, link type, and anchor text. This data helps users improve off-page SEO and maintain their link profile.

---

## Architecture

### System Design

```
┌───────────────────────────────────────────┐
│        Backend API (Laravel)             │
├───────────────────────────────────────────┤
│   Controllers:                          │
│   ├── BacklinkController                 │
│   ├── BacklinkDiscoveryController        │
│   └── DisavowFileController              │
├───────────────────────────────────────────┤
│   Services:                             │
│   ├── BacklinkService                    │
│   ├── ToxicLinkDetector                  │
│   ├── WeeklySyncService                  │
│   └── CompetitorAnalysisService          │
└───────────────────────────────────────────┘
          │
          ▼
┌───────────────────────────────────────────┐
│       Backlink Data Provider API          │
├───────────────────────────────────────────┤
│   • Initial Provider: DataForSEO          │
│   • Other Options: Moz API, Ahrefs API    │
└───────────────────────────────────────────┘
          │
          ▼
┌───────────────────────────────────────────┐
│          Database (MySQL)                 │
├───────────────────────────────────────────┤
│   Tables:                                │
│   ├── backlinks                           │
│   ├── backlink_summaries                  │
│   ├── backlink_changes                    │
└───────────────────────────────────────────┘
```

---

## Database Schema

### Tables

#### Table: backlink_summaries

```sql
CREATE TABLE backlink_summaries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    domain VARCHAR(255) NOT NULL,
    total_backlinks INT UNSIGNED DEFAULT 0,
    referring_domains INT UNSIGNED DEFAULT 0,
    domain_authority FLOAT UNSIGNED DEFAULT 0,
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    UNIQUE KEY idx_project_domain (project_id, domain)
);
```

#### Table: backlinks

```sql
CREATE TABLE backlinks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    source_url VARCHAR(2083) NOT NULL,
    target_url VARCHAR(2083) NOT NULL,
    anchor_text VARCHAR(255) NOT NULL,
    link_type ENUM('dofollow', 'nofollow') DEFAULT 'dofollow',
    domain_authority FLOAT UNSIGNED DEFAULT 0,
    page_authority FLOAT UNSIGNED DEFAULT 0,
    first_seen DATE NOT NULL,
    last_checked DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_toxic BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_source_target (source_url, target_url),
    INDEX idx_project_active (project_id, is_active)
);
```

#### Table: backlink_changes

```sql
CREATE TABLE backlink_changes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    backlink_id BIGINT UNSIGNED NOT NULL,
    change_type ENUM('new', 'lost', 'modified') NOT NULL,
    details JSON NOT NULL,
    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (backlink_id) REFERENCES backlinks(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_project_change (project_id, change_type)
);
```

---

## API Design

### Backlink Management

```yaml
# Fetch backlink summary
GET /api/projects/{projectId}/backlink-summary
Response: {
    "total_backlinks": 1500,
    "referring_domains": 200,
    "domain_authority": 48.5,
    "checked_at": "2026-02-20T10:00:00"
}

# List backlinks
GET /api/projects/{projectId}/backlinks
Query Params: { "filter": { "is_active": true, "link_type": "dofollow" }, "sort": "domain_authority desc" }
Response:
{
    "data": [
        {
            "id": 1,
            "source_url": "https://example.com/article",
            "anchor_text": "SEO Tools",
            "domain_authority": 50,
            "link_type": "dofollow",
            "first_seen": "2026-01-01",
            "last_checked": "2026-02-15"
        }
    ],
    "meta": { "total": 300 }
}

# Mark backlink as toxic
PATCH /api/backlinks/{id}/mark-toxic
Body: { "is_toxic": true }
Response: { "id": 1, "is_toxic": true }
```

---

## Rollout Plan

1. Select DataForSEO for initial API integration, balance cost/value for further scaling.
2. Focus on MVP scope:
   - Weekly backlink sync
   - Backlink detail view with basic filtering
   - New/lost backlink detection
3. Phase 2: Competitor comparisons, disavow file generation, and advanced toxic link detection.
4. Optimize data pulls and storage for scalability.
5. Fully test sync, UI, and PDF export functions.

---

## Comments
_Agent communication log_

### [2026-02-20 17:35] Agent: Architect
Created specification SPEC-013 for Backlink Monitoring (FEAT-013). Key decisions:
- DataForSEO selected for MVP API integration.
- Architecture supports future competitor comparisons and disavow generation.
- Focused on MVP scope to balance costs while delivering core value.
Ready for SpecSystemAnalyst breakdown.

### [2026-02-28 21:38 GMT+2] DocSystemAnalyst: Specification Complete
All tasks completed:
- TASK-13-1: Backlink Summaries Table (Done)
- TASK-13-2: Backlinks Table (Done)
- TASK-13-3: Backlink Changes Table (Done)

Database schema fully implemented and deployed to production. Documentation updated in DOCUMENTATION.md.

**Status:** Done