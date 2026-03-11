# SPEC-036: Content Decay Detection & Refresh Planner

## STATUS
`To Prioritize`

## FEATURE
FEAT-036: Content Decay Detection & Refresh Planner

## PRIORITY
`High`

## RISK
`Medium`

---

## Overview

This specification defines an explainable, workflow-oriented system that detects pages losing organic value over time and turns that decline into a prioritized refresh queue. The feature combines page-level Search Console trends, ranking history, technical audit signals, annotations, and optional AI visibility data into a single decision layer that answers one practical question:

**Which pages should the user refresh next, and why?**

The MVP must work even when not all supporting features are enabled. Detection starts with Search Console data and enriches with ranking, audit, annotation, and AI visibility signals when available.

---

## Core Architecture Decisions

### 1. Graceful Data Enrichment Instead of Hard Dependency Blocking

The system must not wait for every related feature to be fully available.

| Signal Source | Purpose | Required for MVP | Behavior if Missing |
|---|---|---:|---|
| FEAT-012 GSC data | Core clicks / impressions / CTR / avg position decay | ✅ Yes | Feature cannot detect decay without this |
| FEAT-001 rank tracking | Confirms keyword-position loss | No | Score renormalizes without ranking input |
| FEAT-014 technical audits | Explains decay via technical blockers | No | Reason codes exclude audit-based causes |
| FEAT-009 annotations | Explains recent updates / cooldowns | No | No annotation correlation shown |
| FEAT-034 AI visibility | Detects answer-engine decline | No | AI visibility signals omitted |

**Decision:** the scoring engine uses only available signals and renormalizes weights automatically. Missing enrichments reduce confidence, not functionality.

### 2. Explainable Heuristics Before Opaque AI

The core queue must be built from transparent formulas and reason codes, not black-box AI outputs.

**Decision:**
- decay detection = deterministic scoring logic
- recommendation generation = deterministic rules first
- AI brief = optional enhancement layered on top of the queue

This keeps the feature auditable, free-tool compliant, and easy to trust.

### 3. Candidate-Centric Workflow, Not Another Analytics Report

Users do not need another chart-only surface. They need actionable units of work.

**Decision:** the primary domain object is a **content decay candidate** (page + evidence + priority + workflow state), not a raw metrics row.

### 4. Reuse Existing Product Infrastructure

The feature should extend existing systems instead of duplicating them.

| Existing Capability | Reuse Strategy |
|---|---|
| FEAT-012 Search Console | Page/query trend source for core decay detection |
| FEAT-001 Rank Tracking | Position-change validation and recovery measurement |
| FEAT-014 Site Audit | Technical reason codes and action recommendations |
| FEAT-009 Annotations | Cooldown logic and pre/post update correlation |
| FEAT-004 / FEAT-029 Exports | CSV/PDF output for queue and recovery reports |
| FEAT-002 Alerts | Weekly digest and significant recovery / decline notifications |
| FEAT-019 Queue System | Background detection, digest, and AI brief jobs |

---

## System Architecture

### High-Level Flow

```text
Search Console page metrics
        +
Ranking history
        +
Audit issues
        +
Annotations
        +
AI visibility signals
        |
        v
ContentDecayDetectionService
        |
        +--> eligibility filtering
        +--> rolling-window comparison
        +--> signal normalization
        +--> decay score calculation
        +--> reason code assignment
        +--> candidate upsert
        |
        v
Refresh Queue API
        |
        +--> queue table
        +--> board view
        +--> candidate detail
        +--> export
        +--> optional AI refresh brief
```

### Main Components

| Layer | Component | Responsibility |
|---|---|---|
| Backend | `ContentDecayDetectionService` | Collects signals, compares windows, creates/updates candidates |
| Backend | `ContentDecayScoringService` | Computes normalized decay score + confidence |
| Backend | `ContentDecayReasonService` | Produces explainable reason codes and evidence |
| Backend | `ContentRefreshPlannerService` | Maps reasons to recommended actions / task suggestions |
| Backend | `ContentDecayDigestService` | Builds weekly summary of new decay and recovery |
| Backend | `ContentDecayExportService` | Reuses export infrastructure for CSV/PDF output |
| Queue | `DetectContentDecayForSiteJob` | Scheduled / manual detection execution |
| Queue | `SendContentDecayDigestJob` | Weekly digest generation |
| Queue | `GenerateRefreshBriefJob` | Optional AI-generated refresh outline |
| Frontend | Refresh Queue page | Prioritized list with filters and bulk workflow |
| Frontend | Candidate detail drawer/page | Evidence, metrics, task list, before/after, AI brief |
| Frontend | Recovery Board | Workflow-state view: backlog → planned → in progress → refreshed → recovered |

