# TEST-36-2: Eloquent Models - Content Decay

## Task Reference
- **Task:** TASK-36-2
- **Specification:** SPEC-036: Content Decay Detection & Refresh Planner
- **Status:** To Write Tests

---

## Overview

This document describes the comprehensive test suite for the four Content Decay Eloquent models:
1. **ContentDecayProject** - Per-site detection configuration
2. **ContentDecayCandidate** - Tracked decay items per page
3. **ContentDecaySignal** - Historical comparison snapshots
4. **ContentRefreshTask** - Actionable work items

---

## Test Files

| File | Location | Tests | Description |
|------|----------|-------|-------------|
| `ContentDecayProjectTest.php` | `tests/Unit/Models/` | 85+ | Project configuration model |
| `ContentDecayCandidateTest.php` | `tests/Unit/Models/` | 120+ | Decay candidate model |
| `ContentDecaySignalTest.php` | `tests/Unit/Models/` | 90+ | Signal snapshot model |
| `ContentRefreshTaskTest.php` | `tests/Unit/Models/` | 90+ | Task management model |
| **Total** | | **385+** | |

---

## Test Scenarios

### ContentDecayProject (85+ tests)

#### Model Instantiation (MI-001 to MI-005)
| ID | Scenario | Expected |
|----|----------|----------|
| MI-001 | Create with required fields | Database record created |
| MI-002 | Create with all fields | All fields populated correctly |
| MI-003 | Mass assignment protects id | ID unchanged after fill |
| MI-004 | Factory creates valid model | All required fields present |
| MI-005 | Model uses timestamps | created_at, updated_at populated |

#### Casts (CA-001 to CA-012)
| ID | Scenario | Expected |
|----|----------|----------|
| CA-001 | detection_enabled cast to boolean | true/false values |
| CA-002 | digest_enabled cast to boolean | true/false values |
| CA-003 | window_days integer | Integer value |
| CA-004 | min_clicks_threshold integer | Integer value |
| CA-005 | min_impressions_threshold integer | Integer value |
| CA-006 | min_decay_score decimal | Decimal with 2 places |
| CA-007 | digest_frequency enum | 'weekly' or 'monthly' |
| CA-008 | settings_json cast to array | Array access works |
| CA-009 | settings_json null returns null | null handled |
| CA-010 | settings_json empty array | Empty array handled |
| CA-011 | last_scanned_at cast to datetime | Carbon instance |
| CA-012 | last_scanned_at null | null handled |

#### Relationships (RE-001 to RE-010)
| ID | Scenario | Expected |
|----|----------|----------|
| RE-001 | user relationship exists | BelongsTo instance |
| RE-002 | user returns owner | Correct User model |
| RE-003 | site relationship exists | BelongsTo instance |
| RE-004 | site returns site | Correct Site model |
| RE-005 | candidates relationship exists | HasMany instance |
| RE-006 | candidates returns collection | Collection of candidates |
| RE-007 | eager loading user | relationLoaded true |
| RE-008 | eager loading site | relationLoaded true |
| RE-009 | unique site constraint | Duplicate throws exception |
| RE-010 | delete cascades to candidates | Candidates deleted |

#### Scopes (SQ-001 to SQ-015)
| ID | Scenario | Expected |
|----|----------|----------|
| SQ-001 | scope_enabled filters enabled | Only enabled projects |
| SQ-002 | scope_enabled excludes disabled | No disabled projects |
| SQ-003 | scope_forUser filters by user | User's projects only |
| SQ-004 | scope_forUser excludes others | Other users excluded |
| SQ-005 | scope chainable | Combined filters work |

#### Helper Methods (HM-001 to HM-008)
| ID | Scenario | Expected |
|----|----------|----------|
| HM-001 | isDetectionEnabled true | Returns true |
| HM-002 | isDetectionEnabled false | Returns false |
| HM-003 | getWindowDays default | Returns 28 |
| HM-004 | getWindowDays custom | Returns custom value |

---

### ContentDecayCandidate (120+ tests)

#### Model Instantiation (MI-001 to MI-006)
| ID | Scenario | Expected |
|----|----------|----------|
| MI-001 | Create with required fields | Database record created |
| MI-002 | Create with all fields | All fields populated |
| MI-003 | Mass assignment protects id | ID unchanged |
| MI-004 | Factory creates valid model | Required fields present |
| MI-005 | Model uses timestamps | Timestamps populated |
| MI-006 | Default status is backlog | Status = 'backlog' |

