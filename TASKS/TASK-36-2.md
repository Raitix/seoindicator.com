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