---

## Detection Pipeline

### 1. Eligibility Rules

A page becomes eligible for decay analysis only when baseline data is meaningful.

#### MVP eligibility
- page has Search Console data for both windows
- previous window impressions >= configurable threshold (default: 100)
  **OR** previous window clicks >= configurable threshold (default: 10)
- page has existed long enough to compare 2 full windows
- page is not in an ignore state

#### Cooldown / suppression rules
- suppress new decay alerts for pages marked `refreshed` in the last 14 days
- suppress high-confidence flags when an annotation indicates a recent content update inside the comparison window
- suppress candidates where total movement is below a low-signal threshold

### 2. Comparison Windows

Default comparison:
- **current window** = last 28 complete days
- **previous window** = prior 28 complete days

Configurable per project in later phases:
- 7 days
- 14 days
- 28 days
- 56 days

**Decision:** MVP stores one active window size per site/project. No user-facing multi-window comparisons in first release.

### 3. Signal Collection

For each eligible page, the detector gathers:

| Signal | Source | Notes |
|---|---|---|
| clicks | GSC | required |
| impressions | GSC | required |
| CTR | GSC | derived or fetched |
| average position | GSC | required for ranking trend hints |
| tracked keyword trend | FEAT-001 | optional enrichment |
| unresolved audit issue count | FEAT-014 | optional enrichment |
| content age / stale age | page metadata or audit crawl | heuristic |
| recent annotation count / last update date | FEAT-009 | optional enrichment |
| AI visibility rate delta | FEAT-034 | optional enrichment |
| page type | URL + title heuristic | used for filtering and recommendation tone |

### 4. Score Calculation

The decay score is a 0-100 value built from normalized decline metrics.

#### Base weights

| Factor | Weight |
|---|---:|
| Click loss | 0.35 |
| Impression loss | 0.15 |
| CTR drop | 0.15 |
| Avg position decline | 0.15 |
| Tracked ranking decline | 0.10 |
| Audit pressure | 0.05 |
| Staleness | 0.05 |

#### Normalization rules
- Only negative movement contributes to decay.
- Positive movement becomes `0` contribution.
- Missing signals are excluded and weights are renormalized across available factors.
- Confidence is reduced when only core signals are present.

#### MVP formula

```text
decay_score = 100 × weighted_sum(available_negative_signals) × confidence_multiplier
```

Where:
- `click_loss` = max((previous_clicks - current_clicks) / max(previous_clicks, 1), 0)
- `impression_loss` = max((previous_impressions - current_impressions) / max(previous_impressions, 1), 0)
- `ctr_drop` = max((previous_ctr - current_ctr) / max(previous_ctr, 0.01), 0)
- `position_decline` = clamp((current_avg_position - previous_avg_position) / 10, 0, 1)
- `ranking_decline` = normalized visibility / tracked rank loss when FEAT-001 data exists
- `audit_pressure` = normalized unresolved issue load for the page
- `staleness` = normalized age signal, capped at 1.0 after configurable threshold

#### Confidence multiplier

| Available Signal Quality | Multiplier |
|---|---:|
| GSC only | 0.85 |
| GSC + rankings | 0.93 |
| GSC + rankings + audit and/or annotations | 1.00 |

### 5. Severity Bands

| Score | Severity |
|---|---|
| 70-100 | Critical |
| 40-69 | High |
| 20-39 | Medium |
| 0-19 | Low / watch |

Only `Medium+` candidates appear in the default queue. `Low` can be shown behind a filter.

### 6. Recovery Upside Estimation

The queue should show why a refresh matters.

#### MVP estimated upside

```text
estimated_recovery_upside =
max(previous_clicks - current_clicks, 0)
+ max((previous_ctr - current_ctr) × current_impressions, 0)
```

This is a heuristic, not a guarantee. It should always be labeled as an estimate.

---

## Reason Codes and Recommendation Engine

### Reason Codes

Each candidate stores one primary reason and multiple supporting reason codes.

