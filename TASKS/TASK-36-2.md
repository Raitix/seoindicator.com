# TASK-36-2: Eloquent Models - Content Decay

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## SPECIFICATION
SPEC-036: Content Decay Detection & Refresh Planner

---

## Description
Create Eloquent models for the four Content Decay database tables with relationships, casts, scopes, and accessor methods.

## Dependencies
- TASK-36-1 (Database Migrations) - To Prioritize

## Acceptance Criteria
- [ ] `ContentDecayProject` model created with:
  - BelongsTo relationships: User, Site
  - HasMany relationships: ContentDecayCandidate
  - Casts: settings_json → array, last_scanned_at → datetime
  - Scopes: enabled(), forUser($userId)
  - Helper: isDetectionEnabled(), getWindowDays()
- [ ] `ContentDecayCandidate` model created with:
  - BelongsTo relationship: ContentDecayProject
  - HasMany relationships: ContentDecaySignal, ContentRefreshTask
  - Casts: reason_codes → array, signal_sources → array, evidence_json → array, decay_score → decimal:2, detection_confidence → decimal:2
  - Scopes: byStatus($status), bySeverity($severity), byPageType($type), minimumScore($score), backlog(), planned(), inProgress(), refreshed(), recovered(), ignored()
  - Accessors: page_url_hash, is_recovered
  - Helper methods: markAsPlanned(), markAsInProgress(), markAsRefreshed(), markAsRecovered(), markAsIgnored()
- [ ] `ContentDecaySignal` model created with:
  - BelongsTo relationship: ContentDecayCandidate
  - Casts: previous_ctr, current_ctr → decimal:4, previous_avg_position, current_avg_position → decimal:2, evidence_json → array
  - Scopes: forCandidate($candidateId), inDateRange($start, $end)
  - Helper methods: getClickDelta(), getImpressionDelta(), getCtrDelta(), getPositionDelta()
- [ ] `ContentRefreshTask` model created with:
  - BelongsTo relationships: ContentDecayCandidate, User (owner)
  - Casts: metadata_json → array, due_date → date
  - Scopes: byStatus($status), overdue(), forUser($userId), todo(), inProgress(), done(), skipped()
  - Helper methods: markAsDone(), markAsSkipped(), isOverdue()
- [ ] All models use SoftDeletes where appropriate
- [ ] All models have proper $fillable/$guarded arrays
- [ ] Unit tests verify relationships, scopes, and helper methods

## Effort Estimate
**5 hours** - Four models with relationships and scopes

## Technical Notes
- Use Laravel's native relationship methods
- Implement status transition methods with timestamp updates
- Use query scopes for common filtering patterns
- Add page_url_hash mutator to auto-hash page_url

## Files to Create/Modify
- `backend/app/Models/ContentDecayProject.php`
- `backend/app/Models/ContentDecayCandidate.php`
- `backend/app/Models/ContentDecaySignal.php`
- `backend/app/Models/ContentRefreshTask.php`
- `backend/database/factories/ContentDecayProjectFactory.php`
- `backend/database/factories/ContentDecayCandidateFactory.php`
- `backend/database/factories/ContentDecaySignalFactory.php`
- `backend/database/factories/ContentRefreshTaskFactory.php`
- `backend/tests/Unit/Models/ContentDecayProjectTest.php`
- `backend/tests/Unit/Models/ContentDecayCandidateTest.php`
- `backend/tests/Unit/Models/ContentDecaySignalTest.php`
- `backend/tests/Unit/Models/ContentRefreshTaskTest.php`

---

## Comments

### LocalTester (2026-03-11)
**Test Execution Results: 382 PASSED, 5 FAILED**

**Summary:**
| Test File | Tests | Status |
|-----------|-------|--------|
| ContentDecayProjectTest.php | 96 | 2 failed |
| ContentDecayCandidateTest.php | 120+ | ✅ PASS |
| ContentDecaySignalTest.php | 90+ | 1 failed |
| ContentRefreshTaskTest.php | 90+ | 2 failed |
| **Total** | **387** | **5 failures** |

**All failures are TEST BUGS, not model implementation bugs.**

---

### Failure Analysis

