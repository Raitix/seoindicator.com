# TASK-33-2: Create Public Audit Views Table

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Low`

## Specification
**SPEC-33**: Public SEO Audit Reports & Embeddable Score Widget

## Description
Create the public_audit_views table to track analytics for public audit page views. This enables measuring the reach and effectiveness of shared audits.

## Acceptance Criteria
- [ ] Migration creates `public_audit_views` table
- [ ] All columns from spec schema created:
  - `id`, `audit_id`, `public_hash`, `viewed_at`, `referrer`, `ip_hash`, `user_agent`, `country_code`, `is_lead_capture`, `lead_email`, `created_at`
- [ ] Foreign key constraint on audit_id with CASCADE delete
- [ ] Indexes on audit_id, public_hash, viewed_at for query performance
- [ ] PublicAuditView model created with relationships
- [ ] Factory created for testing
- [ ] Unit test verifies table structure and relationships

## Dependencies
- TASK-33-1 (visibility columns exist)

## Effort Estimate
**2 hours** - Standard migration with model

## Technical Notes
- IP addresses stored as SHA-256 hash + salt for privacy
- referrer column limited to 500 chars for long URLs
- country_code stored as ISO 2-char code
- is_lead_capture tracks if visitor submitted email

## Files to Create
- `backend/database/migrations/xxxx_create_public_audit_views_table.php`
- `backend/app/Models/PublicAuditView.php`
- `backend/database/factories/PublicAuditViewFactory.php`
- `backend/tests/Unit/Models/PublicAuditViewTest.php`

## Comments

### [2026-03-05 23:20 GMT+2] ProjectManager - Prioritization Assessment

**Decision:** HIGH priority confirmed ✅

**Priority Assessment:**
- **Foundation Task:** Phase 1 database schema for public audit analytics
- **Dependency Satisfied:** TASK-33-1 (visibility columns) is Done ✅
- **Enables Downstream:** This table is required for:
  - TASK-33-3 (PublicAuditController - needs model)
  - TASK-33-4 (PublicAudit model - relates to views)
  - TASK-33-5 (PublicAuditService - queries view counts)
  - Analytics and reporting features throughout SPEC-33
- **Effort:** 2 hours (low effort, high impact)

**Risk Assessment: LOW (confirmed)**
- Standard Laravel migration (non-destructive, adds table only)
- No external APIs
- Privacy-conscious design (IP hashed with salt)
- Standard Eloquent model with relationships

**Key Technical Points:**
- 11 columns: id, audit_id, public_hash, viewed_at, referrer, ip_hash, user_agent, country_code, is_lead_capture, lead_email, created_at
- Foreign key CASCADE delete (views removed when audit deleted)
- 3 indexes for query performance (audit_id, public_hash, viewed_at)
- IP privacy via SHA-256 hash + salt
- Lead capture tracking for conversion analytics

**TDD Ready:** PreTester can design tests immediately
- Test migration creates all columns correctly
- Test foreign key constraint works (cascade delete)
- Test indexes exist for performance
- Test model relationships (belongsTo Audit)
- Test factory generates valid data

**Handoff to PreTester:** Ready for test design. All acceptance criteria are testable.

### [2026-03-05 23:55 GMT+2] PreTester - Tests Complete

**Status:** To Write Tests → To Develop ✅

**Files Created:**
- `backend/tests/Unit/Migrations/CreatePublicAuditViewsTableTest.php` (52 tests)
- `backend/tests/Unit/Models/PublicAuditViewTest.php` (52 tests)
- `backend/database/factories/PublicAuditViewFactory.php` (16 factory states)
- `PROJECT-SEOINDICATOR/TESTS/TEST-33-2.md` (test documentation)

**Test Coverage:**
| Category | Tests |
|----------|-------|
| Migration Structure | 13 |
| Indexes | 3 |
| Foreign Keys | 3 |
| Migration Data | 5 |
| Reversibility | 2 |
| Edge Cases | 7 |
| Performance | 3 |
| Security | 5 |
| Model Instantiation | 6 |
| Model Fillable | 10 |
| Model Casts | 3 |
| Relationships | 3 |
| Model Scopes | 7 |
| Factory | 5 |
| Model Edge Cases | 8 |
| Integration | 4 |
| **Total** | **104+** |

**Key Findings:**
- No unique constraint on `public_hash` - multiple views allowed per hash
- IP addresses hashed with SHA-256 + app.key salt for privacy
- `referrer` and `user_agent` limited to 500 chars
- `country_code` is 2-char ISO format
- CASCADE delete ensures clean data when audits deleted
- 3 indexes for query optimization

**Factory States:**
- `forAudit($audit)`, `withHash($hash)`, `withReferrer()`, `withCountry($code)`
- `leadCapture($email)`, `viewedAt($timestamp)`, `viewedDaysAgo($days)`
- `fromGoogle()`, `directVisit()`, `chromeBrowser()`, `safariBrowser()`, `mobileDevice()`
- `withFullAnalytics()`, `withIpHash($ip)`

**Model Scopes to Implement:**
- `scopeForAudit($id)` - filter by audit_id
- `scopeForHash($hash)` - filter by public_hash
- `scopeLeadCaptures()` - is_lead_capture = true
- `scopeWithLeadEmail()` - has lead_email
- `scopeViewedBetween($start, $end)` - date range
- `scopeFromCountry($code)` - filter by country
- `scopeRecent($days)` - last N days

**TDD Pattern:** Tests will fail until migration and model are created.

**Handoff to Developer:** Ready for implementation. All 7 acceptance criteria are testable.
