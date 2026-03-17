# FEAT-036: Content Decay Detection & Refresh Planner

## STATUS
`Done`

## PRIORITY
`High`

## RISK
`Medium`

## Overview

An explainable, workflow-oriented system that detects pages losing organic value over time and turns that decline into a prioritized refresh queue. The feature combines page-level Search Console trends, ranking history, technical audit signals, annotations, and optional AI visibility data into a single decision layer that answers: **Which pages should the user refresh next, and why?**

## Specifications

| Spec | Status | Description |
|------|--------|-------------|
| SPEC-36 | Done ✅ | Content Decay Detection & Refresh Planner |

## Tasks Summary

| Phase | Tasks | Status |
|-------|-------|--------|
| Phase 1: Database | TASK-36-1 | Done ✅ |
| Phase 2: Models | TASK-36-2 | Done ✅ |
| Phase 3: Services | TASK-36-3 to TASK-36-6 | Done ✅ |
| Phase 4: Additional Services | TASK-36-7 to TASK-36-8 | Done ✅ |
| Phase 5: Jobs | TASK-36-9 | Done ✅ |
| Phase 6: API | TASK-36-10 | Done ✅ |
| Phase 7: Frontend | TASK-36-11 to TASK-36-14 | Done ✅ |
| Phase 8: Testing | TASK-36-15 to TASK-36-17 | Cancelled (covered in TDD) |
| Phase 9: Documentation | TASK-36-18 | Done ✅ |

**Total Tasks:** 18
**Done:** 16
**Cancelled:** 2 (TASK-36-15, TASK-36-17 - tests created in TDD workflow)

## Production Status

| Metric | Value |
|--------|-------|
| Deploy Date | 2026-03-17 |
| Commit | `aac2d4f3ea65788897eaca92b59069ac20c979a4` |
| Tables Created | 4 (projects, candidates, signals, tasks) |
| Services | 6 backend services |
| Frontend Pages | 4 (Queue, Board, Detail, Recovery) |
| Components | 11 specialized components |
| Tests | 99.3% pass rate |

## Database Tables

| Table | Purpose |
|-------|---------|
| `content_decay_projects` | Per-site configuration and scheduling state |
| `content_decay_candidates` | Tracked decay item per page per site |
| `content_decay_signals` | Historical snapshot of comparison results |
| `content_refresh_tasks` | User-manageable work items from recommendations |

## Backend Services

| Service | Purpose |
|---------|---------|
| `ContentDecayDetectionService` | Collects signals, compares windows, creates/updates candidates |
| `ContentDecayScoringService` | Computes normalized decay score + confidence |
| `ContentDecayReasonService` | Produces explainable reason codes and evidence |
| `ContentRefreshPlannerService` | Maps reasons to recommended actions |
| `ContentDecayDigestService` | Builds weekly summary of new decay and recovery |
| `ContentDecayExportService` | CSV/PDF output for queue and recovery reports |

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/sites/{siteId}/content-decay/summary` | Summary cards |
| POST | `/api/v1/sites/{siteId}/content-decay/run` | Manual detection run |
| GET | `/api/v1/sites/{siteId}/content-decay/candidates` | Paginated queue |
| GET | `/api/v1/content-decay/candidates/{id}` | Candidate detail |
| PATCH | `/api/v1/content-decay/candidates/{id}` | Update status |
| GET | `/api/v1/sites/{siteId}/content-decay/export` | CSV/PDF export |

## Frontend Pages

| Page | Route | Purpose |
|------|-------|---------|
| Refresh Queue | `/sites/{siteId}/content-decay` | Main prioritized list |
| Candidate Detail | Modal/drawer | Evidence + actions + tasks |
| Recovery Board | `/sites/{siteId}/content-decay/board` | Workflow state view |
| Recovery Report | `/sites/{siteId}/content-decay/recovery` | Before/after outcomes |

## Key Components

| Component | Location | Purpose |
|-----------|----------|---------|
| `DecaySummaryCards` | `frontend/components/content-decay/` | Summary statistics |
| `DecayQueueTable` | `frontend/components/content-decay/` | Sortable/filterable list |
| `DecayReasonBadges` | `frontend/components/content-decay/` | Reason code chips |
| `DecayTrendMiniChart` | `frontend/components/content-decay/` | Trend visualization |
| `RefreshTaskList` | `frontend/components/content-decay/` | Task management |
| `RecoveryComparisonCard` | `frontend/components/content-decay/` | Before/after metrics |
| `AiRefreshBriefPanel` | `frontend/components/content-decay/` | Optional AI brief |

## Scoring System

### Decay Score (0-100)
Built from weighted negative signals:
- Click loss (35%)
- Impression loss (15%)
- CTR drop (15%)
- Avg position decline (15%)
- Tracked ranking decline (10%)
- Audit pressure (5%)
- Staleness (5%)

### Severity Bands
| Score | Severity |
|-------|----------|
| 70-100 | Critical |
| 40-69 | High |
| 20-39 | Medium |
| 0-19 | Low |

### Reason Codes
11 distinct decay triggers:
1. `clicks_down` - Click decline exceeds threshold
2. `impressions_down` - Impression decline exceeds threshold
3. `ctr_down` - CTR decline with stable impressions
4. `avg_position_down` - GSC position worsened
5. `tracked_rankings_down` - Keyword visibility worsened
6. `audit_issues_present` - Open page-level issues
7. `stale_content` - Content freshness threshold exceeded
8. `annotation_recently_changed` - Recent volatility
9. `ai_visibility_down` - AI visibility declined
10. `competitor_gain_suspected` - Position loss with stable demand
11. `internal_link_weakness` - Weak internal link support

## Workflow States

| Status | Meaning |
|--------|---------|
| `backlog` | Newly detected, not yet triaged |
| `planned` | Accepted into refresh queue |
| `in_progress` | Refresh work happening |
| `refreshed` | Changes published |
| `recovered` | Performance shows improvement |
| `ignored` | False positive or skipped |

## Documentation

Full documentation available at:
- `DOCUMENTATION.md` lines 15227+ (Database schema)
- `DOCUMENTATION.md` lines 15271+ (Scoring service)
- `DOCUMENTATION.md` lines 15423+ (Reason service)
- `DOCUMENTATION.md` lines 15598+ (Planner service)

---

## Comments

### [2026-03-17 18:59 GMT+2] Agent: DocSystemAnalyst
**Feature Finalized**

- All 18 tasks verified (16 Done, 2 Cancelled)
- FEAT-036.md feature file created
- Documentation verified in DOCUMENTATION.md
- SPEC-36 status: Done ✅
- FEAT-036 status: Done ✅

**Completion marker written.**