| Reason Code | Trigger |
|---|---|
| `clicks_down` | Click decline exceeds threshold |
| `impressions_down` | Impression decline exceeds threshold |
| `ctr_down` | CTR decline exceeds threshold while impressions remain meaningful |
| `avg_position_down` | GSC average position worsened materially |
| `tracked_rankings_down` | Tracked keyword visibility worsened |
| `audit_issues_present` | Open page-level audit issues exist |
| `stale_content` | Last meaningful update exceeds freshness threshold |
| `annotation_recently_changed` | Recent annotation explains possible volatility |
| `ai_visibility_down` | AI visibility metric declined |
| `competitor_gain_suspected` | Position/visibility loss with stable demand signals |
| `internal_link_weakness` | Audit/internal-link data indicates weak support |

### Recommendation Mapping

| Trigger Combination | Recommended Action |
|---|---|
| CTR down, impressions stable | Rewrite title and meta description |
| Position down, ranking loss | Refresh core sections for affected queries |
| Audit issues present | Fix technical blockers before content rewrite |
| Stale content | Update examples, screenshots, stats, and date references |
| AI visibility down | Add definitions, FAQs, citations, quotes, and structured summaries |
| Competitor gain suspected | Add comparison / alternative sections |
| Internal link weakness | Add links from stronger related pages |

### Planning Output

The planner produces:
- 1 primary recommendation
- up to 3 supporting recommendations
- optional task suggestions with due date and owner fields
- optional AI-generated brief (Phase 3)

---

## Workflow Model

### Candidate Status Lifecycle

| Status | Meaning |
|---|---|
| `backlog` | Newly detected, not yet triaged |
| `planned` | User accepted candidate into refresh queue |
| `in_progress` | Refresh work is happening |
| `refreshed` | User indicates changes were published |
| `recovered` | Performance shows meaningful recovery |
| `ignored` | False positive, low value, or intentionally skipped |

### Recovery Detection

A `refreshed` candidate becomes `recovered` when post-refresh window shows measurable improvement versus the decay baseline.

#### MVP recovery rule
- clicks improve by configurable threshold **or**
- CTR / avg position meaningfully improve **and**
- recovery holds for at least one full comparison window

The system must avoid auto-marking recovery too quickly because GSC data lags.

---

## Database Schema

### 1. content_decay_projects

Per-site configuration and scheduling state.

```sql
CREATE TABLE content_decay_projects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    site_id BIGINT UNSIGNED NOT NULL,
    detection_enabled BOOLEAN DEFAULT TRUE,
    window_days TINYINT UNSIGNED DEFAULT 28,
    min_clicks_threshold INT UNSIGNED DEFAULT 10,
    min_impressions_threshold INT UNSIGNED DEFAULT 100,
    min_decay_score DECIMAL(5,2) DEFAULT 20.00,
    digest_enabled BOOLEAN DEFAULT TRUE,
    digest_frequency ENUM('weekly', 'monthly') DEFAULT 'weekly',
    settings_json JSON NULL,
    last_scanned_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE INDEX idx_site_unique (site_id),
    INDEX idx_user_enabled (user_id, detection_enabled),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);
```

### 2. content_decay_candidates

One tracked decay item per page per site/project.

```sql
CREATE TABLE content_decay_candidates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    page_url VARCHAR(2048) NOT NULL,
    page_url_hash CHAR(64) NOT NULL,
    page_title VARCHAR(500) NULL,
    page_type VARCHAR(50) DEFAULT 'other',
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    decay_score DECIMAL(5,2) NOT NULL,
    detection_confidence DECIMAL(5,2) DEFAULT 0.85,
    lost_click_estimate INT UNSIGNED DEFAULT 0,
    recovery_upside_estimate INT UNSIGNED DEFAULT 0,
    primary_reason_code VARCHAR(100) NOT NULL,
    reason_codes JSON NOT NULL,
    signal_sources JSON NULL,
    evidence_json JSON NULL,
    status ENUM('backlog', 'planned', 'in_progress', 'refreshed', 'recovered', 'ignored') DEFAULT 'backlog',
    first_detected_at TIMESTAMP NOT NULL,
    last_detected_at TIMESTAMP NOT NULL,
    planned_at TIMESTAMP NULL,
    started_at TIMESTAMP NULL,
    refreshed_at TIMESTAMP NULL,
    recovered_at TIMESTAMP NULL,
    ignored_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE INDEX idx_project_page_unique (project_id, page_url_hash),
    INDEX idx_project_status (project_id, status),
    INDEX idx_project_score (project_id, decay_score),
    INDEX idx_project_severity (project_id, severity),
    FOREIGN KEY (project_id) REFERENCES content_decay_projects(id) ON DELETE CASCADE
);
```

