# SPEC-014: Technical SEO Site Audit - Technical Specification

## STATUS
`To Break Into Tasks`

## PRIORITY
`Low`

## RISK
`Medium`

## Related
- Feature: [FEAT-014](../FEATURES/FEAT-014.md)
- Tasks: (To be created by SpecSystemAnalyst)
- Complements: SPEC-001 (rankings), SPEC-013 (backlinks)

---

## Overview

Automated technical SEO audits crawl tracked domains to identify issues affecting search performance. Detects problems like broken links, missing meta tags, duplicate content, slow pages, mobile issues, and schema errors.

---

## Architecture

### System Design

```
┌───────────────────────────────────────────┐
│        Backend API (Laravel)             │
├───────────────────────────────────────────┤
│   Controllers:                          │
│   ├── AuditController                    │
│   ├── AuditIssueController               │
│   └── AuditScheduleController            │
├───────────────────────────────────────────┤
│   Services:                             │
│   ├── SiteCrawler                        │
│   ├── IssueDetector                      │
│   ├── AuditReportGenerator               │
│   └── RobotsParser                       │
└───────────────────────────────────────────┘
          │
          ▼
┌───────────────────────────────────────────┐
│       Crawler Infrastructure              │
├───────────────────────────────────────────┤
│   Options:                               │
│   • Custom spider (Guzzle + DOM parser)  │
│   • Integration: Screaming Frog API      │
│   • Queue-based processing (Redis)       │
└───────────────────────────────────────────┘
          │
          ▼
┌───────────────────────────────────────────┐
│          Database (MySQL)                 │
├───────────────────────────────────────────┤
│   Tables:                                │
│   ├── audits                              │
│   ├── audit_issues                        │
│   └── issue_types                         │
└───────────────────────────────────────────┘
```

---

## Database Schema

### Tables

#### Table: audits

```sql
CREATE TABLE audits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending',
    pages_crawled INT UNSIGNED DEFAULT 0,
    issues_found INT UNSIGNED DEFAULT 0,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_project_status (project_id, status)
);
```

#### Table: audit_issues

```sql
CREATE TABLE audit_issues (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    audit_id BIGINT UNSIGNED NOT NULL,
    url VARCHAR(2083) NOT NULL,
    issue_type_id BIGINT UNSIGNED NOT NULL,
    severity ENUM('critical', 'warning', 'notice') NOT NULL,
    description TEXT NOT NULL,
    recommendation TEXT NOT NULL,
    status ENUM('open', 'fixed', 'ignored') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE,
    FOREIGN KEY (issue_type_id) REFERENCES issue_types(id),
    INDEX idx_audit_severity (audit_id, severity)
);
```

#### Table: issue_types

```sql
CREATE TABLE issue_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    severity_default ENUM('critical', 'warning', 'notice') NOT NULL,
    recommendation_template TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## API Design

### Audit Management

```yaml
# Trigger new audit
POST /api/projects/{projectId}/audits
Response: { "id": 1, "status": "pending" }

# Get audit status
GET /api/audits/{auditId}
Response:
{
    "id": 1,
    "status": "running",
    "pages_crawled": 150,
    "issues_found": 12,
    "started_at": "2026-02-20T10:00:00"
}

# List issues
GET /api/audits/{auditId}/issues
Query Params: { "severity": "critical", "status": "open" }
Response:
{
    "data": [
        {
            "id": 1,
            "url": "https://example.com/page",
            "issue_type": "Missing title tag",
            "severity": "critical",
            "recommendation": "Add a unique title tag to this page."
        }
    ],
    "meta": { "total": 50 }
}

# Mark issue status
PATCH /api/issues/{issueId}
Body: { "status": "fixed" }
Response: { "id": 1, "status": "fixed" }
```

---

## Issue Detection (MVP)

| Issue Type | Detection | Severity |
|------------|-----------|----------|
| Missing title tag | HTML parsing | Critical |
| Duplicate title | Comparison | Warning |
| Missing meta description | HTML parsing | Warning |
| Missing H1 | HTML parsing | Warning |
| Multiple H1s | HTML parsing | Notice |
| Broken links (404/5xx) | HTTP status | Critical |
| Slow page (>3s) | Load time | Notice |
| Missing viewport | HTML parsing | Warning |
| Images missing alt | HTML parsing | Notice |

---

## Rollout Plan

1. Implement core crawling with robots.txt respect and rate limiting.
2. Build issue detection engine for MVP checks.
3. Create issue management UI with severity filtering.
4. Add audit summary dashboard.
5. Integrate audit results into PDF reports.
6. Phase 2: Scheduled audits, schema validation, re-crawl verification.

---

## Comments
_Agent communication log_

### [2026-02-20 17:40] Agent: Architect
Created specification SPEC-014 for Technical SEO Site Audit (FEAT-014). Key decisions:
- Custom crawler with queue-based processing for scalability.
- MVP focuses on core checks; schema validation deferred.
- Issue severity system enables prioritized fixes.
Ready for SpecSystemAnalyst breakdown.