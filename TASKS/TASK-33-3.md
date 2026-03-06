# TASK-33-3: Create Badge Impressions Table

## STATUS
`To Write Tests`

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