### 3. content_decay_signals

Historical snapshot of comparison results used to explain detection and measure recovery.

```sql
CREATE TABLE content_decay_signals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_id BIGINT UNSIGNED NOT NULL,
    previous_start DATE NOT NULL,
    previous_end DATE NOT NULL,
    current_start DATE NOT NULL,
    current_end DATE NOT NULL,
    previous_clicks INT UNSIGNED DEFAULT 0,
    current_clicks INT UNSIGNED DEFAULT 0,
    previous_impressions INT UNSIGNED DEFAULT 0,
    current_impressions INT UNSIGNED DEFAULT 0,
    previous_ctr DECIMAL(8,4) DEFAULT 0.0000,
    current_ctr DECIMAL(8,4) DEFAULT 0.0000,
    previous_avg_position DECIMAL(8,2) NULL,
    current_avg_position DECIMAL(8,2) NULL,
    previous_rank_visibility DECIMAL(8,2) NULL,
    current_rank_visibility DECIMAL(8,2) NULL,
    previous_ai_visibility DECIMAL(8,2) NULL,
    current_ai_visibility DECIMAL(8,2) NULL,
    unresolved_audit_issues INT UNSIGNED DEFAULT 0,
    content_age_days INT UNSIGNED NULL,
    last_annotation_at TIMESTAMP NULL,
    evidence_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_candidate_created (candidate_id, created_at),
    UNIQUE INDEX idx_candidate_window_unique (candidate_id, previous_start, previous_end, current_start, current_end),
    FOREIGN KEY (candidate_id) REFERENCES content_decay_candidates(id) ON DELETE CASCADE
);
```

### 4. content_refresh_tasks

User-manageable work items generated from planner recommendations.

```sql
CREATE TABLE content_refresh_tasks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    recommendation_type VARCHAR(100) NOT NULL,
    description TEXT NULL,
    notes TEXT NULL,
    owner_user_id BIGINT UNSIGNED NULL,
    due_date DATE NULL,
    status ENUM('todo', 'in_progress', 'done', 'skipped') DEFAULT 'todo',
    source ENUM('system', 'manual', 'ai') DEFAULT 'system',
    metadata_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_candidate_status (candidate_id, status),
    INDEX idx_owner_due (owner_user_id, due_date),
    FOREIGN KEY (candidate_id) REFERENCES content_decay_candidates(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

---

## API Design

### Project / Summary Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/sites/{siteId}/content-decay/summary` | Summary cards: total candidates, critical pages, estimated upside, recovered pages |
| POST | `/api/v1/sites/{siteId}/content-decay/run` | Manual detection run |
| PATCH | `/api/v1/sites/{siteId}/content-decay/settings` | Update thresholds and digest settings |

### Queue Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/sites/{siteId}/content-decay/candidates` | Paginated queue with filters |
| GET | `/api/v1/content-decay/candidates/{id}` | Candidate detail |
| PATCH | `/api/v1/content-decay/candidates/{id}` | Update status (`planned`, `in_progress`, `refreshed`, `ignored`) |
| POST | `/api/v1/content-decay/candidates/{id}/restore` | Restore ignored / archived candidate to backlog |

### Task Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/content-decay/candidates/{id}/tasks` | List refresh tasks |
| POST | `/api/v1/content-decay/candidates/{id}/tasks` | Create task / note / due date |
| PATCH | `/api/v1/content-refresh-tasks/{id}` | Update task status, notes, owner, due date |

### Export / Brief Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/sites/{siteId}/content-decay/export?format=csv|pdf` | Export queue and/or recovery report |
| POST | `/api/v1/content-decay/candidates/{id}/ai-brief` | Queue optional AI refresh brief generation |

### Queue Filters

Supported query params for `GET /candidates`:
- `status`
- `severity`
- `page_type`
- `reason_code`
- `min_lost_clicks`
- `search`
- `sort` = `decay_score | recovery_upside_estimate | lost_click_estimate | last_detected_at`
- `order` = `asc | desc`

