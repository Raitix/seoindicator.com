# TASK-5-1: Database Schema - Keyword Suggestions Table

## STATUS
`In Deployment`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create database migration for keyword_suggestions and dismissed_suggestions tables. This is the foundation for the keyword discovery feature.

## Acceptance Criteria
- [x] Migration creates `keyword_suggestions` table with: id, site_id, keyword, source, search_volume, difficulty_score, opportunity_score, is_tracking, is_dismissed, discovered_at, updated_at
- [x] Migration creates `dismissed_suggestions` table with: id, site_id, keyword, dismissed_at
- [x] Foreign key constraints: keyword_suggestions.site_id → sites.id, dismissed_suggestions.site_id → sites.id
- [x] Unique constraint on (site_id, keyword) in keyword_suggestions
- [x] Indexes on: site_id, keyword, opportunity_score DESC
- [x] Migration is reversible (down method drops tables)

## Dependencies
- sites table must exist (FEAT-001)
- FEAT-001 (Keyword Rank Tracking) must be available for tracking status

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Source column values: 'suggest', 'competitor', 'related'
- search_volume and difficulty_score nullable for MVP (no data provider yet)
- opportunity_score calculated by utility (TASK-5-3)

---

## Comments
_Agent communication log_

### [2026-02-27 10:20] Agent: DevOps
**Deployment Started**

Connecting to production server via SSH to:
1. Pull latest code from origin/main
2. Run composer install
3. Execute database migration

Migration: `2026_02_26_230500_create_keyword_suggestions_tables.php`

Status: To Deploy → In Deployment

### [2026-02-26 23:10] Agent: Developer
**Implementation Complete**

Created migration file: `backend/database/migrations/2026_02_26_230500_create_keyword_suggestions_tables.php`

**Implementation Details:**
1. **keyword_suggestions table:**
   - All required columns with correct types
   - Used `string('keyword')` (VARCHAR 255) instead of TEXT to support unique constraint in MySQL
   - Unique constraint on (site_id, keyword) - allows same keyword for different sites
   - FK constraint to sites.id with CASCADE delete
   - Indexes: idx_suggestions_site_id, idx_suggestions_keyword, idx_suggestions_opportunity

2. **dismissed_suggestions table:**
   - All required columns
   - Added timestamps() for test compatibility (created_at, updated_at)
   - FK constraint to sites.id with CASCADE delete
   - Index: idx_dismissed_site

3. **Test Results:**
   - All 26 unit tests pass ✓
   - Tests run on SQLite in-memory database

**Notes:**
- Changed keyword column from TEXT to VARCHAR(255) to support unique constraint in MySQL
- Added timestamps to dismissed_suggestions table (test requirement)

Status: To Develop → To Test Locally

### [2026-02-26 22:52] Agent: PreTester
Test design complete. Created:
- **Test Documentation**: `TESTS/SPEC-5-1.md` - 29 test scenarios
- **Unit Tests**: `backend/tests/Unit/Migrations/CreateKeywordSuggestionsTableTest.php` - 26 test methods

**Test Coverage:**
- keyword_suggestions table: 19 scenarios (columns, types, defaults, constraints, indexes)
- dismissed_suggestions table: 8 scenarios (columns, defaults, FK constraints)
- Migration rollback: 2 scenarios

**Key Test Requirements for Developer:**
1. Both tables must exist with all specified columns
2. FK constraints to sites.id with CASCADE delete
3. Unique constraint on (site_id, keyword) in keyword_suggestions
4. Indexes on: site_id, keyword, opportunity_score DESC
5. Valid source values: 'suggest', 'competitor', 'related'
6. Nullable fields: search_volume, difficulty_score, opportunity_score
7. Boolean defaults: is_tracking=FALSE, is_dismissed=FALSE
8. Timestamp defaults: discovered_at=CURRENT_TIMESTAMP, dismissed_at=CURRENT_TIMESTAMP

**Migration File Name Pattern:** `2026_02_26_HHMMSS_create_keyword_suggestions_tables.php`

Status: To Develop → Developer

### [2026-02-26 22:42] Agent: ProjectManager
Created task from SPEC-005 breakdown. Priority: High (foundation task for all TASK-5-* tasks). Risk: Medium (non-destructive schema migration). Dependencies: FEAT-001 (sites table).

### [2026-02-27 08:10] Agent: LocalTester
**Local Testing Complete - PASS**

**Test Execution:**
```
php artisan test --filter CreateKeywordSuggestionsTableTest
```

**Results: 26/26 PASS (46 assertions)**

**keyword_suggestions table (19 tests):**
- ✓ Table exists
- ✓ All required columns (id, site_id, keyword, source, search_volume, difficulty_score, opportunity_score, is_tracking, is_dismissed, discovered_at, updated_at)
- ✓ Primary key auto-increment
- ✓ FK constraint to sites.id (rejects invalid site_id)
- ✓ Required fields (keyword, source) reject NULL
- ✓ Nullable fields (search_volume, difficulty_score, opportunity_score) accept NULL
- ✓ Boolean defaults: is_tracking=FALSE, is_dismissed=FALSE
- ✓ Timestamp defaults: discovered_at=CURRENT_TIMESTAMP
- ✓ Unique constraint on (site_id, keyword) - duplicates rejected
- ✓ Same keyword allowed for different sites
- ✓ Cascade delete when site deleted
- ✓ Valid source values: 'suggest', 'competitor', 'related'
- ✓ Indexes: idx_suggestions_site_id, idx_suggestions_keyword

**dismissed_suggestions table (7 tests):**
- ✓ Table exists
- ✓ All required columns (id, site_id, keyword, dismissed_at)
- ✓ FK constraint to sites.id
- ✓ Timestamp default: dismissed_at=CURRENT_TIMESTAMP
- ✓ Cascade delete when site deleted
- ✓ Index: idx_dismissed_site

**Migration rollback:**
- ✓ down() method exists and drops both tables

**Adversarial Testing:**
- Tested invalid FK references - correctly rejected
- Tested duplicate unique constraint - correctly rejected
- Tested NULL in required fields - correctly rejected

**Decision: APPROVED FOR DEPLOYMENT**

Status: Testing Locally → To Deploy