#### Casts (CA-001 to CA-015)
| ID | Scenario | Expected |
|----|----------|----------|
| CA-001 | decay_score decimal | 0-100 with 2 places |
| CA-002 | detection_confidence decimal | 0.70-1.00 |
| CA-003 | reason_codes array | JSON array |
| CA-004 | signal_sources array | JSON array |
| CA-005 | evidence_json array | JSON array |
| CA-006 | signal_sources null | null handled |
| CA-007 | evidence_json null | null handled |
| CA-008 | severity enum | low/medium/high/critical |
| CA-009 | status enum | 6 valid states |

#### Relationships (RE-001 to RE-010)
| ID | Scenario | Expected |
|----|----------|----------|
| RE-001 | project relationship exists | BelongsTo instance |
| RE-002 | project returns project | Correct project |
| RE-003 | signals relationship exists | HasMany instance |
| RE-004 | refreshTasks relationship exists | HasMany instance |
| RE-005 | eager loading project | relationLoaded true |
| RE-006 | unique project+page constraint | Duplicate throws |
| RE-007 | same URL different project | Allowed |
| RE-008 | delete cascades to signals | Signals deleted |
| RE-009 | delete cascades to tasks | Tasks deleted |
| RE-010 | cascade from project | Deleted with project |

#### Scopes (SQ-001 to SQ-030)
| ID | Scenario | Expected |
|----|----------|----------|
| SQ-001 | byStatus('backlog') | Backlog items only |
| SQ-002 | byStatus('planned') | Planned items only |
| SQ-003 | bySeverity('critical') | Critical items only |
| SQ-004 | bySeverity('high') | High items only |
| SQ-005 | byPageType('blog') | Blog pages only |
| SQ-006 | minimumScore(50) | Score >= 50 |
| SQ-007 | backlog() | Status = backlog |
| SQ-008 | planned() | Status = planned |
| SQ-009 | inProgress() | Status = in_progress |
| SQ-010 | refreshed() | Status = refreshed |
| SQ-011 | recovered() | Status = recovered |
| SQ-012 | ignored() | Status = ignored |
| SQ-013 | scope chainable | Combined filters work |

#### Helper Methods (HM-001 to HM-015)
| ID | Scenario | Expected |
|----|----------|----------|
| HM-001 | markAsPlanned() | Status + planned_at set |
| HM-002 | markAsInProgress() | Status + started_at set |
| HM-003 | markAsRefreshed() | Status + refreshed_at set |
| HM-004 | markAsRecovered() | Status + recovered_at set |
| HM-005 | markAsIgnored() | Status + ignored_at set |

---

### ContentDecaySignal (90+ tests)

#### Model Instantiation (MI-001 to MI-005)
| ID | Scenario | Expected |
|----|----------|----------|
| MI-001 | Create with required fields | Database record created |
| MI-002 | Create with all fields | All metrics populated |
| MI-003 | Mass assignment protects id | ID unchanged |
| MI-004 | Factory creates valid model | Required fields present |
| MI-005 | Model uses timestamps | created_at populated |

#### Casts (CA-001 to CA-018)
| ID | Scenario | Expected |
|----|----------|----------|
| CA-001 | previous_clicks integer | Integer value |
| CA-002 | current_clicks integer | Integer value |
| CA-003 | previous_impressions integer | Integer value |
| CA-004 | current_impressions integer | Integer value |
| CA-005 | previous_ctr decimal | 4 decimal places |
| CA-006 | current_ctr decimal | 4 decimal places |
| CA-007 | previous_avg_position decimal | 2 decimal places |
| CA-008 | current_avg_position decimal | 2 decimal places |
| CA-015 | evidence_json array | JSON array |
| CA-017 | previous_start date | Carbon date |
| CA-018 | last_annotation_at datetime | Carbon datetime |

#### Relationships (RE-001 to RE-008)
| ID | Scenario | Expected |
|----|----------|----------|
| RE-001 | candidate relationship exists | BelongsTo instance |
| RE-002 | candidate returns candidate | Correct candidate |
| RE-003 | eager loading candidate | relationLoaded true |
| RE-004 | unique window constraint | Duplicate throws |
| RE-005 | same candidate different windows | Allowed |
| RE-006 | delete cascade from candidate | Signal deleted |
| RE-007 | multiple signals per candidate | Multiple records |
| RE-008 | no updated_at column | Only created_at |

#### Scopes (SQ-001 to SQ-015)
| ID | Scenario | Expected |
|----|----------|----------|
| SQ-001 | forCandidate($id) | Candidate's signals |
| SQ-002 | inDateRange($start, $end) | Date filtered |
| SQ-006 | with click decline | Clicks decreased |
| SQ-007 | with impression decline | Impressions decreased |
| SQ-008 | with position decline | Position worsened |
| SQ-009 | with CTR decline | CTR decreased |

