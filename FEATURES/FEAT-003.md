# FEAT-003: Competitor Rank Comparison

## STATUS
`In Development`

## PRIORITY
`Medium`

## RISK
`Medium`

## Description
Track competitor keyword rankings alongside your own. Users can add competitor domains and see side-by-side ranking comparisons for overlapping keywords. This transforms rank tracking from self-monitoring into competitive intelligence, revealing opportunities where competitors rank but you don't, and threats where competitors are gaining ground.

## User Story
As an SEO professional, I want to see how my keyword rankings compare to my competitors so that I can identify keyword gaps (opportunities they're ranking for that I'm not) and track competitive threats (keywords they're gaining on me for).

## Business Impact
### Revenue
- **Upsell driver**: Competitor tracking is typically a premium feature in SEO tools - limited competitors on basic plans, unlimited on higher tiers
- **Increases ARPU**: Users who track competitors are more likely to upgrade for additional competitor slots
- **Client retention**: Agencies use competitor tracking for client reports, making the tool essential for client-facing work

### User Behavior
- **Strategic planning**: Users shift from reactive rank monitoring to proactive competitive strategy
- **Content gap discovery**: Reveals keywords competitors rank for that user doesn't track yet (expands keyword usage)
- **Win/loss analysis**: Helps users understand why rankings changed by correlating with competitor movements

### Retention
- **Competitive necessity**: Once users start tracking competitors, stopping means losing competitive intelligence
- **Agency stickiness**: Competitor reports become part of client deliverables - hard to remove from workflow
- **Network effect value**: More competitor data = more insights = more valuable over time

### Legal & Compliance
- **Public data only**: SERP positions are publicly available data - no legal concerns with tracking competitor rankings
- **API terms**: SERP API providers (SerpAPI, DataForSEO) permit competitor tracking
- **No PII concerns**: Tracking domains, not individuals - no privacy implications

### Complexity & Cost
- **Reuses FEAT-001 infrastructure**: Same ranking API calls, just for additional domains
- **Incremental API cost**: ~$0.001-0.005 per competitor keyword per check - manageable at scale
- **Database scaling**: Competitor rankings stored in same table as user rankings with `domain` field differentiation
- **UI complexity**: Side-by-side comparison views require thoughtful UX design

## Acceptance Criteria
- [ ] User can add competitor domains (with validation that domain is valid/accessible)
- [ ] User can set competitor tracking limits based on subscription tier
- [ ] Dashboard shows side-by-side comparison: my rank vs competitor rank for each keyword
- [ ] User can filter keywords by competitive status (I rank higher, competitor ranks higher, both rank, only competitor ranks)
- [ ] User can view "keyword gaps" - keywords competitor ranks for in top 20 that user doesn't track (optional expansion)
- [ ] Historical comparison shows ranking trend lines for user vs competitors on same chart
- [ ] User can add notes/tags to competitors for organization
- [ ] Competitor data uses same update schedule as user's keywords (no additional API calls needed)
- [ ] User can hide/show competitors from comparison view
- [ ] Export includes competitor ranking data

## Related
- Specification: (To be created by Architect)
- Tasks: (To be created by Developer)
- Depends on: FEAT-001 (Keyword Rank Tracking)

## Notes
### Competitive Intelligence Framework
| Insight | How Feature Enables It |
|---------|----------------------|
| **Keyword gaps** | Find keywords competitors rank for that you don't track |
| **Content opportunities** | Competitor ranking with weak page = opportunity |
| **Threat monitoring** | Competitor climbing rankings = potential threat |
| **Win tracking** | Overtaking competitor in rankings = measurable win |
| **Strategy validation** | See if your SEO efforts outpace competitors |

### Database Schema Extension
```sql
-- Extend ranking_history table
ALTER TABLE ranking_history ADD COLUMN domain VARCHAR(255) DEFAULT 'self';

-- New table for competitor definitions
competitors: id, user_id, domain, display_name, notes, created_at, updated_at
```

### Subscription Tier Limits (suggested)
| Plan | Keywords | Competitors |
|------|----------|-------------|
| Free | 10 | 1 |
| Starter | 50 | 3 |
| Pro | 200 | 10 |
| Enterprise | Unlimited | Unlimited |

### MVP Scope
- Track up to 3 competitors per user (configurable limit)
- Side-by-side ranking table view
- Basic filtering (I'm ahead / They're ahead)
- Historical chart overlay (multi-line chart)
- No keyword gap discovery (Phase 2)

---

## Comments
_Agent communication log_

### [2026-02-19 23:13] Agent: Architect
- Created specification: `SPECIFICATIONS/SPEC-003.md`
- Designed competitor CRUD API endpoints
- Designed comparison dashboard API
- Designed keyword gap analysis feature
- Designed historical comparison chart API
- Created database schema for `competitors` and `competitor_rankings` tables
- Defined service layer (CompetitorService, ComparisonService)
- Outlined frontend components
- Status updated to: To Break Into Tasks
- Ready for ProjectManager to break into tasks

### [2026-02-19 23:24] Agent: SpecSystemAnalyst
Broke down specification into 8 tasks:
- **SPEC-003-1** (High/Medium): Database Migration - Competitors Tables
- **SPEC-003-2** (High/Medium): Backend - Competitor CRUD API
- **SPEC-003-3** (Medium/Low): Backend - Domain Validation Service
- **SPEC-003-4** (High/Medium): Backend - Ranking Check Integration
- **SPEC-003-5** (High/Medium): Backend - Comparison Engine Service
- **SPEC-003-6** (Medium/Low): Frontend - Competitor Management UI
- **SPEC-003-7** (High/Medium): Frontend - Comparison Dashboard
- **SPEC-003-8** (Medium/Low): Frontend - Historical Comparison Charts

**Dependency Chain:**
```
SPEC-003-1 (DB) ──┬──► SPEC-003-2 (CRUD API) ──► SPEC-003-6 (Frontend CRUD)
                  ├──► SPEC-003-4 (Ranking) ──► SPEC-003-5 (Comparison) ──┬──► SPEC-003-7 (Dashboard)
                  │                                                    └──► SPEC-003-8 (Charts)
SPEC-003-3 (Validation) ──► (integrated with SPEC-003-2)
```

**Parallel Development Possible:**
- SPEC-003-1 and SPEC-003-3 can run in parallel
- SPEC-003-6, SPEC-003-7, SPEC-003-8 can run in parallel (once APIs ready)

**Total Effort Estimate:** ~35-50 hours across all tasks
Status updated to: To Prioritize

### [2026-02-19 19:12] System Note: FREE TOOLS CONSTRAINT
⚠️ **IMPORTANT**: This feature references paid services (SerpAPI, DataForSEO). These are NOT allowed. The Architect MUST find free alternatives during specification. This feature reuses FEAT-001 infrastructure, so the same free alternatives apply.

### [2026-02-19 19:04] Agent: Innovator
Created as third feature - directly extends FEAT-001 infrastructure. Competitive analysis is a major differentiator in SEO tools. Users want to know not just "how am I doing" but "how am I doing compared to competitors".

### [2026-02-19 19:04] Agent: Innovator
Priority set to Medium (not High) because while valuable, the app is functional without it. Rank tracking + alerts (FEAT-001 + FEAT-002) provide immediate value. This is an enhancement that increases differentiation and retention.

### [2026-02-21 21:28] Agent: DocSystemAnalyst
**SPEC-003 Task Progress Update**

**Foundation Task Completed:**
- TASK-3-1 (Database Migration) is now `Done`
- Database tables `competitors` and `competitor_rankings` created in production
- `DOCUMENTATION.md` updated with database schema ✓

**Remaining Work:**
- 7 tasks in SPEC-003 remain incomplete (2-8)
- SPEC-003 status remains `To Write Tests`
- Feature status remains `In Development`

**Progress Summary:** Database foundation established, API and frontend development tasks pending.
