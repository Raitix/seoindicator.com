# FEAT-008: Multi-Site Project Management

## STATUS
`New Feature`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Organize keywords, competitors, and reports into separate projects/sites. Users managing multiple websites (agencies, multi-brand companies, freelancers) can compartmentalize their SEO work, making the dashboard relevant and manageable. Each project has its own keywords, competitors, alerts, and reports.

## User Story
As an agency managing multiple client websites, I want to organize my keywords and reports by client project so that I can quickly switch between clients and keep their SEO data separate and organized.

## Business Impact
### Revenue
- **Agency tier driver**: Multi-site management is essential for agencies - reserved for Pro and Enterprise tiers
- **Natural upsell path**: Users hit project limits → upgrade to higher tier
- **ARPU increase**: Agencies managing 10+ projects represent significant revenue per account

### User Behavior
- **Clean workflow**: Users see only relevant data for current project (no clutter from other sites)
- **Quick switching**: Dropdown or sidebar navigation between projects
- **Delegation ready**: Foundation for future team member access per project

### Retention
- **Agency lock-in**: Once agencies set up 20+ client projects, switching tools is prohibitively painful
- **Organizational value**: Projects become the organizing principle for SEO work
- **Client continuity**: Historical data per project builds long-term value

### Legal & Compliance
- **No additional concerns**: Same data, just organized differently
- **Data isolation**: Ensure proper access control so users can't accidentally access others' projects (multi-tenant security)

### Complexity & Cost
- **Database migration**: Add `project_id` foreign key to keywords, competitors, reports tables
- **UI restructuring**: Project selector in navigation, project-level dashboards
- **Access control**: Future team features will need project-level permissions
- **Low technical risk**: Standard multi-tenancy pattern, well-documented

## Acceptance Criteria
- [ ] User can create projects with name, domain URL, and notes
- [ ] User can edit project settings (name, domain, notes)
- [ ] User can archive projects (hide from active view but retain data)
- [ ] User can delete projects with confirmation (and data retention option)
- [ ] Keywords are associated with specific projects
- [ ] Competitors are associated with specific projects
- [ ] Reports are associated with specific projects
- [ ] Dashboard shows data for currently selected project only
- [ ] Project selector in navigation shows all projects with quick search
- [ ] User can set a default project that loads on login
- [ ] All filters and exports respect current project context
- [ ] "All Projects" view shows aggregate metrics across projects
- [ ] Project-level alert settings (different thresholds per project)

## Related
- Specification: (To be created by Architect)
- Tasks: (To be created by Developer)
- Foundation for: Future team collaboration features

## Notes
### Database Schema Changes
```sql
-- New projects table
projects: id, user_id, name, domain, notes, is_default, archived_at, created_at, updated_at

-- Add project_id to existing tables
ALTER TABLE keywords ADD COLUMN project_id BIGINT UNSIGNED;
ALTER TABLE competitors ADD COLUMN project_id BIGINT UNSIGNED;
ALTER TABLE reports ADD COLUMN project_id BIGINT UNSIGNED;
ALTER TABLE alert_settings ADD COLUMN project_id BIGINT UNSIGNED;

-- Existing data migration
UPDATE keywords SET project_id = (SELECT id FROM projects WHERE user_id = keywords.user_id LIMIT 1);
```

### Project Dashboard Metrics
| Metric | Description |
|--------|-------------|
| Total Keywords | Keywords in this project |
| Average Position | Mean position across all keywords |
| Top 10 Keywords | Keywords ranking in top 10 |
| Position Changes (7d) | Net position change over 7 days |
| SERP Features | Count of keywords with rich results |
| Competitors | Number of tracked competitors |

### Subscription Tier Limits (suggested)
| Plan | Projects | Keywords/Project |
|------|----------|------------------|
| Free | 1 | 10 |
| Starter | 3 | 50 |
| Pro | 10 | 200 |
| Enterprise | Unlimited | Unlimited |

### UI Layout (suggested)
```
┌─────────────────────────────────────────────────┐
│ SEOIndicator    [Project: Client A ▼]  [User]  │
├─────────────────────────────────────────────────┤
│ Dashboard | Keywords | Competitors | Reports   │
├─────────────────────────────────────────────────┤
│                                                 │
│  [Current Project: Client A - example.com]      │
│                                                 │
│  Project Overview Metrics...                    │
│                                                 │
└─────────────────────────────────────────────────┘
```

### MVP Scope
- Create/edit/delete projects
- Project selector in navigation
- Keywords organized by project
- "All Projects" aggregate view
- No team permissions (Phase 2)
- No project templates (Phase 2)

---

## Comments
_Agent communication log_

### [2026-02-19 19:19] Agent: Innovator
Created as eighth feature. Multi-site management is essential for agencies - a key customer segment for SEO tools. Without projects, agencies would have all client keywords mixed together, making the tool unusable for their workflow.

### [2026-02-19 19:19] Agent: Innovator
Priority Medium because it's essential for agencies but not blocking for individual users. Risk Low due to standard database schema changes and well-understood multi-tenancy patterns. This also lays groundwork for future team collaboration features.