#### Helper Methods (HM-001 to HM-012)
| ID | Scenario | Expected |
|----|----------|----------|
| HM-001 | getClickDelta() | current - previous |
| HM-002 | getClickDelta() positive | Positive value |
| HM-003 | getImpressionDelta() | current - previous |
| HM-004 | getCtrDelta() | current - previous |
| HM-005 | getPositionDelta() | current - previous |

---

### ContentRefreshTask (90+ tests)

#### Model Instantiation (MI-001 to MI-005)
| ID | Scenario | Expected |
|----|----------|----------|
| MI-001 | Create with required fields | Database record created |
| MI-002 | Create with all fields | All fields populated |
| MI-003 | Mass assignment protects id | ID unchanged |
| MI-004 | Factory creates valid model | Required fields present |
| MI-005 | Model uses timestamps | Timestamps populated |

#### Casts (CA-001 to CA-010)
| ID | Scenario | Expected |
|----|----------|----------|
| CA-001 | due_date date | Carbon date |
| CA-002 | due_date null | null handled |
| CA-003 | metadata_json array | JSON array |
| CA-005 | status enum | 4 valid states |
| CA-006 | source enum | 3 valid sources |

#### Relationships (RE-001 to RE-010)
| ID | Scenario | Expected |
|----|----------|----------|
| RE-001 | candidate relationship exists | BelongsTo instance |
| RE-002 | candidate returns candidate | Correct candidate |
| RE-003 | owner relationship exists | BelongsTo instance |
| RE-004 | owner returns user | Correct User |
| RE-005 | owner null | null handled |
| RE-008 | delete cascade from candidate | Task deleted |
| RE-009 | multiple tasks per candidate | Multiple records |
| RE-010 | owner delete sets null | owner_user_id = null |

#### Scopes (SQ-001 to SQ-020)
| ID | Scenario | Expected |
|----|----------|----------|
| SQ-001 | byStatus('todo') | Todo tasks only |
| SQ-002 | byStatus('done') | Done tasks only |
| SQ-003 | overdue() | Past due + todo |
| SQ-004 | overdue() excludes done | No done tasks |
| SQ-005 | forUser($id) | User's tasks |
| SQ-007 | todo() | Status = todo |
| SQ-008 | inProgress() | Status = in_progress |
| SQ-009 | done() | Status = done |
| SQ-010 | skipped() | Status = skipped |

#### Helper Methods (HM-001 to HM-010)
| ID | Scenario | Expected |
|----|----------|----------|
| HM-001 | markAsDone() | Status = done |
| HM-002 | markAsSkipped() | Status = skipped |
| HM-003 | isOverdue() true | Past due + todo |
| HM-004 | isOverdue() false future | Future due |
| HM-005 | isOverdue() false done | Done status |

---

## Factory States

### ContentDecayProjectFactory
| State | Description |
|-------|-------------|
| `disabled()` | detection_enabled = false |
| `noDigest()` | digest_enabled = false |
| `weeklyDigest()` | digest_frequency = 'weekly' |
| `monthlyDigest()` | digest_frequency = 'monthly' |
| `window7Days()` | window_days = 7 |
| `window14Days()` | window_days = 14 |
| `window56Days()` | window_days = 56 |
| `neverScanned()` | last_scanned_at = null |
| `recentlyScanned()` | last_scanned_at = recent |

### ContentDecayCandidateFactory
| State | Description |
|-------|-------------|
| `planned()` | status = 'planned' + planned_at |
| `inProgress()` | status = 'in_progress' + started_at |
| `refreshed()` | status = 'refreshed' + refreshed_at |
| `recovered()` | status = 'recovered' + recovered_at |
| `ignored()` | status = 'ignored' + ignored_at |
| `criticalSeverity()` | severity = 'critical' |
| `highSeverity()` | severity = 'high' |
| `mediumSeverity()` | severity = 'medium' |
| `lowSeverity()` | severity = 'low' |
| `blogPage()` | page_type = 'blog' |
| `productPage()` | page_type = 'product' |
| `highLostClicks()` | Large lost_click_estimate |

### ContentDecaySignalFactory
| State | Description |
|-------|-------------|
| `clickDecline()` | Significant click drop |
| `impressionDecline()` | Significant impression drop |
| `ctrDecline()` | CTR decrease |
| `positionDecline()` | Position worsened |
| `withAuditIssues()` | unresolved_audit_issues > 0 |
| `staleContent()` | content_age_days > 365 |
| `severeDecay()` | All metrics declined |

### ContentRefreshTaskFactory
| State | Description |
|-------|-------------|
| `inProgress()` | status = 'in_progress' |
| `done()` | status = 'done' |
| `skipped()` | status = 'skipped' |
| `overdue()` | Past due_date + todo |
| `dueSoon()` | Due within 7 days |
| `highPriority()` | priority = high |
| `withOwner()` | Has owner_user_id |
| `metaUpdate()` | recommendation_type = meta_update |
| `contentRefresh()` | recommendation_type = content_refresh |