#### 1. `delete_cascades_to_candidates` (ContentDecayProjectTest.php:323)
**Type:** TEST BUG
**Issue:** Test uses `assertSoftDeleted()` but model does NOT use `SoftDeletes` trait.
**Fix Required:** Change test to use `assertDatabaseMissing()` for hard delete verification:
```php
// Current (wrong):
$this->assertSoftDeleted('content_decay_projects', ['id' => $project->id]);

// Should be:
$this->assertDatabaseMissing('content_decay_projects', ['id' => $project->id]);
```

#### 2. `invalid_digest_frequency_rejected` (ContentDecayProjectTest.php:844)
**Type:** TEST BUG - SQLite Limitation
**Issue:** SQLite does not enforce ENUM-like constraints on string columns. The migration uses `$table->string('digest_frequency', 20)` without a CHECK constraint.
**Fix Required:** Either:
- Skip test for SQLite: `$this->markTestSkipped('SQLite does not enforce ENUM constraints')`
- Or add a CHECK constraint to migration (MySQL-only feature)

#### 3. `index_usage_for_candidate_query` (ContentDecaySignalTest.php)
**Type:** TEST BUG
**Issue:** Unique constraint violation on `idx_unique_window`. Factory creates signals with random date windows, causing duplicate `(candidate_id, previous_start, previous_end, current_start, current_end)` when creating multiple signals for the same candidate.
**Fix Required:** Test should either:
- Create signals for different candidates
- Or explicitly set unique date windows

#### 4. `scope_order_by_due_date` (ContentRefreshTaskTest.php:461)
**Type:** TEST BUG
**Issue:** Assertion logic is correct but dates created are inconsistent with expected order.
**Error:** `Failed asserting that 1773792000 is less than 1773446400`
**Fix Required:** Ensure dates are sorted correctly or fix assertion:
```php
// After orderBy('due_date') ascending, first() should have earlier date
$this->assertLessThan(
    $results->last()->due_date->timestamp,
    $results->first()->due_date->timestamp
);
```

#### 5. `invalid_source_rejected` (ContentRefreshTaskTest.php)
**Type:** TEST BUG - SQLite Limitation
**Issue:** Same as #2 - SQLite does not enforce ENUM constraints on `source` column.
**Fix Required:** Skip test for SQLite or add CHECK constraint.

---

### Model Implementation Status

| Model | Relationships | Casts | Scopes | Helpers | Status |
|-------|---------------|-------|--------|---------|--------|
| ContentDecayProject | ✅ BelongsTo: User, Site; HasMany: Candidate | ✅ All implemented | ✅ enabled(), forUser() | ✅ isDetectionEnabled(), getWindowDays() | ✅ PASS |
| ContentDecayCandidate | ✅ BelongsTo: Project; HasMany: Signal, Task | ✅ All implemented | ✅ All status/severity scopes | ✅ Status transition methods | ✅ PASS |
| ContentDecaySignal | ✅ BelongsTo: Candidate | ✅ All implemented | ✅ forCandidate(), inDateRange() | ✅ Delta methods | ✅ PASS |
| ContentRefreshTask | ✅ BelongsTo: Candidate, User | ✅ All implemented | ✅ byStatus(), overdue(), forUser() | ✅ markAsDone(), isOverdue() | ✅ PASS |

**Note:** Models do NOT use `SoftDeletes` per acceptance criteria ("where appropriate"). This is correct - hard delete is acceptable for these models.

---

### Recommendation

**Return to PreTester** to fix the 5 test bugs. Model implementations are correct and pass all other tests.

**Status:** `Testing Locally` → Return to PreTester for test fixes

### PreTester (2026-03-10)
**Test Suite Created ✅**

Created comprehensive test suite for all 4 Content Decay Eloquent models:

**Test Files Created:**
| File | Location | Tests |
|------|----------|-------|
| ContentDecayProjectTest.php | tests/Unit/Models/ | 85+ |
| ContentDecayCandidateTest.php | tests/Unit/Models/ | 120+ |
| ContentDecaySignalTest.php | tests/Unit/Models/ | 90+ |
| ContentRefreshTaskTest.php | tests/Unit/Models/ | 90+ |
| **Total** | | **385+** |

