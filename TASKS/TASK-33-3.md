# TASK-33-3: Create Badge Impressions Table

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Low`

## Specification
**SPEC-33**: Public SEO Audit Reports & Embeddable Score Widget

## Description
Create the badge_impressions table to track analytics for embedded badge views and clicks. This measures the reach of embedded widgets on third-party sites.

## Acceptance Criteria
- [ ] Migration creates `badge_impressions` table
- [ ] All columns from spec schema created:
  - `id`, `audit_id`, `public_hash`, `referrer_domain`, `impression_at`, `clicked_at`, `click_target`, `ip_hash`
- [ ] Foreign key constraint on audit_id with CASCADE delete
- [ ] Indexes on audit_id, public_hash, impression_at for query performance
- [ ] BadgeImpression model created with relationships
- [ ] Factory created for testing
- [ ] Unit test verifies table structure and relationships

## Dependencies
- TASK-33-1 (visibility columns exist)

## Effort Estimate
**2 hours** - Standard migration with model

## Technical Notes
- referrer_domain extracted from HTTP referer header (domain only, no path)
- click_target indicates where user clicked: 'audit' or 'home'
- clicked_at initially NULL, set when click event received
- IP hashed for privacy compliance

## Comments

### [2026-03-06 11:10 GMT+2] ProjectManager - Prioritization Assessment

**Decision:** HIGH priority confirmed ✅

**Priority Assessment:**
- **Foundation Task:** Creates database schema for badge impression analytics
- **Dependency Status:** TASK-33-1 (visibility columns) - Done ✅ - SATISFIED
- **Critical Path:** Part of Phase 1 (Database Schema) in SPEC-33
- **Effort:** 2 hours (standard migration + model)
- **Impact:** Enables tracking of embedded badge views/clicks on third-party sites

**Risk Assessment: LOW (confirmed)**
- Standard Laravel migration with model and factory
- Non-destructive (creates new table)
- No external API dependencies
- IP hashing ensures privacy compliance

**Key Technical Points:**
- `badge_impressions` table with 8 columns
- Foreign key CASCADE delete on audit_id
- Indexes on audit_id, public_hash, impression_at for query performance
- BadgeImpression model with relationships
- Factory for testing support

**TDD Ready:** PreTester can design tests immediately
- Migration creates correct columns and indexes
- Foreign key constraints work
- Model relationships function correctly
- Factory creates valid test data

**Handoff to PreTester:** Ready for test design. All dependencies satisfied.
- `backend/database/migrations/xxxx_create_badge_impressions_table.php`
- `backend/app/Models/BadgeImpression.php`
- `backend/database/factories/BadgeImpressionFactory.php`
- `backend/tests/Unit/Models/BadgeImpressionTest.php`

### [2026-03-06 11:15 GMT+2] ProjectManager - Prioritization Complete

**Decision:** Prioritization complete - Handoff to PreTester ✅

**Status Update:** `To Prioritize` → `To Write Tests`

**Summary:**
- Priority: HIGH (confirmed) - Foundation task for badge analytics
- Risk: LOW (confirmed) - Standard Laravel migration + model
- Dependencies: TASK-33-1 (visibility columns) Done ✅
- All acceptance criteria are testable
- TDD pattern applicable: tests can be designed immediately

**Next Agent:** PreTester - Design test coverage for migration, model, and factory

### [2026-03-06 12:50 GMT+2] PreTester - Test Design Complete ✅

**Tests Designed:** 106+ test scenarios across 3 files
- Migration Tests: 54 scenarios (CreateBadgeImpressionsTableTest.php)
- Model Tests: 52 scenarios (BadgeImpressionTest.php)
- Factory: 11 states (BadgeImpressionFactory.php)

**Files Created:**
- `PROJECT-SEOINDICATOR/TESTS/TEST-33-3.md` (test documentation)
- `backend/tests/Unit/Migrations/CreateBadgeImpressionsTableTest.php` (54 tests)
- `backend/tests/Unit/Models/BadgeImpressionTest.php` (52 tests)
- `backend/database/factories/BadgeImpressionFactory.php` (11 factory states)

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| Table Structure (MS) | 9 | All 8 columns verified with types |
| Indexes (MS) | 3 | audit_id, public_hash, impression_at |
| Foreign Keys (MS) | 2 | CASCADE delete verified |
| Constraints (MS) | 1 | No unique on public_hash |
| Data Operations (MS) | 8 | Insert, NULL handling, errors |
| Cascade Delete (MS) | 1 | Orphan removal verified |
| Reversibility (MS) | 2 | Rollback, re-migrate |
| Max Lengths (MS) | 4 | Field boundary tests |
| Click Target Values (MS) | 3 | audit/home/invalid tests |
| Index Usage (MS) | 3 | Query performance |
| Bulk Operations (MS) | 6 | Insert, update, aggregation |
| Edge Cases (MS) | 12 | Unicode, timestamps, FK violations |
| Model Instantiation (MO) | 4 | Table, PK, timestamps |
| Model Fillable (MO) | 7 | All 7 fields |
| Model Casts (MO) | 2 | datetime casts |
| Relationships (MO) | 2 | belongsTo Audit |
| Model Scopes (MO) | 6 | forAudit, forHash, clicked, notClicked, withClickTarget, recent |
| Accessors (MO) | 1 | is_clicked |
| CRUD (MO) | 4 | Create, query, delete, update |
| Analytics (MO) | 4 | Count, CTR, unique impressions |
| Validation (MO) | 3 | click_target values |
| Serialization (MO) | 4 | Update, JSON, hidden, guarded |
| Bulk Operations (MO) | 2 | Delete, cascade |
| Factory States (MO) | 6 | clicked, notClicked, forAudit, fromDomain, clickAudit, clickHome |
| Performance (MO) | 2 | Large dataset, indexed queries |
| Security (MO) | 1 | IP hashed, not plain |

**Factory States:** 11 states for testing flexibility
- `clicked()` - Impression with click event
- `notClicked()` - Impression without click
- `forAudit($audit)` - Link to specific audit
- `fromDomain($domain)` - Set referrer domain
- `clickAudit()` - Click target = 'audit'
- `clickHome()` - Click target = 'home'
- `recent($days)` - Within X days
- `withIpHash($ip)` - Set IP hash
- `fromGoogle()` - Referrer = google.com
- `directVisit()` - No referrer
- `withHash($hash)` - Specific public hash

**Key Test Patterns:**
- No unique constraint on `public_hash` (multiple impressions per hash allowed)
- `clicked_at` initially NULL, set when click event received
- `click_target` enum-like values: 'audit' or 'home'
- IP hashed with SHA-256 + app.key salt for privacy
- CASCADE delete foreign key on `audit_id`
- Timestamp precision tests for impression_at

**TDD Pattern:** Tests will fail until migration and model are created by Developer.

**Handoff to Developer:** Ready for implementation. All test scenarios documented.

**Status Update:** `To Write Tests` → `To Develop`