---

## Test Execution

```bash
# Run all Content Decay model tests
cd backend
php artisan test --filter ContentDecay

# Run individual test files
php artisan test tests/Unit/Models/ContentDecayProjectTest.php
php artisan test tests/Unit/Models/ContentDecayCandidateTest.php
php artisan test tests/Unit/Models/ContentDecaySignalTest.php
php artisan test tests/Unit/Models/ContentRefreshTaskTest.php

# Run with coverage
php artisan test --coverage --filter ContentDecay
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-36-1 (Migrations) | To Deploy | Tables must exist for tests |
| User model | Done | Existing |
| Site model | Done | Existing |

---

## Notes for Developer

### Model Implementation Checklist

**ContentDecayProject:**
- [ ] BelongsTo: User, Site
- [ ] HasMany: ContentDecayCandidate (candidates)
- [ ] Casts: settings_json → array, last_scanned_at → datetime
- [ ] Scopes: enabled(), forUser($userId)
- [ ] Helpers: isDetectionEnabled(), getWindowDays()

**ContentDecayCandidate:**
- [ ] BelongsTo: ContentDecayProject (project)
- [ ] HasMany: ContentDecaySignal (signals), ContentRefreshTask (refreshTasks)
- [ ] Casts: reason_codes → array, signal_sources → array, evidence_json → array
- [ ] Casts: decay_score → decimal:2, detection_confidence → decimal:2
- [ ] Scopes: byStatus, bySeverity, byPageType, minimumScore, backlog, planned, etc.
- [ ] Accessors: page_url_hash, is_recovered
- [ ] Helpers: markAsPlanned, markAsInProgress, markAsRefreshed, markAsRecovered, markAsIgnored

**ContentDecaySignal:**
- [ ] BelongsTo: ContentDecayCandidate (candidate)
- [ ] Casts: previous_ctr, current_ctr → decimal:4
- [ ] Casts: previous_avg_position, current_avg_position → decimal:2
- [ ] Casts: evidence_json → array
- [ ] Scopes: forCandidate, inDateRange
- [ ] Helpers: getClickDelta, getImpressionDelta, getCtrDelta, getPositionDelta

**ContentRefreshTask:**
- [ ] BelongsTo: ContentDecayCandidate (candidate), User (owner)
- [ ] Casts: metadata_json → array, due_date → date
- [ ] Scopes: byStatus, overdue, forUser, todo, inProgress, done, skipped
- [ ] Helpers: markAsDone, markAsSkipped, isOverdue

### Key Implementation Notes

1. **Unique Constraints:**
   - `content_decay_projects`: unique site_id
   - `content_decay_candidates`: unique (project_id, page_url_hash)
   - `content_decay_signals`: unique (candidate_id, previous_start, previous_end, current_start, current_end)

2. **Cascade Deletes:**
   - Project delete → cascade to candidates
   - Candidate delete → cascade to signals, tasks
   - User delete → SET NULL on refresh_tasks.owner_user_id

3. **Auto-Hash page_url:**
   - Consider adding a mutator to auto-generate page_url_hash from page_url

4. **Soft Deletes:**
   - Consider if any models should use SoftDeletes (not required by spec)

---

## Comments

### PreTester (2026-03-10)
**Test Suite Created**

Created comprehensive test suite for all 4 Content Decay Eloquent models:

**Test Summary:**
| Model | Tests | File Size |
|-------|-------|-----------|
| ContentDecayProject | 85+ | 30KB |
| ContentDecayCandidate | 120+ | 52KB |
| ContentDecaySignal | 90+ | 39KB |
| ContentRefreshTask | 90+ | 32KB |
| **Total** | **385+** | **153KB** |

**Factory Files Created:**
- ContentDecayProjectFactory.php (4.3KB)
- ContentDecayCandidateFactory.php (7.6KB)
- ContentDecaySignalFactory.php (7.1KB)
- ContentRefreshTaskFactory.php (6.3KB)

**Test Categories Covered:**
1. Model Instantiation
2. Casts (decimal, boolean, array, enum, date/datetime)
3. Relationships (BelongsTo, HasMany)
4. Scopes (filtering, status-based, severity-based, etc.)
5. Helper Methods (status transitions, delta calculations)
6. Accessors (computed attributes)
7. Factory States
8. Edge Cases (nulls, large values, unicode)
9. Security (SQL injection, XSS, invalid values)
10. Integration (CRUD, relationships, queries)
11. Performance (N+1 prevention, index usage)

**Status:** `Tests Coverage In Progress` → Ready for Developer implementation

---

*Created by PreTester agent on 2026-03-10*