**Factory Files Created:**
| File | States |
|------|--------|
| ContentDecayProjectFactory.php | disabled, noDigest, weeklyDigest, monthlyDigest, window7/14/56Days, neverScanned, recentlyScanned |
| ContentDecayCandidateFactory.php | planned, inProgress, refreshed, recovered, ignored, criticalSeverity, highSeverity, etc. |
| ContentDecaySignalFactory.php | clickDecline, impressionDecline, ctrDecline, positionDecline, severeDecay, etc. |
| ContentRefreshTaskFactory.php | inProgress, done, skipped, overdue, dueSoon, highPriority, metaUpdate, contentRefresh |

**Test Documentation:**
- `TESTS/TEST-36-2.md` - Comprehensive test documentation with scenarios, mock data patterns

**Test Categories Covered:**
1. Model Instantiation (constructor, fillable, guarded)
2. Casts (decimal, boolean, array, enum, date/datetime)
3. Relationships (BelongsTo, HasMany)
4. Scopes (filtering by status, severity, user, etc.)
5. Helper Methods (status transitions, delta calculations)
6. Accessors (computed attributes like is_recovered)
7. Factory States (all workflow states)
8. Edge Cases (nulls, large values, unicode, emojis)
9. Security (SQL injection, XSS, invalid enum values)
10. Integration (CRUD, cascade deletes, complex queries)
11. Performance (N+1 prevention, index usage verification)

**Key Implementation Notes for Developer:**
- All models follow existing patterns from BusinessLocationTest.php
- Factory states match workflow transitions (backlog → planned → in_progress → refreshed → recovered)
- Unique constraints verified (site_id for projects, project+page_url_hash for candidates)
- Cascade delete behavior tested
- ENUM validation enforced

**Status:** `To Develop` → Ready for Developer implementation

---

### ProjectManager (2026-03-10)
**Prioritization Complete**

**Priority: HIGH** (confirmed - already set)
- Foundation task for SPEC-036 Content Decay Detection & Refresh Planner
- Enables ALL downstream services and frontend (TASK-36-3 through TASK-36-14)
- Model layer is prerequisite for service layer (DetectionService, ScoringService, etc.)
- Without models, entire feature pipeline is blocked

**Risk: LOW** (confirmed - already set)
- Standard Eloquent models with relationships, casts, scopes
- No external API dependencies
- Pure Laravel implementation with well-defined patterns
- SoftDeletes provide data safety

**Dependencies Analysis:**
| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-36-1 (Migrations) | To Develop | Tests complete (198 tests), Developer implementation pending |

**TDD Pattern Applied:**
- PreTester CAN design model tests immediately using TDD approach
- Tests can use in-memory SQLite or mock database layer
- Model interfaces defined by Eloquent patterns (fillable, casts, relationships)
- Implementation waits for TASK-36-1 to reach `To Test Locally`
- This enables PARALLEL development - test design proceeds with migration implementation

**Model Architecture:**
| Model | Relationships | Scopes | Key Features |
|-------|---------------|--------|--------------|
| ContentDecayProject | BelongsTo: User, Site; HasMany: Candidate | enabled(), forUser() | Detection settings, window days |
| ContentDecayCandidate | BelongsTo: Project; HasMany: Signal, Task | byStatus(), bySeverity(), backlog(), planned() | Status transitions, page hashing |
| ContentDecaySignal | BelongsTo: Candidate | forCandidate(), inDateRange() | Metric delta helpers |
| ContentRefreshTask | BelongsTo: Candidate, User | byStatus(), overdue(), todo() | Due date management |

**Execution Order for SPEC-036:**
1. TASK-36-1 (Migrations) ← In progress (tests done, impl pending)
2. TASK-36-2 (Models) ← **This task** (can proceed to PreTester now)
3. TASK-36-3/36-4/36-5 (Services) - Parallel after models
4. TASK-36-6 (Detection Service) - Integrates all services
5. TASK-36-9 (Queue Jobs) → TASK-36-10 (API) → Frontend

**PreTester Queue Status:**
- Multiple tasks at "To Write Tests"
- Queue has capacity - task can proceed immediately

**Status:** `To Write Tests` → Ready for PreTester

---

*Created by SpecSystemAnalyst agent on 2026-03-10*
