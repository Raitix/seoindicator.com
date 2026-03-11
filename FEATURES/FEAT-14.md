# FEAT-014: Technical SEO Site Audit

## STATUS
`To Prioritize`

## PRIORITY
`Low`

## RISK
`Medium`

## Description
Automated technical SEO audits that crawl tracked domains to identify issues affecting search performance. Detects problems like broken links, missing meta tags, duplicate content, slow pages, mobile issues, and schema errors. This completes the SEO toolkit by addressing on-page technical health alongside rankings and backlinks.

## User Story
As an SEO professional, I want to automatically detect technical SEO issues on my site so that I can fix problems that prevent my content from ranking as well as it should.

## Business Impact
### Revenue
- **Complete platform**: Technical SEO + rankings + backlinks = all-in-one solution
- **Upsell driver**: Site audits are typically reserved for higher tiers
- **Recurring value**: Sites need regular audits as content changes

### User Behavior
- **Issue prioritization**: Users fix highest-impact technical issues first
- **Before/after tracking**: See if technical fixes correlate with ranking improvements
- **Client deliverables**: Agency users include audit findings in client reports

### Retention
- **Regular check-ups**: Users run audits monthly/quarterly, creating recurring engagement
- **Fix verification**: Users return to verify fixes resolved issues
- **Comprehensive value**: Technical + off-page + rankings = complete SEO picture

### Legal & Compliance
- **Crawling etiquette**: Respect robots.txt, rate limit requests
- **No authentication needed**: Public page analysis only
- **User's own sites**: Users audit domains they own/track

### Complexity & Cost
- **Crawling infrastructure**: Build or integrate crawler (Screaming Frog API, custom spider)
- **Page analysis logic**: Multiple check implementations (meta tags, speed, mobile, etc.)
- **Resource intensive**: Crawling generates server load and storage
- **Medium-high effort**: 2-3 weeks for core audit functionality

## Acceptance Criteria
- [ ] User can trigger site crawl from project dashboard
- [ ] System crawls publicly accessible pages (respecting robots.txt)
- [ ] Detects critical issues: broken links (404s), server errors (5xx), redirect chains
- [ ] Detects on-page issues: missing title tags, missing meta descriptions, duplicate titles/descriptions
- [ ] Detects content issues: thin content, duplicate content, missing H1, multiple H1s
- [ ] Detects indexation issues: noindex tags, blocked by robots.txt, canonical issues
- [ ] Detects performance issues: slow page load (>3s), large page size
- [ ] Detects mobile issues: viewport missing, small tap targets
- [ ] Detects schema issues: invalid structured data, missing recommended schema
- [ ] Issues categorized by severity: Critical, Warning, Notice
- [ ] Dashboard shows issue count by severity with trend indicator
- [ ] Issue detail view shows affected URLs and recommended fixes
- [ ] User can mark issues as "fixed" or "ignored"
- [ ] Audit results included in PDF reports
- [ ] User can schedule recurring audits (weekly/monthly)

## Related
- Specification: [SPEC-014](../SPECIFICATIONS/SPEC-014.md)
- Tasks: (To be created by Developer)
- Complements: FEAT-001 (rankings), FEAT-013 (backlinks)

## Notes
### Issue Categories & Severities
| Category | Example Issues | Severity |
|----------|----------------|----------|
| **Critical** | 5xx errors, broken canonicals, noindex on important pages | 🔴 Critical |
| **Indexation** | Blocked by robots.txt, noindex tags, canonical conflicts | 🟠 Warning |
| **On-Page** | Missing titles, long titles, missing meta descriptions | 🟠 Warning |
| **Content** | Thin content, duplicate content, missing H1 | 🟡 Notice |
| **Performance** | Slow pages, large images, no compression | 🟡 Notice |
| **Mobile** | No viewport, small tap targets, horizontal scroll | 🟡 Notice |
| **Schema** | Invalid JSON-LD, missing recommended fields | 🟡 Notice |

### Audit Checks (MVP)
| Check | Detection Method | Impact |
|-------|------------------|--------|
| Title tag missing | HTML parsing | High |
| Title too long (>60 chars) | Length check | Medium |
| Meta description missing | HTML parsing | Medium |
| Duplicate title tags | Comparison | High |
| H1 missing | HTML parsing | Medium |
| Multiple H1s | HTML parsing | Medium |
| Broken internal links | HTTP status check | High |
| Broken external links | HTTP status check | Medium |
| Page too slow (>3s) | Load time check | Medium |
| Images missing alt text | HTML parsing | Low |
| No viewport meta tag | HTML parsing | Medium |

### Database Schema
```sql
-- Audit runs
audits: id, project_id, status (running/completed/failed), pages_crawled, issues_found, started_at, completed_at

-- Issues found
audit_issues: id, audit_id, url, issue_type, severity, description, recommendation, status (open/fixed/ignored), created_at

-- Issue types catalog
issue_types: id, category, name, description, severity_default, recommendation_template
```

### Audit Workflow
```
User triggers audit → 
Create audit record → 
Initialize crawler → 
Fetch robots.txt → 
Crawl pages (rate-limited) → 
Analyze each page → 
Store issues → 
Complete audit → 
Notify user
```

### Subscription Tier Limits (suggested)
| Plan | Audits/Month | Pages/Audit | Issue History |
|------|--------------|-------------|---------------|
| Free | ❌ | N/A | N/A |
| Starter | 1 | 100 | 30 days |
| Pro | 4 | 1000 | 1 year |
| Enterprise | Unlimited | Unlimited | Unlimited |

### MVP Scope
- Manual audit trigger only (no scheduling)
- Core issue detection (title, meta, H1, broken links, slow pages)
- Issue list view with severity filtering
- Mark as fixed/ignored
- No automatic re-crawl verification (Phase 2)
- No schema validation (Phase 2)

---

## Comments
_Agent communication log_

### [2026-02-19 23:54] Agent: Innovator
Created as fourteenth feature. Technical SEO audits complete the SEO toolkit - rankings show results, backlinks show off-page causes, audits show on-page issues. Without technical audits, users may fix content and build links but still not rank due to technical problems.

### [2026-02-19 23:54] Agent: Innovator
Priority Low because while valuable, it's resource-intensive to build and maintain. Core rank tracking provides immediate value; technical audits are an enhancement for power users. Risk Medium due to crawling infrastructure complexity and ongoing maintenance for detection rules.

### [2026-02-20 17:40] Agent: Architect
Created specification SPEC-014. Risk confirmed Medium due to crawler infrastructure.

Key architectural decisions:
- Custom crawler with queue-based processing for scalability.
- MVP focuses on 9 core checks; schema validation deferred.
- Issue severity categorization enables prioritized fixes.
Ready for SpecSystemAnalyst breakdown.