---

## Backend Services

### ContentDecayDetectionService

**Responsibilities**
- load project/site settings
- gather page-level GSC metrics for previous + current windows
- enrich with ranking / audit / annotation / AI visibility inputs when available
- upsert candidates and signal snapshots
- archive or downgrade candidates that no longer meet thresholds

### ContentDecayScoringService

**Responsibilities**
- normalize all available negative signals
- renormalize weights when signals are missing
- compute `decay_score`, `severity`, and `detection_confidence`
- expose explainable scoring metadata for UI/tooltips

### ContentDecayReasonService

**Responsibilities**
- convert numeric deltas into human-readable reason codes
- choose primary reason
- build evidence payload for the candidate detail screen

### ContentRefreshPlannerService

**Responsibilities**
- map reason codes to concrete recommendations
- optionally generate default `content_refresh_tasks`
- provide page-type-aware recommendations

### ContentDecayDigestService

**Responsibilities**
- summarize newly detected pages
- summarize recovered pages
- exclude ignored and stale-noise candidates
- send digest through existing notification system

### ContentDecayExportService

**Responsibilities**
- adapt queue/recovery data into export payloads
- reuse FEAT-029 exporter patterns for PDF/XLS/CSV generation

---

## Jobs and Scheduling

### Jobs

| Job | Purpose |
|---|---|
| `DetectContentDecayForSiteJob` | Run detection for one site/project |
| `BackfillContentDecayRecoveryJob` | Re-evaluate refreshed items for recovery status |
| `SendContentDecayDigestJob` | Weekly digest generation |
| `GenerateRefreshBriefJob` | Optional AI brief generation |

### Scheduling Rules

- nightly detection run after daily GSC data is expected to be complete
- weekly digest only if digest enabled and site has meaningful changes
- AI brief only on user request or explicit bulk action

**Decision:** no always-on AI generation in MVP. That avoids unnecessary cost and noisy output.

---

## Frontend UX

### Primary Pages

| Page | Route | Purpose |
|---|---|---|
| Refresh Queue | `/sites/{siteId}/content-decay` | Main prioritized list |
| Candidate Detail | drawer/modal or `/sites/{siteId}/content-decay/{candidateId}` | Evidence + actions + tasks |
| Recovery Board | `/sites/{siteId}/content-decay/board` | Workflow state view |
| Recovery Report | `/sites/{siteId}/content-decay/recovery` | Before/after outcomes |

### Key Components

| Component | Purpose |
|---|---|
| `DecaySummaryCards` | Critical count, total candidates, estimated upside, recovered pages |
| `DecayQueueTable` | Sortable/filterable candidate list |
| `DecayReasonBadges` | Explainable reason code chips |
| `DecayTrendMiniChart` | Previous vs current window trend snapshot |
| `RefreshTaskList` | Notes, due dates, owner, task status |
| `RecoveryComparisonCard` | Before/after metrics after refresh |
| `AiRefreshBriefPanel` | Optional brief with warning label |

### Page Type Filtering

MVP page type values:
- `home`
- `blog`
- `landing`
- `product`
- `tool`
- `compare`
- `docs`
- `other`

**Decision:** derive this heuristically from URL path/title first; user overrides can be added later.

---

## Free Tools Compliance

| Requirement | Compliance Strategy |
|---|---|
| No paid API for core feature | Core detection uses existing first-party GSC, ranking, audit, and annotation data |
| No paid AI dependency | AI brief uses existing free-tier AI infrastructure only |
| No external content-decay SaaS | All scoring and planning are implemented in-app |
| No mandatory subscription add-on | Feature derives value from already-connected sources |

The optional AI brief must always be labeled as assistive output, not guaranteed advice.

---

## Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Seasonal pages create false positives | Medium | Medium | Thresholds, ignore state, annotation support, future YoY dampening |
| GSC data lag causes premature conclusions | High | Medium | Use complete windows only; avoid same-day judgments |
| Missing enrichment sources weaken explanation quality | Medium | Low | Graceful degradation + confidence score |
| Too many low-value candidates flood queue | Medium | High | Eligibility thresholds + severity filter + default Medium+ view |
| AI brief hallucinations | Medium | Medium | Optional only, explicit disclaimer, deterministic recommendations remain primary |
| Page typing heuristic misclassifies URLs | Medium | Low | Treat as filter aid only, allow later override |

---

## Testing Strategy

### Unit Tests
- score normalization and weight renormalization
- severity thresholds
- reason code assignment
- recommendation mapping
- page type classification
- recovery-state transition rules

### Feature Tests
- queue listing and filter combinations
- candidate status updates
- task CRUD
- export endpoint authorization and payload generation
- manual detection run endpoint

### Integration Tests
- GSC-only mode detection
- GSC + ranking enrichment mode
- annotation cooldown behavior
- recovery detection after refresh
- weekly digest generation with changed candidates only

### UI Tests
- queue filters, sorting, and empty states
- status workflow actions
- before/after comparison rendering
- AI brief request flow

---

## Success Criteria

- [ ] System detects decaying pages from rolling time-window comparison
- [ ] Score calculation is explainable and inspectable
- [ ] Queue shows decay score, severity, and estimated upside
- [ ] Candidate detail shows reason codes with evidence
- [ ] User can move candidates through refresh workflow states
- [ ] User can create notes/tasks with due dates
- [ ] Recovery view compares pre-refresh and post-refresh performance
- [ ] Weekly digest summarizes new decay and recovered pages
- [ ] CSV/PDF export works through existing export infrastructure
- [ ] Optional AI brief is available without becoming a hard dependency

---

## Comments

### Architect (2026-03-10)
**Specification Created: SPEC-036.md**

**Key Design Decisions:**
1. **Graceful enrichment**: GSC is the only hard requirement; rankings, audits, annotations, and AI visibility enrich score and explanations when present
2. **Explainable scoring**: deterministic 0-100 decay score with renormalized weights instead of opaque AI-first logic
3. **Candidate-first workflow**: pages become actionable queue items with lifecycle states, not just trend rows
4. **No paid dependencies**: core functionality uses existing first-party/free infrastructure; AI brief stays optional

**Architecture Highlights:**
- 4 new tables: `content_decay_projects`, `content_decay_candidates`, `content_decay_signals`, `content_refresh_tasks`
- Scheduled detection + weekly digest via existing queue infrastructure
- Reuse of existing export, ranking, audit, annotation, and notification systems
- Recovery measurement built from stored signal snapshots and refresh lifecycle timestamps

**Risks Identified:**
- Seasonal content can create false positives without thresholding/cooldowns
- GSC lag requires complete-window comparisons only
- AI brief quality must never replace deterministic planner recommendations

**Status:** Ready for SpecSystemAnalyst to break into tasks.

---

*Created by Architect agent on 2026-03-10*
*Feature: FEAT-036*

---

### SpecSystemAnalyst (2026-03-11 14:02 GMT+2)
**Stale Assignment - Breakdown Already Complete**

**Task Breakdown Status:**
- 18 tasks already created (TASK-36-1 through TASK-36-18)
- Tasks are progressing through the pipeline:
  - TASK-36-1: To Test Prod (migrations deployed)
  - TASK-36-10, 36-11: To Test Prod (API & frontend deployed)
  - TASK-36-14: To Test Locally (navigation integration)
  - TASK-36-2 to 36-9: Various stages (To Write Tests / To Develop)
  - TASK-36-18: To Prioritize (documentation - blocked)

**Tasks Created:**
| Phase | Tasks | Purpose |
|-------|-------|---------|
| Phase 1: Database | TASK-36-1 | Migrations (4 tables) |
| Phase 2: Models | TASK-36-2 | Eloquent models + interfaces |
| Phase 3: Services | TASK-36-3 to 36-6 | Scoring, Reason, Detection, Planner |
| Phase 4: Additional Services | TASK-36-7 to 36-8 | Digest, Export |
| Phase 5: Jobs | TASK-36-9 | Queue jobs |
| Phase 6: API | TASK-36-10 | Controllers & routes |
| Phase 7: Frontend | TASK-36-11 to 36-14 | Queue page, Board, Detail, Navigation |
| Phase 8: Testing | TASK-36-15 to 36-17 | Unit, Feature, Integration tests |
| Phase 9: Documentation | TASK-36-18 | Documentation update |

**Status Update:** `To Break Into Tasks` → `To Prioritize`
- SpecSystemAnalyst work was completed in a previous session
- All tasks created with dependencies, acceptance criteria, and effort estimates
- Status updated to reflect actual state
